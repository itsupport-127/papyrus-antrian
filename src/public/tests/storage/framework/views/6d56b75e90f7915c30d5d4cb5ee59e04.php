<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Kategori Layanan - ' . $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Kategori Layanan - ' . $location->name)]); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <!-- Admin Header -->
        <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Kategori Layanan</h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($location->name); ?></p>
                        </div>
                    </div>
                    <a href="<?php echo e(route('admin.locations')); ?>" class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Add Form -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Tambah Kategori</h2>
                <form method="POST" action="<?php echo e(route('admin.categories.store', $location)); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Kategori</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?php echo e(old('name')); ?>"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm"
                                placeholder="Contoh: Informasi Umum"
                                required
                            >
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
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi (Opsional)</label>
                            <input
                                type="text"
                                id="description"
                                name="description"
                                value="<?php echo e(old('description')); ?>"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm"
                                placeholder="Deskripsi singkat"
                            >
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                        Tambah Kategori
                    </button>
                </form>
            </div>

            <!-- Categories List -->
            <div id="categories-list-card" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Daftar Kategori (<?php echo e($categories->count()); ?>)</h2>
                </div>

                <?php if($categories->count() > 0): ?>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between p-6 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center <?php echo e($category->is_active ? 'bg-green-100 dark:bg-green-900/30' : 'bg-slate-100 dark:bg-slate-700'); ?>">
                                        <svg class="w-5 h-5 <?php echo e($category->is_active ? 'text-green-600' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white"><?php echo e($category->name); ?></p>
                                        <?php if($category->description): ?>
                                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($category->description); ?></p>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full mt-1 <?php echo e($category->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'); ?>">
                                            <?php echo e($category->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <form method="POST" action="<?php echo e(route('admin.categories.toggle', $category)); ?>" class="flex items-center gap-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="is_active" value="0">
                                        <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none px-2">
                                            <input type="checkbox" name="is_active" value="1" <?php echo e($category->is_active ? 'checked' : ''); ?> class="rounded border-slate-300">
                                            <span class="<?php echo e($category->is_active ? 'text-green-600' : 'text-slate-500'); ?>">
                                                <?php echo e($category->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                            </span>
                                        </label>
                                        <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white transition-colors">
                                            Simpan
                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.categories.delete', $category)); ?>" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <p class="text-slate-500 dark:text-slate-400">Belum ada kategori layanan.</p>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Tambahkan kategori di atas untuk memulai.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        window.autoRefreshIds(['categories-list-card'], 6000);
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
<?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/admin/service-categories/index.blade.php ENDPATH**/ ?>