
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'openTime' => '08:00',
    'closeTime' => '17:00',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'openTime' => '08:00',
    'closeTime' => '17:00',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $presets = [
        ['label' => '08:00 - 17:00 (Pagi - Sore)', 'open' => '08:00', 'close' => '17:00'],
        ['label' => '09:00 - 21:00 (Pagi - Malam)', 'open' => '09:00', 'close' => '21:00'],
        ['label' => '10:00 - 22:00 (Siang - Malam)', 'open' => '10:00', 'close' => '22:00'],
        ['label' => '08:00 - 22:00 (Buka Panjang)', 'open' => '08:00', 'close' => '22:00'],
        ['label' => '00:00 - 23:59 (Buka 24 Jam)', 'open' => '00:00', 'close' => '23:59'],
    ];
    $matchingPreset = collect($presets)->first(fn ($p) => $p['open'] === $openTime && $p['close'] === $closeTime);
?>

<div x-data="{
        mode: <?php echo e($matchingPreset ? "'preset'" : "'custom'"); ?>,
        openTime: '<?php echo e($openTime); ?>',
        closeTime: '<?php echo e($closeTime); ?>',
    }">
    <div class="flex items-center gap-4 mb-3">
        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Jenis Pengaturan Jam</span>
        <label class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
            <input type="radio" x-model="mode" value="preset" class="border-slate-300">
            Pilihan Cepat
        </label>
        <label class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
            <input type="radio" x-model="mode" value="custom" class="border-slate-300">
            Custom (Jam Bebas)
        </label>
    </div>

    <!-- Mode: Pilihan Cepat -->
    <div x-show="mode === 'preset'" x-cloak>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Operasional *</label>
        <select
            @change="const [o, c] = $event.target.value.split('|'); if (o) { openTime = o; closeTime = c; }"
            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="" <?php if (! ($matchingPreset)): ?> selected <?php endif; ?>>-- Pilih jam operasional --</option>
            <?php $__currentLoopData = $presets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($preset['open']); ?>|<?php echo e($preset['close']); ?>"
                    <?php if($matchingPreset && $matchingPreset['open'] === $preset['open'] && $matchingPreset['close'] === $preset['close']): ?> selected <?php endif; ?>>
                    <?php echo e($preset['label']); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <p class="text-xs text-slate-400 mt-1">Jam akan otomatis terisi sesuai pilihan di atas.</p>
    </div>

    <!-- Mode: Custom (jam bebas) -->
    <div x-show="mode === 'custom'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Buka *</label>
            <input type="time" x-model="openTime" required
                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Tutup *</label>
            <input type="time" x-model="closeTime" required
                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    <!-- Ringkasan jam yang sedang aktif (selalu terlihat, apapun mode-nya) -->
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
        Jam operasional saat ini: <span class="font-semibold" x-text="openTime + ' - ' + closeTime"></span>
    </p>

    
    <input type="hidden" name="open_time" :value="openTime">
    <input type="hidden" name="close_time" :value="closeTime">
    <?php $__errorArgs = ['open_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php $__errorArgs = ['close_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH /var/www/html/resources/views/components/operating-hours-picker.blade.php ENDPATH**/ ?>