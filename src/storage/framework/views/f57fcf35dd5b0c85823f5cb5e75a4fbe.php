<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Tambah Reservasi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tambah Reservasi']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Tambah Reservasi</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Buat reservasi manual untuk pelanggan.</p>
                    </div>

                    <form method="POST" action="<?php echo e(route('operator.bookings.store')); ?>" class="p-6 space-y-4">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan</label>
                            <input type="text" name="customer_name" value="<?php echo e(old('customer_name')); ?>" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                                <input type="text" name="customer_phone" value="<?php echo e(old('customer_phone')); ?>" required
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email (opsional)</label>
                                <input type="email" name="customer_email" value="<?php echo e(old('customer_email')); ?>"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                <input type="date" name="booking_date" value="<?php echo e(old('booking_date', now()->toDateString())); ?>" min="<?php echo e(now()->toDateString()); ?>" required
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam (WIB)</label>
                                <?php
                                    $opHour = (int) $location->open_time->format('H');
                                    $clHour = (int) $location->close_time->format('H');
                                    [$opOldHour, $opOldMinute] = old('booking_time') ? explode(':', old('booking_time')) : [null, null];
                                ?>
                                <input type="hidden" name="booking_time" id="op_booking_time" value="<?php echo e(old('booking_time')); ?>" required>
                                <div class="flex items-center gap-1.5">
                                    <select id="op_booking_time_hour" class="w-full px-3 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="">Jam</option>
                                        <?php for($h = $opHour; $h <= $clHour; $h++): ?>
                                            <option value="<?php echo e(sprintf('%02d', $h)); ?>" <?php echo e($opOldHour === sprintf('%02d', $h) ? 'selected' : ''); ?>><?php echo e(sprintf('%02d', $h)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="text-slate-400 font-bold">:</span>
                                    <select id="op_booking_time_minute" class="w-full px-3 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="">Menit</option>
                                        <?php for($m = 0; $m <= 59; $m++): ?>
                                            <option value="<?php echo e(sprintf('%02d', $m)); ?>" <?php echo e($opOldMinute === sprintf('%02d', $m) ? 'selected' : ''); ?>><?php echo e(sprintf('%02d', $m)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <script>
                            (function() {
                                const hidden = document.getElementById('op_booking_time');
                                const hourSel = document.getElementById('op_booking_time_hour');
                                const minSel = document.getElementById('op_booking_time_minute');
                                function sync() {
                                    hidden.value = (hourSel.value && minSel.value) ? `${hourSel.value}:${minSel.value}` : '';
                                }
                                hourSel.addEventListener('change', sync);
                                minSel.addEventListener('change', sync);
                            })();
                        </script>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jumlah Orang di Foto</label>
                                <input type="number" name="people_count" value="<?php echo e(old('people_count')); ?>" min="1" max="<?php echo e(config('booking.max_people', 500)); ?>" inputmode="numeric" placeholder="mis. 6"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tambahan Pergantian Kostum</label>
                                <input type="number" name="costume_changes" value="<?php echo e(old('costume_changes', 0)); ?>" min="0" max="<?php echo e(config('booking.max_costume_changes', 20)); ?>" inputmode="numeric" placeholder="0"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilihan Paket</label>
                            <select name="package_name" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="">Belum pilih paket</option>
                                <?php $__currentLoopData = \App\Models\BookingPackage::activeNames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($pk); ?>" <?php echo e(old('package_name') === $pk ? 'selected' : ''); ?>><?php echo e($pk); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">DP (Rp)</label>
                                <input type="number" name="dp_amount" value="<?php echo e(old('dp_amount')); ?>" min="0" step="1000" inputmode="numeric" placeholder="mis. 50000"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <p class="text-xs text-slate-400 mt-1">Ketentuan DP minimal Rp 50.000. Kalau dibatalkan, DP otomatis hangus.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Order (opsional)</label>
                                <input type="text" name="order_code" value="<?php echo e(old('order_code')); ?>" maxlength="50" placeholder="mis. JG035474"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        
                        <?php if($locations->count() > 1): ?>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                                <select name="location_id" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="">- Lokasi Saya (<?php echo e($location->name); ?>) -</option>
                                    <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($loc->id !== $location->id): ?>
                                            <option value="<?php echo e($loc->id); ?>" <?php echo e(old('location_id') == $loc->id ? 'selected' : ''); ?>>
                                                <?php echo e($loc->name); ?>

                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Order</label>
                            <textarea name="notes" rows="3" placeholder="Catatan pesanan (mis. 2x cetak 4R, 1x album)..." class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"><?php echo e(old('notes')); ?></textarea>
                            <p class="text-xs text-slate-400 mt-1">Ikut kebawa otomatis ke tiket begitu reservasi ini check-in — gak perlu ditulis ulang.</p>
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">
                                Tambah Reservasi
                            </button>
                            <a href="<?php echo e(route('operator.dashboard')); ?>" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-lg font-semibold transition-colors">
                                Batal
                            </a>
                        </div>
                    </form>
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
<?php /**PATH /var/www/html/resources/views/operator/bookings/create.blade.php ENDPATH**/ ?>