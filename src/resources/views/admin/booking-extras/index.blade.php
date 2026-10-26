<x-layout title="Tambahan Reservasi">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Tambahan Reservasi" subtitle="Isi dropdown &quot;Tambahan&quot; di form reservasi" />

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full max-w-4xl mx-auto">

                <!-- Tambah pilihan -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Tambah Pilihan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Pilihan baru langsung muncul di dropdown &quot;Tambahan&quot; form reservasi (paling bawah).</p>
                    <form method="POST" action="{{ route('admin.booking-extras.store') }}" class="flex flex-wrap gap-3 items-start">
                        @csrf
                        <div class="flex-1" style="min-width: 14rem;">
                            <input type="text" name="name" value="{{ old('name') }}" maxlength="100" required placeholder="Contoh: Properti"
                                class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
                            Tambah Pilihan
                        </button>
                    </form>
                </div>

                <!-- Daftar pilihan -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Daftar Pilihan ({{ $extras->count() }})</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Urutan di sini = urutan di dropdown. Mengganti nama juga mengubah nama pilihan di reservasi yang sudah ada. Menghapus atau menonaktifkan pilihan tidak mengubah reservasi lama.</p>
                    </div>

                    @if($extras->count() > 0)
                        <div class="divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($extras as $extra)
                                @php $used = $usage[$extra->name] ?? 0; @endphp
                                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.booking-extras.move', $extra) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" {{ $loop->first ? 'disabled' : '' }} title="Naikkan" class="px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" style="{{ $loop->first ? 'opacity: .35; cursor: not-allowed;' : '' }}">▲</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.booking-extras.move', $extra) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" {{ $loop->last ? 'disabled' : '' }} title="Turunkan" class="px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" style="{{ $loop->last ? 'opacity: .35; cursor: not-allowed;' : '' }}">▼</button>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('admin.booking-extras.update', $extra) }}" class="flex flex-1 items-center gap-2" style="min-width: 14rem;">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $extra->name }}" maxlength="100" required
                                            class="flex-1 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm {{ $extra->is_active ? '' : 'opacity-60' }}">
                                        <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">Ganti Nama</button>
                                    </form>

                                    <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $used > 0 ? $used . ' reservasi' : 'belum dipakai' }}</span>

                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.booking-extras.toggle', $extra) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="is_active" value="0">
                                            <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none px-2">
                                                <input type="checkbox" name="is_active" value="1" {{ $extra->is_active ? 'checked' : '' }} class="rounded border-slate-300">
                                                <span class="{{ $extra->is_active ? 'text-green-600' : 'text-slate-500' }}">{{ $extra->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </label>
                                            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white transition-colors">Simpan</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.booking-extras.destroy', $extra) }}" onsubmit="return confirm('Hapus pilihan {{ $extra->name }} dari daftar?{{ $used > 0 ? ' ' . $used . ' reservasi yang sudah memakai pilihan ini tidak berubah.' : '' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <p class="text-slate-500 dark:text-slate-400">Belum ada pilihan.</p>
                            <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Tambahkan pilihan di atas, dropdown di form reservasi akan terisi.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-layout>
