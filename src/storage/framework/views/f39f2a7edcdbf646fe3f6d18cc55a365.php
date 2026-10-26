<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Paket Reservasi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Paket Reservasi']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Paket Reservasi','subtitle' => 'Isi dropdown &quot;Pilihan Paket&quot; di form reservasi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Paket Reservasi','subtitle' => 'Isi dropdown &quot;Pilihan Paket&quot; di form reservasi']); ?>
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

                <!-- Tambah paket -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Tambah Paket</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Paket baru langsung muncul di dropdown form reservasi (paling bawah). Nama otomatis ditulis HURUF BESAR, sama seperti di spreadsheet.</p>
                    <form method="POST" action="<?php echo e(route('admin.booking-packages.store')); ?>" class="flex flex-wrap gap-3 items-start">
                        <?php echo csrf_field(); ?>
                        <div class="flex-1" style="min-width: 14rem;">
                            <input type="text" name="name" value="<?php echo e(old('name')); ?>" maxlength="100" required placeholder="Contoh: PREMIUM C"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                            Tambah Paket
                        </button>
                    </form>
                </div>

                <!-- Daftar paket -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Daftar Paket (<?php echo e($packages->count()); ?>)</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Urutan di sini = urutan di dropdown. Mengganti nama juga mengubah nama paket di reservasi yang sudah ada. Menghapus atau menonaktifkan paket tidak mengubah reservasi lama.</p>
                    </div>

                    <?php if($packages->count() > 0): ?>
                        <div class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $package): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $used = $usage[$package->name] ?? 0; ?>
                                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="<?php echo e(route('admin.booking-packages.move', $package)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" <?php echo e($loop->first ? 'disabled' : ''); ?> title="Naikkan" class="px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" style="<?php echo e($loop->first ? 'opacity: .35; cursor: not-allowed;' : ''); ?>">▲</button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.booking-packages.move', $package)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" <?php echo e($loop->last ? 'disabled' : ''); ?> title="Turunkan" class="px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" style="<?php echo e($loop->last ? 'opacity: .35; cursor: not-allowed;' : ''); ?>">▼</button>
                                        </form>
                                    </div>

                                    <form method="POST" action="<?php echo e(route('admin.booking-packages.update', $package)); ?>" class="flex flex-1 items-center gap-2" style="min-width: 14rem;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <input type="text" name="name" value="<?php echo e($package->name); ?>" maxlength="100" required
                                            class="flex-1 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm <?php echo e($package->is_active ? '' : 'opacity-60'); ?>">
                                        <input type="number" name="edit_days" value="<?php echo e($package->edit_days); ?>" min="0" max="60" placeholder="hari" title="Lama edit (hari) untuk estimasi di tiket reservasi. 0 = edit hari ini, kosong = belum diatur."
                                            class="w-20 border border-slate-300 dark:border-slate-600 rounded-lg px-2 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">hari edit</span>
                                        <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">Simpan</button>
                                    </form>

                                    <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap"><?php echo e($used > 0 ? $used . ' reservasi' : 'belum dipakai'); ?></span>

                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="<?php echo e(route('admin.booking-packages.toggle', $package)); ?>" class="flex items-center gap-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="is_active" value="0">
                                            <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none px-2">
                                                <input type="checkbox" name="is_active" value="1" <?php echo e($package->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
                                                <span class="<?php echo e($package->is_active ? 'text-green-600' : 'text-slate-500'); ?>"><?php echo e($package->is_active ? 'Aktif' : 'Nonaktif'); ?></span>
                                            </label>
                                            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white transition-colors">Simpan</button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.booking-packages.destroy', $package)); ?>" onsubmit="return confirm('Hapus paket <?php echo e($package->name); ?> dari daftar?<?php echo e($used > 0 ? ' ' . $used . ' reservasi yang sudah memakai paket ini tidak berubah.' : ''); ?>')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <p class="text-slate-500 dark:text-slate-400">Belum ada paket.</p>
                            <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Tambahkan paket di atas, dropdown di form reservasi akan terisi.</p>
                        </div>
                    <?php endif; ?>
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
<?php /**PATH /var/www/html/resources/views/admin/booking-packages/index.blade.php ENDPATH**/ ?>