
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['user', 'size' => 32]));

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

foreach (array_filter((['user', 'size' => 32]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php $px = (int) $size; ?>
<?php if($user->avatarUrl()): ?>
    <img src="<?php echo e($user->avatarUrl()); ?>" alt="<?php echo e($user->name); ?>" width="<?php echo e($px); ?>" height="<?php echo e($px); ?>" loading="lazy"
         class="rounded-full object-cover flex-shrink-0 bg-slate-100" style="width:<?php echo e($px); ?>px;height:<?php echo e($px); ?>px">
<?php else: ?>
    <span class="inline-flex items-center justify-center rounded-full font-semibold text-white flex-shrink-0 bg-gradient-to-br from-purple-400 to-pink-400"
          style="width:<?php echo e($px); ?>px;height:<?php echo e($px); ?>px;font-size:<?php echo e(max(10, (int) round($px * 0.4))); ?>px" aria-hidden="true"><?php echo e($user->initials()); ?></span>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/components/user-avatar.blade.php ENDPATH**/ ?>