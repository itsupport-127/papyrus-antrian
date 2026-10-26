<x-layout :title="'Reservasi ' . $booking->booking_code">
    <div class="min-h-screen bg-gradient-to-b from-periwinkle/60 via-slate-50 to-mint/10 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="{{ route('home') }}" class="text-slate-500 dark:text-slate-400 hover:text-emerald-600">Beranda</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-slate-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-slate-900 dark:text-white font-medium">Detail Reservasi</span>
                    </li>
                </ol>
            </nav>

            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                @php
                    $statusColor = match($booking->status) {
                        'pending' => 'bg-amber-500',
                        'confirmed' => 'bg-emerald-600',
                        'checked_in' => 'bg-skyblue',
                        'completed' => 'bg-slate-600',
                        'cancelled' => 'bg-red-500',
                        default => 'bg-slate-500',
                    };
                    $statusLabel = match($booking->status) {
                        'pending' => 'Menunggu Konfirmasi',
                        'confirmed' => 'Dikonfirmasi',
                        'checked_in' => 'Sudah Check-in',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => $booking->status,
                    };
                @endphp

                <div class="{{ $statusColor }} text-white p-6 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold font-mono tracking-wider">{{ $booking->booking_code }}</h1>
                    <span class="inline-flex items-center mt-2 bg-white/20 text-white text-sm font-medium px-3 py-1 rounded-full">
                        {{ $statusLabel }}
                    </span>
                </div>

                <!-- Booking Details -->
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Lokasi</p>
                            <p class="font-semibold text-slate-900 dark:text-white mt-1">{{ $booking->location->name }}</p>
                        </div>
                        @if($booking->serviceCategory)
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Kategori</p>
                            <p class="font-semibold text-slate-900 dark:text-white mt-1">{{ $booking->serviceCategory->name }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Tanggal</p>
                            <p class="font-semibold text-slate-900 dark:text-white mt-1">{{ $booking->booking_date->format('d M Y') }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Waktu</p>
                            <p class="font-semibold text-slate-900 dark:text-white mt-1">{{ is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format('H:i') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Nama</p>
                            <p class="font-semibold text-slate-900 dark:text-white mt-1">{{ $booking->customer_name }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Telepon</p>
                            <p class="font-semibold text-slate-900 dark:text-white mt-1">{{ $booking->customer_phone }}</p>
                        </div>
                    </div>

                    @if($booking->ticket)
                    <div class="bg-lavender/10 dark:bg-lavender/10 border border-lavender/30 dark:border-lavender/30 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-lavender dark:text-lavender">Tiket Antrian</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ $booking->ticket->ticket_number }}</p>
                            </div>
                            <a href="{{ route('tickets.show', $booking->ticket) }}" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-4 py-2 rounded-lg transition-opacity">
                                Lihat Tiket
                            </a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="px-6 pb-6 space-y-3">
                    @php $isStaff = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isOperator()); @endphp

                    @if($isStaff && in_array($booking->status, ['pending', 'confirmed']) && $booking->booking_date->isToday())
                    <form method="POST" action="{{ route('bookings.check-in', $booking) }}">
                        @csrf
                        <button type="submit" class="w-full bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white font-semibold py-3.5 px-6 rounded-xl transition-all duration-200 hover:shadow-lg flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Check-in Sekarang (CS/Admin)</span>
                        </button>
                    </form>
                    @endif

                    @if(!in_array($booking->status, ['checked_in', 'completed', 'cancelled']))
                        @if($isStaff)
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Yakin ingin membatalkan reservasi ini?')">
                                @csrf
                                <button type="submit" class="w-full bg-white dark:bg-slate-700 border-2 border-red-300 dark:border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 font-semibold py-3 px-6 rounded-xl transition-all duration-200">
                                    Batalkan Reservasi (CS/Admin)
                                </button>
                            </form>
                        @else
                            <div class="bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl p-4 text-sm text-slate-600 dark:text-slate-300">
                                Reservasi ini dikelola oleh petugas kami. Kalau mau mengubah jadwal atau membatalkan, silakan hubungi CS di lokasi.
                            </div>
                        @endif
                    @endif

                    {{-- Ubah Jadwal — cuma CS & Kasir dan Admin yang bisa lihat & pakai ini,
                         supaya pelanggan yang mau pindah jadwal tidak perlu reservasi ulang. --}}
                    @if($isStaff && !in_array($booking->status, ['checked_in', 'completed', 'cancelled']))
                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-600 rounded-xl p-4" x-data="{ showReschedule: false }">
                            <button type="button" @click="showReschedule = !showReschedule" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                <span x-text="showReschedule ? 'Tutup form ubah jadwal' : '✎ Ubah Jadwal (CS/Admin)'"></span>
                            </button>
                            <form x-show="showReschedule" x-cloak method="POST" action="{{ route('bookings.reschedule', $booking) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Tanggal Baru</label>
                                    <input type="date" name="booking_date" value="{{ old('booking_date', $booking->booking_date->toDateString()) }}" min="{{ now()->toDateString() }}" required
                                        class="w-full px-3 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Jam Baru</label>
                                    <input type="time" name="booking_time" value="{{ old('booking_time', $booking->booking_time->format('H:i')) }}"
                                        min="{{ $booking->location->open_time->format('H:i') }}" max="{{ $booking->location->close_time->format('H:i') }}" required
                                        class="w-full px-3 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                                </div>
                                <div class="col-span-2">
                                    <button type="submit" class="w-full bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                                        Simpan Jadwal Baru
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- QR Code -->
                    <div class="text-center pt-4 border-t border-slate-100 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">QR Code Reservasi</p>
                        <div id="booking-qr" class="inline-block"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById('booking-qr'), {
                    text: '{{ route('bookings.show', $booking) }}',
                    width: 120,
                    height: 120,
                    colorDark: '#1e293b',
                    colorLight: '#ffffff',
                });
            }
        });
    </script>
    @endpush
</x-layout>
