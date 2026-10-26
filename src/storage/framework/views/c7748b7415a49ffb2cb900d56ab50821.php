<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Profil Saya']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Profil Saya']); ?>
    <?php
        $roleLabel = ['admin' => 'Admin', 'operator' => 'CS & Kasir', 'fotografer' => 'Fotografer', 'viewer' => 'Viewer'][$user->role] ?? ucfirst((string) $user->role);
        $input = 'w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-400 focus:border-violet-400';
        $label = 'block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1';
        $btn = 'px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors';
    ?>
    <div class="min-h-screen bg-gradient-to-b from-periwinkle/60 via-slate-50 to-mint/10 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 space-y-6">

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-br from-purple-400 to-pink-400 text-white p-6 flex items-center gap-4">
                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $user,'size' => 72]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => 72]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
                    <div class="min-w-0">
                        <h1 class="font-display text-xl font-bold truncate"><?php echo e($user->name); ?></h1>
                        <p class="text-white/85 text-sm"><?php echo e($roleLabel); ?></p>
                        <p class="text-white/70 text-xs truncate"><?php echo e($user->email); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Foto Profil</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Bebas pakai foto apa saja (JPG, PNG, atau WebP, maks. 5 MB). Foto otomatis dipotong persegi dan dikompres jadi puluhan KB, jadi tidak membebani server.</p>
                <form method="POST" action="<?php echo e(route('profile.photo')); ?>" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-3" id="photo-form">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="photo" id="photo-input" accept="image/jpeg,image/png,image/webp" required class="text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 dark:file:bg-slate-700 dark:file:text-slate-200">
                    <button type="submit" class="<?php echo e($btn); ?> bg-violet-600 hover:bg-violet-700 text-white">Simpan Foto</button>
                </form>
                <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-sm text-red-600 mt-2"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p id="photo-size-error" class="text-sm text-red-600 mt-2 hidden">Ukuran foto maksimal 5 MB.</p>
                <?php if($user->avatar_path): ?>
                    <form method="POST" action="<?php echo e(route('profile.photo.remove')); ?>" class="mt-3" onsubmit="return confirm('Hapus foto profil?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="text-sm text-red-600 hover:underline">Hapus foto</button>
                    </form>
                <?php endif; ?>
            </div>

            
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Data Akun</h2>
                <form method="POST" action="<?php echo e(route('profile.update')); ?>" class="mt-4 space-y-4">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div>
                        <label for="name" class="<?php echo e($label); ?>">Nama</label>
                        <input type="text" id="name" name="name" value="<?php echo e(old('name', $user->name)); ?>" maxlength="100" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-sm text-red-600 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="<?php echo e($label); ?>">Email (untuk login)</label>
                        <input type="text" value="<?php echo e($user->email); ?>" disabled class="<?php echo e($input); ?> opacity-70 cursor-not-allowed">
                        <p class="text-xs text-slate-400 mt-1">Email hanya bisa diubah oleh Admin.</p>
                    </div>
                    <button type="submit" class="<?php echo e($btn); ?> bg-slate-900 hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 text-white">Simpan Nama</button>
                </form>
            </div>

            
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Ubah Password</h2>
                <form method="POST" action="<?php echo e(route('profile.password')); ?>" class="mt-4 space-y-4">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div>
                        <label for="current_password" class="<?php echo e($label); ?>">Password saat ini</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required class="<?php echo e($input); ?>">
                        <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-sm text-red-600 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="password" class="<?php echo e($label); ?>">Password baru</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required class="<?php echo e($input); ?>">
                        <p class="text-xs text-slate-400 mt-1">Minimal 8 karakter.</p>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-sm text-red-600 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="password_confirmation" class="<?php echo e($label); ?>">Ulangi password baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required class="<?php echo e($input); ?>">
                    </div>
                    <button type="submit" class="<?php echo e($btn); ?> bg-slate-900 hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 text-white">Ubah Password</button>
                </form>
            </div>

            <form method="POST" action="<?php echo e(route('logout')); ?>" class="text-center">
                <?php echo csrf_field(); ?>
                <button type="submit" class="text-sm text-slate-500 hover:text-red-600 dark:text-slate-400">Keluar dari akun</button>
            </form>
        </div>
    </div>
    <script>
        // Cek ukuran di browser dulu (maks. 5 MB) supaya tidak menunggu unggahan besar yang pasti ditolak.
        document.getElementById('photo-input')?.addEventListener('change', function () {
            var tooBig = this.files[0] && this.files[0].size > 5 * 1024 * 1024;
            document.getElementById('photo-size-error').classList.toggle('hidden', !tooBig);
            if (tooBig) this.value = '';
        });
    </script>
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
<?php /**PATH /var/www/html/resources/views/profile/show.blade.php ENDPATH**/ ?>