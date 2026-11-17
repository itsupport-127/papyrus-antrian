@php
    $isEdit = $booking->exists;
    $field = fn (string $name, $default = null) => old($name, $default);
    $cls = 'w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white';
    $time = fn ($v) => $v ? substr((string) $v, 0, 5) : '';
@endphp
<x-layout title="{{ $isEdit ? 'Edit Booking' : 'Tambah Booking' }}">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="max-w-2xl mx-auto">
                <a href="{{ route('operator.list-booking', ['tab' => 'all', 'loc' => $location?->id]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">&larr; Kembali ke List Booking</a>

                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mt-3">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $isEdit ? 'Edit Booking ' . $booking->customer_name : 'Tambah Booking' }}</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ $location?->name }}@if($isEdit && $booking->ticket_id) · sudah check-in, status diatur oleh antrian @endif</p>
                    </div>

                    @if($errors->any())
                        <div class="mx-6 mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm rounded-lg p-3">
                            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $isEdit ? route('operator.list-booking.update', $booking) : route('operator.list-booking.store') }}" class="p-6 space-y-4">
                        @csrf
                        @if($isEdit) @method('PUT') @else <input type="hidden" name="loc" value="{{ $location?->id }}"> @endif

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Order (KO)</label>
                                <input type="text" name="order_code" value="{{ $field('order_code', $booking->order_code) }}" maxlength="50" placeholder="JG035474" class="{{ $cls }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan</label>
                                <input type="text" name="customer_name" value="{{ $field('customer_name', $booking->customer_name) }}" required class="{{ $cls }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Booking</label>
                                <input type="date" name="booking_date" value="{{ $field('booking_date', $booking->booking_date?->format('Y-m-d')) }}" required class="{{ $cls }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Booking</label>
                                <input type="time" name="booking_time" value="{{ $field('booking_time', $booking->booking_time?->format('H:i')) }}" required class="{{ $cls }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Paket</label>
                                <input type="text" name="package_name" list="package-list" value="{{ $field('package_name', $booking->package_name) }}" maxlength="100" class="{{ $cls }}">
                                <datalist id="package-list">@foreach($packages as $p)<option value="{{ $p }}">@endforeach</datalist>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jumlah Orang</label>
                                <input type="number" name="people_count" min="0" max="1000" value="{{ $field('people_count', $booking->people_count) }}" class="{{ $cls }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon (opsional)</label>
                                <input type="text" name="customer_phone" value="{{ $field('customer_phone', $booking->customer_phone) }}" class="{{ $cls }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">UNIV / Instansi (opsional)</label>
                                <input type="text" name="univ" value="{{ $field('univ', $booking->univ) }}" maxlength="100" class="{{ $cls }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach(['arrived_time' => 'Datang', 'started_time' => 'Mulai', 'finished_time' => 'Selesai'] as $name => $label)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>
                                    <input type="time" name="{{ $name }}" value="{{ $field($name, $time($booking->$name)) }}" class="{{ $cls }}">
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Keterangan</label>
                            <textarea name="notes" rows="3" maxlength="2000" class="{{ $cls }}">{{ $field('notes', $booking->notes) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                @if($isEdit && $booking->ticket_id)
                                    <input type="hidden" name="status" value="{{ $booking->status }}">
                                    <p class="text-sm text-slate-600 dark:text-slate-300 py-2">{{ $booking->status }} (mengikuti tiket)</p>
                                @else
                                    <select name="status" class="{{ $cls }}">
                                        @foreach(['confirmed' => 'Terjadwal', 'pending' => 'Menunggu konfirmasi', 'completed' => 'Selesai / Arsip', 'cancelled' => 'Batal'] as $v => $l)
                                            <option value="{{ $v }}" @selected($field('status', $booking->status) === $v)>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            @if($isEdit && $booking->needs_review)
                                <div>
                                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" name="needs_review" value="1" @checked(old('needs_review', true))> Masih perlu dicek
                                    </label>
                                    <input type="hidden" name="review_note" value="{{ $booking->review_note }}">
                                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ $booking->review_note }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white font-medium px-6 py-2.5 rounded-lg">Simpan</button>
                            <a href="{{ route('operator.list-booking', ['tab' => 'all', 'loc' => $location?->id]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
