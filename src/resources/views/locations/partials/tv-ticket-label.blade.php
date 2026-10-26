{{-- Label tiket di layar TV.
     - Tiket biasa (PP): nomor tiketnya, seperti sebelumnya.
     - Tiket RESERVASI: nama (maks. 2 kata) + kode order, mis. "Yoga" / "JG035474".
     Param: $ticket, $mode ('stack' = dua baris untuk kartu besar, 'inline' = satu baris untuk daftar). --}}
@php
    $tvLbl = $ticket->tvLabel();
    $tvMode = $mode ?? 'stack';
@endphp
@if($tvLbl)
    @if($tvMode === 'inline')
        {{-- Nama + kode order di kolom Nomor: sebaris kalau muat, kalau tidak kode turun ke baris bawah. --}}
        <span class="inline-flex flex-wrap items-baseline gap-x-1.5 min-w-0 leading-tight"><span class="break-words">{{ $tvLbl['name'] }}</span>@if($tvLbl['code'])<span class="font-mono opacity-80" style="font-size:.88em">{{ $tvLbl['code'] }}</span>@endif</span>
    @else
        <span class="block leading-tight break-words" style="font-size:.72em">{{ $tvLbl['name'] }}</span>
        @if($tvLbl['code'])
            <span class="block font-mono leading-tight opacity-80" style="font-size:.5em">{{ $tvLbl['code'] }}</span>
        @endif
    @endif
@else
    {{ $ticket->ticket_number }}
@endif
