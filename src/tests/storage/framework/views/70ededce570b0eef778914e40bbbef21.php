<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Order - ' . $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Order - ' . $location->name)]); ?>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="papyrus-swirl-bg bg-gradient-to-br from-lavender to-dustyrose text-white p-6 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold"><?php echo e($location->name); ?></h1>
                    <p class="text-white/85 mt-1">Isi data untuk mengambil antrian order foto</p>
                </div>

                <!-- Queue Info -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-5">
                        <div class="text-center">
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Antrian saat ini</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo e($location->waitingTickets()->count()); ?></p>
                            <p class="text-slate-500 dark:text-slate-400 text-xs">orang menunggu</p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="p-6">
                    <form method="POST" action="<?php echo e(route('tickets.store', $location)); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="space-y-5">
                            <div>
                                <label for="customer_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Nama
                                </label>
                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    value="<?php echo e(old('customer_name')); ?>"
                                    required
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="Masukkan nama Anda"
                                >
                                <?php $__errorArgs = ['customer_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-red-600 text-sm mt-2"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label for="customer_phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    No. Telepon
                                </label>
                                <input
                                    type="tel"
                                    id="customer_phone"
                                    name="customer_phone"
                                    value="<?php echo e(old('customer_phone')); ?>"
                                    required
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="08xxxxxxxxxx"
                                >
                                <?php $__errorArgs = ['customer_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-red-600 text-sm mt-2"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label for="customer_email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    value="<?php echo e(old('customer_email')); ?>"
                                    required
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="nama@email.com"
                                >
                                <?php $__errorArgs = ['customer_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-red-600 text-sm mt-2"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <?php if($serviceCategories->isNotEmpty()): ?>
                            <div>
                                <label for="service_category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Layanan
                                </label>
                                <select
                                    id="service_category_id"
                                    name="service_category_id"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                    <option value="">Pilih layanan (opsional)</option>
                                    <?php $__currentLoopData = $serviceCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($category->id); ?>" <?php echo e(old('service_category_id') == $category->id ? 'selected' : ''); ?>>
                                            <?php echo e($category->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['service_category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-red-600 text-sm mt-2"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        <?php endif; ?>

                        <button
                            type="submit"
                            class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-blue-200 transition-all hover:shadow-xl"
                        >
                            Order Sekarang
                        </button>
                    </form>

                    <p class="text-slate-500 dark:text-slate-400 text-sm text-center mt-5">
                        Setelah order, Anda akan mendapatkan nomor tiket dan QR untuk memantau status pesanan.
                    </p>
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
<?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/tickets/create.blade.php ENDPATH**/ ?>