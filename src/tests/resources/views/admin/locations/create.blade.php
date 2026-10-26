<x-layout title="Tambah Lokasi">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('admin.locations') }}" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Tambah Lokasi Baru</h1>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <form method="POST" action="{{ route('admin.locations.store') }}">
                @csrf

                <div class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Lokasi *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Customer Service">
                            @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Kode *</label>
                            <input type="text" id="code" name="code" value="{{ old('code') }}" required maxlength="10"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                                placeholder="CS">
                            @error('code')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Deskripsi singkat tentang layanan ini">{{ old('description') }}</textarea>
                        @error('description')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                        <input type="text" id="address" name="address" value="{{ old('address') }}"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Lantai 1, Gedung A">
                        @error('address')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="average_service_time" class="block text-sm font-medium text-slate-700 mb-1">Rata-rata Waktu Layanan (menit) *</label>
                        <input type="number" id="average_service_time" name="average_service_time" value="{{ old('average_service_time', 5) }}" required min="1" max="120"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('average_service_time')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="service_type" class="block text-sm font-medium text-slate-700 mb-1">Layanan yang Dibuka *</label>
                        <select id="service_type" name="service_type" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="both" {{ old('service_type', 'both') === 'both' ? 'selected' : '' }}>Ambil Tiket &amp; Reservasi (dua-duanya)</option>
                            <option value="ticket" {{ old('service_type') === 'ticket' ? 'selected' : '' }}>Ambil Tiket saja (walk-in)</option>
                            <option value="booking" {{ old('service_type') === 'booking' ? 'selected' : '' }}>Reservasi saja</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Menentukan tombol apa yang tampil untuk lokasi ini di halaman publik.</p>
                        @error('service_type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <x-operating-hours-picker
                        :open-time="old('open_time', '08:00')"
                        :close-time="old('close_time', '17:00')" />
                </div>

                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-slate-200">
                    <a href="{{ route('admin.locations') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg font-medium">
                        Simpan Lokasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
