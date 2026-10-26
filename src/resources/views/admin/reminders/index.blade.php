<x-layout title="Pengingat">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Pengingat" subtitle="Atur pengingat suara &amp; pesan di dashboard operator" />

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full max-w-4xl mx-auto">

                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-6">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Cara kerjanya</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-300">Kalau sebuah tiket menggantung di salah satu kondisi di bawah, dashboard operator yang bersangkutan membunyikan <span class="font-semibold">nada pengingat</span> dan menampilkan <span class="font-semibold">pesan di pojok kanan atas</span> (plus notifikasi browser kalau sudah diizinkan). Pengingat mulai setelah <span class="font-semibold">Mulai setelah</span> menit, lalu diulang tiap <span class="font-semibold">Ulangi setiap</span> menit, dan berhenti sendiri begitu tiketnya diselesaikan. Hanya 3 hal ini yang diingatkan, yang lain tidak.</p>
                </div>

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-xl border border-red-300 bg-red-50 text-red-700 text-sm">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.reminders.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        @foreach($types as $type => $meta)
                            @php $s = $settings[$type]; @endphp
                            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div style="min-width: 14rem; flex: 1;">
                                        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $meta['label'] }}</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Untuk: <span class="font-semibold">{{ $meta['who'] }}</span></p>
                                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-2">{{ $meta['desc'] }}</p>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                                        <input type="hidden" name="settings[{{ $type }}][enabled]" value="0">
                                        <input type="checkbox" name="settings[{{ $type }}][enabled]" value="1" {{ old("settings.$type.enabled", $s['enabled']) ? 'checked' : '' }} class="rounded border-slate-300">
                                        <span class="text-slate-700 dark:text-slate-200">Aktif</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mulai setelah (menit)</label>
                                        <input type="number" name="settings[{{ $type }}][start_after_minutes]" value="{{ old("settings.$type.start_after_minutes", $s['start_after_minutes']) }}" min="0" max="240" required
                                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        <p class="text-xs text-slate-400 mt-1">0 = langsung begitu tiket masuk kondisi ini.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ulangi setiap (menit)</label>
                                        <input type="number" name="settings[{{ $type }}][interval_minutes]" value="{{ old("settings.$type.interval_minutes", $s['interval_minutes']) }}" min="1" max="240" required
                                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        <p class="text-xs text-slate-400 mt-1">Jeda antar pengingat selama tiket belum diselesaikan.</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">Simpan Pengaturan</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-layout>
