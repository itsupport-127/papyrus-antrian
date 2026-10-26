{{--
    Baris kecil "catatan Order" di bawah nama pelanggan -- dipakai di semua
    tabel tahap edit (Ditinggal Shift, Sedang Diedit, Siap Diedit, Sudah
    Dibayar-Edit Belum Selesai) biar orang yang ngedit langsung tau APA yang
    mesti dikerjakan (mis. "2X P.P J 2XC.P H BG WARNA BIRU") tanpa buka
    tiketnya satu-satu di tempat lain. Sebelumnya catatan ini SAMA SEKALI
    gak ditampilkan di tabel-tabel ini.

    Variabel: $noteTicket -- instance App\Models\Ticket.
--}}
@if($noteTicket->notes)
    <span class="block text-[11px] text-slate-400 dark:text-slate-500 italic truncate max-w-[220px]" title="{{ $noteTicket->notes }}">📝 {{ $noteTicket->notes }}</span>
@endif
