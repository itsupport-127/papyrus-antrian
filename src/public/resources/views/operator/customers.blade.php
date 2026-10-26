<x-layout title="Data Pelanggan">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <a href="{{ route('operator.dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Dashboard
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Data Pelanggan</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ $location->name }} — semua tiket &amp; status terkininya</p>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-6">
                <form method="GET" action="{{ route('operator.customers') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                        <input type="text" name="q" value="{{ $search }}" placeholder="Nama, no. telepon, atau no. tiket..."
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="" {{ $status === '' ? 'selected' : '' }}>Semua Status</option>
                            <option value="waiting" {{ $status === 'waiting' ? 'selected' : '' }}>Menunggu</option>
                            <option value="calling" {{ $status === 'calling' ? 'selected' : '' }}>Dipanggil</option>
                            <option value="serving" {{ $status === 'serving' ? 'selected' : '' }}>Sedang Dilayani</option>
                            <option value="editing" {{ $status === 'editing' ? 'selected' : '' }}>Sedang Diedit</option>
                            <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tahap</label>
                        <select name="stage" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="" {{ $stage === '' ? 'selected' : '' }}>Semua Tahap</option>
                            <option value="registration" {{ $stage === 'registration' ? 'selected' : '' }}>Registrasi</option>
                            <option value="photo_session" {{ $stage === 'photo_session' ? 'selected' : '' }}>Sesi Foto</option>
                            <option value="payment" {{ $stage === 'payment' ? 'selected' : '' }}>Pembayaran & Cetak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ $date === 'all' ? '' : $date }}"
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                    </div>
                    <div class="md:col-span-5 flex items-center gap-3">
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('operator.customers', ['date' => 'all']) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">
                            Lihat semua tanggal
                        </a>
                        @if($search || $status || $stage || $date !== now()->toDateString())
                            <a href="{{ route('operator.customers') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-900/40">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tiket</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pelanggan</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Email</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tahap</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Dilayani Oleh</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="operator-customers-table-body" class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($tickets as $ticket)
                                <tr>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-purple-700 dark:text-purple-400">{{ $ticket->ticket_number }}</span>
                                        @if($ticket->skip_count > 0)
                                            <span class="block text-xs text-orange-500 mt-0.5">Dilewati {{ $ticket->skip_count }}x</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-900 dark:text-white">{{ $ticket->customer_name ?? '-' }}</span>
                                        <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $ticket->customer_phone ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm">{{ $ticket->customer_email ?? '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm">
                                        {{ $ticket->stageLabel() }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusMap = [
                                                'waiting' => ['label' => 'Menunggu', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'],
                                                'calling' => ['label' => 'Dipanggil', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                                                'serving' => ['label' => 'Sedang Dilayani', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                                                'editing' => ['label' => 'Sedang Diedit', 'class' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'],
                                                'completed' => ['label' => 'Selesai', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                                                'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                            ];
                                            $badge = $statusMap[$ticket->status] ?? ['label' => $ticket->status, 'class' => 'bg-slate-100 text-slate-700'];
                                        @endphp
                                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full {{ $badge['class'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm">
                                        {{ $ticket->servedBy?->name ?? '-' }}
                                        @if($ticket->counter)
                                            <span class="block text-xs text-slate-400">{{ $ticket->counter->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm min-w-[180px]">
                                        @php $waktu = $ticket->stageDurations(); @endphp
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                                            @if($waktu['total']['ongoing'])
                                                <span class="waktu-live" data-start="{{ $waktu['total']['start']->timestamp * 1000 }}">{{ \App\Models\Ticket::formatDuration($waktu['total']['seconds']) }}</span>
                                            @else
                                                {{ \App\Models\Ticket::formatDuration($waktu['total']['seconds']) }}
                                            @endif
                                        </div>
                                        <ul class="mt-1 space-y-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            @foreach($waktu['segments'] as $s)
                                                <li class="flex items-center justify-between gap-3">
                                                    <span>{{ $s['label'] }}</span>
                                                    <span class="font-medium {{ $s['ongoing'] ? 'text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300' }}">
                                                        @if(!$s['available'])
                                                            -
                                                        @elseif($s['ongoing'])
                                                            <span class="waktu-live" data-start="{{ $s['start']->timestamp * 1000 }}">{{ \App\Models\Ticket::formatDuration($s['seconds']) }}</span>
                                                        @else
                                                            {{ \App\Models\Ticket::formatDuration($s['seconds']) }}
                                                        @endif
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Tidak ada tiket yang cocok dengan filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Tabel ikut update otomatis; form filter di atas tidak disentuh
        // sama sekali jadi pilihan/ketikan tidak ke-reset.
        window.autoRefreshIds(['operator-customers-table-body'], 6000);

        // Widget "WAKTU": angka buat tahap yang MASIH BERJALAN harus jalan
        // hidup per detik (bukan cuma ke-update tiap 6 detik bareng
        // autoRefreshIds di atas). Query ulang tiap tick (bukan cache
        // referensi elemen) karena autoRefreshIds mengganti innerHTML
        // tbody-nya secara berkala.
        function formatDurationJs(totalSeconds) {
            totalSeconds = Math.max(0, Math.floor(totalSeconds));
            if (totalSeconds < 60) return totalSeconds + 's';
            const minutes = Math.floor(totalSeconds / 60);
            if (minutes < 60) return minutes + 'm ' + (totalSeconds % 60) + 's';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + 'h ' + (minutes % 60) + 'm';
            const days = Math.floor(hours / 24);
            return days + 'd ' + (hours % 24) + 'h';
        }
        setInterval(() => {
            document.querySelectorAll('.waktu-live').forEach((el) => {
                const start = parseInt(el.dataset.start, 10);
                if (!start) return;
                el.textContent = formatDurationJs((Date.now() - start) / 1000);
            });
        }, 1000);
    </script>
    @endpush
</x-layout>