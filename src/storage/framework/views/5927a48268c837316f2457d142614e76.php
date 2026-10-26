<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Pilih Lokasi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Pilih Lokasi']); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="<?php echo e(route('home')); ?>" class="text-slate-500 hover:text-blue-600">Beranda</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-slate-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-slate-900 font-medium">Lokasi</span>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Pilih Lokasi Layanan</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">Pilih lokasi untuk mengambil tiket antrian digital</p>
        </div>

        <!-- Search/Filter (optional enhancement) -->
        <div class="mb-6">
            <div class="flex flex-wrap gap-3">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-600 text-white cursor-pointer">
                    Semua
                </span>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:border-blue-300 cursor-pointer">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Buka Sekarang
                </span>
            </div>
        </div>

        <?php if($locations->isEmpty()): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-16 text-center">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">Belum Ada Lokasi</h3>
                <p class="text-slate-600 dark:text-slate-400">Belum ada lokasi layanan yang tersedia saat ini.</p>
            </div>
        <?php else: ?>
            <div class="flex flex-wrap justify-start gap-6">
                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        // Lokasi khusus reservasi (tanpa ambil tiket walk-in) memakai tema
                        // hijau (emerald) yang sama seperti halaman "Cek Reservasi", supaya
                        // konsisten dan mudah dibedakan dari lokasi antrian walk-in (biru).
                        $isBookingOnly = $location->offersBooking() && !$location->offersWalkInTicket();
                        $accent = $isBookingOnly ? 'emerald' : 'blue';
                    ?>
                    <div class="w-full sm:w-[calc(50%-0.75rem)] lg:w-[calc(33.333%-1rem)] max-w-md group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-xl <?php echo e($isBookingOnly ? 'hover:border-emerald-200 dark:hover:border-emerald-700' : 'hover:border-blue-200 dark:hover:border-blue-700'); ?> transition-all duration-300" id="location-card-<?php echo e($location->id); ?>">
                        <!-- Card Header -->
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span class="inline-block <?php echo e($isBookingOnly ? 'bg-emerald-600' : 'bg-blue-600'); ?> text-white text-xs font-bold px-2.5 py-1 rounded-lg mb-3">
                                        <?php echo e($location->code); ?>

                                    </span>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white <?php echo e($isBookingOnly ? 'group-hover:text-emerald-600' : 'group-hover:text-blue-600'); ?> transition-colors"><?php echo e($location->name); ?></h3>
                                </div>
                                <span id="location-open-badge-<?php echo e($location->id); ?>">
                                <?php if($location->isOpen()): ?>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-100 text-green-800">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                        Buka
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-800">
                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                                        Tutup
                                    </span>
                                <?php endif; ?>
                                </span>
                            </div>

                            <?php if($location->description): ?>
                                <p class="text-slate-600 text-sm mb-4 line-clamp-2"><?php echo e($location->description); ?></p>
                            <?php endif; ?>

                            <!-- Info Grid -->
                            <div class="flex items-center text-sm text-slate-500 dark:text-slate-400 mb-4">
                                <svg class="w-4 h-4 mr-2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo e(\Carbon\Carbon::parse($location->open_time)->format('H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($location->close_time)->format('H:i')); ?>

                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="block text-3xl font-bold text-slate-900 dark:text-white" id="location-waiting-count-<?php echo e($location->id); ?>"><?php echo e($location->waiting_count); ?></span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">Menunggu</span>
                                </div>
                                <span class="text-sm font-medium <?php echo e($location->isOpen() ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'); ?>" id="location-status-text-<?php echo e($location->id); ?>">
                                    <?php echo e($location->isOpen() ? 'Sedang buka' : 'Sedang tutup'); ?>

                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if($location->offersWalkInTicket()): ?>
                                    <a href="<?php echo e(route('tickets.create', $location)); ?>"
                                       class="flex items-center justify-center flex-1 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-3 rounded-xl font-semibold text-sm transition-colors <?php echo e(!$location->isOpen() ? 'opacity-50 pointer-events-none' : ''); ?>">
                                        Ambil Tiket
                                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <?php if($location->offersBooking()): ?>
                                    <a href="<?php echo e(route('bookings.check')); ?>"
                                       <?php if($isBookingOnly): ?>
                                           class="flex items-center justify-center flex-1 bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200 hover:shadow-lg"
                                       <?php else: ?>
                                           class="flex items-center justify-center flex-1 border-2 border-emerald-600 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 px-4 py-3 rounded-xl font-semibold text-sm transition-colors"
                                       <?php endif; ?>
                                    >
                                        Cek Reservasi
                                        <?php if($isBookingOnly): ?>
                                            <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script type="module">
        // Sebelumnya halaman ini full-reload tiap 30 detik. Selain bikin
        // halaman "kedip" tiap refresh, browser bisa saja menyajikan hasil
        // dari cache-nya sendiri untuk reload biasa (server tidak kirim
        // header Cache-Control eksplisit) — jadi angka "Menunggu" kelihatan
        // seperti macet/tidak update walau data di server sebenarnya sudah
        // berubah. Sekarang diganti fetch() dengan `cache: 'no-store'`
        // (selalu ambil versi terbaru dari server) setiap 5 detik, lalu
        // cuma menimpa angka/badge yang berubah — tanpa reload sama sekali.
        async function refreshLocations() {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('_', Date.now());
                const response = await fetch(url.toString(), { cache: 'no-store' });
                if (!response.ok) return;
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');

                doc.querySelectorAll('[id^="location-card-"]').forEach(newCard => {
                    const id = newCard.id;
                    const oldCard = document.getElementById(id);
                    if (!oldCard) return;

                    const suffix = id.replace('location-card-', '');
                    ['location-waiting-count-', 'location-open-badge-', 'location-status-text-'].forEach(prefix => {
                        const oldEl = document.getElementById(prefix + suffix);
                        const newEl = doc.getElementById(prefix + suffix);
                        if (oldEl && newEl && oldEl.innerHTML !== newEl.innerHTML) {
                            oldEl.innerHTML = newEl.innerHTML;
                        }
                    });
                });
            } catch (err) {
                console.warn('Refresh daftar lokasi gagal:', err);
            }
        }

        setInterval(refreshLocations, 5000);
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/locations/index.blade.php ENDPATH**/ ?>