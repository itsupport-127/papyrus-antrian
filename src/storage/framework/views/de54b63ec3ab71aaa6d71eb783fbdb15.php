<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Dashboard Admin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Dashboard Admin']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Dashboard Admin','subtitle' => 'Kelola sistem antrian '.e(config('app.name')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Dashboard Admin','subtitle' => 'Kelola sistem antrian '.e(config('app.name')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal489711a049975b0fbcd3875ea3652a04)): ?>
<?php $attributes = $__attributesOriginal489711a049975b0fbcd3875ea3652a04; ?>
<?php unset($__attributesOriginal489711a049975b0fbcd3875ea3652a04); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal489711a049975b0fbcd3875ea3652a04)): ?>
<?php $component = $__componentOriginal489711a049975b0fbcd3875ea3652a04; ?>
<?php unset($__componentOriginal489711a049975b0fbcd3875ea3652a04); ?>
<?php endif; ?>

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-6 flex flex-col lg:flex-row gap-6 items-start">
            <?php if (isset($component)) { $__componentOriginal6fc2d165f80d597f34aa0f8014c366d2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2)): ?>
<?php $attributes = $__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2; ?>
<?php unset($__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6fc2d165f80d597f34aa0f8014c366d2)): ?>
<?php $component = $__componentOriginal6fc2d165f80d597f34aa0f8014c366d2; ?>
<?php unset($__componentOriginal6fc2d165f80d597f34aa0f8014c366d2); ?>
<?php endif; ?>
            <div class="flex-1 min-w-0 w-full">
            <!-- Stats Grid -->
            <div id="admin-stats" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Total Lokasi</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($stats['total_locations']); ?></p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-0.5"><?php echo e($stats['active_locations']); ?> aktif</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Total Operator</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($stats['total_operators']); ?></p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">pengguna</p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Tiket Hari Ini</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($stats['today_tickets']); ?></p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-0.5"><?php echo e($stats['today_completed']); ?> selesai</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Rata-rata Waktu</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($stats['avg_service_minutes'] !== null ? '~' . $stats['avg_service_minutes'] : '-'); ?></p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"><?php echo e($stats['avg_service_minutes'] !== null ? 'menit/layanan (hari ini)' : 'belum ada tiket selesai hari ini'); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            

            <!-- Locations Overview -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Ringkasan Lokasi</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Semua lokasi layanan</p>
                    </div>
                    <a href="<?php echo e(route('admin.locations.create')); ?>" class="inline-flex items-center bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Lokasi
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Lokasi</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Tiket</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Menunggu</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Status</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="admin-locations-table-body" class="divide-y divide-slate-100 dark:divide-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <span class="text-blue-600 dark:text-blue-400 font-bold text-xs"><?php echo e(substr($location->code, 0, 2)); ?></span>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-semibold text-slate-900 dark:text-white text-sm truncate block"><?php echo e($location->name); ?></span>
                                                <span class="text-xs text-slate-500"><?php echo e($location->code); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-slate-900 dark:text-white font-semibold text-sm"><?php echo e($location->today_tickets_count); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if($location->waiting_count > 0): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                                <?php echo e($location->waiting_count); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-slate-500 text-xs">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if($location->is_active): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                                Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-1"></span>
                                                Nonaktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="<?php echo e(route('admin.counters', $location)); ?>" class="text-blue-600 hover:text-blue-700 text-xs font-medium">
                                                Loket
                                            </a>
                                            <a href="<?php echo e(route('admin.locations.edit', $location)); ?>" class="text-slate-600 dark:text-slate-400 hover:text-slate-700 text-xs font-medium">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                                        Belum ada lokasi. <a href="<?php echo e(route('admin.locations.create')); ?>" class="text-blue-600 hover:underline">Tambah lokasi baru</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        window.autoRefreshIds(['admin-stats', 'admin-locations-table-body'], 7000);
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
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>