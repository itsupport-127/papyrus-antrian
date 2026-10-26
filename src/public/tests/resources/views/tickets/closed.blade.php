<x-layout :title="$location->name . ' - Tutup'">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-slate-900 mb-2">Lokasi Sedang Tutup</h1>
            <p class="text-slate-600 mb-6">
                {{ $location->name }} tidak menerima antrian saat ini.
            </p>

            <div class="bg-slate-50 rounded-lg p-4 mb-6">
                <p class="text-slate-500 text-sm">Jam Operasional</p>
                <p class="text-lg font-semibold text-slate-900">
                    {{ \Carbon\Carbon::parse($location->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($location->close_time)->format('H:i') }}
                </p>
            </div>

            <a href="{{ route('locations.index') }}" class="inline-block bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium">
                Lihat Lokasi Lain
            </a>
        </div>
    </div>
</x-layout>
