<x-layout :title="'Order - ' . $location->name">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="papyrus-swirl-bg bg-gradient-to-br from-lavender to-dustyrose text-white p-6 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold">{{ $location->name }}</h1>
                    <p class="text-white/85 mt-1">Isi data untuk mengambil antrian order foto</p>
                </div>

                <!-- Queue Info -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-5">
                        <div class="text-center">
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Antrian saat ini</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $location->waitingTickets()->count() }}</p>
                            <p class="text-slate-500 dark:text-slate-400 text-xs">orang menunggu</p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="p-6">
                    <form method="POST" action="{{ route('tickets.store', $location) }}">
                        @csrf

                        <div class="space-y-5">
                            <div>
                                <label for="customer_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Nama
                                </label>
                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    required
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="Masukkan nama Anda"
                                >
                                @error('customer_name')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="customer_phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    No. Telepon
                                </label>
                                <input
                                    type="tel"
                                    id="customer_phone"
                                    name="customer_phone"
                                    value="{{ old('customer_phone') }}"
                                    required
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="08xxxxxxxxxx"
                                >
                                @error('customer_phone')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="customer_email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    value="{{ old('customer_email') }}"
                                    required
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="nama@email.com"
                                >
                                @error('customer_email')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if($serviceCategories->isNotEmpty())
                            <div>
                                <label for="service_category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Layanan
                                </label>
                                <select
                                    id="service_category_id"
                                    name="service_category_id"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                    <option value="">Pilih layanan (opsional)</option>
                                    @foreach($serviceCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('service_category_id')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <button
                            type="submit"
                            class="w-full mt-6 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-blue-200 transition-all hover:shadow-xl"
                        >
                            Order Sekarang
                        </button>
                    </form>

                    <p class="text-slate-500 dark:text-slate-400 text-sm text-center mt-5">
                        Setelah order, Anda akan mendapatkan nomor tiket dan QR untuk memantau status pesanan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layout>
