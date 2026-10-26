{{--
    Komponen jam operasional dengan 2 mode yang bisa dipilih admin:
    - Pilihan Cepat : pilih dari preset jam yang umum (dropdown)
    - Custom        : isi jam bebas sendiri (jam berapa saja)

    Apapun mode yang dipilih, nilai akhir tetap dikirim lewat 2 hidden
    input open_time & close_time — jadi tidak perlu ubah apapun di
    controller/validasi yang sudah ada.

    Props:
    - openTime  (string, format H:i, default nilai awal Jam Buka)
    - closeTime (string, format H:i, default nilai awal Jam Tutup)
--}}
@props([
    'openTime' => '08:00',
    'closeTime' => '17:00',
])

@php
    $presets = [
        ['label' => '08:00 - 17:00 (Pagi - Sore)', 'open' => '08:00', 'close' => '17:00'],
        ['label' => '09:00 - 21:00 (Pagi - Malam)', 'open' => '09:00', 'close' => '21:00'],
        ['label' => '10:00 - 22:00 (Siang - Malam)', 'open' => '10:00', 'close' => '22:00'],
        ['label' => '08:00 - 22:00 (Buka Panjang)', 'open' => '08:00', 'close' => '22:00'],
        ['label' => '00:00 - 23:59 (Buka 24 Jam)', 'open' => '00:00', 'close' => '23:59'],
    ];
    $matchingPreset = collect($presets)->first(fn ($p) => $p['open'] === $openTime && $p['close'] === $closeTime);
@endphp

<div x-data="{
        mode: {{ $matchingPreset ? "'preset'" : "'custom'" }},
        openTime: '{{ $openTime }}',
        closeTime: '{{ $closeTime }}',
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
            <option value="" @unless($matchingPreset) selected @endunless>-- Pilih jam operasional --</option>
            @foreach ($presets as $preset)
                <option value="{{ $preset['open'] }}|{{ $preset['close'] }}"
                    @if($matchingPreset && $matchingPreset['open'] === $preset['open'] && $matchingPreset['close'] === $preset['close']) selected @endif>
                    {{ $preset['label'] }}
                </option>
            @endforeach
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

    {{-- Nilai final yang benar-benar dikirim ke server, sinkron dari mode manapun --}}
    <input type="hidden" name="open_time" :value="openTime">
    <input type="hidden" name="close_time" :value="closeTime">
    @error('open_time')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    @error('close_time')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
</div>
