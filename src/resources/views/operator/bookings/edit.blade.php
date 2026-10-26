<x-layout title="Edit Reservasi">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Edit Reservasi {{ $booking->customer_name }}</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Ubah detail reservasi di bawah ini.</p>
                    </div>

                    <form method="POST" action="{{ route('operator.bookings.update', $booking) }}" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', $booking->customer_name) }}" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                            <input type="text" name="customer_phone" value="{{ old('customer_phone', $booking->customer_phone) }}" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email (opsional)</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', $booking->customer_email) }}"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                <input type="date" name="booking_date" value="{{ old('booking_date', $booking->booking_date->format('Y-m-d')) }}" min="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam</label>
                                <input type="time" name="booking_time" value="{{ old('booking_time', $booking->booking_time->format('H:i')) }}" required
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jumlah Orang di Foto</label>
                                <input type="number" name="people_count" value="{{ old('people_count', $booking->people_count) }}" min="1" max="{{ config('booking.max_people', 500) }}" inputmode="numeric" placeholder="mis. 6"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tambahan Pergantian Kostum</label>
                                <input type="number" name="costume_changes" value="{{ old('costume_changes', $booking->costume_changes) }}" min="0" max="{{ config('booking.max_costume_changes', 20) }}" inputmode="numeric" placeholder="0"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilihan Paket</label>
                            <select name="package_name" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="">Belum pilih paket</option>
                            {{-- Paket tersimpan yang sudah tidak ada di daftar tetap ditampilkan, supaya tidak hilang diam-diam saat disimpan. --}}
                            @if($booking->package_name && !in_array($booking->package_name, \App\Models\BookingPackage::activeNames(), true))
                                <option value="{{ $booking->package_name }}" selected>{{ $booking->package_name }}</option>
                            @endif
                                @foreach(\App\Models\BookingPackage::activeNames() as $pk)
                                    <option value="{{ $pk }}" {{ old('package_name', $booking->package_name) === $pk ? 'selected' : '' }}>{{ $pk }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">DP (Rp)</label>
                                <input type="number" name="dp_amount" value="{{ old('dp_amount', $booking->dp_amount) }}" min="0" step="1000" inputmode="numeric" placeholder="mis. 50000"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <p class="text-xs text-slate-400 mt-1">Ketentuan DP minimal Rp 50.000. Kalau dibatalkan, DP otomatis hangus.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Order (opsional)</label>
                                <input type="text" name="order_code" value="{{ old('order_code', $booking->order_code) }}" maxlength="50" placeholder="mis. JG035474"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                        {{-- Field "Layanan" dihapus -- lihat catatan yang sama di
                             modal Tambah Reservasi (operator/dashboard.blade.php).
                             Kalau reservasi ini kebetulan udah punya
                             service_category_id dari sebelumnya (mis. reservasi
                             yang dibuat pelanggan sendiri lewat form online),
                             nilainya TETAP kesimpen apa adanya -- form ini cuma
                             gak nampilin/gak ngubahnya lagi, bukan ngosongin. --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Order</label>
                            <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none">{{ old('notes', $booking->notes) }}</textarea>
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">
                                Simpan Perubahan
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
