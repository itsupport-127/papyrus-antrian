{{--
    Tombol "Bayar Duluan" -- pelanggan yang fotonya BELUM selesai diedit
    tapi mau bayar dulu. Dua pilihan, dicatat ke tickets.edit_wait_preference:
      - Tunggu di tempat  ('wait')  -> hasil edit langsung diprioritaskan
      - Pulang dulu       ('leave') -> dikabari lewat notifikasi saat selesai

    Variabel:
      $payFirstTicket  App\Models\Ticket
      $payFirstRoute   nama route: 'operator.skip-to-payment-pending-edit'
                       (tiket berstatus ready_for_edit/editing). Dipakai di
                       panel CS/Kasir; fotografer punya tombol "Edit Menyusul"
                       sendiri di panelnya.
      $payFirstLabel   (opsional) judul kecil di atas tombol

    Dua <form> terpisah (bukan satu form dengan dua tombol) karena submit
    lewat fetch (FormData(form)) TIDAK ikut membawa name/value tombol yang
    diklik -- pilihan harus ada di hidden input.
--}}
@php $pfMsg = 'Pelanggan ' . $payFirstTicket->ticket_number . ' bayar duluan sekarang. Hasil edit dikerjakan menyusul.'; @endphp
<div class="mt-2">
    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ $payFirstLabel ?? 'Pelanggan mau bayar duluan?' }}</p>
    <div class="grid grid-cols-2 gap-2">
        <form method="POST" action="{{ route($payFirstRoute, $payFirstTicket) }}" onsubmit="return confirmFormSubmit(this, { message: '{{ $pfMsg }} Pelanggan menunggu hasil edit di tempat.', variant: 'primary', confirmText: 'Ya, Bayar Duluan' })">
            @csrf
            <input type="hidden" name="wait_preference" value="wait">
            <button type="submit" class="w-full px-2 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors leading-tight">
                Bayar Duluan<span class="block font-normal opacity-90">Tunggu di tempat</span>
            </button>
        </form>
        <form method="POST" action="{{ route($payFirstRoute, $payFirstTicket) }}" onsubmit="return confirmFormSubmit(this, { message: '{{ $pfMsg }} Pelanggan pulang dulu dan dikabari lewat notifikasi saat edit selesai.', variant: 'primary', confirmText: 'Ya, Bayar Duluan' })">
            @csrf
            <input type="hidden" name="wait_preference" value="leave">
            <button type="submit" class="w-full px-2 py-2 rounded-lg text-xs font-semibold border-2 border-emerald-600 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-slate-700 transition-colors leading-tight">
                Bayar Duluan<span class="block font-normal opacity-90">Pulang dulu</span>
            </button>
        </form>
    </div>
</div>
