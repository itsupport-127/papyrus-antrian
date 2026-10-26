{{-- Detail pelanggan + order di panel-panel edit fotografer (Sedang Diedit,
     Tiket Siap Diedit, Sudah Dibayar-Edit Belum Selesai, Ambil Alih). Param: $ticket.
     Order tampil LENGKAP (gak dipotong) dan Enter tetap jadi baris baru
     (.ticket-order-text = white-space: pre-line, lihat <style> di dashboard).
     Sengaja gak ada spasi/baris baru di dalam <span> order biar gak ikut kebaca
     sebagai baris kosong. --}}
{{-- Urutan tampil: Kode Order (PJG), lalu NAMA (ditonjolkan, kalau withName=true), lalu
     telepon/email dan Order. Nomor tiket ditampilkan oleh pemanggil.
     part: 'all' (default) | 'head' (PJG + nama) | 'rest' (telepon/email + Order) --}}
@php $part = $part ?? 'all'; @endphp
@if($part !== 'rest')
    <span class="block font-mono text-xs font-normal text-slate-400 dark:text-slate-500 mt-0.5">{{ $ticket->order_code }}</span>
    @if($withName ?? false)
        <span class="block text-base font-bold leading-tight break-words mt-0.5">{{ $ticket->customer_name }}</span>
    @endif
@endif
@if($part !== 'head')
@if($ticket->customer_phone || $ticket->customer_email)
    <span class="block text-xs font-normal text-slate-500 dark:text-slate-400 mt-0.5">{{ $ticket->customer_phone }}@if($ticket->customer_phone && $ticket->customer_email) &middot; @endif{{ $ticket->customer_email }}</span>
@endif
@if($ticket->notes)
    {{-- Satu info per baris, lengkap (+ "Selengkapnya" kalau panjang) -- sama seperti kartu lain di dashboard. --}}
    @include('operator.partials.order-notes', ['notes' => $ticket->notes, 'tone' => 'plain'])
@else
    <span class="block text-xs font-normal text-slate-400 dark:text-slate-500 mt-0.5">Order: -</span>
@endif
@endif
