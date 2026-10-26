<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Edit ' . $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Edit ' . $location->name)]); ?>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="<?php echo e(route('admin.locations')); ?>" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Edit Lokasi</h1>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <form method="POST" action="<?php echo e(route('admin.locations.update', $location)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Lokasi *</label>
                            <input type="text" id="name" name="name" value="<?php echo e(old('name', $location->name)); ?>" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Kode *</label>
                            <input type="text" id="code" name="code" value="<?php echo e(old('code', $location->code)); ?>" required maxlength="10"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase">
                            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo e(old('description', $location->description)); ?></textarea>
                        <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                        <input type="text" id="address" name="address" value="<?php echo e(old('address', $location->address)); ?>"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-medium text-slate-700">Titik GPS Lokasi (opsional)</label>
                            <button type="button" id="use-current-location-btn"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                <?php if (isset($component)) { $__componentOriginalbb2de5a19350412a96a4922f84030513 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb2de5a19350412a96a4922f84030513 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-icon','data' => ['name' => 'map-pin','class' => 'w-3.5 h-3.5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'map-pin','class' => 'w-3.5 h-3.5 shrink-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbb2de5a19350412a96a4922f84030513)): ?>
<?php $attributes = $__attributesOriginalbb2de5a19350412a96a4922f84030513; ?>
<?php unset($__attributesOriginalbb2de5a19350412a96a4922f84030513); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbb2de5a19350412a96a4922f84030513)): ?>
<?php $component = $__componentOriginalbb2de5a19350412a96a4922f84030513; ?>
<?php unset($__componentOriginalbb2de5a19350412a96a4922f84030513); ?>
<?php endif; ?> Gunakan lokasi saya sekarang
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mb-3">
                            Kalau diisi, pelanggan hanya bisa mengambil tiket kalau HP mereka terdeteksi ada di dalam
                            radius ini. Kosongkan (hapus isinya) kalau lokasi ini tidak mau dibatasi GPS-nya.
                            <strong>Catatan:</strong> deteksi GPS di browser cuma jalan di HTTPS.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="latitude" class="block text-xs font-medium text-slate-600 mb-1">Latitude</label>
                                <input type="text" inputmode="decimal" id="latitude" name="latitude" value="<?php echo e(old('latitude', $location->latitude)); ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="-7.7825900">
                                <?php $__errorArgs = ['latitude'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label for="longitude" class="block text-xs font-medium text-slate-600 mb-1">Longitude</label>
                                <input type="text" inputmode="decimal" id="longitude" name="longitude" value="<?php echo e(old('longitude', $location->longitude)); ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="110.3670000">
                                <?php $__errorArgs = ['longitude'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label for="gps_radius_meters" class="block text-xs font-medium text-slate-600 mb-1">Radius (meter)</label>
                                <input type="number" id="gps_radius_meters" name="gps_radius_meters" value="<?php echo e(old('gps_radius_meters', $location->gps_radius_meters ?? 150)); ?>" min="20" max="5000"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php $__errorArgs = ['gps_radius_meters'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <p id="use-current-location-status" class="text-xs mt-2"></p>
                    </div>

                    <div>
                        <label for="order_line_suggestions" class="block text-sm font-medium text-slate-700 mb-1">Template Baris Order (opsional)</label>
                        <p class="text-xs text-slate-500 mb-2">1 baris = 1 tombol cepat yang muncul di kolom Order buat CS/Kasir. Contoh isi:</p>
                        <textarea id="order_line_suggestions" name="order_line_suggestions" rows="5"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                            placeholder="1x PP A merah&#10;1x PP C bw&#10;2x Cetak 10R&#10;+ Cetak PP J"><?php echo e(old('order_line_suggestions', $location->order_line_suggestions)); ?></textarea>
                        <?php $__errorArgs = ['order_line_suggestions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="average_service_time" class="block text-sm font-medium text-slate-700 mb-1">Rata-rata Waktu Layanan (menit) *</label>
                        <input type="number" id="average_service_time" name="average_service_time" value="<?php echo e(old('average_service_time', $location->average_service_time)); ?>" required min="1" max="120"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php $__errorArgs = ['average_service_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="service_type" class="block text-sm font-medium text-slate-700 mb-1">Layanan yang Dibuka *</label>
                        <select id="service_type" name="service_type" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="both" <?php echo e(old('service_type', $location->service_type) === 'both' ? 'selected' : ''); ?>>Ambil Tiket &amp; Reservasi (dua-duanya)</option>
                            <option value="ticket" <?php echo e(old('service_type', $location->service_type) === 'ticket' ? 'selected' : ''); ?>>Ambil Tiket saja (walk-in)</option>
                            <option value="booking" <?php echo e(old('service_type', $location->service_type) === 'booking' ? 'selected' : ''); ?>>Reservasi saja</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Menentukan tombol apa yang tampil untuk lokasi ini di halaman publik.</p>
                        <?php $__errorArgs = ['service_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <?php if (isset($component)) { $__componentOriginal32fe34d90b152f226edbe46096cc0fbe = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal32fe34d90b152f226edbe46096cc0fbe = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.operating-hours-picker','data' => ['openTime' => old('open_time', \Carbon\Carbon::parse($location->open_time)->format('H:i')),'closeTime' => old('close_time', \Carbon\Carbon::parse($location->close_time)->format('H:i'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('operating-hours-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['open-time' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('open_time', \Carbon\Carbon::parse($location->open_time)->format('H:i'))),'close-time' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('close_time', \Carbon\Carbon::parse($location->close_time)->format('H:i')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal32fe34d90b152f226edbe46096cc0fbe)): ?>
<?php $attributes = $__attributesOriginal32fe34d90b152f226edbe46096cc0fbe; ?>
<?php unset($__attributesOriginal32fe34d90b152f226edbe46096cc0fbe); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal32fe34d90b152f226edbe46096cc0fbe)): ?>
<?php $component = $__componentOriginal32fe34d90b152f226edbe46096cc0fbe; ?>
<?php unset($__componentOriginal32fe34d90b152f226edbe46096cc0fbe); ?>
<?php endif; ?>

                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo e(old('is_active', $location->is_active) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm text-slate-700">Lokasi Aktif</label>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-slate-200">
                    <a href="<?php echo e(route('admin.locations')); ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg font-medium">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('use-current-location-btn');
            const status = document.getElementById('use-current-location-status');
            if (!btn) return;

            btn.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    status.textContent = 'Browser ini tidak mendukung deteksi lokasi.';
                    status.className = 'text-xs mt-2 text-red-600';
                    return;
                }

                status.textContent = 'Mendeteksi lokasi...';
                status.className = 'text-xs mt-2 text-slate-500';

                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.getElementById('latitude').value = pos.coords.latitude.toFixed(7);
                    document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
                    status.textContent = 'Lokasi berhasil diisi otomatis (akurasi ±' + Math.round(pos.coords.accuracy) + ' meter).';
                    status.className = 'text-xs mt-2 text-green-600';
                }, function (err) {
                    let msg = 'Gagal mendeteksi lokasi: ' + err.message;
                    if (err.code === err.PERMISSION_DENIED) {
                        msg = 'Izin lokasi ditolak. Aktifkan izin lokasi untuk situs ini, atau isi Latitude/Longitude manual.';
                    } else if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                        msg = 'Deteksi lokasi butuh HTTPS. Isi Latitude/Longitude manual untuk sementara.';
                    }
                    status.textContent = msg;
                    status.className = 'text-xs mt-2 text-red-600';
                }, { enableHighAccuracy: true, timeout: 10000 });
            });
        })();
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
<?php /**PATH /var/www/html/resources/views/admin/locations/edit.blade.php ENDPATH**/ ?>