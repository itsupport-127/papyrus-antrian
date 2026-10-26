
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon', 'color' => 'slate', 'size' => 'md']));

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

foreach (array_filter((['icon', 'color' => 'slate', 'size' => 'md']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $colors = [
        'green'  => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        'amber'  => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
        'indigo' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
        'red'    => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
        'blue'   => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        'slate'  => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400',
    ];
    $sizes = [
        'sm' => ['wrap' => 'w-6 h-6', 'icon' => 'w-3.5 h-3.5'],
        'md' => ['wrap' => 'w-8 h-8', 'icon' => 'w-4 h-4'],
        'lg' => ['wrap' => 'w-11 h-11', 'icon' => 'w-5 h-5'],
    ];
?>
<span <?php echo e($attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full shrink-0 ' . ($sizes[$size]['wrap'] ?? $sizes['md']['wrap']) . ' ' . ($colors[$color] ?? $colors['slate'])])); ?>>
    <?php if (isset($component)) { $__componentOriginalbb2de5a19350412a96a4922f84030513 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb2de5a19350412a96a4922f84030513 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-icon','data' => ['name' => $icon,'class' => ''.e($sizes[$size]['icon'] ?? $sizes['md']['icon']).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => ''.e($sizes[$size]['icon'] ?? $sizes['md']['icon']).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbb2de5a19350412a96a4922f84030513)): ?>
<?php $attributes = $__attributesOriginalbb2de5a19350412a96a4922f84030513; ?>
<?php unset($__attributesOriginalbb2de5a19350412a96a4922f84030513); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbb2de5a19350412a96a4922f84030513)): ?>
<?php $component = $__componentOriginalbb2de5a19350412a96a4922f84030513; ?>
<?php unset($__componentOriginalbb2de5a19350412a96a4922f84030513); ?>
<?php endif; ?>
</span>
<?php /**PATH /var/www/html/resources/views/components/icon-badge.blade.php ENDPATH**/ ?>