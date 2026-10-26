<x-layout title="Pilih Studio">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex items-center justify-center px-4 py-12">
        <div class="max-w-lg w-full">
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pilih Studio / Loket</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Pilih {{ $stageLabels ?? 'studio' }} tempat Anda bertugas hari ini di <strong>{{ $location->name }}</strong>.
                    Pilihan ini nempel sampai Anda logout — kalau mau ganti sebelum itu, klik "Ganti Studio" di Pintasan.
                </p>
            </div>

            <form method="POST" action="{{ route('operator.set-counter') }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 space-y-4">
                @csrf

                @error('counter_id')
                    <p class="text-red-600 text-sm bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">{{ $message }}</p>
                @enderror

                @if($counters->isEmpty())
                    <p class="text-slate-500 dark:text-slate-400 text-center py-4">
                        Belum ada studio/loket aktif untuk role Anda di lokasi ini. Minta Admin buka Admin &rarr; Lokasi &rarr; Loket dulu.
                    </p>
                @else
                    @php $allTaken = $counters->every(fn ($c) => $c->current_operator_id && $c->current_operator_id !== auth()->id()); @endphp
                    @if($allTaken)
                        <p class="text-amber-600 dark:text-amber-400 text-sm bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2">
                            Semua studio lagi dipakai staf lain. Tunggu sebentar, atau minta salah satu logout dulu.
                        </p>
                    @endif
                    <div class="space-y-2">
                        @foreach($counters as $counter)
                            @php
                                $isMine = $counter->current_operator_id === auth()->id();
                                $taken = $counter->current_operator_id && !$isMine;
                                $isPreferred = $preferredCounterId && $counter->id === $preferredCounterId;
                            @endphp
                            <label class="flex items-center gap-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl p-4 {{ $taken ? 'opacity-50' : 'cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20' }} transition-colors">
                                <input
                                    type="radio"
                                    name="counter_id"
                                    value="{{ $counter->id }}"
                                    {{ $taken ? 'disabled' : '' }}
                                    {{ $isPreferred && !$taken ? 'checked' : '' }}
                                    required
                                    class="text-blue-600"
                                >
                                <span class="flex-1">
                                    <span class="block font-semibold text-slate-900 dark:text-white">
                                        {{ $counter->name }}
                                        @if($isPreferred)
                                            <span class="ml-1 text-xs font-normal text-blue-600 dark:text-blue-400">(favorit Anda)</span>
                                        @endif
                                    </span>
                                    @if($taken)
                                        <span class="block text-xs text-amber-600">Sedang dipakai {{ $counter->currentOperator?->name }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" {{ $allTaken ? 'disabled' : '' }} class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-3 rounded-xl font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        Simpan &amp; Lanjutkan
                    </button>
                @endif
            </form>

            <div class="text-center mt-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                        &larr; Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
