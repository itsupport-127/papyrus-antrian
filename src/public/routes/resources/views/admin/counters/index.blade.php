<x-layout :title="'Loket - ' . $location->name">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="{{ route('admin.locations') }}" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Lokasi
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Loket {{ $location->name }}</h1>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Add Counter Form -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Tambah Loket Baru</h2>
                <form method="POST" action="{{ route('admin.counters.store', $location) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Loket</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Loket 1">
                        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tahap Panggilan</label>
                        <p class="text-xs text-slate-500 mb-2">Pilih satu atau lebih. Contoh: loket "CS & Kasir" menangani Registrasi sekaligus Pembayaran &amp; Cetak.</p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="stages[]" value="registration" class="rounded border-slate-300">
                                1. Registrasi
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="stages[]" value="photo_session" class="rounded border-slate-300">
                                2. Sesi Foto
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="stages[]" value="payment" class="rounded border-slate-300">
                                3. Pembayaran & Cetak
                            </label>
                        </div>
                        @error('stages')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-2 rounded-lg font-medium">
                        Tambah Loket
                    </button>
                </form>
            </div>

            <!-- Counters List -->
            <div id="counters-list-card" class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Daftar Loket ({{ $counters->count() }})</h2>

                @if($counters->isEmpty())
                    <p class="text-slate-500 text-center py-8">Belum ada loket</p>
                @else
                    <div class="space-y-3">
                        @foreach($counters as $counter)
                            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                <div>
                                    <span class="font-medium text-slate-900">{{ $counter->name }}</span>
                                    <span class="block text-xs text-blue-600 font-semibold">{{ $counter->stageLabel() }}</span>
                                    @if($counter->currentOperator)
                                        <span class="block text-xs text-slate-500">Operator: {{ $counter->currentOperator->name }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-3">
                                    <form method="POST" action="{{ route('admin.counters.toggle', $counter) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="is_active" value="0">
                                        <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1" {{ $counter->is_active ? 'checked' : '' }} class="rounded border-slate-300">
                                            <span class="{{ $counter->is_active ? 'text-green-600' : 'text-slate-500' }}">
                                                {{ $counter->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </label>
                                        <button type="submit" class="text-xs px-3 py-1.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg font-medium">Simpan</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.counters.delete', $counter) }}" onsubmit="return confirm('Yakin ingin menghapus loket ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.autoRefreshIds(['counters-list-card'], 5000);
    </script>
    @endpush
</x-layout>
