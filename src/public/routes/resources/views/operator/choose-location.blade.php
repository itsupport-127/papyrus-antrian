<x-layout title="Pilih Lokasi — Panel CS">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex items-center justify-center px-4 py-12">
        <div class="max-w-lg w-full">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Masuk Panel CS</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Pilih lokasi tempat Anda mau bertugas sebagai CS & Kasir. Anda bisa berpindah lokasi kapan saja.</p>
            </div>

            <form method="POST" action="{{ route('operator.set-location') }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 space-y-4">
                @csrf
                @if($locations->isEmpty())
                    <p class="text-slate-500 dark:text-slate-400 text-center py-4">Belum ada lokasi aktif. Tambahkan lokasi dulu di panel admin.</p>
                @else
                    <div class="space-y-2">
                        @foreach($locations as $location)
                            <label class="flex items-center gap-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl p-4 cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 transition-colors">
                                <input type="radio" name="location_id" value="{{ $location->id }}" required class="text-blue-600">
                                <span>
                                    <span class="block font-semibold text-slate-900 dark:text-white">{{ $location->name }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $location->code }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-3 rounded-xl font-semibold transition-colors">
                        Masuk Panel CS
                    </button>
                @endif
            </form>

            <div class="text-center mt-6">
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                    &larr; Kembali ke Panel Admin
                </a>
            </div>
        </div>
    </div>
</x-layout>
