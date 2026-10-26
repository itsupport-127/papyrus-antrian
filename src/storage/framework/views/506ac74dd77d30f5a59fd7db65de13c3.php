<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'size' => '1.1em', 'color' => null]));

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

foreach (array_filter((['name', 'size' => '1.1em', 'color' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $style = 'width:' . $size . ';height:' . $size . ';display:inline-block;vertical-align:-0.2em;flex-shrink:0;' . ($color ? 'color:' . $color . ';' : '');
    try {
        $iconHtml = svg('heroicon-o-' . $name, $attributes->get('class', ''), ['style' => $style])->toHtml();
    } catch (\Throwable $e) {
        $iconHtml = '';
    }
?>
<?php echo $iconHtml; ?>

<?php /**PATH /var/www/html/resources/views/components/papyrus-icon.blade.php ENDPATH**/ ?>