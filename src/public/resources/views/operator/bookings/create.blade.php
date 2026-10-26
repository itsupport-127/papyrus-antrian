<x-layout title="Tambah Reservasi">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Tambah Reservasi</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Buat reservasi manual untuk pelanggan.</p>
                    </div>

                    <form method="POST" action="{{ route('operator.bookings.store') }}" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                <input type="date" name="booking_date" value="{{ old('booking_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam (WIB)</label>
                                @php
                                    $opHour = (int) $location->open_time->format('H');
                                    $clHour = (int) $location->close_time->format('H');
                                    [$opOldHour, $opOldMinute] = old('booking_time') ? explode(':', old('booking_time')) : [null, null];
                                @endphp
                                <input type="hidden" name="booking_time" id="op_booking_time" value="{{ old('booking_time') }}" required>
                                <div class="flex items-center gap-1.5">
                                    <select id="op_booking_time_hour" class="w-full px-3 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="">Jam</option>
                                        @for($h = $opHour; $h <= $clHour; $h++)
                                            <option value="{{ sprintf('%02d', $h) }}" {{ $opOldHour === sprintf('%02d', $h) ? 'selected' : '' }}>{{ sprintf('%02d', $h) }}</option>
                                        @endfor
                                    </select>
                                    <span class="text-slate-400 font-bold">:</span>
                                    <select id="op_booking_time_minute" class="w-full px-3 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="">Menit</option>
                                        @for($m = 0; $m <= 59; $m++)
                                            <option value="{{ sprintf('%02d', $m) }}" {{ $opOldMinute === sprintf('%02d', $m) ? 'selected' : '' }}>{{ sprintf('%02d', $m) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        <script>
                            (function() {
                                const hidden = document.getElementById('op_booking_time');
                                const hourSel = document.getElementById('op_booking_time_hour');
                                const minSel = document.getElementById('op_booking_time_minute');
                                function sync() {
                                    hidden.value = (hourSel.value && minSel.value) ? `${hourSel.value}:${minSel.value}` : '';
                                }
                                hourSel.addEventListener('change', sync);
                                minSel.addEventListener('change', sync);
                            })();
                        </script>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Layanan</label>
                            <select name="service_category_id" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="">- Pilih Layanan -</option>
                                @foreach($serviceCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($locations->count() > 1)
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                                <select name="location_id" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="">- Lokasi Saya ({{ $location->name }}) -</option>
                                    @foreach($locations as $loc)
                                        @if($loc->id !== $location->id)
                                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                                {{ $loc->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">
                                Tambah Reservasi
                            </button>
                            <a href="{{ route('operator.dashboard') }}" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-lg font-semibold transition-colors">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
