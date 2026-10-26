<x-layout title="Cek Reservasi">
    <div class="min-h-screen bg-gradient-to-b from-periwinkle/60 via-slate-50 to-mint/10 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="{{ route('home') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600">Beranda</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-slate-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-slate-900 dark:text-white font-medium">Cek Reservasi</span>
                    </li>
                </ol>
            </nav>

            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="papyrus-swirl-bg bg-gradient-to-br from-mint to-skyblue text-white p-6 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold">Cek Reservasi</h1>
                    <p class="text-white/85 mt-1">Masukkan kode reservasi Anda</p>
                </div>

                <!-- Form -->
                <div class="p-6">
                    <form method="GET" action="{{ route('bookings.check') }}">
                        <div class="space-y-5">
                            <div>
                                <label for="booking_code" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Kode Reservasi
                                </label>
                                <input
                                    type="text"
                                    id="booking_code"
                                    name="booking_code"
                                    value="{{ request('booking_code') }}"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-mint focus:border-mint transition-colors bg-white dark:bg-slate-700 dark:text-white uppercase tracking-wider text-center text-lg font-mono"
                                    placeholder="BK-CS-001"
                                    required
                                >
                            </div>

                            <button type="submit" class="w-full bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white font-semibold py-3.5 px-6 rounded-xl transition-all duration-200 hover:shadow-lg">
                                Cek Reservasi
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Info Section -->
                <div class="px-6 pb-6">
                    <div class="bg-periwinkle/30 dark:bg-slate-700 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Belum punya reservasi?</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">Pilih lokasi untuk membuat reservasi baru.</p>
                        <a href="{{ route('locations.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium">
                            Lihat semua lokasi →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
