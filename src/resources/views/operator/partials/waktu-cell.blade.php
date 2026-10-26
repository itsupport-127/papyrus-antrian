{{--
    Partial isi kolom "Waktu". Dipakai bareng di tabel Reservasi & tabel
    Tiket (tab Data Pelanggan, operator/dashboard). Terima 1 variabel:
    $waktuTicket -- instance App\Models\Ticket, boleh null (mis. reservasi
    yang belum ada tiketnya / belum check-in) -> tampil '-' aja.

    Tampilan dibikin gampang dibaca:
    - Total besar di atas.
    - Tahap yang DIKERJAKAN staf (Registrasi, Sesi Foto, Edit, Bayar) = teks
      tebal + nama staf kecil di bawah namanya.
    - Tahap MENUNGGU (Tunggu Foto, Tunggu Bayar) = abu-abu miring, biar
      kelihatan bedanya dari waktu kerja.
    - Jam pembayaran = 4 kotak kecil di bawah (bukan daftar panjang), dan
      TIDAK mengulang total tunggu/proses bayar yang sudah ada di atas.
--}}
@php
    $waktu = $waktuTicket?->stageDurations();
    // key segmen -> [relasi staf, label peran]. Nama staf cuma ditampilkan
    // kalau relasinya sudah di-eager-load (tabel Tiket) -- tabel lain
    // (mis. Reservasi) gak perlu N+1 query cuma buat nama staf.
    $wkStaffMap = [
        'registration' => ['registeredBy', 'CS'],
        'photo_session' => ['photographedBy', 'Fotografer'],
        'editing' => ['editedBy', 'Editor'],
        'payment_processing' => ['paidBy', 'Kasir'],
    ];
    $wkLabels = ['payment_processing' => 'Bayar'];
    $wkWaiting = ['photo_wait', 'payment_wait'];
@endphp
@if(!$waktuTicket || !$waktu)
    <span class="text-slate-400 dark:text-slate-500">-</span>
@else
    <div class="flex items-baseline gap-1.5">
        <span class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Total</span>
        <span class="font-bold text-slate-900 dark:text-white text-base">
            @if($waktu['total']['ongoing'])
                <span class="waktu-live" data-start="{{ $waktu['total']['start']->timestamp * 1000 }}">{{ \App\Models\Ticket::formatDuration($waktu['total']['seconds']) }}</span>
            @else
                {{ \App\Models\Ticket::formatDuration($waktu['total']['seconds']) }}
            @endif
        </span>
    </div>
    <ul class="mt-2 space-y-1.5">
        @foreach($waktu['segments'] as $s)
            @php
                $isWait = in_array($s['key'], $wkWaiting, true);
                $staffUser = null;
                $staffRole = null;
                if (isset($wkStaffMap[$s['key']]) && $waktuTicket->relationLoaded($wkStaffMap[$s['key']][0])) {
                    $staffUser = $waktuTicket->{$wkStaffMap[$s['key']][0]};
                    $staffRole = $wkStaffMap[$s['key']][1];
                }
            @endphp
            <li class="flex items-start justify-between gap-4 {{ $isWait ? 'pl-3' : '' }}">
                <div class="leading-tight">
                    <span class="{{ $isWait ? 'text-xs italic text-slate-400 dark:text-slate-500' : 'text-sm font-semibold text-slate-800 dark:text-slate-100' }}">{{ $wkLabels[$s['key']] ?? $s['label'] }}</span>
                    @if($staffUser)
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">{{ $staffRole }}: {{ $staffUser->name }}</span>
                    @endif
                </div>
                <span class="whitespace-nowrap {{ $isWait ? 'text-xs italic' : 'text-sm font-semibold' }} {{ $s['ongoing'] ? 'text-blue-600 dark:text-blue-400' : ($isWait ? 'text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-100') }}">
                    @if(!$s['available'])
                        -
                    @elseif($s['ongoing'])
                        <span class="waktu-live" data-start="{{ $s['start']->timestamp * 1000 }}">{{ \App\Models\Ticket::formatDuration($s['seconds']) }}</span>
                    @else
                        {{ \App\Models\Ticket::formatDuration($s['seconds']) }}
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
    @if($waktuTicket->payment_called_at || $waktuTicket->payment_served_at || $waktuTicket->payment_completed_at)
        <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-700">
            <div class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1">Jam Pembayaran</div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px]">
                @foreach([
                    'Selesai foto' => $waktuTicket->photo_completed_at,
                    'Dipanggil kasir' => $waktuTicket->payment_called_at,
                    'Mulai bayar' => $waktuTicket->payment_served_at,
                    'Selesai bayar' => $waktuTicket->payment_completed_at,
                ] as $lbl => $jam)
                    <div class="leading-tight">
                        <span class="block text-slate-400 dark:text-slate-500">{{ $lbl }}</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $jam?->format('H:i:s') ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif
