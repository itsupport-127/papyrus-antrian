<x-layout title="Cek Antrian">
    <div class="min-h-screen bg-gradient-to-b from-periwinkle/60 via-slate-50 to-blush/30 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 py-12">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl dark:shadow-slate-900/50 overflow-hidden">
                <!-- Header -->
                <div class="papyrus-swirl-bg bg-gradient-to-br from-lavender to-dustyrose text-white p-8 text-center">
                    <div class="w-16 h-16 bg-white/15 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold">Cek Status Antrian</h1>
                    <p class="text-white/80 mt-2">Masukkan nomor tiket untuk melihat status antrian Anda</p>
                </div>

                <!-- Form -->
                <div class="p-8">
                    <form method="GET" action="{{ route('tickets.check') }}">
                        <div class="mb-6">
                            <label for="ticket_number" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Nomor Tiket
                            </label>
                            <input
                                type="text"
                                id="ticket_number"
                                name="ticket_number"
                                value="{{ request('ticket_number') }}"
                                class="w-full px-6 py-4 border-2 border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-lavender focus:border-lavender text-center text-2xl font-bold tracking-widest uppercase transition-colors"
                                placeholder="CS-001"
                                required
                                autofocus
                            >
                            <p class="text-slate-500 dark:text-slate-400 text-sm text-center mt-2">Format: KODE-NOMOR (contoh: CS-001)</p>
                        </div>

                        <button
                            type="submit"
                            class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-lavender/30 transition-all hover:shadow-xl flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Cek Status
                        </button>
                    </form>
                </div>

                <!-- Divider -->
                <div class="px-8">
                    <div class="border-t border-slate-200 dark:border-slate-700"></div>
                </div>

                <!-- CTA -->
                <div class="p-8 bg-periwinkle/30 dark:bg-slate-900/50 text-center">
                    <p class="text-slate-600 dark:text-slate-400 mb-4">Belum punya tiket?</p>
                    <a href="{{ route('locations.index') }}" class="inline-flex items-center bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-xl font-semibold transition-opacity">
                        Ambil tiket baru
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>
