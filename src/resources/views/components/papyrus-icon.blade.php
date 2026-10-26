@props(['name', 'size' => '1.1em', 'color' => null])
{{--
    Ikon pengganti emoji, memakai LIBRARY IKON YANG SUDAH TERPASANG: paket
    blade-ui-kit/blade-heroicons (set outline). `name` = nama ikon Heroicons tanpa
    prefix "heroicon-o-", mis. name="bell", "check-circle", "speaker-wave".
    Daftar lengkap: https://heroicons.com (atau vendor/blade-ui-kit/blade-heroicons/resources/svg/o-*.svg).

    Kenapa pembungkus, bukan <x-heroicon-o-bell> langsung: supaya ukuran default mengikuti
    teks (1.1em), warna mengikuti teks (currentColor, atau `color`), dan sejajar dengan
    teks -- tanpa bergantung pada kelas Tailwind yang belum tentu ada di build.
    Nama tag SENGAJA bukan "icon": <x-icon> dipakai bawaan paket Blade Icons.
    Nama ikon yang salah ketik tidak membuat halaman 500, cuma tidak menampilkan apa-apa.

    Pemakaian: <x-papyrus-icon name="check-circle" /> ,  <x-papyrus-icon name="trophy" size="1.4em" color="#d4a017" />
--}}
@php
    $style = 'width:' . $size . ';height:' . $size . ';display:inline-block;vertical-align:-0.2em;flex-shrink:0;' . ($color ? 'color:' . $color . ';' : '');
    try {
        $iconHtml = svg('heroicon-o-' . $name, $attributes->get('class', ''), ['style' => $style])->toHtml();
    } catch (\Throwable $e) {
        $iconHtml = '';
    }
@endphp
{!! $iconHtml !!}
