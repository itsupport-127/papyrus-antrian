<x-layout title="Edit Reservasi">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Edit Reservasi {{ $booking->booking_code }}</h2>
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
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
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
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Layanan</label>
                            <select name="service_category_id" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="">- Pilih Layanan -</option>
                                @foreach($serviceCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('service_category_id', $booking->service_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Catatan</label>
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
