<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Kolom staf di tiket => tahap yang dia kerjakan + segmen durasinya
     * (lihat Ticket::stageDurations). Dipakai halaman riwayat staf.
     */
    private const STAFF_STAGES = [
        'registered_by' => ['label' => 'Registrasi', 'role' => 'CS', 'segment' => 'registration'],
        'photographed_by' => ['label' => 'Sesi Foto', 'role' => 'Fotografer', 'segment' => 'photo_session'],
        'edited_by' => ['label' => 'Edit Foto', 'role' => 'Editor', 'segment' => 'editing'],
        'paid_by' => ['label' => 'Proses Bayar', 'role' => 'Kasir', 'segment' => 'payment_processing'],
    ];

    /**
     * Durasi di bawah ini (detik) tidak dihitung sebagai rekor -- hampir pasti
     * salah-klik / tahap yang cuma "numpang lewat" (mis. 0s), bukan kerja asli.
     */
    private const MIN_RECORD_SECONDS = 5;

    public function index(Request $request): View
    {
        $period = $request->get('period', 'today');
        $locationId = $request->get('location_id');

        $dateRange = match ($period) {
            'today' => [today(), today()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [today(), today()],
        };

        $ticketQuery = Ticket::whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()]);

        if ($locationId) {
            $ticketQuery->where('location_id', $locationId);
        }

        $tickets = $ticketQuery->get();

        // General stats
        $stats = [
            'total_tickets' => $tickets->count(),
            'completed' => $tickets->where('status', 'completed')->count(),
            'skipped' => $tickets->where('skip_count', '>', 0)->count(),
            'cancelled' => $tickets->where('status', 'cancelled')->count(),
            'waiting' => $tickets->where('status', 'waiting')->count(),
            // abs(): service_time lama tersimpan NEGATIF (selisih Carbon 3 terbalik, lihat Ticket::advanceStage).
            'avg_service_time' => round(($tickets->where('status', 'completed')->whereNotNull('service_time')->avg(fn ($t) => abs($t->service_time)) ?? 0) / 60, 1),
            'avg_wait_time' => $this->calculateAvgWaitTime($tickets),
        ];

        // Per-location breakdown
        $locationStats = Location::withCount([
            'tickets as total_tickets' => fn ($q) => $q->whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()]),
            'tickets as completed_tickets' => fn ($q) => $q->whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()])->where('status', 'completed'),
            'tickets as skipped_tickets' => fn ($q) => $q->whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()])->where('skip_count', '>', 0),
        ])->get();

        // Hourly distribution for the period
        $hourlyData = $this->getHourlyDistribution($dateRange, $locationId);

        // Operator performance
        $operatorStats = User::whereIn('role', ['operator', 'fotografer'])
            ->withCount([
                'servedTickets as tickets_served' => fn ($q) => $q->whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()])->where('status', 'completed'),
            ])
            ->with('location')
            ->get()
            ->filter(fn ($user) => $user->tickets_served > 0)
            ->sortByDesc('tickets_served')
            ->values();

        // Feedback summary
        $feedbackStats = [
            'total' => Feedback::whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()])->count(),
            'avg_rating' => round(Feedback::whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()])->avg('rating') ?? 0, 1),
        ];

        // Rata-rata waktu per tahap + evaluasi kerja per staf. Cuma tiket
        // yang BENERAN sudah selesai (completed) yang dihitung -- angka
        // per-segmennya sudah final/dibekukan (lihat Ticket::stageDurations,
        // ongoing=false begitu status completed), jadi rata-ratanya gak
        // ketarik naik-turun sama tiket yang masih berjalan.
        [$stageDurationStats, $staffPerformance, $topPerformers] = $this->calculateStagePerformance($tickets);

        $locations = Location::where('is_active', true)->get();

        return view('admin.reports.index', compact(
            'stats',
            'locationStats',
            'hourlyData',
            'operatorStats',
            'feedbackStats',
            'locations',
            'period',
            'locationId',
            'stageDurationStats',
            'staffPerformance',
            'topPerformers'
        ));
    }

    /**
     * Daftar semua operator/fotografer + ringkasan kerjanya (sepanjang waktu).
     * Dipakai sub-menu "Per Operator" di sidebar Laporan; klik nama -> riwayat.
     */
    public function staffIndex(): View
    {
        $stats = []; // userId => ['stages' => [label => count], 'total' => n, 'last_at' => ts]

        foreach (self::STAFF_STAGES as $column => $meta) {
            Ticket::query()
                ->selectRaw("{$column} as uid, COUNT(*) as c, MAX(created_at) as last_at")
                ->whereNotNull($column)
                ->groupBy($column)
                ->get()
                ->each(function ($r) use (&$stats, $meta) {
                    $uid = (int) $r->uid;
                    $stats[$uid]['stages'][$meta['label']] = (int) $r->c;
                    $stats[$uid]['total'] = ($stats[$uid]['total'] ?? 0) + (int) $r->c;
                    $last = Carbon::parse($r->last_at)->getTimestamp();
                    $stats[$uid]['last_at'] = max($stats[$uid]['last_at'] ?? 0, $last);
                });
        }

        $staff = User::with('location')
            ->where(function ($q) use ($stats) {
                $q->whereIn('role', ['operator', 'fotografer'])->orWhereIn('id', array_keys($stats));
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($stats) {
                $st = $stats[$u->id] ?? null;

                return [
                    'user' => $u,
                    'stages' => $st['stages'] ?? [],
                    'total' => $st['total'] ?? 0,
                    'last_at' => isset($st['last_at']) ? Carbon::createFromTimestamp($st['last_at']) : null,
                ];
            });

        return view('admin.reports.staff-index', ['staff' => $staff]);
    }

    /**
     * Riwayat kerja satu staf: tiket apa saja yang dia pegang, pelanggan
     * siapa, tahap apa, mulai-selesai jam berapa, berapa lama, dan statusnya.
     * Default "Semua Waktu". Sumbernya kolom staf per-tahap di tiket
     * (registered_by, photographed_by, edited_by, paid_by) -- sama dengan
     * yang dipakai Top Performer, jadi angkanya nyambung dengan Laporan.
     */
    public function staff(Request $request, User $user): View
    {
        $period = (string) $request->get('period', 'all');
        if (!in_array($period, ['all', 'today', 'week', 'month', 'year', 'custom'], true)) {
            $period = 'all';
        }
        $locationId = $request->get('location_id');
        $search = trim((string) $request->get('q'));

        $from = $this->parseDateParam($request->get('from'));
        $to = $this->parseDateParam($request->get('to'));
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        [$rangeStart, $rangeEnd] = match ($period) {
            'today' => [today()->startOfDay(), today()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [$from?->copy()->startOfDay(), $to?->copy()->endOfDay()],
            default => [null, null],
        };
        if ($period === 'custom' && !$rangeStart && !$rangeEnd) {
            $period = 'all'; // custom tapi kosong = semua waktu
        }

        $id = fn (?Carbon $d) => $d?->copy()->locale('id')->isoFormat('D MMM Y');
        $periodLabel = match ($period) {
            'today' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => $rangeStart && $rangeEnd ? $id($rangeStart) . ' - ' . $id($rangeEnd) : ($rangeStart ? 'Sejak ' . $id($rangeStart) : 'Sampai ' . $id($rangeEnd)),
            default => 'Semua waktu',
        };

        $stageMap = self::STAFF_STAGES;

        $tickets = Ticket::with('location')
            ->when($rangeStart, fn ($q) => $q->where('created_at', '>=', $rangeStart))
            ->when($rangeEnd, fn ($q) => $q->where('created_at', '<=', $rangeEnd))
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->where(function ($q) use ($user, $stageMap) {
                foreach (array_keys($stageMap) as $column) {
                    $q->orWhere($column, $user->id);
                }
            })
            ->get();

        $rows = collect();
        $summary = []; // label => ['handled'=>, 'sum'=>, 'count'=>]

        foreach ($tickets as $ticket) {
            $durations = $ticket->stageDurations();
            $segments = collect($durations['segments'])->keyBy('key');

            foreach ($stageMap as $column => $meta) {
                if ((int) $ticket->{$column} !== (int) $user->id) {
                    continue;
                }

                $seg = $segments->get($meta['segment']);
                $seconds = $seg['seconds'] ?? null;
                $startedAt = $seg['start'] ?? $ticket->created_at;
                $isCompleted = $ticket->status === 'completed';

                $rows->push([
                    'ticket_id' => $ticket->id,
                    'started_at' => $startedAt,
                    'ended_at' => $seg['end'] ?? null,
                    'ticket_number' => $ticket->ticket_number,
                    'location' => $ticket->location?->name,
                    'customer_name' => $ticket->customer_name,
                    'customer_phone' => $ticket->customer_phone,
                    'stage_label' => $meta['label'],
                    'role' => $meta['role'],
                    'seconds' => $seconds,
                    'ongoing' => (bool) ($seg['ongoing'] ?? false),
                    'total_seconds' => $durations['total']['seconds'] ?? null,
                    'status' => $ticket->status,
                ]);

                $summary[$meta['label']] ??= ['handled' => 0, 'sum' => 0, 'count' => 0];
                $summary[$meta['label']]['handled']++;
                // sama seperti laporan utama: rata-rata cuma dari tiket yang sudah selesai
                if ($isCompleted && $seconds !== null) {
                    $summary[$meta['label']]['sum'] += $seconds;
                    $summary[$meta['label']]['count']++;
                }
            }
        }

        // Rekor pribadi + peringkat di antara semua staf (all-time, semua lokasi --
        // rekor itu memang tidak ikut filter periode/lokasi).
        $recordData = $this->buildStaffRecords($user, $rangeStart, $rangeEnd);
        $rows = $rows->map(function ($r) use ($recordData) {
            $r['record_break'] = $recordData['breaks']["{$r['ticket_id']}:{$r['stage_label']}"] ?? null;

            return $r;
        });

        $stageSummary = collect($summary)->map(fn ($s, $label) => [
            'label' => $label,
            'handled' => $s['handled'],
            'avg_seconds' => $s['count'] > 0 ? (int) round($s['sum'] / $s['count']) : null,
        ])->values();

        // Pelanggan unik yang pernah dia layani (pakai no. telepon, kalau kosong nama).
        $customersServed = $rows
            ->map(fn ($r) => $r['customer_phone'] ?: ($r['customer_name'] ? 'n:' . mb_strtolower($r['customer_name']) : null))
            ->filter()->unique()->count();
        $firstActivity = $rows->isEmpty() ? null : $rows->min(fn ($r) => $r['started_at']->getTimestamp());
        $lastActivity = $rows->isEmpty() ? null : $rows->max(fn ($r) => $r['started_at']->getTimestamp());

        $chart = $this->buildWorkChart($rows, $rangeStart, $rangeEnd);

        // Riwayat: terbaru di atas, bisa dicari (nomor tiket / nama / no. telepon).
        $sorted = $rows->sortByDesc(fn ($r) => $r['started_at']->getTimestamp())->values();
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $sorted = $sorted->filter(fn ($r) => str_contains(mb_strtolower($r['ticket_number'] . ' ' . $r['customer_name'] . ' ' . $r['customer_phone']), $needle))->values();
        }

        $perPage = 30;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $history = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.reports.staff', [
            'staff' => $user->load('location'),
            'period' => $period,
            'periodLabel' => $periodLabel,
            'locationId' => $locationId,
            'locations' => Location::where('is_active', true)->get(),
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'search' => $search,
            'tickets_count' => $tickets->count(),
            'completed_count' => $tickets->where('status', 'completed')->count(),
            'customers_served' => $customersServed,
            'first_activity' => $firstActivity ? Carbon::createFromTimestamp($firstActivity) : null,
            'last_activity' => $lastActivity ? Carbon::createFromTimestamp($lastActivity) : null,
            'stageSummary' => $stageSummary,
            'records' => $recordData['records'],
            'chart' => $chart,
            'history' => $history,
        ]);
    }

    /**
     * Grafik jumlah pekerjaan: per hari kalau rentangnya <= 62 hari, selain itu
     * per bulan (supaya "Semua Waktu" tidak jadi ratusan batang).
     *
     * @return array{unit: string, bars: list<array{label: string, title: string, count: int}>}
     */
    private function buildWorkChart(\Illuminate\Support\Collection $rows, ?Carbon $rangeStart, ?Carbon $rangeEnd): array
    {
        if ($rows->isEmpty()) {
            return ['unit' => 'day', 'bars' => []];
        }

        $firstRow = Carbon::createFromTimestamp($rows->min(fn ($r) => $r['started_at']->getTimestamp()));
        $lastRow = Carbon::createFromTimestamp($rows->max(fn ($r) => $r['started_at']->getTimestamp()));
        $start = ($rangeStart ?? $firstRow)->copy()->startOfDay();
        $end = ($rangeEnd ?? $lastRow)->copy()->endOfDay();

        $bars = [];
        if (abs($start->diffInDays($end)) <= 62) {
            $perDay = $rows->groupBy(fn ($r) => $r['started_at']->format('Y-m-d'))->map->count();
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $bars[] = ['label' => $d->format('d'), 'title' => $d->format('d/m/Y'), 'count' => $perDay->get($d->format('Y-m-d'), 0)];
            }

            return ['unit' => 'day', 'bars' => $bars];
        }

        $perMonth = $rows->groupBy(fn ($r) => $r['started_at']->format('Y-m'))->map->count();
        for ($m = $start->copy()->startOfMonth(); $m->lte($end); $m->addMonthNoOverflow()) {
            $bars[] = [
                'label' => $m->copy()->locale('id')->isoFormat('MMM YY'),
                'title' => $m->copy()->locale('id')->isoFormat('MMMM Y'),
                'count' => $perMonth->get($m->format('Y-m'), 0),
            ];
        }

        return ['unit' => 'month', 'bars' => $bars];
    }

    /** Tanggal dari query string (Y-m-d); nilai ngawur dianggap kosong. */
    private function parseDateParam(mixed $value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Rekor (durasi tahap tercepat) satu staf, apakah dia sudah memecahkan
     * rekornya sendiri, dan posisinya di papan rekor semua staf.
     *
     * - Hanya tiket "completed" (angka segmennya sudah final) dan durasi >=
     *   MIN_RECORD_SECONDS yang dihitung.
     * - "Pecah rekor pribadi" = durasi LEBIH CEPAT dari rekor terbaiknya
     *   sebelumnya (urut waktu selesai tahap). Hasil pertama cuma jadi patokan
     *   awal, belum dianggap memecahkan apa-apa.
     * - Peringkat pakai rekor terbaik tiap staf per tahap; yang waktunya sama
     *   dapat peringkat yang sama.
     *
     * @return array{records: array<string,array>, breaks: array<string,array>}
     */
    private function buildStaffRecords(User $user, ?Carbon $periodStart, ?Carbon $periodEnd): array
    {
        $best = [];   // label => userId => ['seconds','ended','ticket_number']
        $mine = [];   // label => list of ['seconds','ended','ticket_id','ticket_number']
        $columns = array_keys(self::STAFF_STAGES);

        Ticket::where('status', 'completed')
            ->where(function ($q) use ($columns) {
                foreach ($columns as $column) {
                    $q->orWhereNotNull($column);
                }
            })
            ->chunkById(500, function ($chunk) use (&$best, &$mine, $user) {
                foreach ($chunk as $ticket) {
                    $segments = collect($ticket->stageDurations()['segments'])->keyBy('key');

                    foreach (self::STAFF_STAGES as $column => $meta) {
                        $uid = $ticket->{$column};
                        if (!$uid) {
                            continue;
                        }

                        $seg = $segments->get($meta['segment']);
                        $seconds = $seg['seconds'] ?? null;
                        if ($seconds === null || $seconds < self::MIN_RECORD_SECONDS) {
                            continue;
                        }

                        $endedAt = ($seg['end'] ?? $ticket->completed_at ?? $ticket->created_at);
                        $ended = $endedAt->getTimestamp();
                        $label = $meta['label'];

                        $cur = $best[$label][$uid] ?? null;
                        if (!$cur || $seconds < $cur['seconds'] || ($seconds === $cur['seconds'] && $ended < $cur['ended'])) {
                            $best[$label][$uid] = ['seconds' => $seconds, 'ended' => $ended, 'ticket_number' => $ticket->ticket_number];
                        }

                        if ((int) $uid === (int) $user->id) {
                            $mine[$label][] = ['seconds' => $seconds, 'ended' => $ended, 'ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number];
                        }
                    }
                }
            });

        $names = User::whereIn('id', collect($best)->flatMap(fn ($byUser) => array_keys($byUser))->unique()->all())
            ->pluck('name', 'id');

        $breaks = [];
        $records = [];
        $periodFrom = $periodStart?->getTimestamp() ?? PHP_INT_MIN;
        $periodTo = $periodEnd?->getTimestamp() ?? PHP_INT_MAX;

        foreach (self::STAFF_STAGES as $meta) {
            $label = $meta['label'];
            $results = collect($mine[$label] ?? [])->sortBy([['ended', 'asc'], ['ticket_id', 'asc']])->values();
            if ($results->isEmpty()) {
                continue;
            }

            // Urut waktu: tiap hasil yang LEBIH CEPAT dari rekor sebelumnya = pecah rekor.
            $running = null;
            $breakCount = 0;
            $breakInPeriod = 0;
            $lastBreakAt = null;
            foreach ($results as $res) {
                if ($running !== null && $res['seconds'] < $running) {
                    $breaks["{$res['ticket_id']}:{$label}"] = ['previous' => $running, 'seconds' => $res['seconds']];
                    $breakCount++;
                    $lastBreakAt = $res['ended'];
                    if ($res['ended'] >= $periodFrom && $res['ended'] <= $periodTo) {
                        $breakInPeriod++;
                    }
                }
                $running = $running === null ? $res['seconds'] : min($running, $res['seconds']);
            }

            $personalBest = $results->sortBy([['seconds', 'asc'], ['ended', 'asc']])->first();
            $last = $results->last();

            // Papan rekor semua staf untuk tahap ini
            $board = collect($best[$label] ?? [])
                ->map(fn ($e, $uid) => $e + ['user_id' => (int) $uid, 'name' => $names[$uid] ?? ('Staf #' . $uid)])
                ->sortBy([['seconds', 'asc'], ['ended', 'asc']])
                ->values();

            $prevSeconds = null;
            $prevRank = 0;
            $board = $board->map(function ($e, $i) use (&$prevSeconds, &$prevRank) {
                $e['rank'] = ($prevSeconds !== null && $e['seconds'] === $prevSeconds) ? $prevRank : $i + 1;
                $prevSeconds = $e['seconds'];
                $prevRank = $e['rank'];

                return $e;
            });

            $me = $board->firstWhere('user_id', (int) $user->id);
            $holder = $board->first();

            $records[$label] = [
                'label' => $label,
                'role' => $meta['role'],
                'results_count' => $results->count(),
                'best_seconds' => $personalBest['seconds'],
                'best_ticket' => $personalBest['ticket_number'],
                'best_at' => Carbon::createFromTimestamp($personalBest['ended']),
                'last_seconds' => $last['seconds'],
                'break_count' => $breakCount,
                'break_in_period' => $breakInPeriod,
                'last_break_at' => $lastBreakAt ? Carbon::createFromTimestamp($lastBreakAt) : null,
                'rank' => $me['rank'] ?? null,
                'total_staff' => $board->count(),
                'holder' => $holder,
                'is_holder' => $me && $holder && $me['rank'] === 1,
                'gap_seconds' => $me && $holder ? $me['seconds'] - $holder['seconds'] : null,
                'board' => $board->map(fn ($e) => [
                    'rank' => $e['rank'],
                    'user_id' => $e['user_id'],
                    'name' => $e['name'],
                    'seconds' => $e['seconds'],
                    'ticket_number' => $e['ticket_number'],
                    'at' => Carbon::createFromTimestamp($e['ended']),
                ])->all(),
            ];
        }

        return ['records' => $records, 'breaks' => $breaks];
    }

    /**
     * @return array{0: array<string, array{label:string, avg_seconds:?int, count:int}>, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection}
     */
    private function calculateStagePerformance($tickets): array
    {
        $completed = $tickets->where('status', 'completed');

        // Kolom staf per-kelompok-tahap (registered_by/photographed_by/paid_by)
        // -- lihat migrasi 2026_08_10_000001. Tiap segmen di stageDurations()
        // dipetakan ke kolom staf yang relevan, biar bisa direkap per orang.
        $stageStaffColumn = [
            'registration' => 'registered_by',
            'photo_session' => 'photographed_by',
            'payment_processing' => 'paid_by',
        ];

        $segmentTotals = []; // key => ['label'=>.., 'sum'=>.., 'count'=>..]
        $staffTotals = [];   // "{stage}:{user_id}" => ['label'=>.., 'user_id'=>.., 'sum'=>.., 'count'=>..]

        foreach ($completed as $ticket) {
            $durations = $ticket->stageDurations();

            foreach ($durations['segments'] as $seg) {
                if ($seg['seconds'] === null) {
                    continue; // tahap ini gak pernah kejadian buat tiket ini, jangan ikut narik rata-rata turun.
                }

                $segmentTotals[$seg['key']] ??= ['label' => $seg['label'], 'sum' => 0, 'count' => 0];
                $segmentTotals[$seg['key']]['sum'] += $seg['seconds'];
                $segmentTotals[$seg['key']]['count']++;

                $staffColumn = $stageStaffColumn[$seg['key']] ?? null;
                if (!$staffColumn) {
                    continue;
                }
                $userId = $ticket->{$staffColumn};
                if (!$userId) {
                    continue; // tiket lama sebelum kolom ini ada, atau tahap dilewati.
                }

                $staffKey = $seg['key'] . ':' . $userId;
                $staffTotals[$staffKey] ??= ['label' => $seg['label'], 'user_id' => $userId, 'sum' => 0, 'count' => 0];
                $staffTotals[$staffKey]['sum'] += $seg['seconds'];
                $staffTotals[$staffKey]['count']++;
            }
        }

        $stageDurationStats = collect($segmentTotals)->map(fn ($s) => [
            'label' => $s['label'],
            'avg_seconds' => $s['count'] > 0 ? (int) round($s['sum'] / $s['count']) : null,
            'count' => $s['count'],
        ])->all();

        $userIds = collect($staffTotals)->pluck('user_id')->unique();
        $usersById = User::whereIn('id', $userIds)->get()->keyBy('id');

        $staffPerformance = collect($staffTotals)
            ->map(function ($s) use ($usersById) {
                return [
                    'label' => $s['label'],
                    'user_id' => $s['user_id'],
                    'user_name' => $usersById->get($s['user_id'])?->name ?? 'Staf #' . $s['user_id'],
                    'avg_seconds' => $s['count'] > 0 ? (int) round($s['sum'] / $s['count']) : null,
                    'count' => $s['count'],
                ];
            })
            ->sortByDesc('avg_seconds')
            ->values();

        // "Top Performer" -- kebalikan dari tabel evaluasi di atas: yang
        // ditonjolkan di sini yang PALING CEPAT, dikelompokkan per tahap
        // (Registrasi, Sesi Foto, Proses Bayar) supaya CS & fotografer
        // sama-sama kebagian panggung, bukan cuma satu peran. Minimal 2
        // tiket biar gak keangkat cuma dari 1 tiket kebetulan cepat.
        $topPerformers = $staffPerformance
            ->filter(fn ($s) => $s['count'] >= 2)
            ->groupBy('label')
            ->map(fn ($group) => $group->sortBy('avg_seconds')->take(3)->values());

        return [$stageDurationStats, $staffPerformance, $topPerformers];
    }

    private function calculateAvgWaitTime($tickets): float
    {
        $completedTickets = $tickets->where('status', 'completed')->whereNotNull('called_at');

        if ($completedTickets->isEmpty()) {
            return 0;
        }

        $totalWait = $completedTickets->sum(function ($ticket) {
            return $ticket->created_at->diffInSeconds($ticket->called_at);
        });

        return round($totalWait / $completedTickets->count() / 60, 1);
    }

    private function getHourlyDistribution(array $dateRange, ?int $locationId): array
    {
        $query = Ticket::whereBetween('created_at', [$dateRange[0]->startOfDay(), $dateRange[1]->endOfDay()]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $tickets = $query->get();
        $hourly = array_fill(0, 24, 0);

        foreach ($tickets as $ticket) {
            $hour = (int) $ticket->created_at->format('G');
            $hourly[$hour]++;
        }

        return $hourly;
    }
}
