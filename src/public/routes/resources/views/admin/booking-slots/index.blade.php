<x-layout title="Jadwal Reservasi">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <a href="{{ $backRoute ?? route('admin.locations') }}" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ $backLabel ?? 'Kembali ke Lokasi' }}
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Jadwal Reservasi</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ $location->name }} — atur jam mana saja yang bisa direservasi pelanggan &amp; berapa kapasitas per jam</p>

                <div class="mt-3 inline-flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl px-4 py-2 text-sm">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-blue-800 dark:text-blue-300">
                        Anda sedang mengatur jadwal untuk <strong>{{ $location->name }}</strong> (ID: {{ $location->id }}, kode: {{ $location->code }}).
                    </span>
                    <a href="{{ route('bookings.create', $location) }}" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-700 flex-shrink-0">
                        Lihat halaman reservasi pelanggan &rarr;
                    </a>
                </div>
            </div>

            <div x-data="{ mode: '{{ old('booking_mode', $location->booking_mode) }}', interval: {{ old('interval_minutes', 30) }} }">

            <!-- Sistem Reservasi: pelanggan bebas pilih jam, atau pilih dari jadwal yang sudah ditentukan -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="font-bold text-slate-900 dark:text-white mb-1">Sistem Reservasi</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Pilih cara pelanggan menentukan jam kedatangan saat reservasi online.</p>
                <form method="POST" action="{{ route(($routePrefix ?? 'admin') . '.booking-slots.mode', ($routePrefix ?? 'admin') === 'admin' ? $location : []) }}">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-3 border-2 rounded-xl p-4 cursor-pointer transition-colors"
                               :class="mode === 'slots' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 dark:border-slate-600'">
                            <input type="radio" name="booking_mode" value="slots" x-model="mode" class="mt-1">
                            <span>
                                <span class="block font-semibold text-slate-900 dark:text-white">Jadwal Ditentukan</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pelanggan pilih dari jam-jam yang sudah Anda siapkan di bawah, masing-masing punya batas kapasitas.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 border-2 rounded-xl p-4 cursor-pointer transition-colors"
                               :class="mode === 'free' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-200 dark:border-slate-600'">
                            <input type="radio" name="booking_mode" value="free" x-model="mode" class="mt-1">
                            <span>
                                <span class="block font-semibold text-slate-900 dark:text-white">Bebas Pilih Jam</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pelanggan bebas isi jam berapa saja, asal masih dalam jam operasional ({{ $location->open_time->format('H:i') }}–{{ $location->close_time->format('H:i') }}). Tidak dibatasi kapasitas per jam.</span>
                            </span>
                        </label>
                    </div>
                    @error('booking_mode')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror

                    @if($location->booking_mode === 'slots' && $slots->isEmpty())
                        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-3 text-sm text-amber-700 dark:text-amber-400">
                            <strong>Belum ada jadwal jam yang disiapkan.</strong> Karena mode masih "Jadwal Ditentukan" tapi belum ada slot sama sekali, pelanggan akan selalu melihat "Tidak ada jadwal reservasi". Tambahkan slot lewat "Generate Otomatis" / "Tambah Slot Custom" di bawah, atau ganti ke "Bebas Pilih Jam" supaya reservasi langsung bisa dipakai.
                        </div>
                    @endif

                    <div class="mt-4 flex items-center gap-3">
                        <button type="submit"
                                :disabled="mode === '{{ $location->booking_mode }}'"
                                :class="mode === '{{ $location->booking_mode }}' ? 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed' : 'bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white'"
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                            Simpan Sistem Reservasi
                        </button>
                        <span x-show="mode !== '{{ $location->booking_mode }}'" x-cloak class="text-xs text-amber-600 dark:text-amber-400">
                            Ada perubahan belum disimpan.
                        </span>
                    </div>
                </form>
            </div>

            @if(($routePrefix ?? 'admin') === 'admin' && $otherLocations->isNotEmpty())
                <!-- Salin Pengaturan Reservasi ke Lokasi Lain — supaya tidak perlu setting manual satu-satu tiap ada lokasi baru -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 mb-6" x-data="{ targets: [] }">
                    <h2 class="font-bold text-slate-900 dark:text-white mb-1">Salin Pengaturan ke Lokasi Lain</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Salin Sistem Reservasi (mode {{ $location->isFreeBookingMode() ? 'Bebas Pilih Jam' : 'Jadwal Ditentukan' }}) beserta semua jam & kapasitas dari <strong>{{ $location->name }}</strong> ke lokasi lain. Jam yang sudah ada di lokasi tujuan akan ditimpa kapasitasnya, bukan digandakan.
                    </p>
                    <form method="POST" action="{{ route('admin.booking-slots.copy', $location) }}" onsubmit="return confirm('Salin pengaturan reservasi ke lokasi terpilih? Jam yang sudah ada di sana akan ditimpa.')">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-2 mb-4">
                            @foreach($otherLocations as $other)
                                <label class="flex items-center gap-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl px-3 py-2 cursor-pointer text-sm"
                                       :class="targets.includes({{ $other->id }}) ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : ''">
                                    <input type="checkbox" name="target_ids[]" value="{{ $other->id }}"
                                        @change="$event.target.checked ? targets.push({{ $other->id }}) : targets = targets.filter(id => id !== {{ $other->id }})">
                                    <span class="text-slate-700 dark:text-slate-300">{{ $other->name }} <span class="text-slate-400">({{ $other->code }})</span></span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" :disabled="targets.length === 0"
                            :class="targets.length === 0 ? 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed' : 'bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white'"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                            Salin Pengaturan
                        </button>
                    </form>
                </div>
            @endif

            <!-- Form kosong khusus aksi massal — checkbox tiap baris "menempel" ke form ini lewat atribut form="bulk-slots-form", tanpa perlu form di dalam form -->
            <form id="bulk-slots-form" method="POST"
                  action="{{ route(($routePrefix ?? 'admin') . '.booking-slots.bulk', ($routePrefix ?? 'admin') === 'admin' ? $location : []) }}"
                  onsubmit="return confirm(this.dataset.confirmMsg || 'Yakin?')">
                @csrf
            </form>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @php $bookedTodayByTime = $bookedTodayByTime ?? collect(); @endphp
                <!-- Left: forms (hanya relevan di mode "Jadwal Ditentukan") -->
                <div class="lg:col-span-1 space-y-6" x-show="mode === 'slots'" x-cloak>
                    <!-- Generate Otomatis -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="font-bold text-slate-900 dark:text-white mb-1">Generate Otomatis</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Buat banyak slot sekaligus dengan interval tetap.</p>
                        <form method="POST" action="{{ route(($routePrefix ?? 'admin') . '.booking-slots.generate', ($routePrefix ?? 'admin') === 'admin' ? $location : []) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hari</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach(\App\Models\BookingSlot::DAY_LABELS as $num => $label)
                                        <label class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300">
                                            <input type="checkbox" name="days[]" value="{{ $num }}" class="rounded border-slate-300">
                                            {{ Str::limit($label, 3, '') }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dari Jam</label>
                                    <input type="time" name="start_time" value="{{ old('start_time', $location->open_time?->format('H:i')) }}" required
                                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sampai Jam</label>
                                    <input type="time" name="end_time" value="{{ old('end_time', $location->close_time?->format('H:i')) }}" required
                                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Interval (menit)</label>
                                    <div class="flex flex-wrap gap-1.5 mb-1.5">
                                        @foreach([15, 30, 45, 60] as $preset)
                                            <button type="button" @click="interval = {{ $preset }}"
                                                :class="interval == {{ $preset }} ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-600'"
                                                class="px-2 py-0.5 text-xs font-medium rounded-full border transition-colors">{{ $preset }}</button>
                                        @endforeach
                                    </div>
                                    <input type="number" name="interval_minutes" x-model="interval" min="5" max="240" required
                                        placeholder="Atau isi bebas (menit)"
                                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kapasitas/slot</label>
                                    <input type="number" name="capacity" value="{{ old('capacity', 1) }}" min="1" max="999" required
                                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                                Generate Slot
                            </button>
                            <p class="text-xs text-slate-400">Slot yang jamnya sudah ada di hari yang sama otomatis dilewati (tidak dobel). Pilih tombol interval di atas untuk cepat, atau ketik angka sendiri untuk interval custom.</p>
                        </form>
                    </div>

                    <!-- Tambah Custom -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                        <h2 class="font-bold text-slate-900 dark:text-white mb-1">Tambah Slot Custom</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Untuk jam-jam khusus di luar pola interval tetap.</p>
                        <form method="POST" action="{{ route(($routePrefix ?? 'admin') . '.booking-slots.store', ($routePrefix ?? 'admin') === 'admin' ? $location : []) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hari</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach(\App\Models\BookingSlot::DAY_LABELS as $num => $label)
                                        <label class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300">
                                            <input type="checkbox" name="days[]" value="{{ $num }}" class="rounded border-slate-300">
                                            {{ Str::limit($label, 3, '') }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam</label>
                                    <input type="time" name="start_time" required
                                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kapasitas</label>
                                    <input type="number" name="capacity" value="1" min="1" max="999" required
                                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                                Tambah Slot
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Placeholder saat mode "Bebas Pilih Jam" aktif — pengaturan jadwal di bawah tidak relevan -->
                <div class="lg:col-span-3" x-show="mode === 'free'" x-cloak>
                    <div class="bg-slate-50 dark:bg-slate-800/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-8 text-center max-w-lg mx-auto">
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Pengaturan jadwal tidak dipakai</p>
                        <p class="text-xs text-slate-400 mt-1">Mode "Bebas Pilih Jam" sedang aktif — pelanggan isi jam sendiri, jadi Generate Otomatis, Tambah Slot Custom, dan daftar jam disembunyikan supaya tidak membingungkan. Data slot yang sudah ada tetap tersimpan, dan akan muncul lagi begitu Anda pilih mode "Jadwal Ditentukan".</p>
                    </div>
                </div>

                <!-- Right: slot list per day (disembunyikan total saat mode "Bebas Pilih Jam") -->
                <div class="lg:col-span-2" x-show="mode === 'slots'" x-cloak x-data="{ selectedCount: 0 }">
                    <!-- Toolbar aksi massal -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 mb-4 flex flex-wrap items-center gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" class="rounded border-slate-300"
                                @change="
                                    document.querySelectorAll('#booking-slots-list input[type=checkbox][form=bulk-slots-form]').forEach(cb => cb.checked = $event.target.checked);
                                    selectedCount = $event.target.checked ? document.querySelectorAll('#booking-slots-list input[type=checkbox][form=bulk-slots-form]').length : 0;
                                ">
                            Pilih Semua
                        </label>
                        <span class="text-xs text-slate-400" x-show="selectedCount > 0" x-cloak x-text="selectedCount + ' dipilih'"></span>
                        <div class="ml-auto flex flex-wrap gap-2">
                            <button type="submit" form="bulk-slots-form" name="action" value="activate"
                                onclick="document.getElementById('bulk-slots-form').dataset.confirmMsg = 'Aktifkan semua slot yang dipilih?'"
                                :disabled="selectedCount === 0"
                                class="text-xs font-semibold text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 disabled:opacity-40 disabled:cursor-not-allowed px-3 py-1.5 rounded-lg transition-colors">
                                Aktifkan Terpilih
                            </button>
                            <button type="submit" form="bulk-slots-form" name="action" value="deactivate"
                                onclick="document.getElementById('bulk-slots-form').dataset.confirmMsg = 'Nonaktifkan semua slot yang dipilih?'"
                                :disabled="selectedCount === 0"
                                class="text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 disabled:opacity-40 disabled:cursor-not-allowed px-3 py-1.5 rounded-lg transition-colors">
                                Nonaktifkan Terpilih
                            </button>
                            <button type="submit" form="bulk-slots-form" name="action" value="delete"
                                onclick="document.getElementById('bulk-slots-form').dataset.confirmMsg = 'Hapus semua slot yang dipilih? Tindakan ini tidak bisa dibatalkan.'"
                                :disabled="selectedCount === 0"
                                class="text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 disabled:opacity-40 disabled:cursor-not-allowed px-3 py-1.5 rounded-lg transition-colors">
                                Hapus Terpilih
                            </button>
                        </div>
                    </div>

                    <div id="booking-slots-list" class="space-y-6">
                        @foreach(\App\Models\BookingSlot::DAY_LABELS as $dayNum => $dayLabel)
                            @php $daySlots = $slots->get($dayNum, collect()); @endphp
                            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-700">
                                    <h3 class="font-bold text-slate-900 dark:text-white">{{ $dayLabel }}</h3>
                                </div>
                                @if($daySlots->isEmpty())
                                    <p class="px-6 py-6 text-sm text-slate-400 text-center">Belum ada slot di hari ini.</p>
                                @else
                                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                        @foreach($daySlots as $slot)
                                            @php $bookedToday = $bookedTodayByTime->get($slot->start_time->format('H:i'), 0); @endphp
                                            <div class="flex items-center justify-between px-6 py-3">
                                                <div class="flex items-center gap-4">
                                                    <input type="checkbox" name="ids[]" value="{{ $slot->id }}" form="bulk-slots-form"
                                                        class="rounded border-slate-300"
                                                        @change="selectedCount = document.querySelectorAll('#booking-slots-list input[type=checkbox][form=bulk-slots-form]:checked').length">
                                                    <span class="font-semibold text-slate-900 dark:text-white w-16">{{ $slot->start_time->format('H:i') }}</span>

                                                    <form method="POST" action="{{ route(($routePrefix ?? 'admin') . '.booking-slots.update', $slot) }}" class="flex items-center gap-3">
                                                        @csrf
                                                        @method('PUT')

                                                        <label class="flex items-center gap-1.5 text-sm">
                                                            <span class="text-xs text-slate-500 dark:text-slate-400">Kapasitas</span>
                                                            <input type="number" name="capacity" value="{{ $slot->capacity }}" min="1" max="999"
                                                                class="w-16 border border-slate-300 dark:border-slate-600 rounded-lg px-2 py-1 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                                        </label>

                                                        <input type="hidden" name="is_active" value="0">
                                                        <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                                                            <input type="checkbox" name="is_active" value="1" {{ $slot->is_active ? 'checked' : '' }}
                                                                class="rounded border-slate-300">
                                                            <span class="{{ $slot->is_active ? 'text-green-600' : 'text-slate-500' }}">
                                                                {{ $slot->is_active ? 'Aktif' : 'Nonaktif' }}
                                                            </span>
                                                        </label>

                                                        <button type="submit" class="text-xs px-3 py-1.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg font-medium">
                                                            Simpan
                                                        </button>
                                                    </form>

                                                    <span class="text-xs text-slate-400">
                                                        {{ $bookedToday }} dibooking hari ini
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <form method="POST" action="{{ route(($routePrefix ?? 'admin') . '.booking-slots.delete', $slot) }}" onsubmit="return confirm('Hapus slot {{ $slot->start_time->format('H:i') }} di hari {{ $dayLabel }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-medium">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.autoRefreshIds(['booking-slots-list'], 8000);
    </script>
    @endpush
</x-layout>