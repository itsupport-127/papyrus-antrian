{{--
    Sidebar navigasi admin, dikelompokkan per kategori -- MENGGANTIKAN baris
    tab horizontal yang sebelumnya ada di x-admin-header (Dashboard | Lokasi
    & Reservasi | Pengguna | Pelanggan | Laporan sejajar, gak dikelompokkan
    sama sekali). Sebelumnya juga ada 4 "kartu menu" (Lokasi/Pengguna/
    Pelanggan/Publik) di halaman Dashboard yang isinya DUPLIKAT dari tab-tab
    ini -- sekarang kartu itu dihapus dari admin/dashboard.blade.php, sidebar
    ini jadi satu-satunya tempat navigasi antar-halaman admin.

    Cuma ditampilin buat Admin (sama kayak tab lama) -- komponen ini juga
    dipakai di halaman yang bisa diakses CS/Kasir (/admin/customers), yang
    kalau ditampilin ke mereka bakal nunjukin link ke halaman admin-only
    yang diklik malah 403.

    Responsif: di layar sempit (<lg) jadi baris horizontal yang bisa
    di-scroll ke samping (mirip tab lama, biar gak makan tempat di HP). Di
    layar lebar (>=lg) jadi kolom tetap di sisi kiri.
--}}
@if(auth()->user()->isAdmin())
    <style>
        .nav-sub > summary { list-style: none; cursor: pointer; }
        .nav-sub > summary::-webkit-details-marker { display: none; }
        .nav-sub[open] > summary .nav-chev { transform: rotate(180deg); }
    </style>
    @php
        $navGroups = [
            [
                'label' => null, // grup pertama gak perlu judul kategori
                'items' => [
                    ['route' => 'admin.dashboard', 'match' => ['admin.dashboard'], 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ],
            ],
            [
                'label' => 'Operasional',
                'items' => [
                    ['route' => 'admin.locations', 'match' => ['admin.locations', 'admin.locations.*', 'admin.counters', 'admin.counters.*', 'admin.categories', 'admin.categories.*', 'admin.booking-slots*'], 'label' => 'Lokasi & Reservasi', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['route' => 'admin.booking-packages', 'match' => ['admin.booking-packages', 'admin.booking-packages.*'], 'label' => 'Paket Reservasi', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z'],
                    ['route' => 'admin.booking-extras', 'match' => ['admin.booking-extras', 'admin.booking-extras.*'], 'label' => 'Tambahan Reservasi', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                    ['route' => 'admin.reminders', 'match' => ['admin.reminders', 'admin.reminders.*'], 'label' => 'Pengingat', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                    ['route' => 'admin.users', 'match' => ['admin.users', 'admin.users.*'], 'label' => 'Pengguna', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ],
            ],
            [
                'label' => 'Data',
                'items' => [
                    ['route' => 'admin.customers', 'match' => ['admin.customers', 'admin.customers.*'], 'label' => 'Pelanggan', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['route' => 'admin.reports', 'match' => ['admin.reports'], 'label' => 'Laporan', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['route' => 'admin.reports.staff.index', 'match' => ['admin.reports.staff.index', 'admin.reports.staff'], 'label' => 'Per Operator', 'child' => true, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ],
            ],
            [
                'label' => 'Lainnya',
                'items' => [
                    ['route' => 'locations.index', 'match' => ['locations.index'], 'label' => 'Lihat Publik', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 'external' => true],
                ],
            ],
        ];
        $isActiveGroup = fn (array $item) => request()->routeIs($item['match']);
        // Sub-menu Laporan > Per Operator: semua operator & fotografer (urut nama).
        $navStaff = \App\Models\User::whereIn('role', ['operator', 'fotografer'])->orderBy('name')->get(['id', 'name', 'role']);
        $routeUser = request()->route('user');
        $activeStaffId = $routeUser instanceof \App\Models\User ? $routeUser->id : null;
    @endphp

    {{-- Versi mobile/tablet: baris horizontal scroll, DATAR (gak dikelompokkan
         per kategori -- di layar sempit kelompok cuma bikin ribet, baris
         datar lebih gampang di-scan & di-scroll satu tangan). --}}
    <nav class="lg:hidden -mx-4 sm:-mx-6 px-4 sm:px-6 mb-4">
        <div class="flex items-center gap-1 overflow-x-auto pb-1">
            @foreach($navGroups as $group)
                @foreach($group['items'] as $item)
                    <a href="{{ route($item['route']) }}" @if($item['external'] ?? false) target="_blank" @endif
                       class="{{ $isActiveGroup($item) ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} px-3 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap shrink-0 transition-colors">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endforeach
        </div>
    </nav>

    {{-- Versi desktop: kolom tetap di kiri, dikelompokkan per kategori. --}}
    <aside class="hidden lg:block w-56 shrink-0">
        <nav class="sticky top-4 space-y-5">
            @foreach($navGroups as $group)
                <div>
                    @if($group['label'])
                        <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $group['label'] }}</p>
                    @endif
                    <div class="space-y-0.5">
                        @foreach($group['items'] as $item)
                            @php $active = $isActiveGroup($item); @endphp
                            @if($item['child'] ?? false)
                                <details class="nav-sub" @if($active) open @endif>
                                    <summary class="{{ $active ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 font-medium' }} flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors">
                                        <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                        </svg>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                        <svg class="nav-chev w-3 h-3 ml-auto shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </summary>
                                    <div class="mt-0.5 pl-3 border-l border-slate-200 dark:border-slate-700 space-y-0.5" style="margin-left: 1.25rem;">
                                        <a href="{{ route('admin.reports.staff.index') }}"
                                           class="{{ request()->routeIs('admin.reports.staff.index') ? 'text-blue-700 dark:text-blue-400 font-semibold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }} block px-2 py-1 rounded-md text-sm truncate">Semua operator</a>
                                        @foreach($navStaff as $navUser)
                                            <a href="{{ route('admin.reports.staff', $navUser) }}" title="{{ $navUser->role === 'operator' ? 'CS & Kasir' : ucfirst($navUser->role) }}"
                                               class="{{ $activeStaffId === $navUser->id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' }} block px-2 py-1 rounded-md text-sm truncate">{{ $navUser->name }}</a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                            <a href="{{ route($item['route']) }}" @if($item['external'] ?? false) target="_blank" @endif
                               class="{{ $active ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 font-medium' }} flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition-colors">
                                <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                </svg>
                                <span class="truncate">{{ $item['label'] }}</span>
                                @if($item['external'] ?? false)
                                    <svg class="w-3 h-3 ml-auto shrink-0 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                @endif
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </aside>
@endif
