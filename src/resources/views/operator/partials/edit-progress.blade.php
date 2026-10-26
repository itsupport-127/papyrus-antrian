{{-- Estimasi selesai + catatan progres edit (khusus tiket RESERVASI yang edit-nya menyusul).
     Yang diisi di sini tampil di tiket pelanggan (langkah "Edit"). Param: $ticket. --}}
@if($ticket->isReservation() && $ticket->payment_skipped_pending_edit_at && !$ticket->edit_completed_at)
    @php
        $etaLate = $ticket->edit_eta_date && $ticket->edit_eta_date->lt(today());
    @endphp
    <div class="mt-2 text-left">
        <p class="text-xs">
            @if($ticket->edit_eta_date)
                Estimasi: <span class="font-semibold">{{ $ticket->edit_eta_date->isToday() ? 'hari ini' : $ticket->edit_eta_date->translatedFormat('d M') }}</span>
                @if($etaLate)
                    <span class="inline-flex px-1.5 py-0.5 rounded bg-red-100 text-red-700 text-[10px] font-semibold ml-1">Terlambat {{ $ticket->edit_eta_date->diffInDays(today()) }} hari</span>
                @endif
            @else
                <span class="text-slate-400">Estimasi belum diisi</span>
            @endif
            @if($ticket->edit_note)
                <span class="block text-slate-500 dark:text-slate-400">{{ $ticket->edit_note }}</span>
            @endif
        </p>
        <form method="POST" action="{{ route('operator.edit-progress', $ticket) }}" class="mt-1 flex flex-wrap items-center gap-1.5">
            @csrf
            <input type="date" name="edit_eta_date" value="{{ $ticket->edit_eta_date?->toDateString() }}" class="px-2 py-1 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-700 text-xs">
            <input type="text" name="edit_note" value="{{ $ticket->edit_note }}" maxlength="255" placeholder="Catatan progres (mis. sedang retouch)" class="px-2 py-1 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-700 text-xs w-56">
            <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200">Simpan</button>
        </form>
    </div>
@endif
