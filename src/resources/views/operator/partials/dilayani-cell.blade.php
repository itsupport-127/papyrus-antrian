{{--
    Partial isi kolom "Dilayani Oleh" (tab Data Pelanggan > tabel Tiket).
    Terima 1 variabel: $dilayaniTicket -- instance App\Models\Ticket.

    SENGAJA cuma nama & peran (tanpa durasi) -- durasi per tahap sudah ada
    di kolom "Waktu" tepat di sebelah (lengkap dengan nama staf), jadi
    angkanya gak dobel & gak bikin bingung. Kotak biru "Sekarang" nunjukin
    siapa yang lagi memegang tiket & di loket mana.
--}}
@php
    $dlStaff = collect($dilayaniTicket->stageStaff())->filter(fn ($s) => $s['user']);
    $dlActive = in_array($dilayaniTicket->status, ['calling', 'serving', 'editing', 'preparing'], true) && $dilayaniTicket->servedBy;
@endphp
@if($dlStaff->isEmpty() && !$dlActive)
    <span class="text-slate-400 dark:text-slate-500">-</span>
@else
    @if($dlActive)
        <div class="mb-2 px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
            <span class="text-[10px] uppercase tracking-wide text-blue-500 dark:text-blue-400">Sedang dipegang</span>
            <span class="block text-sm font-semibold text-blue-700 dark:text-blue-300">{{ $dilayaniTicket->servedBy->name }}</span>
            @if($dilayaniTicket->counter)
                <span class="block text-[11px] text-blue-500 dark:text-blue-400">{{ $dilayaniTicket->counter->name }}</span>
            @endif
        </div>
    @endif
    <ul class="space-y-1.5">
        @foreach($dlStaff as $s)
            <li class="leading-tight">
                <span class="block text-[11px] text-slate-400 dark:text-slate-500">{{ $s['label'] }}</span>
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $s['user']->name }}</span>
            </li>
        @endforeach
    </ul>
@endif
