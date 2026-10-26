<x-layout title="Laporan Per Operator">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Laporan & Analitik" subtitle="Kinerja per operator" />

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full max-w-7xl mx-auto">

                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Per Operator</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pilih nama untuk melihat riwayat pengerjaan lengkap (dari semua waktu), pelanggan yang dilayani, rekor pribadi, dan peringkatnya di antara semua staf.</p>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-700/50">
                                <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase">
                                    <th class="px-6 py-3">Nama</th>
                                    <th class="px-6 py-3">Peran</th>
                                    <th class="px-6 py-3">Lokasi</th>
                                    <th class="px-6 py-3">Pekerjaan (semua waktu)</th>
                                    <th class="px-6 py-3 whitespace-nowrap">Tiket Terakhir</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @forelse($staff as $row)
                                    @php $u = $row['user']; @endphp
                                    <tr>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('admin.reports.staff', $u) }}" class="font-medium text-slate-900 dark:text-white hover:underline">{{ $u->name }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $u->role === 'operator' ? 'CS & Kasir' : ucfirst($u->role) }}</td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $u->location?->name ?? '-' }}</td>
                                        <td class="px-6 py-4">
                                            @if($row['total'] > 0)
                                                <span class="font-semibold text-slate-900 dark:text-white">{{ $row['total'] }}</span>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                                    @foreach($row['stages'] as $label => $count){{ $label }} {{ $count }}@if(!$loop->last) &middot; @endif @endforeach
                                                </span>
                                            @else
                                                <span class="text-slate-400">Belum ada</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $row['last_at'] ? $row['last_at']->format('d/m/Y H:i') : '-' }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <a href="{{ route('admin.reports.staff', $u) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat Riwayat</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">Belum ada operator.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layout>
