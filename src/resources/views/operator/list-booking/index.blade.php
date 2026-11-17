<x-layout title="List Booking">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-operator-header title="List Booking" subtitle="{{ $location->name }} — jadwal booking, check-in, arsip & Excel" />

        @php
            $isAdminUser = auth()->user()->isAdmin();
            $backUrl = $isAdminUser ? route('admin.dashboard') : route('operator.dashboard');
            $backLabel = $isAdminUser ? 'Kembali ke Dashboard Admin' : 'Kembali ke Dashboard';

            $statusBadge = function ($b) {
                if ($b->status === 'cancelled') {
                    return ['Batal', 'bg-red-100 text-red-700'];
                }
                if ($b->ticket_id) {
                    return ['Check-in' . ($b->ticket ? ' · ' . $b->ticket->ticket_number : ''), 'bg-emerald-100 text-emerald-700'];
                }
                if ($b->source === 'excel' && $b->status === 'completed') {
                    return ['Arsip', 'bg-slate-100 text-slate-600'];
                }
                if ($b->status === 'completed') {
                    return ['Selesai', 'bg-slate-100 text-slate-600'];
                }

                return ['Terjadwal', 'bg-blue-100 text-blue-700'];
            };
            $tabUrl = fn (string $t) => route('operator.list-booking', array_filter(['tab' => $t, 'loc' => $location->id]));
            $inputCls = 'w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm';
            $reopenModal = $errors->any() && old('_modal');
        @endphp

        <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
            {{-- Navigasi cepat + lokasi --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <a href="{{ $backUrl }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">&larr; {{ $backLabel }}</a>
                @if($locations->count() > 1)
                    <form method="GET" action="{{ route('operator.list-booking') }}" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <label class="text-sm text-slate-600 dark:text-slate-300">Lokasi</label>
                        <select name="loc" onchange="this.form.submit()" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" @selected($l->id === $location->id)>{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if(session('success'))
                <div class="mb-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
            @endif

            {{-- Tab --}}
            <div class="flex items-center gap-1 overflow-x-auto pb-3">
                @foreach($tabs as $key => $label)
                    <a href="{{ $tabUrl($key) }}" class="{{ $tab === $key ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 font-medium' }} px-4 py-2 text-sm rounded-lg whitespace-nowrap shrink-0">
                        {{ $label }}
                        @if($key === 'today' && $counts['today_waiting'] > 0)
                            <span class="ml-1 inline-block bg-amber-500 text-white text-xs rounded-full px-2 py-0.5">{{ $counts['today_waiting'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Tambah / import / export --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="openBookingModal()" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-4 py-2 rounded-lg">+ Tambah Booking</button>
                    <a href="{{ route('operator.list-booking.export', array_filter(['tab' => $tab, 'loc' => $location->id, 'month' => $tab === 'all' ? $month : 'all', 'q' => $search, 'status' => $status, 'review' => $onlyReview ? 1 : null])) }}"
                        class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Export Excel</a>
                    <button type="button" onclick="document.getElementById('import-panel').classList.toggle('hidden')"
                        class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Import Excel</button>
                    @if($counts['review'] > 0)
                        <a href="{{ route('operator.list-booking', ['tab' => 'all', 'month' => 'all', 'review' => 1, 'loc' => $location->id]) }}"
                            class="ml-auto text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg">{{ $counts['review'] }} baris perlu dicek</a>
                    @endif
                </div>

                <div id="import-panel" class="hidden mt-4 border-t border-slate-100 dark:border-slate-700 pt-4">
                    <form method="POST" action="{{ route('operator.list-booking.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3"
                        onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Mengimpor...';">
                        @csrf
                        <input type="hidden" name="loc" value="{{ $location->id }}">
                        <div class="flex-1" style="min-width: 16rem;">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">File Excel (.xlsx) — semua sheet dibaca</label>
                            <input type="file" name="file" accept=".xlsx,.xlsm,.xls" required class="{{ $inputCls }}">
                            @error('file')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-5 py-2 rounded-lg">Import ke {{ $location->name }}</button>
                    </form>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Format kolom sama dengan Excel "LIST BOOKING" (UNIV, TANGGAL BOOKING, JAM BOOKING, KO, PAKET, JUMLAH ORANG, KETERANGAN, dst). Tanggal & jam dirapikan otomatis (mis. hari/bulan tertukar, jam "9.3" jadi 09:30).
                        Booking yang sudah ada dilewati, jadi aman diimpor ulang. Baris yang meragukan tetap masuk dengan tanda <b>Cek</b>. Booking lama masuk sebagai <b>Arsip</b>; hanya booking hari ini dan ke depan yang bisa di-check-in.
                    </p>
                </div>
            </div>

            @if(session('import_skipped'))
                <details class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 mb-4 text-sm text-amber-900 dark:text-amber-200">
                    <summary class="cursor-pointer font-medium">{{ count(session('import_skipped')) }} baris Excel tidak diimpor (klik untuk lihat)</summary>
                    <ul class="mt-2 space-y-1 list-disc pl-5">
                        @foreach(session('import_skipped') as $s)
                            <li>{{ $s['sheet'] }} baris {{ $s['row'] }}: {{ $s['reason'] }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if($tab === 'calendar')
                @include('operator.list-booking.calendar')
            @else
                {{-- Pilih bulan seperti tab sheet di Excel (Semua Jadwal) --}}
                @if($tab === 'all' && count($months) > 0)
                    @php $monthUrl = fn ($ym) => route('operator.list-booking', array_filter(['tab' => 'all', 'loc' => $location->id, 'month' => $ym, 'status' => $status, 'review' => $onlyReview ? 1 : null])); @endphp
                    <div class="flex items-center gap-1 overflow-x-auto pb-3">
                        <a href="{{ $monthUrl('all') }}" class="{{ $month === 'all' && $search === '' ? 'bg-slate-800 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }} px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap shrink-0 border border-slate-200 dark:border-slate-700">
                            Semua bulan <span class="text-xs opacity-70">({{ array_sum(array_column($months, 'count')) }})</span>
                        </a>
                        @foreach($months as $ym => $m)
                            <a href="{{ $monthUrl($ym) }}" class="{{ $month === $ym && $search === '' ? 'bg-slate-800 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }} px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap shrink-0 border border-slate-200 dark:border-slate-700">
                                {{ $m['label'] }} <span class="text-xs opacity-70">({{ $m['count'] }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Cari + filter. Pencarian jalan otomatis sambil mengetik (mulai 3 karakter). --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 mb-4">
                    <form id="list-filter" method="GET" action="{{ route('operator.list-booking') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="hidden" name="loc" value="{{ $location->id }}">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                            <input type="search" id="list-search" name="q" value="{{ $search }}" autocomplete="off" placeholder="Ketik 3 huruf/angka: nama, kode JG, paket, keterangan, UNIV..." class="{{ $inputCls }}">
                        </div>
                        @if($tab !== 'today')
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bulan</label>
                                <select name="month" class="{{ $inputCls }}">
                                    <option value="all" @selected($month === 'all')>Semua bulan</option>
                                    @foreach($months as $ym => $m)
                                        <option value="{{ $ym }}" @selected($month === $ym)>{{ $m['label'] }} ({{ $m['count'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if($tab === 'all')
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                <select name="status" class="{{ $inputCls }}">
                                    <option value="">Semua</option>
                                    <option value="confirmed" @selected($status === 'confirmed')>Terjadwal</option>
                                    <option value="checked_in" @selected($status === 'checked_in')>Check-in</option>
                                    <option value="completed" @selected($status === 'completed')>Selesai / Arsip</option>
                                    <option value="cancelled" @selected($status === 'cancelled')>Batal</option>
                                </select>
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" name="review" value="1" @checked($onlyReview)> Perlu dicek
                            </label>
                            <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose text-white text-sm font-medium px-4 py-2 rounded-lg">Terapkan</button>
                        </div>
                    </form>
                </div>

                <div id="list-results">
                    @php
                        $showTimes = $tab === 'checkedin' || $bookings->contains(fn ($x) => $x->arrived_time || $x->started_time || $x->finished_time);
                        $cell = 'border:1px solid #888;padding:6px 8px;color:#111;';
                        $monthTitle = ($tab === 'all' && $month !== 'all' && $search === '' && isset($months[$month])) ? $months[$month]['label'] : null;
                    @endphp

                    {{-- Tabel tampil seperti sheet Excel "LIST BOOKING" --}}
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white">
                        <div style="background:#EAD1DC;border-bottom:1px solid #888;text-align:center;padding:14px 10px;">
                            <div style="font-family:'Lora',Georgia,serif;font-size:1.9rem;line-height:1.1;color:#111;">LIST BOOKING</div>
                            <div style="font-size:.8rem;color:#444;margin-top:2px;">
                                {{ $tabs[$tab] }}@if($monthTitle) · {{ $monthTitle }}@endif
                                — {{ $bookings->total() }} booking
                                @if($search !== '') · hasil pencarian "{{ $search }}" di semua bulan @endif
                                @if($tab === 'today') · {{ today()->translatedFormat('l, d F Y') }} · {{ $counts['today_waiting'] }} menunggu check-in @endif
                                @foreach($graduations->get(today()->format('Y-m-d'), collect()) as $g)
                                    @if($tab === 'today')<span style="display:inline-block;background:#d9d2e9;color:#4c1d95;border-radius:999px;padding:1px 8px;margin-left:4px;">🎓 Wisuda {{ $g->campus }}</span>@endif
                                @endforeach
                            </div>
                        </div>

                        @if($bookings->isEmpty())
                            <p class="p-8 text-center text-slate-500 text-sm">
                                @if($search !== '') Tidak ada booking yang cocok dengan "{{ $search }}".
                                @elseif($tab === 'today') Tidak ada booking hari ini.
                                @else Tidak ada data untuk filter ini. @endif
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table style="width:100%;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;background:#fff;">
                                    <thead>
                                        <tr style="background:#D5A6BD;">
                                            @foreach(['UNIV', 'TANGGAL BOOKING', 'JAM BOOKING', 'KO', 'PAKET', 'JUMLAH ORANG'] as $h)
                                                <th style="{{ $cell }}text-align:center;font-weight:600;white-space:nowrap;">{{ $h }}</th>
                                            @endforeach
                                            @if($showTimes)
                                                @foreach(['DATANG', 'MULAI', 'SELESAI'] as $h)
                                                    <th style="{{ $cell }}text-align:center;font-weight:600;">{{ $h }}</th>
                                                @endforeach
                                            @endif
                                            <th style="{{ $cell }}text-align:center;font-weight:600;">KETERANGAN</th>
                                            <th style="{{ $cell }}text-align:center;font-weight:600;background:#f1f5f9;">STATUS</th>
                                            <th style="{{ $cell }}text-align:center;font-weight:600;background:#f1f5f9;">AKSI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $prevDate = null; $dayIdx = -1; @endphp
                                        @foreach($bookings as $b)
                                            @php
                                                [$badgeText, $badgeCls] = $statusBadge($b);
                                                $t = $b->ticket;
                                                $dateKey = $b->booking_date->format('Y-m-d');
                                                $newDay = $dateKey !== $prevDate;
                                                if ($newDay) { $dayIdx++; $prevDate = $dateKey; }
                                                $rowColor = $dayIdx % 2 === 0 ? '#CCCCCC' : '#B7B7B7';
                                                if ($b->status === 'cancelled') { $rowColor = '#F4CCCC'; }
                                                elseif (preg_match('/resc?hedule/i', (string) $b->notes)) { $rowColor = '#FCE5CD'; }
                                                $canCheckIn = !$b->ticket_id && in_array($b->status, ['pending', 'confirmed'], true) && $b->booking_date->isToday();
                                                $payload = [
                                                    'id' => $b->id,
                                                    'order_code' => $b->order_code,
                                                    'customer_name' => $b->customer_name,
                                                    'booking_date' => $dateKey,
                                                    'booking_time' => $b->booking_time->format('H:i'),
                                                    'package_name' => $b->package_name,
                                                    'people_count' => $b->people_count,
                                                    'customer_phone' => $b->customer_phone,
                                                    'univ' => $b->univ,
                                                    'arrived_time' => $b->arrived_time ? substr($b->arrived_time, 0, 5) : '',
                                                    'started_time' => $b->started_time ? substr($b->started_time, 0, 5) : '',
                                                    'finished_time' => $b->finished_time ? substr($b->finished_time, 0, 5) : '',
                                                    'notes' => $b->notes,
                                                    'status' => $b->status,
                                                    'has_ticket' => (bool) $b->ticket_id,
                                                    'needs_review' => (bool) $b->needs_review,
                                                    'review_note' => $b->review_note,
                                                ];
                                            @endphp
                                            <tr>
                                                <td style="{{ $cell }}text-align:center;">{{ $b->univ }}</td>
                                                <td style="{{ $cell }}text-align:center;white-space:nowrap;{{ $newDay && $graduations->has($dateKey) ? 'background:#D9D2E9;' : '' }}">
                                                    @if($newDay)
                                                        {{ $b->booking_date->format('d-m-Y') }}
                                                        @foreach($graduations->get($dateKey, collect()) as $g)
                                                            <div style="font-size:11px;color:#4c1d95;font-weight:600;">🎓 {{ $g->campus }}</div>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td style="{{ $cell }}text-align:center;white-space:nowrap;background:{{ $rowColor }};">{{ $b->booking_time->format('H:i') === '00:00' ? '—' : $b->booking_time->format('G:i') }}</td>
                                                <td style="{{ $cell }}text-align:left;background:{{ $rowColor }};">{{ trim(($b->order_code ? $b->order_code . '  ' : '') . $b->customer_name) }}</td>
                                                <td style="{{ $cell }}text-align:center;white-space:nowrap;background:{{ $rowColor }};">{{ $b->package_name ?: '—' }}</td>
                                                <td style="{{ $cell }}text-align:center;background:{{ $rowColor }};">{{ $b->people_count ?? '—' }}</td>
                                                @if($showTimes)
                                                    <td style="{{ $cell }}text-align:center;white-space:nowrap;">{{ $b->arrived_time ? ltrim(substr($b->arrived_time, 0, 5), '0') : ($t?->created_at?->format('G:i') ?? '') }}</td>
                                                    <td style="{{ $cell }}text-align:center;white-space:nowrap;">{{ $b->started_time ? ltrim(substr($b->started_time, 0, 5), '0') : (($t?->photo_served_at ?? $t?->served_at)?->format('G:i') ?? '') }}</td>
                                                    <td style="{{ $cell }}text-align:center;white-space:nowrap;">{{ $b->finished_time ? ltrim(substr($b->finished_time, 0, 5), '0') : (($t?->photo_completed_at ?? $t?->completed_at)?->format('G:i') ?? '') }}</td>
                                                @endif
                                                <td style="{{ $cell }}text-align:center;white-space:pre-line;overflow-wrap:anywhere;min-width:12rem;{{ $b->status === 'cancelled' ? 'background:#F4CCCC;' : '' }}">{{ $b->notes }}</td>
                                                <td style="{{ $cell }}text-align:center;white-space:nowrap;">
                                                    <span class="inline-block text-xs font-medium px-2 py-1 rounded-full {{ $badgeCls }}">{{ $badgeText }}</span>
                                                    @if($t && $tab === 'checkedin')
                                                        <div style="font-size:11px;color:#555;margin-top:2px;">{{ \App\Models\Ticket::STAGE_LABELS[$t->stage] ?? $t->stage }} · {{ $t->status }}</div>
                                                    @endif
                                                    @if($b->needs_review)
                                                        <span class="inline-block text-xs font-medium px-2 py-1 rounded-full bg-amber-100 text-amber-800 cursor-help" title="{{ $b->review_note }}">Cek</span>
                                                    @endif
                                                </td>
                                                <td style="{{ $cell }}text-align:center;white-space:nowrap;">
                                                    @if($canCheckIn)
                                                        <form method="POST" action="{{ route('operator.bookings.call', $b) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">Check-in</button>
                                                        </form>
                                                    @endif
                                                    <button type="button" data-booking="{{ json_encode($payload) }}" onclick="openBookingModal(JSON.parse(this.dataset.booking))" class="text-xs font-medium text-blue-600 hover:underline px-2">Edit</button>
                                                    <form method="POST" action="{{ route('operator.list-booking.destroy', $b) }}" class="inline"
                                                        onsubmit="return confirm('Hapus booking {{ e(addslashes($b->customer_name)) }} secara permanen?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs font-medium text-red-600 hover:underline px-1">Hapus</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 border-t border-slate-200">{{ $bookings->links() }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Popup Tambah / Edit Booking. Wajib: kode order, nama, tanggal, jam, paket. Sisanya opsional. --}}
    <div id="booking-modal" class="{{ $reopenModal ? '' : 'hidden' }} fixed inset-0 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto" style="background:rgba(15,23,42,.55);">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-2xl my-auto">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 dark:border-slate-700">
                <h2 id="bm-title" class="text-lg font-bold text-slate-900 dark:text-white">Tambah Booking</h2>
                <button type="button" onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl leading-none" aria-label="Tutup">&times;</button>
            </div>

            @if($reopenModal && $errors->any())
                <div class="mx-5 mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm rounded-lg p-3">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form id="bm-form" method="POST" action="{{ route('operator.list-booking.store') }}" class="p-5 space-y-4"
                data-store-url="{{ route('operator.list-booking.store') }}"
                data-update-url="{{ route('operator.list-booking.update', ['booking' => '__ID__']) }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="bm-method" disabled>
                <input type="hidden" name="_modal" id="bm-mode" value="{{ old('_modal', 'add') }}">
                <input type="hidden" name="_edit_id" id="bm-edit-id" value="{{ old('_edit_id') }}">
                <input type="hidden" name="loc" value="{{ $location->id }}">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Order <span class="text-red-500">*</span></label>
                        <input type="text" name="order_code" id="bm-order_code" value="{{ old('order_code') }}" maxlength="50" required placeholder="JG035474" class="{{ $inputCls }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" id="bm-customer_name" value="{{ old('customer_name') }}" maxlength="255" required class="{{ $inputCls }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Booking <span class="text-red-500">*</span></label>
                        <input type="date" name="booking_date" id="bm-booking_date" value="{{ old('booking_date') }}" required class="{{ $inputCls }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Booking <span class="text-red-500">*</span></label>
                        <input type="time" name="booking_time" id="bm-booking_time" value="{{ old('booking_time') }}" required class="{{ $inputCls }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Paket <span class="text-red-500">*</span></label>
                        <input type="text" name="package_name" id="bm-package_name" list="bm-package-list" value="{{ old('package_name') }}" maxlength="100" required class="{{ $inputCls }}">
                        <datalist id="bm-package-list">@foreach($packages as $p)<option value="{{ $p }}">@endforeach</datalist>
                    </div>
                </div>

                <details class="rounded-lg border border-slate-200 dark:border-slate-700" {{ $reopenModal ? 'open' : '' }}>
                    <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300">Data tambahan (opsional)</summary>
                    <div class="p-4 space-y-4 border-t border-slate-100 dark:border-slate-700">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jumlah Orang</label>
                                <input type="number" name="people_count" id="bm-people_count" min="0" max="1000" value="{{ old('people_count') }}" class="{{ $inputCls }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                                <input type="text" name="customer_phone" id="bm-customer_phone" value="{{ old('customer_phone') }}" class="{{ $inputCls }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">UNIV / Instansi</label>
                                <input type="text" name="univ" id="bm-univ" value="{{ old('univ') }}" maxlength="100" class="{{ $inputCls }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach(['arrived_time' => 'Datang', 'started_time' => 'Mulai', 'finished_time' => 'Selesai'] as $name => $label)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>
                                    <input type="time" name="{{ $name }}" id="bm-{{ $name }}" value="{{ old($name) }}" class="{{ $inputCls }}">
                                </div>
                            @endforeach
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Keterangan</label>
                            <textarea name="notes" id="bm-notes" rows="3" maxlength="2000" class="{{ $inputCls }}">{{ old('notes') }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                <select name="status" id="bm-status" class="{{ $inputCls }}">
                                    @foreach(['confirmed' => 'Terjadwal', 'pending' => 'Menunggu konfirmasi', 'completed' => 'Selesai / Arsip', 'cancelled' => 'Batal'] as $v => $l)
                                        <option value="{{ $v }}" @selected(old('status', 'confirmed') === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                                <p id="bm-status-lock" class="hidden text-xs text-slate-500 mt-1">Sudah check-in: status mengikuti tiket antrian.</p>
                            </div>
                            <div id="bm-review-row" class="hidden">
                                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" name="needs_review" id="bm-needs_review" value="1"> Masih perlu dicek
                                </label>
                                <input type="hidden" name="review_note" id="bm-review_note" value="">
                                <p id="bm-review-text" class="text-xs text-amber-700 dark:text-amber-300 mt-1"></p>
                            </div>
                        </div>
                    </div>
                </details>

                <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="button" onclick="closeBookingModal()" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white px-3 py-2">Batal</button>
                    <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white font-medium px-6 py-2.5 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('booking-modal');
            const form = document.getElementById('bm-form');
            const fields = ['order_code', 'customer_name', 'booking_date', 'booking_time', 'package_name', 'people_count', 'customer_phone', 'univ', 'arrived_time', 'started_time', 'finished_time', 'notes'];
            const $ = (id) => document.getElementById(id);

            window.closeBookingModal = function () { modal.classList.add('hidden'); };

            // data = null -> Tambah Booking; data = objek booking -> Edit.
            window.openBookingModal = function (data) {
                const edit = !!(data && data.id);
                $('bm-title').textContent = edit ? 'Edit Booking ' + (data.customer_name || '') : 'Tambah Booking';
                form.action = edit ? form.dataset.updateUrl.replace('__ID__', data.id) : form.dataset.storeUrl;
                $('bm-method').disabled = !edit;
                $('bm-mode').value = edit ? 'edit' : 'add';
                $('bm-edit-id').value = edit ? data.id : '';

                fields.forEach(function (f) {
                    const el = $('bm-' + f);
                    if (el) el.value = edit && data[f] != null ? data[f] : '';
                });
                if (!edit) { $('bm-booking_date').value = new Date().toISOString().slice(0, 10); }

                const status = $('bm-status');
                status.value = edit ? data.status : 'confirmed';
                status.disabled = edit && data.has_ticket;       // status tiket diatur antrian
                $('bm-status-lock').classList.toggle('hidden', !(edit && data.has_ticket));

                const reviewRow = $('bm-review-row');
                const flagged = edit && data.needs_review;
                reviewRow.classList.toggle('hidden', !flagged);
                $('bm-needs_review').checked = !!flagged;
                $('bm-review_note').value = flagged ? (data.review_note || '') : '';
                $('bm-review-text').textContent = flagged ? (data.review_note || '') : '';

                modal.classList.remove('hidden');
                setTimeout(function () { $('bm-order_code').focus(); }, 50);
            };

            // Setelah validasi gagal: modal dibuka lagi dengan isian lama (diisi Blade lewat old()).
            @if($reopenModal)
            (function () {
                const mode = @json(old('_modal'));
                const id = @json(old('_edit_id'));
                if (mode === 'edit' && id) {
                    form.action = form.dataset.updateUrl.replace('__ID__', id);
                    $('bm-method').disabled = false;
                    $('bm-title').textContent = 'Edit Booking';
                }
            })();
            @endif

            modal.addEventListener('mousedown', function (e) { if (e.target === modal) closeBookingModal(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeBookingModal(); });

            // ---- Pencarian langsung: ketik >= 3 karakter (atau kosongkan) -> tabel diperbarui tanpa muat ulang.
            const filter = document.getElementById('list-filter');
            const search = document.getElementById('list-search');
            if (filter && search) {
                let timer = null;
                let lastReq = 0;
                const run = function () {
                    const params = new URLSearchParams(new FormData(filter));
                    for (const [k, v] of Array.from(params.entries())) { if (v === '') params.delete(k); }
                    const url = filter.action + '?' + params.toString();
                    const req = ++lastReq;
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                        .then(function (r) { return r.text(); })
                        .then(function (html) {
                            if (req !== lastReq) return; // ada ketikan yang lebih baru
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const fresh = doc.getElementById('list-results');
                            const cur = document.getElementById('list-results');
                            if (fresh && cur) { cur.innerHTML = fresh.innerHTML; history.replaceState(null, '', url); }
                        })
                        .catch(function () { /* diam: tombol Terapkan tetap bisa dipakai */ });
                };
                search.addEventListener('input', function () {
                    clearTimeout(timer);
                    const len = search.value.trim().length;
                    if (len !== 0 && len < 3) return;
                    timer = setTimeout(run, 300);
                });
                filter.addEventListener('change', function (e) { if (e.target !== search) { clearTimeout(timer); run(); } });
            }
        })();
    </script>
</x-layout>
