{{--
    Chip bulat pastel buat bungkus icon status -- gaya yang sama kayak
    badge status tiket yang sudah ada (bg-{color}-50 + border + text
    berwarna). Dipakai buat icon yang berdiri sendiri di depan teks
    singkat (bukan icon kecil di dalam tombol -- itu cukup <x-icon> polos).

    Pemakaian:
        <x-icon-badge icon="bell" color="green" />
        <x-icon-badge icon="clock" color="purple" size="sm" />
--}}
@props(['icon', 'color' => 'slate', 'size' => 'md'])
@php
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
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full shrink-0 ' . ($sizes[$size]['wrap'] ?? $sizes['md']['wrap']) . ' ' . ($colors[$color] ?? $colors['slate'])]) }}>
    <x-app-icon :name="$icon" class="{{ $sizes[$size]['icon'] ?? $sizes['md']['icon'] }}" />
</span>
