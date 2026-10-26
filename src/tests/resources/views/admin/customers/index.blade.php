<x-layout title="Data Pelanggan">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Dashboard
            </a>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Data Pelanggan</h1>
                <form method="GET" action="{{ route('admin.customers') }}" class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau no. telepon..."
                        class="w-64 px-4 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-xl text-sm font-semibold">Cari</button>
                    @if($search)
                        <a href="{{ route('admin.customers') }}" class="px-4 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300">Reset</a>
                    @endif
                </form>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Dikumpulkan otomatis dari histori tiket, dikelompokkan per no. telepon.</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Nama</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">No. Telepon</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Email</th>
                            <th class="text-right px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Total Order</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Order Terakhir</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="customers-table-body" class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($customers as $c)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ $c->customer_name ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $c->customer_phone }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $c->customer_email ?? '-' }}</td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white">{{ $c->total_orders }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ \Carbon\Carbon::parse($c->last_order_at)->diffForHumans() }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.customers.show', $c->customer_phone) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        Lihat Histori
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                                    Belum ada data pelanggan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    </div>

    @push('scripts')
    <script>
        // Tabel ikut update otomatis; form pencarian di atas tidak disentuh
        // sama sekali jadi ketikan admin tetap aman.
        window.autoRefreshIds(['customers-table-body'], 8000);
    </script>
    @endpush
</x-layout>
