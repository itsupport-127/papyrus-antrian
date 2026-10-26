<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="vapid-public-key" content="<?php echo e(config('services.webpush.vapid_public_key')); ?>">

    <title><?php echo e($title ?? config('app.name')); ?> - Sistem Manajemen Antrian</title>

    <!-- PWA -->
    <link rel="manifest" href="<?php echo e(asset('manifest.json')); ?>">
    <meta name="theme-color" content="#c5a4e8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="<?php echo e(asset('images/logo-papyrus.png')); ?>">

    <?php if(file_exists(public_path('images/logo-papyrus.png'))): ?>
        <link rel="icon" href="<?php echo e(asset('images/logo-papyrus.png')); ?>">
    <?php else: ?>
        <link rel="icon" href="<?php echo e(asset('favicon.svg')); ?>" type="image/svg+xml">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|baloo-2:500,600,700,800" rel="stylesheet" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="bg-gradient-to-b from-periwinkle/60 via-slate-50 to-blush/30 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 text-slate-900 dark:text-slate-100 min-h-screen transition-colors duration-300">
    <!-- Aksen "washi tape" khas Papyrus Photo -->
    <div class="papyrus-tape"></div>

    <!-- Navigation -->
    <nav class="bg-white/90 dark:bg-slate-800 backdrop-blur border-b border-slate-200 dark:border-slate-700" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="<?php echo e(url('/')); ?>" class="flex items-center space-x-2 min-w-0">
                        <?php if(file_exists(public_path('images/logo-papyrus.png'))): ?>
                            <span class="w-9 h-9 shrink-0 rounded-full p-[2px] bg-gradient-to-br from-lavender via-skyblue to-dustyrose">
                                <img src="<?php echo e(asset('images/logo-papyrus.png')); ?>" alt="Papyrus Photo" class="w-full h-full object-contain rounded-full bg-white">
                            </span>
                        <?php else: ?>
                            <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center bg-gradient-to-br from-lavender via-skyblue to-dustyrose">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                        <?php endif; ?>
                        <span class="papyrus-brand text-lg sm:text-xl truncate"><?php echo e(config('app.name')); ?></span>
                    </a>

                    <!-- Badge peran — biar langsung kelihatan sedang login sebagai apa -->
                    <?php if(auth()->guard()->check()): ?>
                        <?php if(auth()->user()->isAdmin()): ?>
                            <span class="hidden lg:inline-flex items-center gap-1 bg-blush/60 text-dustyrose dark:bg-amber-900/30 dark:text-amber-400 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 bg-dustyrose rounded-full"></span> Admin
                            </span>
                        <?php elseif(auth()->user()->isOperator()): ?>
                            <span class="hidden lg:inline-flex items-center gap-1 bg-mint/40 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 bg-mint rounded-full"></span> CS &amp; Kasir
                            </span>
                        <?php elseif(auth()->user()->isFotografer()): ?>
                            <span class="hidden lg:inline-flex items-center gap-1 bg-skyblue/20 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400 text-xs font-semibold px-2.5 py-1 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 bg-skyblue rounded-full"></span> Fotografer
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php
                    $navLink = fn (bool $active) => $active
                        ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-2 text-sm font-semibold rounded-lg'
                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50';
                    $navLinkMobile = fn (bool $active) => $active
                        ? 'block text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-4 py-3 text-sm font-semibold rounded-lg'
                        : 'block text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-4 py-3 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50';
                ?>

                <!-- Menu desktop -->
                <div class="hidden md:flex items-center space-x-1">
                    <?php if(auth()->guard()->check()): ?>
                        <?php if(auth()->user()->isAdmin()): ?>
                            <a href="<?php echo e(route('admin.dashboard')); ?>" class="<?php echo e($navLink(request()->routeIs('admin.*'))); ?>">
                                Dashboard
                            </a>
                            <a href="<?php echo e(route('admin.locations')); ?>" class="<?php echo e($navLink(request()->routeIs('admin.locations') || request()->routeIs('admin.booking-slots'))); ?>">
                                Lokasi &amp; Reservasi
                            </a>
                            <a href="<?php echo e(route('admin.users')); ?>" class="<?php echo e($navLink(request()->routeIs('admin.users'))); ?>">
                                Pengguna
                            </a>
                            <a href="<?php echo e(route('admin.customers')); ?>" class="<?php echo e($navLink(request()->routeIs('admin.customers'))); ?>">
                                Pelanggan
                            </a>
                            <a href="<?php echo e(route('admin.reports')); ?>" class="<?php echo e($navLink(request()->routeIs('admin.reports'))); ?>">
                                Laporan
                            </a>
                        <?php elseif(auth()->user()->isOperator()): ?>
                            <a href="<?php echo e(route('operator.dashboard')); ?>" class="<?php echo e($navLink(request()->routeIs('operator.dashboard'))); ?>">
                                Dashboard
                            </a>
                            <a href="<?php echo e(route('operator.booking-slots')); ?>" class="<?php echo e($navLink(request()->routeIs('operator.booking-slots'))); ?>">
                                Jadwal Reservasi
                            </a>
                        <?php elseif(auth()->user()->isFotografer()): ?>
                            <a href="<?php echo e(route('operator.dashboard')); ?>" class="<?php echo e($navLink(request()->routeIs('operator.*'))); ?>">
                                Panel Fotografer
                            </a>
                        <?php endif; ?>

                        <span class="w-px h-6 bg-slate-200 dark:bg-slate-600 mx-1"></span>

                        <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                Keluar
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="<?php echo e(route('locations.index')); ?>" class="<?php echo e($navLink(request()->routeIs('locations.*'))); ?>">
                            Lokasi
                        </a>
                        <a href="<?php echo e(route('tickets.check')); ?>" class="<?php echo e($navLink(request()->routeIs('tickets.*'))); ?>">
                            Cek Antrian
                        </a>
                        <a href="<?php echo e(route('bookings.check')); ?>" class="<?php echo e($navLink(request()->routeIs('bookings.*'))); ?>">
                            Reservasi
                        </a>
                        <a href="<?php echo e(route('login')); ?>" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-medium ml-2 transition-opacity">
                            Masuk
                        </a>
                    <?php endif; ?>

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
            <?php if(auth()->guard()->check()): ?>
                <?php if(auth()->user()->isAdmin()): ?>
                    <span class="inline-flex items-center gap-1 bg-blush/60 text-dustyrose dark:bg-amber-900/30 dark:text-amber-400 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <span class="w-1.5 h-1.5 bg-dustyrose rounded-full"></span> Admin
                    </span>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('admin.*'))); ?>">Dashboard</a>
                    <a href="<?php echo e(route('admin.locations')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('admin.locations') || request()->routeIs('admin.booking-slots'))); ?>">Lokasi &amp; Reservasi</a>
                    <a href="<?php echo e(route('admin.users')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('admin.users'))); ?>">Pengguna</a>
                    <a href="<?php echo e(route('admin.customers')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('admin.customers'))); ?>">Pelanggan</a>
                    <a href="<?php echo e(route('admin.reports')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('admin.reports'))); ?>">Laporan</a>
                <?php elseif(auth()->user()->isOperator()): ?>
                    <span class="inline-flex items-center gap-1 bg-mint/40 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <span class="w-1.5 h-1.5 bg-mint rounded-full"></span> CS &amp; Kasir
                    </span>
                    <a href="<?php echo e(route('operator.dashboard')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('operator.dashboard'))); ?>">Dashboard</a>
                    <a href="<?php echo e(route('operator.booking-slots')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('operator.booking-slots'))); ?>">Jadwal Reservasi</a>
                <?php elseif(auth()->user()->isFotografer()): ?>
                    <span class="inline-flex items-center gap-1 bg-skyblue/20 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400 text-xs font-semibold px-2.5 py-1 rounded-full mb-2">
                        <span class="w-1.5 h-1.5 bg-skyblue rounded-full"></span> Fotografer
                    </span>
                    <a href="<?php echo e(route('operator.dashboard')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('operator.*'))); ?>">Panel Fotografer</a>
                <?php endif; ?>

                <div class="border-t border-slate-100 dark:border-slate-700 my-2"></div>

                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="w-full text-left block text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-4 py-3 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        Keluar
                    </button>
                </form>
            <?php else: ?>
                <a href="<?php echo e(route('locations.index')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('locations.*'))); ?>">Lokasi</a>
                <a href="<?php echo e(route('tickets.check')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('tickets.*'))); ?>">Cek Antrian</a>
                <a href="<?php echo e(route('bookings.check')); ?>" class="<?php echo e($navLinkMobile(request()->routeIs('bookings.*'))); ?>">Reservasi</a>
                <a href="<?php echo e(route('login')); ?>" class="block text-center bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-3 rounded-lg text-sm font-medium mt-2 transition-opacity">
                    Masuk
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Flash Messages (toast, otomatis hilang) -->
    <?php if(session('success') || session('error') || session('info')): ?>
        <div class="fixed top-20 inset-x-4 sm:inset-x-auto sm:right-4 z-50 flex flex-col gap-2 sm:max-w-sm w-auto">
            <?php if(session('success')): ?>
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                     class="bg-white dark:bg-slate-800 border-l-4 border-emerald-500 shadow-lg rounded-lg px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    <p class="text-sm text-slate-700 dark:text-slate-200 flex-1"><?php echo e(session('success')); ?></p>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
                     class="bg-white dark:bg-slate-800 border-l-4 border-red-500 shadow-lg rounded-lg px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm text-slate-700 dark:text-slate-200 flex-1"><?php echo e(session('error')); ?></p>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            <?php endif; ?>
            <?php if(session('info')): ?>
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                     class="bg-white dark:bg-slate-800 border-l-4 border-lavender shadow-lg rounded-lg px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-lavender shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm text-slate-700 dark:text-slate-200 flex-1"><?php echo e(session('info')); ?></p>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?php echo e($slot); ?>

    </main>

    <!-- Footer -->
    <footer class="papyrus-swirl-bg bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 mt-auto overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-slate-500 dark:text-slate-400 text-sm">
                &copy; <?php echo e(date('Y')); ?> <span class="papyrus-brand"><?php echo e(config('app.name')); ?></span> - Sistem Manajemen Antrian
            </p>
        </div>
    </footer>
    <div class="papyrus-tape"></div>

    <!-- Notification Sound + Vibration Helper -->
    <script>
        window.playNotificationSound = function() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                oscillator.frequency.setValueAtTime(1320, audioCtx.currentTime + 0.1);
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime + 0.2);
                gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
                oscillator.start(audioCtx.currentTime);
                oscillator.stop(audioCtx.currentTime + 0.5);
            } catch (e) {
                console.log('Audio notification not supported');
            }
        };

        // Getar HP (kalau perangkatnya support, umumnya Android/Chrome).
        // Aman dipanggil di device/browser yang nggak dukung — cuma no-op.
        window.vibrateDevice = function(pattern = [200, 100, 200]) {
            try {
                if ('vibrate' in navigator) navigator.vibrate(pattern);
            } catch (e) {
                // diamkan, nggak semua device/browser dukung
            }
        };
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

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/components/layout.blade.php ENDPATH**/ ?>