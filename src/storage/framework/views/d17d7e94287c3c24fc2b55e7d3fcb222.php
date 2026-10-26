<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Pengingat']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Pengingat']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Pengingat','subtitle' => 'Atur pengingat suara &amp; pesan di dashboard operator']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Pengingat','subtitle' => 'Atur pengingat suara &amp; pesan di dashboard operator']); ?>
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
            <div class="flex-1 min-w-0 w-full max-w-4xl mx-auto">

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-6">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Cara kerjanya</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-300">Kalau sebuah tiket menggantung di salah satu kondisi di bawah, dashboard operator yang bersangkutan membunyikan <span class="font-semibold">nada pengingat</span> dan menampilkan <span class="font-semibold">pesan di pojok kanan atas</span> (plus notifikasi browser kalau sudah diizinkan). Pengingat mulai setelah <span class="font-semibold">Mulai setelah</span> menit, lalu diulang tiap <span class="font-semibold">Ulangi setiap</span> menit, dan berhenti sendiri begitu tiketnya diselesaikan. Hanya 3 hal ini yang diingatkan, yang lain tidak.</p>
                </div>

                <?php if($errors->any()): ?>
                    <div class="mb-6 p-4 rounded-xl border border-red-300 bg-red-50 text-red-700 text-sm">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <p><?php echo e($error); ?></p>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('admin.reminders.update')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="space-y-4">
                        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $s = $settings[$type]; ?>
                            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div style="min-width: 14rem; flex: 1;">
                                        <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo e($meta['label']); ?></h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Untuk: <span class="font-semibold"><?php echo e($meta['who']); ?></span></p>
                                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-2"><?php echo e($meta['desc']); ?></p>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                                        <input type="hidden" name="settings[<?php echo e($type); ?>][enabled]" value="0">
                                        <input type="checkbox" name="settings[<?php echo e($type); ?>][enabled]" value="1" <?php echo e(old("settings.$type.enabled", $s['enabled']) ? 'checked' : ''); ?> class="rounded border-slate-300">
                                        <span class="text-slate-700 dark:text-slate-200">Aktif</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mulai setelah (menit)</label>
                                        <input type="number" name="settings[<?php echo e($type); ?>][start_after_minutes]" value="<?php echo e(old("settings.$type.start_after_minutes", $s['start_after_minutes'])); ?>" min="0" max="240" required
                                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        <p class="text-xs text-slate-400 mt-1">0 = langsung begitu tiket masuk kondisi ini.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ulangi setiap (menit)</label>
                                        <input type="number" name="settings[<?php echo e($type); ?>][interval_minutes]" value="<?php echo e(old("settings.$type.interval_minutes", $s['interval_minutes'])); ?>" min="1" max="240" required
                                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        <p class="text-xs text-slate-400 mt-1">Jeda antar pengingat selama tiket belum diselesaikan.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan Pengaturan</button>
                    </div>
                </form>

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
<?php /**PATH /var/www/html/resources/views/admin/reminders/index.blade.php ENDPATH**/ ?>