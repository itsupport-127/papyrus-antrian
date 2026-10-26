{{-- Avatar staf: foto profil kalau ada, kalau belum -> lingkaran berisi inisial. --}}
@props(['user', 'size' => 32])
@php $px = (int) $size; @endphp
@if($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" width="{{ $px }}" height="{{ $px }}" loading="lazy"
         class="rounded-full object-cover flex-shrink-0 bg-slate-100" style="width:{{ $px }}px;height:{{ $px }}px">
@else
    <span class="inline-flex items-center justify-center rounded-full font-semibold text-white flex-shrink-0 bg-gradient-to-br from-purple-400 to-pink-400"
          style="width:{{ $px }}px;height:{{ $px }}px;font-size:{{ max(10, (int) round($px * 0.4)) }}px" aria-hidden="true">{{ $user->initials() }}</span>
@endif
