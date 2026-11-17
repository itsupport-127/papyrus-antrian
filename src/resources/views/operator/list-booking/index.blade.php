<x-layout title="List Booking">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-operator-header title="List Booking" subtitle="{{ $location->name }} — jadwal booking, check-in, arsip & Excel" />

        @php
            $statusBadge = function ($b) {
                if ($b->status === 'cancelled') {
                    return ['Batal', 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'];
                }
                if ($b->ticket_id) {
                    return ['Check-in' . ($b->ticket ? ' · ' . $b->ticket->ticket_number : ''), 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'];
                }
                if ($b->source === 'excel' && $b->status === 'completed') {
                    return ['Arsip', 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'];
                }
                if ($b->status === 'completed') {
                    return ['Selesai', 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'];
                }

                return ['Terjadwal', 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'];
            };
            $tabUrl = fn (string $t) => route('operator.list-booking', array_filter(['tab' => $t, 'loc' => $location->id]));
            $inputCls = 'w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm';
        @endphp

        <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
            {{-- Navigasi cepat + lokasi --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <a href="{{ route('operator.dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">&larr; Kembali ke Dashboard</a>
                @if($locations->count() > 1)
                    <form method="GET" action="{{ route('operator.list-booking') }}" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <label class="text-sm text-slate-600 dark:text-slate-300">Lokasi</label>
                        <select name="loc" onchange="this.form.submit()" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" @selected($l->id === $location->id)>{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            {{-- Tab --}}
            <div class="flex items-center gap-1 overflow-x-auto pb-3">
                @foreach($tabs as $key => $label)
                    <a href="{{ $tabUrl($key) }}" class="{{ $tab === $key ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 font-medium' }} px-4 py-2 text-sm rounded-lg whitespace-nowrap shrink-0">
                        {{ $label }}
                        @if($key === 'today' && $counts['today_waiting'] > 0)
                            <span class="ml-1 inline-block bg-amber-500 text-white text-xs rounded-full px-2 py-0.5">{{ $counts['today_waiting'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Import / export / tambah --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('operator.list-booking.create', ['loc' => $location->id]) }}" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-4 py-2 rounded-lg">+ Tambah Booking</a>
                    <a href="{{ route('operator.list-booking.export', array_filter(['tab' => $tab, 'loc' => $location->id, 'month' => $tab === 'all' ? $month : 'all', 'q' => $search, 'status' => $status, 'review' => $onlyReview ? 1 : null])) }}"
                        class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Export Excel</a>
                    <button type="button" onclick="document.getElementById('import-panel').classList.toggle('hidden')"
                        class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Import Excel</button>
                    @if($counts['review'] > 0)
                        <a href="{{ route('operator.list-booking', ['tab' => 'all', 'month' => 'all', 'review' => 1, 'loc' => $location->id]) }}"
                            class="ml-auto text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg">{{ $counts['review'] }} baris perlu dicek</a>
                    @endif
                </div>

                <div id="import-panel" class="hidden mt-4 border-t border-slate-100 dark:border-slate-700 pt-4">
                    <form method="POST" action="{{ route('operator.list-booking.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3"
                        onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Mengimpor...';">
                        @csrf
                        <input type="hidden" name="loc" value="{{ $location->id }}">
                        <div class="flex-1" style="min-width: 16rem;">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">File Excel (.xlsx) — semua sheet dibaca</label>
                            <input type="file" name="file" accept=".xlsx,.xlsm,.xls" required class="{{ $inputCls }}">
                            @error('file')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-5 py-2 rounded-lg">Import ke {{ $location->name }}</button>
                    </form>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Tanggal & jam dirapikan otomatis (mis. hari/bulan tertukar, jam "9.3" jadi 09:30). Booking yang sudah ada dilewati, jadi aman diimpor ulang.
                        Baris yang meragukan tetap masuk dengan tanda <b>Cek</b>. Booking lama masuk sebagai <b>Arsip</b>; hanya booking hari ini dan ke depan yang bisa di-check-in.
                    </p>
                </div>
            </div>

            @if(session('import_skipped'))
                <details class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 mb-4 text-sm text-amber-900 dark:text-amber-200">
                    <summary class="cursor-pointer font-medium">{{ count(session('import_skipped')) }} baris Excel tidak diimpor (klik untuk lihat)</summary>
                    <ul class="mt-2 space-y-1 list-disc pl-5">
                        @foreach(session('import_skipped') as $s)
                            <li>{{ $s['sheet'] }} baris {{ $s['row'] }}: {{ $s['reason'] }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            {{-- Filter (tidak untuk tab Hari Ini) --}}
            @if($tab !== 'today')
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 mb-4">
                    <form method="GET" action="{{ route('operator.list-booking') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="hidden" name="loc" value="{{ $location->id }}">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                            <input type="text" name="q" value="{{ $search }}" placeholder="Nama, kode JG, paket, keterangan..." class="{{ $inputCls }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bulan</label>
                            <select name="month" class="{{ $inputCls }}">
                                <option value="all" @selected($month === 'all')>Semua bulan</option>
                                @foreach($months as $ym => $label)
                                    <option value="{{ $ym }}" @selected($month === $ym)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($tab === 'all')
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                <select name="status" class="{{ $inputCls }}">
                                    <option value="">Semua</option>
                                    <option value="confirmed" @selected($status === 'confirmed')>Terjadwal</option>
                                    <option value="checked_in" @selected($status === 'checked_in')>Check-in</option>
                                    <option value="completed" @selected($status === 'completed')>Selesai / Arsip</option>
                                    <option value="cancelled" @selected($status === 'cancelled')>Batal</option>
                                </select>
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" name="review" value="1" @checked($onlyReview)> Perlu dicek
                            </label>
                            <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose text-white text-sm font-medium px-4 py-2 rounded-lg">Terapkan</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Tabel --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-bold text-slate-900 dark:text-white">
                        {{ $tabs[$tab] }}
                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">— {{ $bookings->total() }} booking</span>
                    </h2>
                    @if($tab === 'today')
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ today()->translatedFormat('l, d F Y') }} · {{ $counts['today_waiting'] }} menunggu check-in</span>
                    @endif
                </div>

                @if($bookings->isEmpty())
                    <p class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                        @if($tab === 'today') Tidak ada booking hari ini. @else Tidak ada data untuk filter ini. @endif
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-left">
                                <tr>
                                    @if($tab !== 'today')<th class="px-3 py-2 font-semibold whitespace-nowrap">Tanggal</th>@endif
                                    <th class="px-3 py-2 font-semibold">Jam</th>
                                    <th class="px-3 py-2 font-semibold">KO / Nama</th>
                                    <th class="px-3 py-2 font-semibold">Paket</th>
                                    <th class="px-3 py-2 font-semibold text-center">Orang</th>
                                    @if($tab === 'checkedin')
                                        <th class="px-3 py-2 font-semibold">Datang</th>
                                        <th class="px-3 py-2 font-semibold">Mulai</th>
                                        <th class="px-3 py-2 font-semibold">Selesai</th>
                                    @endif
                                    <th class="px-3 py-2 font-semibold">Keterangan</th>
                                    <th class="px-3 py-2 font-semibold">Status</th>
                                    <th class="px-3 py-2 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($bookings as $b)
                                    @php
                                        [$badgeText, $badgeCls] = $statusBadge($b);
                                        $t = $b->ticket;
                                        $canCheckIn = !$b->ticket_id && in_array($b->status, ['pending', 'confirmed'], true) && $b->booking_date->isToday();
                                    @endphp
                                    <tr class="{{ $b->needs_review ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' }}">
                                        @if($tab !== 'today')
                                            <td class="px-3 py-2 whitespace-nowrap text-slate-700 dark:text-slate-200">{{ $b->booking_date->format('d-m-Y') }}</td>
                                        @endif
                                        <td class="px-3 py-2 whitespace-nowrap font-semibold text-slate-900 dark:text-white">{{ $b->booking_time->format('H:i') }}</td>
                                        <td class="px-3 py-2">
                                            @if($b->order_code)<span class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ $b->order_code }}</span><br>@endif
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $b->customer_name }}</span>
                                            @if($b->univ)<span class="text-xs text-slate-500 dark:text-slate-400"> · {{ $b->univ }}</span>@endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-700 dark:text-slate-200">{{ $b->package_name ?: '—' }}</td>
                                        <td class="px-3 py-2 text-center text-slate-700 dark:text-slate-200">{{ $b->people_count ?? '—' }}</td>
                                        @if($tab === 'checkedin')
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $b->arrived_time ? substr($b->arrived_time, 0, 5) : ($t?->created_at?->format('H:i') ?? '—') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $b->started_time ? substr($b->started_time, 0, 5) : (($t?->photo_served_at ?? $t?->served_at)?->format('H:i') ?? '—') }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $b->finished_time ? substr($b->finished_time, 0, 5) : (($t?->photo_completed_at ?? $t?->completed_at)?->format('H:i') ?? '—') }}</td>
                                        @endif
                                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300 max-w-xs" style="white-space: pre-line; overflow-wrap: anywhere;">{{ $b->notes }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span class="inline-block text-xs font-medium px-2 py-1 rounded-full {{ $badgeCls }}">{{ $badgeText }}</span>
                                            @if($t && $tab === 'checkedin')
                                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ \App\Models\Ticket::STAGE_LABELS[$t->stage] ?? $t->stage }} · {{ $t->status }}</div>
                                            @endif
                                            @if($b->needs_review)
                                                <span class="inline-block text-xs font-medium px-2 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 cursor-help" title="{{ $b->review_note }}">Cek</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            @if($canCheckIn)
                                                <form method="POST" action="{{ route('operator.bookings.call', $b) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">Check-in</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('operator.list-booking.edit', $b) }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline px-2">Edit</a>
                                            <form method="POST" action="{{ route('operator.list-booking.destroy', $b) }}" class="inline"
                                                onsubmit="return confirm('Hapus booking {{ e(addslashes($b->customer_name)) }} secara permanen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline px-1">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">{{ $bookings->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layout>
