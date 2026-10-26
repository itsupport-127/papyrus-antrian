{{--
    Icon component — pengganti emoji di seluruh app.

    SENGAJA dinamai app-icon (bukan cuma "icon") -- nama "icon" kebentur
    sama komponen <x-icon> bawaan package blade-ui-kit/blade-icons yang
    juga ke-install di project ini (lewat blade-heroicons), dan package
    itu yang menang kalau namanya sama. Efeknya: <x-icon name="map-pin">
    di beberapa halaman (Edit/Buat Lokasi Admin, Ambil Tiket pelanggan)
    selalu 500 (SvgNotFound) karena package itu gak punya ikon "map-pin"
    di set default-nya -- lihat storage/logs/laravel.log kalau mau cek
    histori errornya.

    Path SVG diambil dari Lucide (https://lucide.dev, lisensi ISC — bebas
    dipakai/dimodifikasi tanpa atribusi wajib), ditulis manual di sini
    (bukan lewat CDN/paket) supaya tidak ada dependency jaringan tambahan
    dan gampang di-custom warna/ukurannya lewat class Tailwind biasa.

    Pemakaian:
        <x-app-icon name="bell" class="w-5 h-5" />
        <x-app-icon name="star" class="w-4 h-4" :filled="true" /> (bintang terisi)
--}}
@props(['name', 'filled' => false])
@php
    $paths = [
        'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'bell'           => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'camera'         => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'palette'        => '<path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r="1.25" fill="currentColor" stroke="none"/><circle cx="17.5" cy="10.5" r="1.25" fill="currentColor" stroke="none"/><circle cx="6.5" cy="12.5" r="1.25" fill="currentColor" stroke="none"/><circle cx="8.5" cy="7.5" r="1.25" fill="currentColor" stroke="none"/>',
        'folder'         => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>',
        'check'          => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle'   => '<path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>',
        'star'           => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>',
        'x-circle'       => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'alert-triangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'map-pin'        => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'lock'           => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'pencil'         => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.986L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
        'ticket'         => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
        'play'           => '<polygon points="6 3 20 12 6 21 6 3" fill="currentColor" stroke="currentColor"/>',
        'pause'          => '<rect x="14" y="4" width="4" height="16" rx="1" fill="currentColor" stroke="none"/><rect x="6" y="4" width="4" height="16" rx="1" fill="currentColor" stroke="none"/>',
    ];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $filled ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => 'w-4 h-4']) }}>
    {!! $paths[$name] ?? '' !!}
</svg>
