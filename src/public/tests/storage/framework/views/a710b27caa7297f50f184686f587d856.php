<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Beranda']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Beranda']); ?>
    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-white dark:bg-slate-900">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white dark:bg-slate-900 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="sm:text-center lg:text-left">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mb-4">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                            </svg>
                            Antrian Order Foto Digital
                        </span>
                        <h1 class="font-display text-4xl tracking-tight font-extrabold text-slate-900 dark:text-white sm:text-5xl md:text-6xl">
                            <span class="block">Order Foto Tanpa</span>
                            <span class="block papyrus-brand">Antre Berdesakan</span>
                        </h1>
                        <p class="mt-3 text-base text-slate-500 dark:text-slate-400 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                            Scan QR di kasir, isi data singkat, dapatkan nomor tiket + QR pelacakan. Pantau status order — dari registrasi, sesi foto, hingga pembayaran & cetak — langsung dari HP Anda.
                        </p>
                        <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <div class="rounded-xl shadow">
                                <a href="<?php echo e(route('locations.index')); ?>" class="w-full flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-xl text-white bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 transition-opacity md:py-4 md:text-lg md:px-10">
                                    Ambil Tiket Sekarang
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="<?php echo e(route('tickets.check')); ?>" class="w-full flex items-center justify-center px-8 py-4 border border-slate-300 dark:border-slate-600 text-base font-medium rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 md:py-4 md:text-lg md:px-10">
                                    Cek Antrian Saya
                                </a>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <div class="papyrus-swirl-bg h-56 w-full bg-gradient-to-br from-periwinkle via-blush/40 to-cream dark:from-slate-800 dark:via-slate-800 dark:to-slate-800 sm:h-72 md:h-96 lg:w-full lg:h-full flex items-center justify-center overflow-hidden">
                <!-- Motif kartu foto warna-warni, meniru strip foto di logo Papyrus Photo -->
                <div class="flex items-end gap-2 sm:gap-3">
                    <div class="w-11 h-32 sm:w-14 sm:h-40 md:w-16 md:h-48 rounded-md bg-dustyrose/70 shadow-lg -rotate-6"></div>
                    <div class="w-11 h-36 sm:w-14 sm:h-44 md:w-16 md:h-52 rounded-md bg-lavender/70 shadow-lg rotate-3"></div>
                    <div class="w-11 h-40 sm:w-14 sm:h-52 md:w-16 md:h-60 rounded-md bg-skyblue/70 shadow-lg -rotate-3"></div>
                    <div class="w-11 h-36 sm:w-14 sm:h-44 md:w-16 md:h-52 rounded-md bg-mint/70 shadow-lg rotate-6"></div>
                    <div class="w-11 h-32 sm:w-14 sm:h-40 md:w-16 md:h-48 rounded-md bg-blush/70 shadow-lg -rotate-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-gradient-to-r from-lavender via-[#a878cf] to-dustyrose">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:py-16 sm:px-6 lg:px-8">
            <div id="home-stats" class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo e($stats['locations'] ?? 3); ?>+</p>
                    <p class="mt-1 text-white/80">Lokasi Layanan</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo e($stats['counters'] ?? 9); ?>+</p>
                    <p class="mt-1 text-white/80">Loket Aktif</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo e($stats['today_tickets'] ?? 0); ?></p>
                    <p class="mt-1 text-white/80">Tiket Hari Ini</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-extrabold text-white"><?php echo e($stats['today_called'] ?? 0); ?></p>
                    <p class="mt-1 text-white/80">Sudah Dipanggil Hari Ini</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-blue-600 dark:text-blue-400 font-semibold tracking-wide uppercase">Fitur Unggulan</h2>
                <p class="font-display mt-2 text-3xl font-extrabold text-slate-900 dark:text-white sm:text-4xl">
                    Cara Kerja <?php echo e(config('app.name')); ?>

                </p>
                <p class="mt-4 max-w-2xl text-xl text-slate-500 dark:text-slate-400 mx-auto">
                    Tiga langkah mudah untuk pengalaman antri tanpa stres
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <!-- Step 1 -->
                <div class="relative bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow">
                    <div class="absolute -top-4 left-8">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold">1</span>
                    </div>
                    <div class="mt-4">
                        <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Ambil Tiket Digital</h3>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Pilih lokasi layanan dan ambil tiket antrian secara online. Tidak perlu datang lebih awal.</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="relative bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow">
                    <div class="absolute -top-4 left-8">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-600 text-white text-sm font-bold">2</span>
                    </div>
                    <div class="mt-4">
                        <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Pantau Real-time</h3>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Lihat posisi antrian Anda secara langsung dari smartphone.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow">
                    <div class="absolute -top-4 left-8">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-600 text-white text-sm font-bold">3</span>
                    </div>
                    <div class="mt-4">
                        <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Datang Tepat Waktu</h3>
                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Terima notifikasi saat giliran hampir tiba. Datang tepat waktu tanpa menunggu lama.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutorial: Cara Ambil Tiket -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Panduan</h2>
                <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white sm:text-4xl">
                    Cara Ambil Tiket
                </p>
                <p class="mt-4 max-w-2xl text-xl text-slate-500 dark:text-slate-400 mx-auto">
                    4 langkah mudah, bisa dilakukan dari mana saja sebelum datang ke lokasi
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Langkah 1 -->
                <div class="relative bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-600 text-white text-sm font-bold mb-4">1</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Pilih Lokasi</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Klik tombol <span class="font-semibold text-slate-900 dark:text-white">"Ambil Tiket Sekarang"</span>, lalu pilih lokasi studio yang mau Anda kunjungi.
                    </p>
                </div>

                <!-- Langkah 2 -->
                <div class="relative bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-600 text-white text-sm font-bold mb-4">2</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Isi Data Singkat</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Masukkan <span class="font-semibold text-slate-900 dark:text-white">Nama, No. Telepon, dan Email</span> Anda. Cukup sekali isi, tidak perlu antre di tempat untuk daftar.
                    </p>
                </div>

                <!-- Langkah 3 -->
                <div class="relative bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-600 text-white text-sm font-bold mb-4">3</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Dapat Nomor Tiket</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Anda akan langsung mendapat <span class="font-semibold text-slate-900 dark:text-white">nomor tiket & link pelacakan</span>. Simpan link-nya, bisa dibuka lagi kapan saja.
                    </p>
                </div>

                <!-- Langkah 4 -->
                <div class="relative bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-600 text-white text-sm font-bold mb-4">4</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Pantau & Datang</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Pantau posisi antrian dari HP lewat halaman tiket atau tombol <span class="font-semibold text-slate-900 dark:text-white">"Cek Antrian Saya"</span>. Datang saat giliran Anda hampir tiba.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Locations Preview -->
    <?php if(isset($locations) && $locations->count() > 0): ?>
    <div class="py-16 bg-slate-50 dark:bg-slate-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Lokasi Tersedia</h2>
                <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white sm:text-4xl">
                    Pilih Layanan Anda
                </p>
            </div>

            <div id="home-locations-grid" class="flex flex-wrap justify-center gap-6">
                <?php $__currentLoopData = $locations->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    // Sama seperti di halaman "Pilih Lokasi": lokasi khusus reservasi
                    // (tanpa ambil tiket walk-in) pakai tema hijau (emerald) yang sama
                    // dengan halaman "Cek Reservasi", biar konsisten.
                    $isBookingOnly = $location->offersBooking() && !$location->offersWalkInTicket();
                ?>
                <div class="w-full sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)] max-w-md bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-block <?php echo e($isBookingOnly ? 'bg-emerald-600' : 'bg-blue-600'); ?> text-white text-xs font-bold px-2.5 py-1 rounded-lg mb-2">
                                    <?php echo e($location->code); ?>

                                </span>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo e($location->name); ?></h3>
                            </div>
                            <?php if($location->isOpen()): ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                    Buka
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                                    Tutup
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if($location->description): ?>
                            <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 line-clamp-2"><?php echo e($location->description); ?></p>
                        <?php endif; ?>

                        <div class="flex items-center text-sm text-slate-500 dark:text-slate-400 mb-4 space-x-4">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo e(\Carbon\Carbon::parse($location->open_time)->format('H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($location->close_time)->format('H:i')); ?>

                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?php echo e($location->waiting_count); ?> menunggu
                            </span>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700">
                        <?php if($location->offersWalkInTicket()): ?>
                            <a href="<?php echo e(route('tickets.create', $location)); ?>" class="flex items-center justify-center w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-3 rounded-xl font-semibold transition-colors <?php echo e(!$location->isOpen() ? 'opacity-50 pointer-events-none' : ''); ?>">
                                Ambil Tiket
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('bookings.create', $location)); ?>" class="flex items-center justify-center w-full bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white py-3 rounded-xl font-semibold transition-all duration-200 hover:shadow-lg">
                                Reservasi
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php if($locations->count() > 3): ?>
            <div class="text-center mt-8">
                <a href="<?php echo e(route('locations.index')); ?>" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold">
                    Lihat Semua Lokasi
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-lavender via-[#a878cf] to-dustyrose">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="font-display text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Siap untuk menghemat waktu?</span>
                <span class="block text-white/80">Mulai gunakan <?php echo e(config('app.name')); ?> sekarang.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-xl shadow">
                    <a href="<?php echo e(route('locations.index')); ?>" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-xl text-lavender bg-white hover:bg-blush/40">
                        Mulai Sekarang
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        // Angka statistik & badge buka/tutup + jumlah menunggu ikut update otomatis,
        // tanpa reload halaman.
        window.autoRefreshIds(['home-stats', 'home-locations-grid'], 7000);
    </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $attributes = $__attributesOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__attributesOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $component = $__componentOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__componentOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?><?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/home.blade.php ENDPATH**/ ?>