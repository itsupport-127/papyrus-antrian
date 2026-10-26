<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Loket - ' . $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Loket - ' . $location->name)]); ?>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="<?php echo e(route('admin.locations')); ?>" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Lokasi
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Loket <?php echo e($location->name); ?></h1>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Add Counter Form -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Tambah Loket Baru</h2>
                <form method="POST" action="<?php echo e(route('admin.counters.store', $location)); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Loket</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Loket 1">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tahap Panggilan</label>
                        <p class="text-xs text-slate-500 mb-2">Pilih satu atau lebih. Contoh: loket "CS & Kasir" menangani Registrasi sekaligus Pembayaran &amp; Cetak.</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="stages[]" value="registration" class="rounded border-slate-300">
                                1. Registrasi
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="stages[]" value="photo_session" class="rounded border-slate-300">
                                2. Sesi Foto
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="stages[]" value="payment" class="rounded border-slate-300">
                                3. Pembayaran & Cetak
                            </label>
                        </div>
                        <?php $__errorArgs = ['stages'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jenis Tiket yang Dilayani</label>
                        <select name="serves" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="both">Semua (walk-in & reservasi)</option>
                            <option value="walkin">Cuma Walk-in</option>
                            <option value="booking">Cuma Reservasi</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Kalau lagi ramai, bisa dikhususkan biar pelanggan yang sudah reservasi nggak ketiban antrian walk-in (atau sebaliknya).</p>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-2 rounded-lg font-medium">
                        Tambah Loket
                    </button>
                </form>
            </div>

            <!-- Counters List -->
            <div id="counters-list-card" class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Daftar Loket (<?php echo e($counters->count()); ?>)</h2>

                <?php if($counters->isEmpty()): ?>
                    <p class="text-slate-500 text-center py-8">Belum ada loket</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $counters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $counter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="py-3 <?php echo e(!$loop->last ? 'border-b border-slate-100' : ''); ?>">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-slate-900"><?php echo e($counter->name); ?></span>
                                        <span class="block text-xs text-blue-600 font-semibold"><?php echo e($counter->stageLabel()); ?></span>
                                        <?php if($counter->serves !== 'both'): ?>
                                            <span class="inline-block text-[11px] font-semibold px-1.5 py-0.5 rounded <?php echo e($counter->serves === 'booking' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700'); ?>"><?php echo e($counter->servesLabel()); ?></span>
                                        <?php endif; ?>
                                        <?php if($counter->currentOperator): ?>
                                            <span class="block text-xs text-slate-500">Operator: <?php echo e($counter->currentOperator->name); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <form method="POST" action="<?php echo e(route('admin.counters.toggle', $counter)); ?>" class="flex items-center gap-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="is_active" value="0">
                                            <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                                                <input type="checkbox" name="is_active" value="1" <?php echo e($counter->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
                                                <span class="<?php echo e($counter->is_active ? 'text-green-600' : 'text-slate-500'); ?>">
                                                    <?php echo e($counter->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                                </span>
                                            </label>
                                            <button type="submit" class="text-xs px-3 py-1.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg font-medium">Simpan</button>
                                        </form>
                                        <button type="button" onclick="document.getElementById('edit-counter-<?php echo e($counter->id); ?>').classList.toggle('hidden')" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Edit</button>
                                        <form method="POST" action="<?php echo e(route('admin.counters.delete', $counter)); ?>" onsubmit="return confirm('Yakin ingin menghapus loket ini?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Hapus</button>
                                        </form>
                                    </div>
                                </div>

                                <div id="edit-counter-<?php echo e($counter->id); ?>" class="hidden mt-3 bg-slate-50 rounded-lg p-4">
                                    <form method="POST" action="<?php echo e(route('admin.counters.update', $counter)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <div class="mb-3">
                                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Loket</label>
                                            <input type="text" name="name" value="<?php echo e($counter->name); ?>" required
                                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <p class="text-[11px] text-slate-400 mt-1">Nama ini yang langsung muncul ke pelanggan pas dipanggil (mis. "Silakan Bersiap-siap di Studio Atas").</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="block text-xs font-medium text-slate-700 mb-1">Tahap Panggilan</label>
                                            <div class="space-y-1.5">
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="stages[]" value="registration" class="rounded border-slate-300" <?php echo e(in_array('registration', $counter->stages ?? []) ? 'checked' : ''); ?>>
                                                    1. Registrasi
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="stages[]" value="photo_session" class="rounded border-slate-300" <?php echo e(in_array('photo_session', $counter->stages ?? []) ? 'checked' : ''); ?>>
                                                    2. Sesi Foto
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="stages[]" value="payment" class="rounded border-slate-300" <?php echo e(in_array('payment', $counter->stages ?? []) ? 'checked' : ''); ?>>
                                                    3. Pembayaran & Cetak
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="block text-xs font-medium text-slate-700 mb-1">Jenis Tiket yang Dilayani</label>
                                            <select name="serves" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="both" <?php echo e($counter->serves === 'both' ? 'selected' : ''); ?>>Semua (walk-in & reservasi)</option>
                                                <option value="walkin" <?php echo e($counter->serves === 'walkin' ? 'selected' : ''); ?>>Cuma Walk-in</option>
                                                <option value="booking" <?php echo e($counter->serves === 'booking' ? 'selected' : ''); ?>>Cuma Reservasi</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="text-xs px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg font-medium">Simpan Perubahan</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        window.autoRefreshIds(['counters-list-card'], 5000);
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
<?php /**PATH /var/www/html/resources/views/admin/counters/index.blade.php ENDPATH**/ ?>