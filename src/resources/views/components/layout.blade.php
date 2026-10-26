<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Cegah Edge/Chrome nawarin atau otomatis nerjemahin halaman ini ke
         bahasa lain -- ini yang bikin sebagian teks di layar display bisa
         "kecampur" jadi Inggris kalau bahasa default Edge di PC-nya bukan
         Indonesia (translate cuma jalan sekali pas load, teks yang
         di-update belakangan lewat JS -- jam, angka antrian -- gak ikut
         ke-translate lagi, makanya kelihatan campur-campur). Dua penanda
         ini (atribut HTML + meta tag) dipakai bareng karena beda browser
         engine baca yang beda: translate="no" itu standar HTML5, meta
         "google" dipakai khusus mesin Google Translate yang juga dipakai
         Edge (Edge berbasis Chromium). --}}
    <meta name="google" content="notranslate">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('services.webpush.vapid_public_key') }}">

    <title>{{ $title ?? config('app.name') }} - Sistem Manajemen Antrian</title>

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#c5a4e8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-papyrus.png') }}">

    @if(file_exists(public_path('images/logo-papyrus.png')))
        <link rel="icon" href="{{ asset('images/logo-papyrus.png') }}">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|baloo-2:500,600,700,800" rel="stylesheet" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        /* Ganti outline fokus bawaan browser (kadang tebal & item hitam,
           beda-beda tiap browser/OS) di semua input/select/textarea dengan
           glow lembut warna brand -- elemen yang lagi difokus tetap jelas
           kelihatan (aksesibilitas kejaga), cuma tampilannya lebih halus &
           konsisten sama desain app, bukan kotak hitam kaku. */
        input:focus, select:focus, textarea:focus {
            outline: none !important;
            border-color: var(--color-lavender, #c5a4e8) !important;
            box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-lavender, #c5a4e8) 30%, transparent),
                        0 0 12px 1px color-mix(in oklab, var(--color-lavender, #c5a4e8) 25%, transparent);
        }
        /* Tombol/link tetap dikasih outline pas navigasi keyboard (Tab) --
           cuma diganti warnanya, biar gak ilang sama sekali buat
           aksesibilitas, dan gak nongol pas diklik mouse biasa. */
        button:focus-visible, a:focus-visible {
            outline: 2px solid var(--color-lavender, #c5a4e8) !important;
            outline-offset: 2px;
        }
        /* Animasi lonceng notifikasi admin (lihat adminNotifBell() di
           bawah) -- gerakan "goyang" singkat pas ada aktivitas baru
           (tiket baru daftar / lagi dipanggil), BUKAN animasi terus-
           menerus (biar gak ganggu/distracting selama dibuka). */
        @keyframes bell-ring {
            0%, 100% { transform: rotate(0deg); }
            15% { transform: rotate(14deg); }
            30% { transform: rotate(-12deg); }
            45% { transform: rotate(9deg); }
            60% { transform: rotate(-7deg); }
            75% { transform: rotate(4deg); }
            90% { transform: rotate(-2deg); }
        }
        .animate-bell-ring {
            animation: bell-ring 0.7s ease-in-out 2;
            transform-origin: top center;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-periwinkle/60 via-slate-50 to-blush/30 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 text-slate-900 dark:text-slate-100 min-h-screen transition-colors duration-300">
    <!-- Aksen "washi tape" khas Papyrus Photo -->
    <div class="papyrus-tape print:hidden"></div>

    <!-- Navigation -->
    <nav class="relative z-30 bg-white/90 dark:bg-slate-800 backdrop-blur border-b border-slate-200 dark:border-slate-700 print:hidden" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2 min-w-0">
                        @if(file_exists(public_path('images/logo-papyrus.png')))
                            <span class="w-9 h-9 shrink-0 rounded-full p-[2px] bg-gradient-to-br from-lavender via-skyblue to-dustyrose">
                                <img src="{{ asset('images/logo-papyrus.png') }}" alt="Papyrus Photo" class="w-full h-full object-contain rounded-full bg-white">
                            </span>
                        @else
                            <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center bg-gradient-to-br from-lavender via-skyblue to-dustyrose">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                        @endif
                        <span class="papyrus-brand text-lg sm:text-xl truncate">{{ config('app.name') }}</span>
                    </a>

                    <!-- Badge peran — biar langsung kelihatan sedang login sebagai apa -->
                    @auth
                        @if(auth()->user()->isAdmin())
                            <span class="hidden lg:inline-flex items-center gap-1 bg-blush/60 text-dustyrose dark:bg-amber-900/30 dark:text-amber-400 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 bg-dustyrose rounded-full"></span> Admin
                            </span>
                        @elseif(auth()->user()->isOperator())
                            <span class="hidden lg:inline-flex items-center gap-1 bg-mint/40 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 bg-mint rounded-full"></span> CS &amp; Kasir
                            </span>
                        @elseif(auth()->user()->isFotografer())
                            <span class="hidden lg:inline-flex items-center gap-1 bg-skyblue/20 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 bg-skyblue rounded-full"></span> Fotografer
                            </span>
                        @endif
                    @endauth
                </div>

                @php
                    $navLink = fn (bool $active) => $active
                        ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-2 text-sm font-semibold rounded-lg'
                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50';
                    $navLinkMobile = fn (bool $active) => $active
                        ? 'block text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-4 py-3 text-sm font-semibold rounded-lg'
                        : 'block text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-4 py-3 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50';
                @endphp

                <!-- Menu desktop -->
                <div class="hidden md:flex items-center space-x-1">
                    @auth
                        {{--
                            Buat Admin, Operator (CS & Kasir), dan Fotografer, navbar
                            umum ini cuma nyimpen SATU pintu balik ke area kerja
                            masing-masing (bukan menu lengkap) -- menu lengkap tiap
                            role (Dashboard, Lokasi & Reservasi, Pengguna, dst / atau
                            Dashboard, Jadwal Reservasi, Data Pelanggan) ada di baris
                            tab khusus di bawah judul halaman kerja mereka (lihat
                            komponen x-admin-header / x-operator-header). Jadi navbar
                            atas isinya menu umum + satu tombol balik ke "rumah"
                            masing-masing role, gak nyampur sama menu manajemen.
                        --}}
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="{{ $navLink(request()->routeIs('admin.*')) }}">
                                Dashboard
                            </a>
                        @elseif(auth()->user()->isOperator())
                            <a href="{{ route('operator.dashboard') }}" class="{{ $navLink(request()->routeIs('operator.*')) }}">
                                Dashboard
                            </a>
                        @elseif(auth()->user()->isFotografer())
                            <a href="{{ route('operator.dashboard') }}" class="{{ $navLink(request()->routeIs('operator.*')) }}">
                                Panel Fotografer
                            </a>
                        @endif

                        <span class="w-px h-6 bg-slate-200 dark:bg-slate-600 mx-1" title="Menu manajemen di atas, menu publik di bawah"></span>

                        {{-- Menu PUBLIK -- sama persis kayak yang dilihat pelanggan
                             yang belum login. Operator/Admin/Fotografer tetap bisa
                             buka ini (mis. buat ngecek tampilan pelanggan, atau
                             ambilin nomor antrian manual dari sini), dipisah pakai
                             garis di atas biar jelas beda dari menu manajemen. --}}
                        <a href="{{ route('locations.index') }}" class="{{ $navLink(request()->routeIs('locations.*')) }}">
                            Lokasi
                        </a>
                        <a href="{{ route('tickets.check') }}" class="{{ $navLink(request()->routeIs('tickets.check') || request()->routeIs('tickets.show')) }}">
                            Cek Antrian
                        </a>
                        <a href="{{ route('bookings.check') }}" class="{{ $navLink(request()->routeIs('bookings.check')) }}">
                            Cek Reservasi
                        </a>

                        <span class="w-px h-6 bg-slate-200 dark:bg-slate-600 mx-1"></span>

                        {{-- Menu profil: foto + nama, isinya Profil Saya (ubah foto/nama/password) dan Keluar. --}}
                        @php
                            $roleLabelNav = ['admin' => 'Admin', 'operator' => 'CS & Kasir', 'fotografer' => 'Fotografer', 'viewer' => 'Viewer'][auth()->user()->role] ?? ucfirst((string) auth()->user()->role);
                        @endphp
                        <details class="profile-menu relative">
                            <summary class="list-none cursor-pointer flex items-center gap-2 pl-1 pr-2 py-1 rounded-full hover:bg-slate-50 dark:hover:bg-slate-700/50 select-none">
                                <x-user-avatar :user="auth()->user()" :size="32" />
                                <span class="hidden lg:block text-sm font-medium text-slate-700 dark:text-slate-200 max-w-[8rem] truncate">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg py-2 z-50">
                                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $roleLabelNav }}</p>
                                </div>
                                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50">Profil Saya</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-700/50">Keluar</button>
                                </form>
                            </div>
                        </details>
                        <script>
                            // Tutup menu profil kalau klik di luar atau tekan Esc.
                            if (!window.__profileMenuInit) {
                                window.__profileMenuInit = true;
                                document.addEventListener('click', function (e) {
                                    document.querySelectorAll('details.profile-menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
                                });
                                document.addEventListener('keydown', function (e) {
                                    if (e.key === 'Escape') document.querySelectorAll('details.profile-menu[open]').forEach(function (d) { d.removeAttribute('open'); });
                                });
                            }
                        </script>
                    @else
                        <a href="{{ route('locations.index') }}" class="{{ $navLink(request()->routeIs('locations.*')) }}">
                            Lokasi
                        </a>
                        <a href="{{ route('tickets.check') }}" class="{{ $navLink(request()->routeIs('tickets.*')) }}">
                            Cek Antrian
                        </a>
                        <a href="{{ route('bookings.check') }}" class="{{ $navLink(request()->routeIs('bookings.*')) }}">
                            Cek Reservasi
                        </a>
                        <a href="{{ route('login') }}" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium ml-2 transition-opacity">
                            Masuk
                        </a>
                    @endauth

                    @auth
                        @if(auth()->user()->isAdmin())
                            {{-- Lonceng notifikasi -- cuma buat Admin, sesuai
                                 diminta. Ringkasan: total tiket
                                 menunggu+sedang dipanggil di SEMUA lokasi,
                                 di-poll tiap 20 detik lewat
                                 admin.notifications-summary (lihat
                                 AdminController::notificationsSummary()).
                                 Logic Alpine-nya (adminNotifBell) ada di
                                 blok <script> paling bawah file ini. --}}
                            <div x-data="adminNotifBell()" x-init="init()" class="relative">
                                <button @click="open = !open" type="button" class="relative p-2 rounded-lg transition-colors ml-1"
                                    :class="total > 0 ? 'text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    title="Notifikasi antrian">
                                    <svg class="w-5 h-5" :class="{ 'animate-bell-ring': ringing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span x-show="total > 0" x-cloak x-text="total > 99 ? '99+' : total" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center leading-none"></span>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-4 text-sm z-50">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200 mb-2">Ringkasan Antrian</p>
                                    <div class="flex justify-between py-1">
                                        <span class="text-slate-500 dark:text-slate-400">Menunggu</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="waiting"></span>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-slate-500 dark:text-slate-400">Sedang dipanggil</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="calling"></span>
                                    </div>
                                    <p x-show="total === 0" x-cloak class="text-xs text-slate-400 dark:text-slate-500 pt-1">Gak ada yang perlu diperhatikan saat ini.</p>
                                    <a href="{{ route('admin.dashboard') }}" class="block mt-3 text-center text-blue-600 dark:text-blue-400 text-xs font-semibold hover:underline">Lihat Dashboard</a>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors ml-1" title="Toggle Dark Mode">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                </div>

                <!-- Tombol menu mobile -->
                <div class="flex md:hidden items-center gap-1">
                    @auth
                        @if(auth()->user()->isAdmin())
                            {{-- Sama persis kayak versi desktop di atas, cuma
                                 markup-nya diulang biar posisinya pas di
                                 toolbar mobile (x-data terpisah = polling-nya
                                 juga jalan sendiri-sendiri, gak masalah,
                                 cuma satu yang kepakai tergantung breakpoint). --}}
                            <div x-data="adminNotifBell()" x-init="init()" class="relative">
                                <button @click="open = !open" type="button" class="relative p-2 rounded-lg transition-colors"
                                    :class="total > 0 ? 'text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    title="Notifikasi antrian">
                                    <svg class="w-5 h-5" :class="{ 'animate-bell-ring': ringing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span x-show="total > 0" x-cloak x-text="total > 99 ? '99+' : total" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center leading-none"></span>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-60 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-4 text-sm z-50">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200 mb-2">Ringkasan Antrian</p>
                                    <div class="flex justify-between py-1">
                                        <span class="text-slate-500 dark:text-slate-400">Menunggu</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="waiting"></span>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-slate-500 dark:text-slate-400">Sedang dipanggil</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="calling"></span>
                                    </div>
                                    <p x-show="total === 0" x-cloak class="text-xs text-slate-400 dark:text-slate-500 pt-1">Gak ada yang perlu diperhatikan saat ini.</p>
                                    <a href="{{ route('admin.dashboard') }}" class="block mt-3 text-center text-blue-600 dark:text-blue-400 text-xs font-semibold hover:underline">Lihat Dashboard</a>
                                </div>
                            </div>
                        @endif
                    @endauth
                    <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Toggle Dark Mode">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <button @click="mobileOpen = !mobileOpen" class="p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" aria-label="Buka menu">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu mobile (dropdown) -->
        <div x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false" x-transition class="md:hidden border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 space-y-1">
            @auth
                @if(auth()->user()->isAdmin())
                    <span class="inline-flex items-center gap-1 bg-blush/60 text-dustyrose dark:bg-amber-900/30 dark:text-amber-400 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <span class="w-1.5 h-1.5 bg-dustyrose rounded-full"></span> Admin
                    </span>
                    <a href="{{ route('admin.dashboard') }}" class="{{ $navLinkMobile(request()->routeIs('admin.*')) }}">Dashboard</a>
                @elseif(auth()->user()->isOperator())
                    <span class="inline-flex items-center gap-1 bg-mint/40 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <span class="w-1.5 h-1.5 bg-mint rounded-full"></span> CS &amp; Kasir
                    </span>
                    <a href="{{ route('operator.dashboard') }}" class="{{ $navLinkMobile(request()->routeIs('operator.*')) }}">Dashboard</a>
                @elseif(auth()->user()->isFotografer())
                    <span class="inline-flex items-center gap-1 bg-skyblue/20 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <span class="w-1.5 h-1.5 bg-skyblue rounded-full"></span> Fotografer
                    </span>
                    <a href="{{ route('operator.dashboard') }}" class="{{ $navLinkMobile(request()->routeIs('operator.*')) }}">Panel Fotografer</a>
                @endif

                <p class="px-4 pt-1 text-[11px] text-slate-400 dark:text-slate-500">Menu lengkap tiap role ada di tab bawah judul halaman kerja.</p>

                <div class="border-t border-slate-100 dark:border-slate-700 my-2"></div>

                {{-- Menu PUBLIK -- lihat catatan yang sama di menu desktop di atas. --}}
                <p class="px-4 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Menu Publik</p>
                <a href="{{ route('locations.index') }}" class="{{ $navLinkMobile(request()->routeIs('locations.*')) }}">Lokasi</a>
                <a href="{{ route('tickets.check') }}" class="{{ $navLinkMobile(request()->routeIs('tickets.check') || request()->routeIs('tickets.show')) }}">Cek Antrian</a>
                <a href="{{ route('bookings.check') }}" class="{{ $navLinkMobile(request()->routeIs('bookings.check')) }}">Cek Reservasi</a>

                <div class="border-t border-slate-100 dark:border-slate-700 my-2"></div>

                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <x-user-avatar :user="auth()->user()" :size="28" />
                    <span class="truncate">Profil Saya &middot; {{ auth()->user()->name }}</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left block text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-4 py-3 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        Keluar
                    </button>
                </form>
            @else
                <a href="{{ route('locations.index') }}" class="{{ $navLinkMobile(request()->routeIs('locations.*')) }}">Lokasi</a>
                <a href="{{ route('tickets.check') }}" class="{{ $navLinkMobile(request()->routeIs('tickets.*')) }}">Cek Antrian</a>
                <a href="{{ route('bookings.check') }}" class="{{ $navLinkMobile(request()->routeIs('bookings.*')) }}">Cek Reservasi</a>
                <a href="{{ route('login') }}" class="block text-center bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-3 rounded-lg text-sm font-medium mt-2 transition-opacity">
                    Masuk
                </a>
            @endauth
        </div>
    </nav>

    <!--
        Flash Messages
        Dulu ini banner statis yang nempel terus di halaman sampai halaman
        di-reload lagi. Sekarang isinya cuma "data" tersembunyi (dibaca oleh
        script toast di bawah, dan juga dibaca oleh fetch() di dashboard
        operator supaya aksi seperti "Panggil Ulang" tidak perlu reload
        halaman). Tampilannya sendiri jadi toast kecil pop-up yang otomatis
        hilang sendiri beberapa detik kemudian.
    -->
    @if(session('success'))
        <div id="flash-success" class="hidden">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div id="flash-error" class="hidden">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div id="flash-info" class="hidden">{{ session('info') }}</div>
    @endif

    <!-- Toast container: pop-up notifikasi kecil di pojok kanan atas -->
    <div id="toast-container" class="fixed z-[100] top-4 right-4 left-4 sm:left-auto flex flex-col gap-2 items-stretch sm:items-end pointer-events-none"></div>

    <!-- Modal konfirmasi custom (ganti confirm() bawaan browser yang jelek/nunjukin
         hostname) -- dipakai lewat window.showConfirmModal(...) dari halaman manapun. -->
    <div id="confirm-modal-backdrop" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md shadow-xl overflow-hidden">
            <div class="p-5 sm:p-6">
                <div id="confirm-modal-icon" class="w-12 h-12 rounded-full flex items-center justify-center mb-4">
                    <svg id="confirm-modal-icon-svg" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
                </div>
                <h2 id="confirm-modal-title" class="text-lg font-bold text-slate-900 dark:text-white mb-1.5"></h2>
                <p id="confirm-modal-message" class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed"></p>
            </div>
            <div class="flex items-center gap-3 px-5 sm:px-6 pb-6">
                <button type="button" id="confirm-modal-ok" class="flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90"></button>
                <button type="button" id="confirm-modal-cancel" class="flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition-colors">Batal</button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="papyrus-swirl-bg bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 mt-auto overflow-hidden print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-slate-500 dark:text-slate-400 text-sm">
                &copy; {{ date('Y') }} <span class="papyrus-brand">{{ config('app.name') }}</span> - Sistem Manajemen Antrian
            </p>
        </div>
    </footer>
    <div class="papyrus-tape print:hidden"></div>

    <!-- Notification Sound Helper -->
    <script>
        // Lonceng notifikasi Admin di navbar (lihat markup-nya di <nav> di
        // atas, dua salinan: desktop & mobile). Poll ringan tiap 20 detik
        // ke admin.notifications-summary -- cuma count + satu timestamp,
        // gak bikin query berat kalau dibiarkan kebuka lama.
        function adminNotifBell() {
            return {
                open: false,
                waiting: 0,
                calling: 0,
                total: 0,
                ringing: false,
                lastActivityAt: null,
                pollTimer: null,
                init() {
                    this.poll();
                    // clearInterval bukan cuma buat rapi -- kalau halamannya
                    // ke-load ulang lewat navigasi SPA-ish (jarang di app
                    // ini, tapi jaga-jaga) dua interval numpuk bakal dobel
                    // fetch tiap siklus.
                    if (this.pollTimer) clearInterval(this.pollTimer);
                    this.pollTimer = setInterval(() => this.poll(), 20000);
                },
                async poll() {
                    try {
                        const res = await fetch('{{ route('admin.notifications-summary') }}', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!res.ok) return;
                        const data = await res.json();

                        this.waiting = data.waiting_count ?? 0;
                        this.calling = data.calling_count ?? 0;
                        this.total = data.total ?? 0;

                        // "Berdering" cuma kalau ada aktivitas BARU sejak
                        // poll sebelumnya (tiket baru daftar / baru masuk
                        // status "calling") -- string timestamp dari MySQL
                        // (format "Y-m-d H:i:s") sortnya sama kayak string
                        // biasa, jadi perbandingan `>` di bawah ini valid
                        // tanpa perlu di-parse ke Date dulu.
                        if (this.lastActivityAt && data.latest_activity_at && data.latest_activity_at > this.lastActivityAt) {
                            this.ringing = true;
                            setTimeout(() => { this.ringing = false; }, 1500);
                        }
                        this.lastActivityAt = data.latest_activity_at ?? this.lastActivityAt;
                    } catch (e) {
                        // Poll gagal (mis. koneksi putus sebentar) -- diemin
                        // aja, coba lagi di siklus berikutnya. Gak perlu
                        // ganggu operator/admin dengan toast error buat
                        // sesuatu yang non-kritikal kayak gini.
                    }
                },
            };
        }

        // Satu AudioContext yang dipakai berulang kali (bukan bikin baru tiap
        // panggil). Browser modern (Chrome/Safari) MEMBLOKIR AudioContext yang
        // dibuat tanpa sentuhan/klik user — kalau sebelumnya kode bikin
        // AudioContext baru di dalam handler event WebSocket (bukan dari klik
        // langsung), context itu langsung 'suspended' dan bisu TANPA ERROR.
        // Ini akar masalah "suara tidak bunyi" di pelanggan.
        window._papyrusAudioCtx = null;
        function getPapyrusAudioCtx() {
            if (!window._papyrusAudioCtx) {
                try {
                    window._papyrusAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                } catch (e) {
                    return null;
                }
            }
            if (window._papyrusAudioCtx.state === 'suspended') {
                window._papyrusAudioCtx.resume().catch(() => {});
            }
            return window._papyrusAudioCtx;
        }

        // Bunyi 1 kali "ting-ting-ting" pendek.
        function playPapyrusBeep(audioCtx, startAt) {
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.frequency.setValueAtTime(880, startAt);
            oscillator.frequency.setValueAtTime(1320, startAt + 0.1);
            oscillator.frequency.setValueAtTime(880, startAt + 0.2);
            // Gain dinaikkan dari 0.3 -> 0.85 (maksimal aman sebelum
            // berpotensi clipping) supaya lebih menonjol dibanding musik
            // yang mungkin sedang diputar dari APLIKASI LAIN di device
            // (Spotify/YouTube dll). CATATAN: browser TIDAK BISA mengecilkan
            // volume aplikasi lain -- ini cuma menaikkan volume suara kita
            // sendiri sampai batas amannya, bukan "menang" atas musiknya.
            gainNode.gain.setValueAtTime(0.85, startAt);
            gainNode.gain.exponentialRampToValueAtTime(0.01, startAt + 0.5);
            oscillator.start(startAt);
            oscillator.stop(startAt + 0.5);
        }

        // Diulang 3x dengan jeda supaya lebih kerasa/kedengeran kalau
        // dipanggil (dibanding cuma 1x bunyi pendek sebelumnya). Ini hanya
        // berlaku saat halaman sedang aktif/terlihat di layar — kalau app
        // diminimize/browser di-background, JS di halaman ini otomatis
        // dibekukan oleh Android/iOS jadi tidak akan bunyi sama sekali;
        // untuk kondisi itu, yang bunyi adalah notifikasi push dari sw.js.
        window.playNotificationSound = function() {
            // Dipakai dashboard operator: bel "ting" aktivitas dilewati kalau bel
            // panggilan ini baru saja bunyi (biar gak dobel).
            window._lastLoudBellAt = Date.now();
            try {
                const audioCtx = getPapyrusAudioCtx();
                if (!audioCtx) return;
                const now = audioCtx.currentTime;
                playPapyrusBeep(audioCtx, now);
                playPapyrusBeep(audioCtx, now + 0.7);
                playPapyrusBeep(audioCtx, now + 1.4);
            } catch (e) {
                console.log('Audio notification not supported');
            }
        };

        // "Buka kunci" AudioContext begitu pelanggan sentuh halaman PERTAMA
        // KALI dengan cara apa saja (tap layar, klik tombol apapun, dsb).
        // Setelah ini, context tetap "running" dan bisa dipakai lagi nanti
        // walau suara dipicu otomatis (lewat WebSocket/push), tanpa perlu
        // sentuhan user lagi saat itu juga.
        // Listener SENGAJA tidak dilepas setelah sentuhan pertama: browser/HP bisa
        // mengunci AudioContext lagi (layar dikunci, tab lama di-background, app
        // dipindah). Dengan listener tetap terpasang, sentuhan/klik berikutnya
        // membuka kuncinya lagi, jadi suara tidak mati selamanya sampai refresh.
        function unlockPapyrusAudioOnce() {
            getPapyrusAudioCtx();
        }
        ['click', 'touchstart', 'keydown'].forEach((ev) => document.addEventListener(ev, unlockPapyrusAudioOnce, { passive: true }));
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) getPapyrusAudioCtx(); // tab dibuka lagi: coba bangunkan context
        });

        // Bicara (text-to-speech) dengan PENGAMAN MACET. Browser kadang tidak pernah
        // memanggil onend/onerror (bug yang dikenal di Chrome: utterance di-garbage-collect
        // atau antrian suara macet), akibatnya antrian pengumuman tersangkut selamanya
        // dan semua panggilan berikutnya diam sampai halaman di-refresh. Di sini:
        //  - referensi utterance disimpan supaya tidak di-GC sebelum selesai;
        //  - kalau suara sedang dalam keadaan paused, dilanjutkan;
        //  - kalau onend/onerror tidak datang dalam batas wajar (12 dtk + panjang teks),
        //    suara dibatalkan paksa dan antrian dilepas supaya pengumuman berikutnya jalan.
        // onDone dijamin dipanggil TEPAT SEKALI.
        // Ikon SVG untuk JavaScript (pengganti emoji): diambil dari library ikon yang sudah
        // terpasang (blade-heroicons) lewat helper svg() -- jadi bentuknya sama dengan ikon
        // di Blade (x-papyrus-icon). Hanya ikon yang dipakai script yang dimuat.
        window.PAPYRUS_ICON_SVG = {!! json_encode(collect(['check-circle', 'credit-card', 'photo', 'x-mark', 'bell', 'bell-slash'])->mapWithKeys(function ($n) {
            try {
                return [$n => svg('heroicon-o-' . $n)->toHtml()];
            } catch (\Throwable $e) {
                return [$n => ''];
            }
        })->all()) !!};
        window.papyrusIconSvg = function (name, size, color) {
            const svg = window.PAPYRUS_ICON_SVG[name];
            if (!svg) return '';
            size = size || '1.1em';
            return svg.replace('<svg ', '<svg style="width:' + size + ';height:' + size + ';display:inline-block;vertical-align:-0.2em;flex-shrink:0;' + (color ? 'color:' + color + ';' : '') + '" ');
        };

        window.papyrusSpeak = function (text, onDone) {
            let finished = false;
            let timer = null;
            const done = () => {
                if (finished) return;
                finished = true;
                clearTimeout(timer);
                window._papyrusUtterance = null;
                if (onDone) onDone();
            };
            try {
                if (!('speechSynthesis' in window)) { done(); return; }
                const synth = window.speechSynthesis;
                if (synth.paused) synth.resume();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.9;
                utterance.volume = 1; // maksimal -- lihat catatan pembatasan volume di atas
                utterance.onend = utterance.onerror = done;
                window._papyrusUtterance = utterance;
                timer = setTimeout(() => {
                    try { synth.cancel(); } catch (e) {}
                    done();
                }, 12000 + String(text).length * 120);
                synth.speak(utterance);
            } catch (e) {
                console.log('Text-to-speech tidak didukung:', e);
                done();
            }
        };

        // Getar HP (dipanggil dari dashboard operator & halaman tiket pelanggan
        // saat ada tiket baru / giliran dipanggil). Sebelumnya fungsi ini
        // dipanggil tapi tidak pernah didefinisikan di manapun, jadi getar
        // tidak pernah benar-benar jalan meskipun tidak error (karena dipanggil
        // pakai `window.vibrateDevice && ...`).
        window.vibrateDevice = function(pattern) {
            try {
                if (navigator.vibrate) {
                    navigator.vibrate(pattern || [200, 100, 200]);
                }
            } catch (e) {
                console.log('Vibration not supported');
            }
        };

        // Pop-up notifikasi kecil (toast) yang otomatis hilang sendiri.
        // Dipakai untuk flash message hasil aksi (mis. "Memanggil ulang tiket
        // PP-001") supaya tidak nempel selamanya di halaman.
        window.showToast = function(message, type = 'success', duration = 4000) {
            if (!message) return;
            const container = document.getElementById('toast-container');
            if (!container) return;

            const styles = {
                success: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-300',
                error: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300',
                info: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300',
            };

            const toast = document.createElement('div');
            toast.className = `pointer-events-auto sm:max-w-sm w-full border px-4 py-3 rounded-lg shadow-lg text-sm font-medium transition-all duration-300 ease-out opacity-0 -translate-y-2 ${styles[type] || styles.success}`;
            toast.textContent = message;
            toast.style.cursor = 'pointer';
            toast.title = 'Klik untuk menutup';
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('opacity-0', '-translate-y-2');
            });

            let hideTimer;
            const remove = () => {
                clearTimeout(hideTimer);
                toast.classList.add('opacity-0', '-translate-y-2');
                setTimeout(() => toast.remove(), 300);
            };
            toast.addEventListener('click', remove);
            hideTimer = setTimeout(remove, duration);
        };

        // Modal konfirmasi custom -- pengganti confirm() bawaan browser.
        // Dipakai: window.showConfirmModal({ title, message, variant, confirmText })
        // Return-nya Promise<boolean> (true = user pencet tombol konfirmasi).
        //
        // variant menentukan warna ikon + tombol OK:
        //   'danger'  -> merah, buat aksi yang bikin sesuatu ilang/batal
        //                (Batalkan tiket/reservasi, dsb).
        //   'primary' -> gradient ungu-pink brand, buat aksi biasa yang cuma
        //                perlu dikonfirmasi (Check-in & Panggil, dsb).
        (function () {
            const backdrop = document.getElementById('confirm-modal-backdrop');
            if (!backdrop) return;

            const iconWrap = document.getElementById('confirm-modal-icon');
            const iconSvg = document.getElementById('confirm-modal-icon-svg');
            const titleEl = document.getElementById('confirm-modal-title');
            const messageEl = document.getElementById('confirm-modal-message');
            const okBtn = document.getElementById('confirm-modal-ok');
            const cancelBtn = document.getElementById('confirm-modal-cancel');

            const ICONS = {
                danger: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
                primary: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            };
            const ICON_WRAP_CLASS = {
                danger: 'w-12 h-12 rounded-full flex items-center justify-center mb-4 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                primary: 'w-12 h-12 rounded-full flex items-center justify-center mb-4 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
            };
            const OK_BTN_CLASS = {
                danger: 'flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90 bg-red-600',
                primary: 'flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition-opacity hover:opacity-90 bg-gradient-to-r from-lavender to-dustyrose',
            };

            let activeResolve = null;

            function close(result) {
                backdrop.classList.add('hidden');
                backdrop.classList.remove('flex');
                document.body.style.overflow = '';
                if (activeResolve) {
                    const resolve = activeResolve;
                    activeResolve = null;
                    resolve(result);
                }
            }

            window.showConfirmModal = function (options) {
                const opts = typeof options === 'string' ? { message: options } : (options || {});
                const variant = opts.variant === 'danger' ? 'danger' : 'primary';

                titleEl.textContent = opts.title || (variant === 'danger' ? 'Konfirmasi Pembatalan' : 'Konfirmasi');
                messageEl.textContent = opts.message || 'Yakin ingin melanjutkan?';
                iconWrap.className = ICON_WRAP_CLASS[variant];
                iconSvg.innerHTML = ICONS[variant];
                okBtn.className = OK_BTN_CLASS[variant];
                okBtn.textContent = opts.confirmText || (variant === 'danger' ? 'Ya, Batalkan' : 'Ya, Lanjutkan');
                cancelBtn.textContent = opts.cancelText || 'Batal';

                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                document.body.style.overflow = 'hidden';

                return new Promise((resolve) => {
                    activeResolve = resolve;
                });
            };

            // Helper siap-pakai buat form: onsubmit="return confirmFormSubmit(this, {...})"
            // Nyegah submit asli, munculin modal, baru submit beneran kalau
            // user pencet konfirmasi.
            //
            // PENTING: pakai form.requestSubmit() (bukan form.submit()) supaya
            // event "submit" tetap ke-fire dan ke-tangkep listener AJAX global
            // di dashboard (yang bikin aksi jalan tanpa reload halaman) --
            // form.submit() bawaan browser diam-diam SKIP event "submit" itu.
            // Ditandai lewat data-confirm-bypass biar onsubmit ini gak nanya
            // konfirmasi lagi pas requestSubmit() memicu ulang handler-nya.
            window.confirmFormSubmit = function (form, options) {
                if (form.dataset.confirmBypass === '1') {
                    delete form.dataset.confirmBypass;
                    return true;
                }
                window.showConfirmModal(options).then((ok) => {
                    if (!ok) return;
                    form.dataset.confirmBypass = '1';
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
                return false;
            };

            okBtn.addEventListener('click', () => close(true));
            cancelBtn.addEventListener('click', () => close(false));
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) close(false);
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !backdrop.classList.contains('hidden')) close(false);
            });
        })();

        // Kalau ada flash message dari server (hasil load halaman penuh /
        // fallback non-JS), tampilkan sebagai toast juga lalu buang datanya
        // supaya tidak dobel saat auto-refresh membaca ulang HTML ini.
        document.addEventListener('DOMContentLoaded', () => {
            const flashMap = { success: 'flash-success', error: 'flash-error', info: 'flash-info' };
            Object.entries(flashMap).forEach(([type, id]) => {
                const el = document.getElementById(id);
                if (el && el.textContent.trim()) {
                    window.showToast(el.textContent.trim(), type);
                }
            });
        });
    </script>

    <!-- Register PWA Service Worker (enables push notifications even when tab/app is closed) -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch((err) => {
                    console.warn('Service worker registration failed:', err);
                });
            });
        }
    </script>

    <!--
        Shared "safe" auto-refresh helper.
        Fetches the current page, then swaps innerHTML ONLY for the given
        element ids — never the whole page. Skips any id the admin is
        currently interacting with (typing in a field inside it, an open
        <select>, etc.) so in-progress form edits are never wiped out.
        Usage: window.autoRefreshIds(['stat-total-locations', 'table-body-users'], 5000);
    -->
    <script>
        window.autoRefreshIds = function (ids, intervalMs = 5000) {
            async function tick() {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    });
                    if (!response.ok) return;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    ids.forEach((id) => {
                        const oldEl = document.getElementById(id);
                        const newEl = doc.getElementById(id);
                        if (!oldEl || !newEl) return;

                        // Don't clobber an element the admin is actively using
                        // (typing in an input inside it, a focused select, etc.)
                        const active = document.activeElement;
                        if (active && oldEl.contains(active) && active !== document.body) {
                            return;
                        }

                        oldEl.innerHTML = newEl.innerHTML;
                    });
                } catch (err) {
                    console.warn('Auto-refresh failed:', err);
                }
            }
            tick();
            setInterval(tick, intervalMs);
        };
    </script>

    <script>
        // Textarea auto-grow: dipakai buat kolom "Order" supaya kolomnya
        // ngikutin tinggi isinya, gak perlu scroll internal buat isi
        // panjang/multi-baris. Cukup tambah class="autogrow-textarea".
        (function () {
            function autoGrow(el) {
                el.style.height = 'auto';
                // Floor 42px -- kalau scrollHeight kebaca kepeleset kekecilan
                // (mis. sesaat abis di-patch AJAX sebelum layout final), kotak
                // Order jangan sampai collapse jadi gepeng/nyaris gak
                // kelihatan. min-height di CSS (class min-h-[42px]) juga
                // jaga-jaga yang sama, ini lapis kedua di JS-nya.
                el.style.height = Math.max(el.scrollHeight, 42) + 'px';
            }

            function initAutoGrow(el) {
                if (el.dataset.autogrowBound) return;
                el.dataset.autogrowBound = '1';
                autoGrow(el);
                el.addEventListener('input', () => autoGrow(el));
            }

            document.querySelectorAll('textarea.autogrow-textarea').forEach(initAutoGrow);

            // Elemen baru bisa muncul lewat auto-refresh AJAX (lihat script
            // di atas), jadi pantau juga DOM buat textarea yang baru masuk.
            new MutationObserver((mutations) => {
                mutations.forEach((m) => {
                    m.addedNodes.forEach((node) => {
                        if (node.nodeType !== 1) return;
                        if (node.matches?.('textarea.autogrow-textarea')) initAutoGrow(node);
                        node.querySelectorAll?.('textarea.autogrow-textarea').forEach(initAutoGrow);
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        })();
    </script>

    <script>
        // "Dirty state" — nandain input/textarea/select yang lagi ada
        // perubahan BELUM DISIMPAN, supaya auto-refresh dashboard (jalan
        // tiap beberapa detik) gak menimpa isinya. Sebelumnya proteksinya
        // cuma ngecek "lagi difokus SEKARANG" — begitu operator pencet Tab
        // buat pindah ke tombol Simpan (fokus lepas sepersekian detik dari
        // kotak Order/Catatan), refresh berikutnya nganggep kotaknya sudah
        // "gak dipakai" dan nimpa isinya pakai data lama dari server,
        // sehingga tulisan yang belum sempat disimpan hilang.
        (function () {
            document.addEventListener('input', (e) => {
                const el = e.target;
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName)) {
                    el.dataset.dirty = '1';
                }
            });

            // Auto-save catatan Order -- gak ada lagi tombol "Simpan" manual,
            // tersimpan sendiri begitu operator berhenti ngetik sebentar
            // (debounce ~900ms). Sengaja debounce (bukan simpan tiap
            // huruf) biar gak nge-spam server dengan request tiap ketikan.
            const orderAutosaveTimers = new WeakMap();
            document.addEventListener('input', (e) => {
                const textarea = e.target;
                if (!textarea.classList || !textarea.classList.contains('order-textarea')) return;
                const form = textarea.closest('.order-autosave-form');
                if (!form) return;
                const statusEl = form.querySelector('.order-save-status');

                clearTimeout(orderAutosaveTimers.get(textarea));
                if (statusEl) {
                    statusEl.textContent = 'Belum tersimpan…';
                    statusEl.className = 'order-save-status text-xs text-amber-500 h-4';
                }

                const timer = setTimeout(async () => {
                    if (statusEl) {
                        statusEl.textContent = 'Menyimpan…';
                        statusEl.className = 'order-save-status text-xs text-slate-400 dark:text-slate-500 h-4';
                    }
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!response.ok) throw new Error('Gagal menyimpan');
                        delete textarea.dataset.dirty;
                        if (statusEl) {
                            statusEl.innerHTML = window.papyrusIconSvg('check', '1em') + ' Tersimpan';
                            statusEl.className = 'order-save-status text-xs text-green-600 dark:text-green-400 h-4';
                            setTimeout(() => {
                                statusEl.textContent = '';
                            }, 2000);
                        }
                    } catch (err) {
                        if (statusEl) {
                            statusEl.textContent = 'Gagal menyimpan, coba lagi.';
                            statusEl.className = 'order-save-status text-xs text-red-500 h-4';
                        }
                    }
                }, 900);
                orderAutosaveTimers.set(textarea, timer);
            });

            // Auto-simpan Catatan Order dulu kalau operator klik tombol AKSI
            // LAIN (mis. "Konfirmasi Pesanan"/"Lewati"/"Mulai Melayani")
            // padahal kolom Order masih ada perubahan BELUM sempat
            // ke-auto-save (dalam jeda debounce 900ms di atas). Tanpa ini,
            // klik tombol lain langsung nyubmit form-nya sendiri-sendiri
            // (beda form dari kolom Order), jadi ketikan yang belum sempat
            // disimpan HILANG diam-diam tanpa peringatan apa pun
            // -- operator gak perlu lagi INGET klik "Simpan" manual dulu,
            // tapi tetap bukan auto-save tiap ketikan (baru dikirim pas
            // beneran mau pindah aksi).
            document.addEventListener('submit', (e) => {
                const form = e.target;
                const dirtyOrderTextarea = document.querySelector('.order-textarea[data-dirty="1"]');
                if (!dirtyOrderTextarea) return; // gak ada perubahan Order yang belum disimpan

                const notesForm = dirtyOrderTextarea.closest('form');
                // Kalau yang disubmit justru form Order itu sendiri (klik
                // "Simpan" biasa), biarkan jalan normal -- gak perlu
                // di-intercept.
                if (!notesForm || notesForm === form) return;

                e.preventDefault();
                e.stopImmediatePropagation();

                fetch(notesForm.action, {
                    method: 'POST',
                    body: new FormData(notesForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                }).then((res) => {
                    if (!res.ok) throw new Error('Gagal menyimpan');
                    delete dirtyOrderTextarea.dataset.dirty;
                    form.submit(); // lanjut aksi yang tadi ditahan (Konfirmasi/Lewati/dst)
                }).catch(() => {
                    alert('Gagal menyimpan catatan Order secara otomatis. Silakan klik "Simpan" manual dulu, baru coba lagi.');
                });
            }, true);

            // Begitu form-nya disubmit (baik AJAX maupun submit biasa),
            // lepas flag dirty semua field di form itu — soalnya begitu
            // submit, isinya sudah "resmi" mau disimpan/sudah cocok sama
            // yang bakal dikirim server, jadi aman ditimpa hasil refresh
            // berikutnya.
            document.addEventListener('submit', (e) => {
                e.target.querySelectorAll?.('[data-dirty]').forEach(el => delete el.dataset.dirty);
            }, true);

            // Peringatan browser bawaan kalau operator mau PINDAH HALAMAN
            // (klik tombol AKSI LAIN yang formnya beda -- mis. "Selesaikan"
            // atau "Lewati" -- klik link tab lain, atau tutup tab) padahal
            // masih ada input dirty yang BELUM disimpan (mis. lagi ngetik
            // Catatan Order tapi belum sempat klik "Simpan"). Sengaja tidak
            // dibuat auto-save -- auto-save tiap ketikan bikin banyak
            // request server buat hal yang cukup 1x kirim, dan operator
            // tetap perlu kontrol kapan catatan itu "resmi" tersimpan.
            //
            // Ini otomatis TIDAK muncul kalau operator klik "Simpan" pada
            // form yang sama isinya (listener submit di atas sudah
            // melepas flag dirty-nya duluan sebelum browser sempat
            // unload halaman), tapi TETAP muncul kalau yang disubmit
            // adalah FORM LAIN (dirty flag punya textarea Catatan tidak
            // ikut kehapus karena beda form).
            window.addEventListener('beforeunload', (e) => {
                if (document.querySelector('[data-dirty="1"]')) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        })();
    </script>

    <script>
        // Tombol cepat "Template Baris Order" (diatur Admin -> Lokasi):
        // klik = tambahin baris itu ke textarea Order terdekat, bukan
        // menimpa isi yang sudah ada.
        document.addEventListener('click', function (e) {
            const chip = e.target.closest('.order-suggestion-chip');
            if (!chip) return;

            const container = chip.closest('div');
            const textarea = container?.previousElementSibling?.querySelector?.('.order-textarea')
                ?? container?.parentElement?.querySelector?.('.order-textarea');
            if (!textarea) return;

            const line = chip.dataset.orderLine;
            textarea.value = textarea.value.trim() ? textarea.value.replace(/\s+$/, '') + '\n' + line : line;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.focus();
        });
    </script>

    @stack('scripts')
</body>
</html>