{{-- Kalender List Booking: jumlah booking per hari + penanda hari wisuda kampus (diisi CS sendiri).
     Variabel dari BookingListController@index: $calendar, $location, $campusSuggestions. --}}
@php
    $calUrl = fn (array $extra) => route('operator.list-booking', array_filter(['tab' => 'calendar', 'loc' => $location->id] + $extra));
    $selectedDate = $calendar['selected'];
    $formDate = $selectedDate ?: today()->toDateString();
    $inputCls2 = 'w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm';
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    {{-- Kalender --}}
    <div class="xl:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
            <a href="{{ $calUrl(['month' => $calendar['prev']]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">&larr;</a>
            <h2 class="font-bold text-slate-900 dark:text-white">{{ $calendar['first']->translatedFormat('F Y') }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ $calUrl(['month' => now()->format('Y-m')]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Bulan ini</a>
                <a href="{{ $calUrl(['month' => $calendar['next']]) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">&rarr;</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div style="min-width: 40rem;">
                <div class="grid grid-cols-7 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-700/50">
                    @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)
                        <div class="py-2">{{ $d }}</div>
                    @endforeach
                </div>
                @foreach($calendar['weeks'] as $week)
                    <div class="grid grid-cols-7">
                        @foreach($week as $day)
                            @php
                                $isSelected = $selectedDate === $day['key'];
                                $hasGrad = $day['grads']->isNotEmpty();
                                $cell = $isSelected
                                    ? 'bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-500 ring-inset'
                                    : ($hasGrad ? 'bg-purple-50 dark:bg-purple-900/20' : ($day['in_month'] ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-900/40'));
                            @endphp
                            <a href="{{ $calUrl(['month' => $calendar['first']->format('Y-m'), 'day' => $day['key']]) }}"
                                class="block border-t border-l border-slate-100 dark:border-slate-700 p-1.5 min-h-24 hover:bg-blue-50/60 dark:hover:bg-slate-700/60 {{ $cell }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold {{ $day['is_today'] ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 rounded-full w-6 h-6 inline-flex items-center justify-center' : ($day['in_month'] ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500') }}">{{ $day['date']->day }}</span>
                                    @if($day['total'] > 0)
                                        <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full {{ $day['total'] >= 10 ? 'bg-amber-200 text-amber-900' : 'bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-100' }}">{{ $day['total'] }}</span>
                                    @endif
                                </div>
                                @foreach($day['grads']->take(2) as $g)
                                    <div class="mt-1 text-[11px] leading-tight font-medium text-purple-700 dark:text-purple-300 truncate" title="Wisuda {{ $g->campus }}">🎓 {{ $g->campus }}</div>
                                @endforeach
                                @if($day['grads']->count() > 2)
                                    <div class="text-[11px] text-purple-600 dark:text-purple-300">+{{ $day['grads']->count() - 2 }} kampus</div>
                                @endif
                                @if($day['cancelled'] > 0)
                                    <div class="text-[10px] text-red-500 mt-0.5">{{ $day['cancelled'] }} batal</div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="px-4 py-2 border-t border-slate-100 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
            <span>Angka = jumlah booking (tidak termasuk batal), kuning = 10 atau lebih</span>
            <span>🎓 = hari wisuda kampus</span>
        </div>
    </div>

    {{-- Panel samping --}}
    <div class="space-y-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <h3 class="font-bold text-slate-900 dark:text-white mb-1">Tandai Hari Wisuda</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Isi tanggal wisuda kampus dari jadwal resmi kampusnya. Klik tanggal di kalender untuk mengisi tanggal otomatis.</p>
            <form method="POST" action="{{ route('operator.list-booking.graduation.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="loc" value="{{ $location->id }}">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date', $formDate) }}" required class="{{ $inputCls2 }}">
                    @error('date')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Kampus</label>
                    <input type="text" name="campus" list="campus-list" value="{{ old('campus') }}" maxlength="120" required placeholder="Pilih atau ketik nama kampus" class="{{ $inputCls2 }}">
                    <datalist id="campus-list">@foreach($campusSuggestions as $c)<option value="{{ $c }}">@endforeach</datalist>
                    @error('campus')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Catatan (opsional)</label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="255" placeholder="mis. Wisuda periode II, sesi pagi" class="{{ $inputCls2 }}">
                </div>
                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg">🎓 Tandai Wisuda</button>
            </form>
        </div>

        @if($selectedDate)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <h3 class="font-bold text-slate-900 dark:text-white mb-2">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</h3>

                @forelse($calendar['selected_grads'] as $g)
                    <div class="flex items-start justify-between gap-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg px-3 py-2 mb-2">
                        <div class="text-sm text-purple-800 dark:text-purple-200">🎓 <b>{{ $g->campus }}</b>@if($g->note)<br><span class="text-xs">{{ $g->note }}</span>@endif</div>
                        <form method="POST" action="{{ route('operator.list-booking.graduation.destroy', $g) }}" onsubmit="return confirm('Hapus penanda wisuda ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Belum ada penanda wisuda di tanggal ini.</p>
                @endforelse

                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mt-3 mb-1">{{ $calendar['selected_bookings']->count() }} booking</p>
                <ul class="divide-y divide-slate-100 dark:divide-slate-700 max-h-96 overflow-y-auto text-sm">
                    @foreach($calendar['selected_bookings'] as $b)
                        <li class="py-1.5 flex items-start justify-between gap-2 {{ $b->status === 'cancelled' ? 'opacity-50 line-through' : '' }}">
                            <div>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $b->booking_time->format('H:i') }}</span>
                                <span class="text-slate-800 dark:text-slate-100">{{ $b->customer_name }}</span>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $b->package_name ?: '—' }}@if($b->people_count) · {{ $b->people_count }} orang @endif</div>
                            </div>
                            <a href="{{ route('operator.list-booking.edit', $b) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline shrink-0">Edit</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <h3 class="font-bold text-slate-900 dark:text-white mb-2">Wisuda Mendatang</h3>
            @forelse($calendar['upcoming'] as $g)
                <a href="{{ $calUrl(['month' => $g->date->format('Y-m'), 'day' => $g->date->toDateString()]) }}" class="flex items-center justify-between text-sm py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded px-1">
                    <span class="text-slate-800 dark:text-slate-100">🎓 {{ $g->campus }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $g->date->translatedFormat('d M Y') }}</span>
                </a>
            @empty
                <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada hari wisuda yang ditandai.</p>
            @endforelse
        </div>
    </div>
</div>
