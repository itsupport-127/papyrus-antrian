<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Kelola Lokasi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kelola Lokasi']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Kelola Lokasi','subtitle' => 'Tambah, edit, atau hapus lokasi layanan dan loket']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kelola Lokasi','subtitle' => 'Tambah, edit, atau hapus lokasi layanan dan loket']); ?>
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

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8 flex flex-col lg:flex-row gap-6 items-start">
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
            <div class="flex items-center justify-between mb-8">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white text-sm inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Dashboard
                </a>
                <a href="<?php echo e(route('admin.locations.create')); ?>" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium">
                    Tambah Lokasi
                </a>
            </div>

        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Lokasi</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Kode</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Layanan</th>
                            <th class="text-center px-6 py-3 text-xs font-medium text-slate-500 uppercase">Loket</th>
                            <th class="text-center px-6 py-3 text-xs font-medium text-slate-500 uppercase">Operator</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Jam Operasional</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Reservasi</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="locations-table-body" class="divide-y divide-slate-200">
                        <?php $__empty_1 = true; $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-900"><?php echo e($location->name); ?></span>
                                    <?php if($location->description): ?>
                                        <span class="block text-sm text-slate-500"><?php echo e(Str::limit($location->description, 50)); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-sm"><?php echo e($location->code); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                        $serviceLabels = ['both' => 'Tiket & Reservasi', 'ticket' => 'Tiket saja', 'booking' => 'Reservasi saja'];
                                    ?>
                                    <span class="text-sm text-slate-600"><?php echo e($serviceLabels[$location->service_type] ?? $location->service_type); ?></span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-600"><?php echo e($location->counters_count); ?></td>
                                <td class="px-6 py-4 text-center text-slate-600"><?php echo e($location->users_count); ?></td>
                                <td class="px-6 py-4 text-slate-600 text-sm">
                                    <?php echo e(\Carbon\Carbon::parse($location->open_time)->format('H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($location->close_time)->format('H:i')); ?>

                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <?php if($location->is_active): ?>
                                            <span class="inline-flex items-center text-green-600 text-sm">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                                Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center text-slate-500 text-sm">
                                                <span class="w-2 h-2 bg-slate-400 rounded-full mr-1.5"></span>
                                                Nonaktif
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center text-xs <?php echo e($location->isOpen() ? 'text-green-600' : 'text-red-500'); ?>">
                                            <?php echo e($location->isOpen() ? 'Sedang buka sekarang' : 'Sedang tutup sekarang'); ?>

                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($location->offersBooking()): ?>
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-block w-fit text-xs font-semibold px-2 py-0.5 rounded-full <?php echo e($location->isFreeBookingMode() ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'); ?>">
                                                <?php echo e($location->isFreeBookingMode() ? 'Bebas Pilih Jam' : 'Jadwal Ditentukan'); ?>

                                            </span>
                                            <?php if(!$location->isFreeBookingMode()): ?>
                                                <?php if($location->active_booking_slots_count > 0): ?>
                                                    <span class="text-xs text-slate-500"><?php echo e($location->active_booking_slots_count); ?> slot aktif</span>
                                                <?php else: ?>
                                                    <span class="text-xs text-red-500 font-medium flex items-center gap-1"><?php if (isset($component)) { $__componentOriginalbb2de5a19350412a96a4922f84030513 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb2de5a19350412a96a4922f84030513 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-icon','data' => ['name' => 'alert-triangle','class' => 'w-3.5 h-3.5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert-triangle','class' => 'w-3.5 h-3.5 shrink-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbb2de5a19350412a96a4922f84030513)): ?>
<?php $attributes = $__attributesOriginalbb2de5a19350412a96a4922f84030513; ?>
<?php unset($__attributesOriginalbb2de5a19350412a96a4922f84030513); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbb2de5a19350412a96a4922f84030513)): ?>
<?php $component = $__componentOriginalbb2de5a19350412a96a4922f84030513; ?>
<?php unset($__componentOriginalbb2de5a19350412a96a4922f84030513); ?>
<?php endif; ?> 0 slot — reservasi akan gagal</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Tidak menerima reservasi</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <a href="<?php echo e(route('admin.locations.edit', $location)); ?>" class="text-blue-600 hover:text-blue-700 text-sm">Edit</a>
                                        <a href="<?php echo e(route('admin.counters', $location)); ?>" class="text-slate-600 hover:text-slate-700 text-sm">Loket</a>
                                        <?php if($location->offersBooking()): ?>
                                            <a href="<?php echo e(route('admin.booking-slots', $location)); ?>" class="text-slate-600 hover:text-slate-700 text-sm">Jadwal Reservasi</a>
                                        <?php endif; ?>
                                        <form method="POST" action="<?php echo e(route('admin.locations.delete', $location)); ?>" onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                    Belum ada lokasi. <a href="<?php echo e(route('admin.locations.create')); ?>" class="text-blue-600 hover:text-blue-700">Tambah lokasi baru</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        window.autoRefreshIds(['locations-table-body'], 6000);
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/locations/index.blade.php ENDPATH**/ ?>