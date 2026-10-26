{{-- Antrian "Pilih Foto" (khusus tiket reservasi), dikelola CS & Kasir.
     Alur: sesi foto selesai -> MENUNGGU di sini -> CS "Panggil" -> "Mulai Pilih Foto" (timer jalan)
     -> "Selesai Pilih Foto" -> otomatis ke Pembayaran (edit menyusul).
     Fotografer & Admin juga bisa. Durasi default timer diatur CS & Kasir / Admin (kolom di atas).
     @once: dashboard punya beberapa tata letak yang memuat partial ini; hanya yang pertama dirender. --}}
@once
    @php
        $selectingTickets = $selectingTickets ?? collect();
        $photoSelectMinutes = $photoSelectMinutes ?? 15;
        $canSetSelectTimer = auth()->user()->isOperator() || auth()->user()->isAdmin();
    @endphp
    @if($selectingTickets->isNotEmpty() || $canSetSelectTimer)
        <div id="photo-selection-panel" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Pilih Foto <span class="text-xs font-semibold text-violet-600 dark:text-violet-400 align-middle">Khusus Reservasi</span></h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $selectingTickets->count() }} tiket di tahap Pilih Foto</p>
                </div>
                @if($canSetSelectTimer)
                    <form method="POST" action="{{ route('operator.photo-select-setting') }}" class="flex items-center gap-2">
                        @csrf
                        <label for="photo_select_minutes" class="text-xs font-medium text-slate-600 dark:text-slate-300">Durasi Pilih Foto</label>
                        <input type="number" id="photo_select_minutes" name="photo_select_minutes" min="1" max="120" value="{{ $photoSelectMinutes }}" class="w-20 px-2 py-1.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <span class="text-xs text-slate-500 dark:text-slate-400">menit</span>
                        <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold">Simpan</button>
                    </form>
                @endif
            </div>
            <div class="p-4">
                @if($selectingTickets->isEmpty())
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada tiket reservasi di tahap Pilih Foto. Durasi di atas berlaku untuk tiket berikutnya.</p>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($selectingTickets as $st)
                            @php $state = $st->photoSelectionState(); @endphp
                            <div class="py-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-lg font-bold text-slate-900 dark:text-white">{{ $st->ticket_number }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300 truncate">{{ $st->customer_name ?: '-' }}</p>
                                    @if($st->booking?->order_code)
                                        <p class="font-mono text-xs text-slate-400">{{ $st->booking->order_code }}</p>
                                    @endif
                                </div>
                                <div class="text-center">
                                    @if($state === 'selecting')
                                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Sisa waktu</p>
                                        <p class="font-mono text-2xl font-bold text-violet-700 dark:text-violet-300" data-select-end="{{ $st->select_ends_at?->getTimestamp() }}">--:--</p>
                                    @elseif($state === 'called')
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Sudah dipanggil</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Menunggu dipanggil</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($state === 'waiting' || $state === 'called')
                                        <form method="POST" action="{{ route('operator.call-photo-selection', $st) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 {{ $state === 'waiting' ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'border border-amber-500 text-amber-700 dark:text-amber-400' }} rounded-lg text-xs font-semibold">{{ $state === 'waiting' ? 'Panggil Pilih Foto' : 'Panggil Ulang' }}</button>
                                        </form>
                                    @endif
                                    @if($state === 'called')
                                        <form method="POST" action="{{ route('operator.begin-photo-selection', $st) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold">Mulai Pilih Foto</button>
                                        </form>
                                    @endif
                                    @if($state === 'selecting')
                                        @foreach([5, 10] as $addMin)
                                            <form method="POST" action="{{ route('operator.extend-photo-selection', $st) }}">
                                                @csrf
                                                <input type="hidden" name="minutes" value="{{ $addMin }}">
                                                <button type="submit" class="px-2.5 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold">+{{ $addMin }} menit</button>
                                            </form>
                                        @endforeach
                                        <form method="POST" action="{{ route('operator.finish-photo-selection', $st) }}" onsubmit="return confirmFormSubmit(this, { message: 'Selesai Pilih Foto untuk {{ $st->ticket_number }}? Tiket lanjut ke Pembayaran, hasil edit menyusul.', variant: 'primary', confirmText: 'Ya, ke Pembayaran' })">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold">Selesai Pilih Foto</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <script>
            (function () {
                if (window.__papyrusSelectTimer) return;
                window.__papyrusSelectTimer = setInterval(function () {
                    document.querySelectorAll('[data-select-end]').forEach(function (el) {
                        var end = parseInt(el.getAttribute('data-select-end'), 10);
                        if (!end) return;
                        var left = end - Math.floor(Date.now() / 1000);
                        if (left <= 0) {
                            el.textContent = 'Waktu habis';
                            el.classList.add('text-red-600');
                            return;
                        }
                        var m = Math.floor(left / 60), s = left % 60;
                        el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    });
                }, 1000);
            })();
        </script>
    @endif
@endonce
