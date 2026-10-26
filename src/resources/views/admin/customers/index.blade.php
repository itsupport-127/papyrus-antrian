<x-layout title="Data Pelanggan">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Data Pelanggan" subtitle="Dikumpulkan otomatis dari histori tiket, dikelompokkan per no. telepon" />

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full max-w-7xl mx-auto">
        @php
            $bulanId = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
            $isFiltered = $search !== '' || $filter['range'] !== 'all';
            $inputCls = 'px-4 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white';
            $labelCls = 'block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1';
        @endphp

        <style>
            /* Tab periode (Semua / Hari / Minggu / Bulan / Tahun / Custom). CSS biasa,
               bukan utility Tailwind, supaya gak butuh build ulang aset. */
            .cf-seg { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; border-radius: 12px; background: #f1f5f9; border: 1px solid #e2e8f0; }
            .cf-seg label { position: relative; cursor: pointer; }
            .cf-seg input { position: absolute; opacity: 0; pointer-events: none; }
            .cf-seg span { display: block; padding: 6px 14px; border-radius: 9px; font-size: 13px; font-weight: 600; color: #64748b; }
            .cf-seg input:checked + span { background: #fff; color: #7c3aed; box-shadow: 0 1px 2px rgba(0, 0, 0, .08); }
            .cf-seg input:focus-visible + span { outline: 2px solid #c5a4e8; }
            html.dark .cf-seg { background: #0f172a; border-color: #334155; }
            html.dark .cf-seg span { color: #94a3b8; }
            html.dark .cf-seg input:checked + span { background: #334155; color: #e9d5ff; }
        </style>

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Data Pelanggan</h1>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4"
                 x-data="{ range: '{{ $filter['range'] }}' }">
                <form method="GET" action="{{ route('admin.customers') }}" class="flex flex-wrap gap-4 items-end">

                    <div>
                        <label class="{{ $labelCls }}">Cari</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau no. telepon..." class="w-64 {{ $inputCls }}">
                    </div>

                    <div>
                        <span class="{{ $labelCls }}">Periode order</span>
                        <div class="cf-seg" role="radiogroup" aria-label="Periode order">
                            @foreach(['all' => 'Semua', 'day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun', 'custom' => 'Custom'] as $val => $text)
                                <label><input type="radio" name="range" value="{{ $val }}" x-model="range"><span>{{ $text }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Hari & Minggu: satu tanggal. Minggu = Senin-Minggu yang memuat tanggal itu. --}}
                    <div x-show="range === 'day' || range === 'week'" x-cloak>
                        <label class="{{ $labelCls }}" x-text="range === 'week' ? 'Tanggal dalam minggu' : 'Tanggal'"></label>
                        <input type="date" name="date" value="{{ $filter['date'] }}" class="{{ $inputCls }}" :disabled="!(range === 'day' || range === 'week')">
                    </div>

                    {{-- Bulan --}}
                    <div x-show="range === 'month'" x-cloak class="flex gap-3">
                        <div>
                            <label class="{{ $labelCls }}">Bulan</label>
                            <select name="month" class="{{ $inputCls }}" :disabled="range !== 'month'">
                                @foreach($bulanId as $num => $nama)
                                    <option value="{{ $num }}" {{ (int) $filter['month'] === $num ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Tahun</label>
                            <select name="year" class="{{ $inputCls }}" :disabled="range !== 'month'">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ (int) $filter['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Tahun --}}
                    <div x-show="range === 'year'" x-cloak>
                        <label class="{{ $labelCls }}">Tahun</label>
                        <select name="year" class="{{ $inputCls }}" :disabled="range !== 'year'">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ (int) $filter['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Custom: dari - sampai (boleh isi salah satu) --}}
                    <div x-show="range === 'custom'" x-cloak class="flex gap-3">
                        <div>
                            <label class="{{ $labelCls }}">Dari</label>
                            <input type="date" name="from" value="{{ $filter['from'] }}" class="{{ $inputCls }}" :disabled="range !== 'custom'">
                        </div>
                        <div>
                            <label class="{{ $labelCls }}">Sampai</label>
                            <input type="date" name="to" value="{{ $filter['to'] }}" class="{{ $inputCls }}" :disabled="range !== 'custom'">
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-xl text-sm font-semibold">Terapkan</button>
                        @if($isFiltered)
                            <a href="{{ route('admin.customers') }}" class="px-4 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <p id="customers-summary" class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                @if($filter['label'])
                    Order periode <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $filter['label'] }}</span>
                @else
                    Semua periode
                @endif
                &middot; {{ number_format($customers->total(), 0, ',', '.') }} pelanggan &middot; {{ number_format($totalOrders, 0, ',', '.') }} order
            </p>
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
                                    {{ $isFiltered ? 'Tidak ada pelanggan yang cocok dengan filter ini.' : 'Belum ada data pelanggan.' }}
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
        </div>
    </div>

    @push('scripts')
    <script>
        // Tabel ikut update otomatis; form pencarian di atas tidak disentuh
        // sama sekali jadi ketikan admin tetap aman.
        window.autoRefreshIds(['customers-summary', 'customers-table-body'], 8000);
    </script>
    @endpush
</x-layout>
