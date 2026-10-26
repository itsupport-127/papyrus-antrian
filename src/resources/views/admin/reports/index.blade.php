<x-layout title="Laporan & Analitik">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Laporan & Analitik" subtitle="Statistik dan performa antrian" />

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full max-w-7xl mx-auto">
            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                        <select name="period" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Minggu Ini</option>
                            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                        <select name="location_id" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                        Filter
                    </button>
                </form>
            </div>

            <div id="reports-content">
            @php $staffQuery = array_filter(['period' => $period, 'location_id' => $locationId]); @endphp
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total Tiket</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ $stats['total_tickets'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Selesai</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['completed'] }}</p>
                    @if($stats['total_tickets'] > 0)
                        <p class="text-sm text-slate-400 mt-1">{{ round($stats['completed'] / $stats['total_tickets'] * 100) }}%</p>
                    @endif
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Rata-rata Layanan</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['avg_service_time'] }}</p>
                    <p class="text-sm text-slate-400 mt-1">menit</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Rata-rata Tunggu</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1">{{ $stats['avg_wait_time'] }}</p>
                    <p class="text-sm text-slate-400 mt-1">menit</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8 mb-8">
                <!-- Ticket Status Breakdown -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Status Tiket</h2>
                    <div class="space-y-3">
                        @php
                            $statuses = [
                                ['label' => 'Menunggu', 'count' => $stats['waiting'], 'color' => 'bg-amber-500'],
                                ['label' => 'Selesai', 'count' => $stats['completed'], 'color' => 'bg-green-500'],
                                ['label' => 'Dilewati', 'count' => $stats['skipped'], 'color' => 'bg-orange-500'],
                                ['label' => 'Dibatalkan', 'count' => $stats['cancelled'], 'color' => 'bg-red-500'],
                            ];
                        @endphp
                        @foreach($statuses as $st)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full {{ $st['color'] }}"></div>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $st['label'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $st['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Feedback Summary -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Feedback Pelanggan</h2>
                    <div class="text-center py-4">
                        <p class="text-4xl font-bold text-amber-500">{{ $feedbackStats['avg_rating'] > 0 ? $feedbackStats['avg_rating'] : '-' }}</p>
                        <div class="flex items-center justify-center mt-2 space-x-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= round($feedbackStats['avg_rating']) ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">{{ $feedbackStats['total'] }} feedback diterima</p>
                    </div>
                </div>
            </div>

            <!-- Hourly Distribution -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Distribusi Per Jam</h2>
                <div class="flex items-end space-x-1 h-40">
                    @php $maxHourly = max($hourlyData) ?: 1; @endphp
                    @foreach($hourlyData as $hour => $count)
                        @if($hour >= 7 && $hour <= 18)
                            <div class="flex-1 flex flex-col items-center group">
                                <span class="text-xs text-slate-500 dark:text-slate-400 mb-1 opacity-0 group-hover:opacity-100 transition-opacity">{{ $count }}</span>
                                <div
                                    class="w-full bg-blue-500 rounded-t-sm transition-all hover:bg-blue-600"
                                    style="height: {{ $count > 0 ? max(4, (int) round($count / $maxHourly * 100)) : 0 }}px"
                                    title="{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 - {{ $count }} tiket"
                                ></div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Per Location Breakdown -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Per Lokasi</h2>
                    <div class="space-y-3">
                        @foreach($locationStats as $loc)
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white text-sm">{{ $loc->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $loc->code }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $loc->total_tickets }}</p>
                                    <p class="text-xs text-green-600">{{ $loc->completed_tickets }} selesai</p>
                                </div>
                            </div>
                        @endforeach
                        @if($locationStats->isEmpty())
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada data.</p>
                        @endif
                    </div>
                </div>

                <!-- Operator Performance -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Performa Operator</h2>
                    <div class="space-y-3">
                        @foreach($operatorStats as $op)
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                <div>
                                    <a href="{{ route('admin.reports.staff', ['user' => $op->id] + $staffQuery) }}" class="font-medium text-slate-900 dark:text-white text-sm hover:underline">{{ $op->name }}</a>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $op->location?->name ?? '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $op->tickets_served }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">tiket dilayani</p>
                                </div>
                            </div>
                        @endforeach
                        @if($operatorStats->isEmpty())
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada data.</p>
                        @endif
                    </div>
                </div>

                <!-- Rata-rata Waktu per Tahap (estimasi) -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Rata-rata Waktu per Tahap</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Cuma tiket yang sudah "Selesai" di periode ini yang dihitung. Ini dipakai buat estimasi waktu internal -- gak ditampilkan ke pelanggan.</p>
                    <div class="space-y-3">
                        @foreach($stageDurationStats as $stat)
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white text-sm">{{ $stat['label'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $stat['count'] }} tiket</p>
                                </div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm">{{ \App\Models\Ticket::formatDuration($stat['avg_seconds']) }}</p>
                            </div>
                        @endforeach
                        @if(empty($stageDurationStats))
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada tiket selesai di periode ini.</p>
                        @endif
                    </div>
                </div>

                <!-- Top Performer (Tercepat) per Tahap -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 lg:col-span-2">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><x-papyrus-icon name="trophy" size="1.15em" color="#d4a017" /> Top Performer</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Staf dengan rata-rata waktu tercepat per tahap (Registrasi, Sesi Foto, Proses Bayar &amp; Cetak) -- CS dan fotografer sama-sama masuk sesuai tahap yang mereka tangani. Peringkat murni dari rata-rata waktu paling cepat (bukan dari jumlah tiket); jumlah tiket cuma syarat minimal 2 tiket biar gak keangkat cuma dari 1 tiket kebetulan cepat, dan cuma tiket &quot;Selesai&quot; yang dihitung. Klik nama buat lihat riwayat kerjanya.</p>
                    @if($topPerformers->isEmpty())
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum cukup data di periode ini.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($topPerformers as $label => $ranked)
                                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-3">{{ $label }}</p>
                                    <div class="space-y-2.5">
                                        @foreach($ranked as $i => $sp)
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-base leading-none flex-shrink-0">@if($i < 3)<x-papyrus-icon name="trophy" size="1.35em" :color="['#d4a017', '#94a3b8', '#b4763a'][$i]" />@endif</span>
                                                <div class="min-w-0 flex-1">
                                                    <a href="{{ route('admin.reports.staff', ['user' => $sp['user_id']] + $staffQuery) }}" class="block text-sm font-medium text-slate-900 dark:text-white truncate hover:underline">{{ $sp['user_name'] }}</a>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ \App\Models\Ticket::formatDuration($sp['avg_seconds']) }} &middot; {{ $sp['count'] }} tiket</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Evaluasi Kerja per Staf per Tahap -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 lg:col-span-2">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Evaluasi Kerja per Staf</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rata-rata durasi tiap staf menangani tahapnya masing-masing (Registrasi, Sesi Foto, Proses Bayar) -- diurutkan dari yang paling lama. Cuma tiket sejak fitur ini aktif yang tercatat per-staf; tiket lama tetap masuk ke rata-rata per tahap di atas.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                    <th class="pb-2 pr-4">Staf</th>
                                    <th class="pb-2 pr-4">Tahap</th>
                                    <th class="pb-2 pr-4">Rata-rata Waktu</th>
                                    <th class="pb-2">Jumlah Tiket</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($staffPerformance as $sp)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium text-slate-900 dark:text-white"><a href="{{ route('admin.reports.staff', ['user' => $sp['user_id']] + $staffQuery) }}" class="hover:underline">{{ $sp['user_name'] }}</a></td>
                                        <td class="py-2 pr-4 text-slate-600 dark:text-slate-300">{{ $sp['label'] }}</td>
                                        <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white">{{ \App\Models\Ticket::formatDuration($sp['avg_seconds']) }}</td>
                                        <td class="py-2 text-slate-500 dark:text-slate-400">{{ $sp['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($staffPerformance->isEmpty())
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada data per-staf di periode ini.</p>
                        @endif
                    </div>
                </div>
            </div>
            </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.autoRefreshIds(['reports-content'], 10000);
    </script>
    @endpush
</x-layout>
