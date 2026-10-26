<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Laporan Per Operator']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Laporan Per Operator']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Laporan & Analitik','subtitle' => 'Kinerja per operator']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Laporan & Analitik','subtitle' => 'Kinerja per operator']); ?>
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

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
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
                                <?php $__empty_1 = true; $__currentLoopData = $staff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php $u = $row['user']; ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <a href="<?php echo e(route('admin.reports.staff', $u)); ?>" class="font-medium text-slate-900 dark:text-white hover:underline"><?php echo e($u->name); ?></a>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e($u->role === 'operator' ? 'CS & Kasir' : ucfirst($u->role)); ?></td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e($u->location?->name ?? '-'); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if($row['total'] > 0): ?>
                                                <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($row['total']); ?></span>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">
                                                    <?php $__currentLoopData = $row['stages']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($label); ?> <?php echo e($count); ?><?php if(!$loop->last): ?> &middot; <?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400">Belum ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300 whitespace-nowrap"><?php echo e($row['last_at'] ? $row['last_at']->format('d/m/Y H:i') : '-'); ?></td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <a href="<?php echo e(route('admin.reports.staff', $u)); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat Riwayat</a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">Belum ada operator.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
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
<?php /**PATH /var/www/html/resources/views/admin/reports/staff-index.blade.php ENDPATH**/ ?>