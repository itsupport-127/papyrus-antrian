<x-layout :title="'Riwayat Kerja - ' . $staff->name">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Laporan & Analitik" subtitle="Riwayat kerja per staf" />

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full max-w-7xl mx-auto">

                <!-- Header staf + filter -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                    <a href="{{ route('admin.reports', array_filter(['period' => in_array($period, ['today', 'week', 'month'], true) ? $period : null, 'location_id' => $locationId])) }}"
                       class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 mb-3">
                        &larr; Kembali ke Laporan
                    </a>
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $staff->name }}</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ ucfirst($staff->role) }}@if($staff->location) &middot; {{ $staff->location->name }}@endif
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Periode: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $periodLabel }}</span></p>
                        </div>
                        <form method="GET" action="{{ route('admin.reports.staff', $staff) }}" class="flex flex-wrap gap-4 items-end" x-data="{ period: '{{ $period }}' }">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                                <select name="period" x-model="period" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    <option value="all">Semua Waktu</option>
                                    <option value="today">Hari Ini</option>
                                    <option value="week">Minggu Ini</option>
                                    <option value="month">Bulan Ini</option>
                                    <option value="year">Tahun Ini</option>
                                    <option value="custom">Custom...</option>
                                </select>
                            </div>
                            <div x-show="period === 'custom'" x-cloak class="flex gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dari</label>
                                    <input type="date" name="from" value="{{ $from }}" :disabled="period !== 'custom'" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sampai</label>
                                    <input type="date" name="to" value="{{ $to }}" :disabled="period !== 'custom'" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                                <select name="location_id" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    <option value="">Semua Lokasi</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari tiket / pelanggan</label>
                                <input type="text" name="q" value="{{ $search }}" placeholder="No. tiket, nama, telepon" style="width: 13rem;" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            </div>
                            <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                                Terapkan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Ringkasan -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tiket Dipegang</p>
                        <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ $tickets_count }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $completed_count }} selesai</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Pelanggan Dilayani</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $customers_served }}</p>
                        <p class="text-xs text-slate-400 mt-1">pelanggan berbeda</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Pertama Kerja</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-2">{{ $first_activity ? $first_activity->format('d/m/Y') : '-' }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $first_activity ? $first_activity->format('H:i') : '' }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Terakhir Kerja</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-2">{{ $last_activity ? $last_activity->format('d/m/Y') : '-' }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $last_activity ? $last_activity->format('H:i') : '' }}</p>
                    </div>
                </div>

                <!-- Rekor pribadi & peringkat -->
                @php $staffQuery = array_filter(['period' => $period !== 'all' ? $period : null, 'location_id' => $locationId, 'from' => $period === 'custom' ? $from : null, 'to' => $period === 'custom' ? $to : null]); @endphp
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><x-papyrus-icon name="trophy" size="1.15em" color="#d4a017" /> Rekor &amp; Peringkat</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rekor = durasi satu tahap tercepat sepanjang waktu (semua lokasi, tidak ikut filter periode). Cuma tiket &quot;Selesai&quot; dengan durasi minimal 5 detik yang dihitung. &quot;Pecah rekor&quot; = lebih cepat dari rekor pribadinya sebelumnya. Peringkat membandingkan rekor terbaik semua staf di tahap yang sama.</p>

                    @if(empty($records))
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada tiket selesai yang tercatat atas nama {{ $staff->name }}, jadi belum ada rekor.</p>
                    @else
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($records as $rec)
                                @php
                                    $medalColor = ['#d4a017', '#94a3b8', '#b4763a'][($rec['rank'] ?? 0) - 1] ?? null;
                                    $diffLast = $rec['last_seconds'] - $rec['best_seconds'];
                                    $board = collect($rec['board']);
                                    $shown = $board->take(5);
                                    if ($rec['rank'] && !$shown->contains('user_id', $staff->id)) {
                                        $shown = $shown->push($board->firstWhere('user_id', $staff->id));
                                    }
                                @endphp
                                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ $rec['label'] }} &middot; {{ $rec['role'] }}</p>
                                            <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ \App\Models\Ticket::formatDuration($rec['best_seconds']) }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Rekor pribadi &middot; {{ $rec['best_ticket'] }} &middot; {{ $rec['best_at']->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <div class="text-right">
                                            @if($rec['rank'])
                                                <p class="text-2xl leading-none">@if($medalColor)<x-papyrus-icon name="trophy" size="1.6em" :color="$medalColor" />@endif</p>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1">Peringkat #{{ $rec['rank'] }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">dari {{ $rec['total_staff'] }} staf</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-1 text-sm">
                                        <p class="text-slate-700 dark:text-slate-300">
                                            @if($rec['break_count'] > 0)
                                                <x-papyrus-icon name="arrow-trending-up" color="#16a34a" /> Pecah rekor pribadi <span class="font-semibold">{{ $rec['break_count'] }}×</span>
                                                @if($rec['break_in_period'] > 0)
                                                    (<span class="font-semibold text-green-600">{{ $rec['break_in_period'] }}× di periode ini</span>)
                                                @else
                                                    (belum di periode ini)
                                                @endif
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">Terakhir {{ $rec['last_break_at']->format('d/m/Y H:i') }}</span>
                                            @else
                                                Belum pernah memecahkan rekor pribadinya ({{ $rec['results_count'] }} tiket tercatat).
                                            @endif
                                        </p>
                                        <p class="text-slate-700 dark:text-slate-300">
                                            Terakhir dikerjakan: <span class="font-semibold">{{ \App\Models\Ticket::formatDuration($rec['last_seconds']) }}</span>
                                            @if($diffLast > 0)
                                                <span class="text-xs text-slate-500 dark:text-slate-400">(+{{ \App\Models\Ticket::formatDuration($diffLast) }} dari rekornya)</span>
                                            @else
                                                <span class="text-xs text-green-600">(sama dengan rekornya)</span>
                                            @endif
                                        </p>
                                        <p class="text-slate-700 dark:text-slate-300">
                                            @if($rec['is_holder'])
                                                <x-papyrus-icon name="star" color="#d4a017" /> Pemegang rekor tercepat semua staf untuk tahap ini.
                                            @elseif($rec['holder'])
                                                Rekor tercepat: <span class="font-semibold">{{ $rec['holder']['name'] }}</span> {{ \App\Models\Ticket::formatDuration($rec['holder']['seconds']) }}
                                                @if($rec['gap_seconds'] !== null)
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">(selisih +{{ \App\Models\Ticket::formatDuration($rec['gap_seconds']) }})</span>
                                                @endif
                                            @endif
                                        </p>
                                    </div>

                                    <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-2">Papan rekor {{ $rec['label'] }}</p>
                                        <div class="space-y-1">
                                            @foreach($shown as $b)
                                                @php $isMe = $b['user_id'] === $staff->id; @endphp
                                                <div class="flex items-center justify-between gap-2 text-sm px-2 py-1 rounded-lg {{ $isMe ? 'bg-purple-50 dark:bg-slate-700' : '' }}">
                                                    <span class="flex items-center gap-2 min-w-0">
                                                        <span class="w-6 text-xs text-slate-500 dark:text-slate-400">#{{ $b['rank'] }}</span>
                                                        @if($isMe)
                                                            <span class="truncate font-semibold text-slate-900 dark:text-white">{{ $b['name'] }} (ini)</span>
                                                        @else
                                                            <a href="{{ route('admin.reports.staff', ['user' => $b['user_id']] + $staffQuery) }}" class="truncate text-slate-700 dark:text-slate-300 hover:underline">{{ $b['name'] }}</a>
                                                        @endif
                                                    </span>
                                                    <span class="font-semibold text-slate-900 dark:text-white">{{ \App\Models\Ticket::formatDuration($b['seconds']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Per tahap -->
                @if($stageSummary->count() > 0)
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Per Tahap</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rata-rata waktu cuma dihitung dari tiket yang sudah &quot;Selesai&quot;, sama seperti di halaman Laporan.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($stageSummary as $sum)
                                <div class="p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                    <p class="font-medium text-slate-900 dark:text-white text-sm">{{ $sum['label'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $sum['handled'] }} tiket</p>
                                    <p class="font-bold text-slate-900 dark:text-white text-sm mt-2">{{ \App\Models\Ticket::formatDuration($sum['avg_seconds']) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Pekerjaan per hari / bulan -->
                @if(count($chart['bars']) > 1)
                    @php $maxBar = max(array_column($chart['bars'], 'count')) ?: 1; @endphp
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Pekerjaan per {{ $chart['unit'] === 'month' ? 'Bulan' : 'Hari' }}</h2>
                        <div class="overflow-x-auto">
                            <div class="flex items-end gap-1" style="height: 120px; min-width: max-content;">
                                @foreach($chart['bars'] as $bar)
                                    <div class="flex flex-col items-center justify-end {{ $chart['unit'] === 'month' ? 'w-12' : 'w-8' }}" title="{{ $bar['title'] }} - {{ $bar['count'] }} pekerjaan">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mb-1">{{ $bar['count'] ?: '' }}</span>
                                        <div class="w-full bg-blue-500 rounded-t-sm" style="height: {{ $bar['count'] > 0 ? max(4, (int) round($bar['count'] / $maxBar * 80)) : 0 }}px;"></div>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ $bar['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Riwayat -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Riwayat Pengerjaan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Satu baris = satu tahap yang dikerjakan {{ $staff->name }} di satu tiket, terbaru di atas ({{ $history->total() }} baris{{ $search !== '' ? ', hasil pencarian' : '' }}). Kalau satu tiket dipegang di dua tahap (mis. Registrasi dan Proses Bayar), tampil dua baris. Klik nama pelanggan untuk lihat seluruh riwayat pelanggan itu.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                    <th class="pb-2 pr-4 whitespace-nowrap">Tanggal</th>
                                    <th class="pb-2 pr-4 whitespace-nowrap">Jam (Mulai - Selesai)</th>
                                    <th class="pb-2 pr-4">Tiket</th>
                                    <th class="pb-2 pr-4">Pelanggan</th>
                                    <th class="pb-2 pr-4">Tahap</th>
                                    <th class="pb-2 pr-4 whitespace-nowrap">Durasi Tahap</th>
                                    <th class="pb-2 pr-4 whitespace-nowrap">Total Tiket</th>
                                    <th class="pb-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @forelse($history as $row)
                                    <tr>
                                        <td class="py-2 pr-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $row['started_at']->format('d/m/Y') }}</td>
                                        <td class="py-2 pr-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                            {{ $row['started_at']->format('H:i:s') }} - {{ $row['ended_at'] ? $row['ended_at']->format('H:i:s') : 'berjalan' }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $row['ticket_number'] }}</span>
                                            @if($row['location'])
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $row['location'] }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">
                                            @if($row['customer_phone'])
                                                <a href="{{ route('admin.customers.show', ['phone' => $row['customer_phone']]) }}" class="text-slate-700 dark:text-slate-300 hover:underline">{{ $row['customer_name'] ?: '-' }}</a>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $row['customer_phone'] }}</span>
                                            @else
                                                <span class="text-slate-600 dark:text-slate-300">{{ $row['customer_name'] ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $row['stage_label'] }}</span>
                                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $row['role'] }}</span>
                                        </td>
                                        <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                            {{ \App\Models\Ticket::formatDuration($row['seconds']) }}
                                            @if($row['ongoing'])
                                                <span class="ml-1 text-xs font-normal text-amber-600">berjalan</span>
                                            @endif
                                            @if(!empty($row['record_break']))
                                                <span class="block text-xs font-normal text-green-600" title="Sebelumnya rekor {{ \App\Models\Ticket::formatDuration($row['record_break']['previous']) }}"><x-papyrus-icon name="arrow-trending-up" size="1em" /> Pecah rekor (sebelumnya {{ \App\Models\Ticket::formatDuration($row['record_break']['previous']) }})</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ \App\Models\Ticket::formatDuration($row['total_seconds']) }}</td>
                                        <td class="py-2">
                                            @if($row['status'] === 'completed')
                                                <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-green-50 text-green-700 border border-green-200">Selesai</span>
                                            @elseif($row['status'] === 'cancelled')
                                                <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-red-50 text-red-700 border border-red-200">Dibatalkan</span>
                                            @else
                                                <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-amber-50 text-amber-700 border border-amber-200">Berjalan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-6 text-center text-slate-500 dark:text-slate-400">Belum ada pekerjaan tercatat{{ $search !== '' ? ' yang cocok dengan pencarian' : ' di periode ini' }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($history->hasPages())
                        <div class="mt-4">{{ $history->links() }}</div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-layout>
