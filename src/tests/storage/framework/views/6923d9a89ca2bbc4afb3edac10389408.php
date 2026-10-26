<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Reservasi - ' . $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Reservasi - ' . $location->name)]); ?>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="<?php echo e(route('home')); ?>" class="text-slate-500 dark:text-slate-400 hover:text-blue-600">Beranda</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-slate-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="<?php echo e(route('locations.index')); ?>" class="text-slate-500 dark:text-slate-400 hover:text-blue-600">Lokasi</a>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-slate-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-slate-900 dark:text-white font-medium">Reservasi</span>
                    </li>
                </ol>
            </nav>

            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-emerald-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</div>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">Data Diri</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-slate-200 dark:bg-slate-700 mx-4"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-full flex items-center justify-center text-sm font-bold" id="step2-indicator">2</div>
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400" id="step2-label">Jadwal</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-slate-200 dark:bg-slate-700 mx-4"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded-full flex items-center justify-center text-sm font-bold" id="step3-indicator">3</div>
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400" id="step3-label">Konfirmasi</span>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-br from-mint to-skyblue text-white p-6 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold">Buat Reservasi</h1>
                    <p class="text-white/85 mt-1"><?php echo e($location->name); ?></p>
                    <p class="text-white/70 text-sm mt-2">Jam operasional: <?php echo e($location->open_time->format('H:i')); ?> - <?php echo e($location->close_time->format('H:i')); ?></p>
                </div>

                <!-- Form -->
                <div class="p-6">
                    <form method="POST" action="<?php echo e(route('bookings.store', $location)); ?>" id="booking-form">
                        <?php echo csrf_field(); ?>

                        <!-- Step 1: Data Diri -->
                        <div class="space-y-5" id="step1">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
                                <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">Langkah 1: Masukkan data diri Anda</p>
                            </div>

                            <?php if($serviceCategories->count() > 0): ?>
                            <div>
                                <label for="service_category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Kategori Layanan
                                </label>
                                <select
                                    id="service_category_id"
                                    name="service_category_id"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white dark:bg-slate-700 dark:text-white"
                                >
                                    <option value="">— Pilih kategori —</option>
                                    <?php $__currentLoopData = $serviceCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($category->id); ?>" <?php echo e(old('service_category_id') == $category->id ? 'selected' : ''); ?>>
                                            <?php echo e($category->name); ?>

                                            <?php if($category->description): ?> — <?php echo e($category->description); ?> <?php endif; ?>
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

                            <div>
                                <label for="customer_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    value="<?php echo e(old('customer_name')); ?>"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white dark:bg-slate-700 dark:text-white"
                                    placeholder="Masukkan nama lengkap"
                                    required
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
                                    Nomor Telepon <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="tel"
                                    id="customer_phone"
                                    name="customer_phone"
                                    value="<?php echo e(old('customer_phone')); ?>"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white dark:bg-slate-700 dark:text-white"
                                    placeholder="08xxxxxxxxxx"
                                    required
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

                            <button type="button" onclick="showStep(2)" class="w-full bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white font-semibold py-3.5 px-6 rounded-xl transition-colors">
                                Lanjut ke Jadwal
                            </button>
                        </div>

                        <!-- Step 2: Jadwal -->
                        <div class="space-y-5 hidden" id="step2">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
                                <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">Langkah 2: Pilih tanggal dan jam</p>
                            </div>

                            <div>
                                <label for="booking_date" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Tanggal Kunjungan <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="booking_date"
                                    name="booking_date"
                                    value="<?php echo e(old('booking_date', today()->addDay()->format('Y-m-d'))); ?>"
                                    min="<?php echo e(today()->format('Y-m-d')); ?>"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white dark:bg-slate-700 dark:text-white"
                                    required
                                >
                                <?php $__errorArgs = ['booking_date'];
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
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Waktu Kunjungan <span class="text-red-500">*</span>
                                </label>

                                <?php if($location->isFreeBookingMode()): ?>
                                    <?php
                                        $openHour = (int) $location->open_time->format('H');
                                        $closeHour = (int) $location->close_time->format('H');
                                        $oldTime = old('booking_time', '');
                                        [$oldHour, $oldMinute] = $oldTime ? explode(':', $oldTime) : [null, null];
                                    ?>
                                    <input type="hidden" id="booking_time" name="booking_time" value="<?php echo e($oldTime); ?>" required>
                                    <div class="flex items-center gap-2">
                                        <select id="booking_time_hour" class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white dark:bg-slate-700 dark:text-white">
                                            <option value="">Jam</option>
                                            <?php for($h = $openHour; $h <= $closeHour; $h++): ?>
                                                <option value="<?php echo e(sprintf('%02d', $h)); ?>" <?php echo e($oldHour === sprintf('%02d', $h) ? 'selected' : ''); ?>><?php echo e(sprintf('%02d', $h)); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <span class="text-slate-400 font-bold">:</span>
                                        <select id="booking_time_minute" class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white dark:bg-slate-700 dark:text-white">
                                            <option value="">Menit</option>
                                            <?php for($m = 0; $m <= 59; $m++): ?>
                                                <option value="<?php echo e(sprintf('%02d', $m)); ?>" <?php echo e($oldMinute === sprintf('%02d', $m) ? 'selected' : ''); ?>><?php echo e(sprintf('%02d', $m)); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <span class="text-xs text-slate-400 whitespace-nowrap">WIB</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2">Bebas pilih jam berapa saja antara <?php echo e($location->open_time->format('H:i')); ?> - <?php echo e($location->close_time->format('H:i')); ?> WIB (format 24 jam).</p>
                                    <div id="time-conflict-warning" class="hidden mt-3 flex items-start gap-2 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg px-3 py-2.5 text-sm">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Jam ini sudah dipakai reservasi lain. Silakan pilih jam yang berbeda.</span>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" id="booking_time" name="booking_time" value="<?php echo e(old('booking_time')); ?>" required>

                                    <div id="slots-loading" class="text-sm text-slate-400 py-4 text-center" style="display:none;">
                                        Memuat jadwal tersedia...
                                    </div>
                                    <div id="slots-empty" class="text-sm text-slate-400 py-4 text-center" style="display:none;">
                                        Tidak ada jadwal reservasi untuk tanggal ini. Silakan pilih tanggal lain.
                                    </div>
                                    <div id="slots-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
                                <?php endif; ?>

                                <?php $__errorArgs = ['booking_time'];
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

                            <div class="flex gap-3">
                                <button type="button" onclick="showStep(1)" class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold py-3.5 px-6 rounded-xl transition-colors">
                                    Kembali
                                </button>
                                <button type="button" onclick="showStep(3)" id="to-step3" class="flex-1 bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white font-semibold py-3.5 px-6 rounded-xl transition-colors" disabled>
                                    Lanjut ke Konfirmasi
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Konfirmasi -->
                        <div class="space-y-5 hidden" id="step3">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
                                <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">Langkah 3: Periksa kembali data Anda</p>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-5 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Nama</span>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white" id="summary-name">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Telepon</span>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white" id="summary-phone">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Tanggal</span>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white" id="summary-date">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Waktu</span>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white" id="summary-time">-</span>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <button type="button" onclick="showStep(2)" class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold py-3.5 px-6 rounded-xl transition-colors">
                                    Kembali
                                </button>
                                <button type="submit" class="flex-1 bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white font-semibold py-3.5 px-6 rounded-xl transition-colors">
                                    Buat Reservasi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Info -->
                <div class="px-6 pb-6">
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm text-amber-800 dark:text-amber-300 font-medium">Informasi Reservasi</p>
                                <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                                    Silakan datang tepat waktu sesuai jadwal. Anda bisa melakukan check-in pada hari kunjungan untuk mendapatkan tiket antrian.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        let currentStep = 1;

        function showStep(step) {
            document.getElementById('step1').classList.toggle('hidden', step !== 1);
            document.getElementById('step2').classList.toggle('hidden', step !== 2);
            document.getElementById('step3').classList.toggle('hidden', step !== 3);

            const step2Indicator = document.getElementById('step2-indicator');
            const step2Label = document.getElementById('step2-label');
            const step3Indicator = document.getElementById('step3-indicator');
            const step3Label = document.getElementById('step3-label');

            if (step === 1) {
                [step2Indicator, step3Indicator].forEach(el => {
                    el.classList.remove('bg-emerald-600', 'text-white');
                    el.classList.add('bg-slate-200', 'dark:bg-slate-700', 'text-slate-500', 'dark:text-slate-400');
                });
                [step2Label, step3Label].forEach(el => {
                    el.classList.remove('text-slate-900', 'dark:text-white');
                    el.classList.add('text-slate-500', 'dark:text-slate-400');
                });
            }

            if (step === 2) {
                step2Indicator.classList.remove('bg-slate-200', 'dark:bg-slate-700', 'text-slate-500', 'dark:text-slate-400');
                step2Indicator.classList.add('bg-emerald-600', 'text-white');
                step2Label.classList.remove('text-slate-500', 'dark:text-slate-400');
                step2Label.classList.add('text-slate-900', 'dark:text-white');
            }

            if (step === 3) {
                step3Indicator.classList.remove('bg-slate-200', 'dark:bg-slate-700', 'text-slate-500', 'dark:text-slate-400');
                step3Indicator.classList.add('bg-emerald-600', 'text-white');
                step3Label.classList.remove('text-slate-500', 'dark:text-slate-400');
                step3Label.classList.add('text-slate-900', 'dark:text-white');

                updateSummary();
            }

            currentStep = step;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateSummary() {
            document.getElementById('summary-name').textContent = document.getElementById('customer_name').value || '-';
            document.getElementById('summary-phone').textContent = document.getElementById('customer_phone').value || '-';
            document.getElementById('summary-date').textContent = document.getElementById('booking_date').value || '-';
            document.getElementById('summary-time').textContent = document.getElementById('booking_time').value || '-';
        }

        const dateInput = document.getElementById('booking_date');
        const slotsGrid = document.getElementById('slots-grid');
        const toStep3Btn = document.getElementById('to-step3');

        if (toStep3Btn && !slotsGrid) {
            const freeTimeInput = document.getElementById('booking_time');
            const hourSelect = document.getElementById('booking_time_hour');
            const minuteSelect = document.getElementById('booking_time_minute');

            if (freeTimeInput && hourSelect && minuteSelect) {
                const syncFreeTime = () => {
                    const h = hourSelect.value;
                    const m = minuteSelect.value;
                    freeTimeInput.value = (h && m) ? `${h}:${m}` : '';
                    freeTimeInput.dispatchEvent(new Event('input'));
                };
                hourSelect.addEventListener('change', syncFreeTime);
                minuteSelect.addEventListener('change', syncFreeTime);
            }

            if (freeTimeInput) {
                freeTimeInput.addEventListener('input', () => {
                    toStep3Btn.disabled = !freeTimeInput.value.trim();
                });
                if (freeTimeInput.value.trim()) {
                    toStep3Btn.disabled = false;
                }
            }

            // --- Cek bentrok jam: kalau jam yang dipilih sudah dipakai
            // reservasi lain di tanggal yang sama, tampilkan peringatan
            // (tidak memblokir, cuma mengingatkan). ---
            const conflictWarning = document.getElementById('time-conflict-warning');
            const takenTimesUrl = "<?php echo e(route('bookings.taken-times', $location)); ?>";
            let takenTimes = [];

            async function loadTakenTimes() {
                takenTimes = [];
                if (!dateInput || !dateInput.value) return;
                try {
                    const res = await fetch(`${takenTimesUrl}?date=${encodeURIComponent(dateInput.value)}`);
                    const data = await res.json();
                    takenTimes = data.times || [];
                } catch (e) {
                    takenTimes = [];
                }
                checkConflict();
            }

            function checkConflict() {
                if (!conflictWarning || !freeTimeInput) return false;
                const value = freeTimeInput.value;
                const isConflict = !!(value && takenTimes.includes(value));
                conflictWarning.classList.toggle('hidden', !isConflict);
                if (toStep3Btn) {
                    toStep3Btn.disabled = isConflict || !freeTimeInput.value.trim();
                }
                return isConflict;
            }

            if (dateInput && conflictWarning) {
                dateInput.addEventListener('change', loadTakenTimes);
                if (freeTimeInput) {
                    freeTimeInput.addEventListener('input', checkConflict);
                }
                loadTakenTimes();
            }
        }

        if (slotsGrid) {
            const slotsLoading = document.getElementById('slots-loading');
            const slotsEmpty = document.getElementById('slots-empty');
            const bookingTimeInput = document.getElementById('booking_time');
            const submitBtn = document.getElementById('submit-booking');
            const slotsUrl = "<?php echo e(route('bookings.slots', $location)); ?>";
            let oldBookingTime = <?php echo json_encode(old('booking_time'), 15, 512) ?>;

            function selectSlot(time, btn) {
                bookingTimeInput.value = time;
                document.querySelectorAll('#slots-grid button').forEach((b) => {
                    b.classList.remove('border-emerald-600', 'bg-emerald-50', 'dark:bg-emerald-900/30', 'text-emerald-700', 'dark:text-emerald-400');
                    b.classList.add('border-slate-200', 'dark:border-slate-600');
                });
                btn.classList.remove('border-slate-200', 'dark:border-slate-600');
                btn.classList.add('border-emerald-600', 'bg-emerald-50', 'dark:bg-emerald-900/30', 'text-emerald-700', 'dark:text-emerald-400');
                toStep3Btn.disabled = false;
            }

            async function loadSlots() {
                const date = dateInput.value;
                if (!date) return;

                bookingTimeInput.value = '';
                toStep3Btn.disabled = true;
                slotsGrid.innerHTML = '';
                slotsEmpty.style.display = 'none';
                slotsLoading.style.display = '';

                try {
                    const response = await fetch(`${slotsUrl}?date=${encodeURIComponent(date)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    slotsLoading.style.display = 'none';

                    if (!data.slots || data.slots.length === 0) {
                        if (data.schedule_unconfigured) {
                            slotsEmpty.innerHTML = 'Reservasi online untuk lokasi ini belum disiapkan. Silakan hubungi kami langsung, atau coba lagi nanti.';
                        } else {
                            slotsEmpty.textContent = 'Tidak ada jadwal reservasi untuk tanggal ini. Silakan pilih tanggal lain.';
                        }
                        slotsEmpty.style.display = '';
                        return;
                    }

                    data.slots.forEach((slot) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = slot.time;
                        btn.disabled = !slot.available;

                        if (slot.available) {
                            btn.title = `Sisa kapasitas: ${slot.remaining}`;
                            btn.className = 'border-2 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:border-emerald-400 transition-colors';
                            btn.addEventListener('click', () => selectSlot(slot.time, btn));

                            if (oldBookingTime && slot.time === oldBookingTime) {
                                selectSlot(slot.time, btn);
                            }
                        } else {
                            btn.title = 'Penuh';
                            btn.className = 'border-2 border-slate-100 dark:border-slate-700 rounded-xl py-2.5 text-sm font-semibold text-slate-300 dark:text-slate-600 line-through cursor-not-allowed';
                        }

                        slotsGrid.appendChild(btn);
                    });

                    oldBookingTime = null;
                } catch (err) {
                    slotsLoading.style.display = 'none';
                    slotsEmpty.textContent = 'Gagal memuat jadwal. Coba refresh halaman.';
                    slotsEmpty.style.display = '';
                }
            }

            dateInput.addEventListener('change', loadSlots);
            loadSlots();
        }

        // --- Draft otomatis: simpan isian form supaya tidak hilang kalau
        // ke-refresh / ke-close tab tidak sengaja sebelum submit. ---
        (function () {
            const form = document.getElementById('booking-form');
            if (!form) return;

            const draftKey = 'booking_draft_<?php echo e($location->id); ?>';
            const fieldIds = ['service_category_id', 'customer_name', 'customer_phone', 'booking_date', 'booking_time_hour', 'booking_time_minute'];

            function saveDraft() {
                const data = {};
                fieldIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) data[id] = el.value;
                });
                try { sessionStorage.setItem(draftKey, JSON.stringify(data)); } catch (e) { /* ignore, storage tidak tersedia */ }
            }

            function restoreDraft() {
                let data;
                try { data = JSON.parse(sessionStorage.getItem(draftKey) || 'null'); } catch (e) { data = null; }
                if (!data) return;
                fieldIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el && data[id]) {
                        el.value = data[id];
                        el.dispatchEvent(new Event('change'));
                        el.dispatchEvent(new Event('input'));
                    }
                });
            }

            // Hanya restore kalau tidak ada data lama dari server (old())
            // — supaya tidak menimpa data validasi gagal yang baru dikirim balik.
            restoreDraft();

            fieldIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', saveDraft);
                    el.addEventListener('change', saveDraft);
                }
            });

            form.addEventListener('submit', () => {
                try { sessionStorage.removeItem(draftKey); } catch (e) { /* ignore */ }
            });
        })();
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
<?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/bookings/create.blade.php ENDPATH**/ ?>