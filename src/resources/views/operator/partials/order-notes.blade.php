{{-- Isi order / note order: SATU info per baris biar gampang dibaca, tampil lengkap.
     Kalau lebih dari $limit baris, sisanya disembunyikan dengan tombol "Selengkapnya" (load more).
     Param: $notes (teks order tiket / reservasi), $tone ('plain'|'blue'), $hide (label yang disembunyikan,
     mis. ['Kode Order'] kalau kodenya sudah tampil di tempat lain), $limit (default 4),
     $prependRows (baris tambahan [label, isi], hanya tampil kalau isinya terisi). --}}
@php
    $raw = trim((string) ($notes ?? ''));
    $tone = $tone ?? 'plain';
    $hide = $hide ?? [];
    $limit = $limit ?? 4;
    $rows = [];

    if ($raw !== '') {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)), fn ($l) => $l !== ''));
        $hasReservation = collect($lines)->contains(fn ($l) => str_starts_with($l, 'Reservasi:'));

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Reservasi:')) {
                $body = trim(substr($line, strlen('Reservasi:')));
                foreach (preg_split('/\s·\s/u', $body) as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }
                    if (preg_match('/^KO\s+(.+)$/u', $part, $m)) {
                        $rows[] = ['Kode Order', $m[1]];
                    } elseif (preg_match('/^(\d+)\s+orang$/u', $part, $m)) {
                        $rows[] = ['Jumlah Orang', $m[1] . ' orang'];
                    } elseif (preg_match('/^Tambahan:\s*(.+)$/u', $part, $m)) {
                        $rows[] = ['Tambahan', $m[1]];
                    } elseif (preg_match('/^\+(\d+)\s+kostum$/u', $part, $m)) {
                        $rows[] = ['Kostum Extra', '+' . $m[1]];
                    } elseif (preg_match('/^DP\b/u', $part)) {
                        $rows[] = ['DP', $part];
                    } else {
                        $rows[] = ['Paket', $part];
                    }
                }
            } else {
                // Baris bebas: kalau tiket reservasi = "Note Order", kalau tiket biasa = isi order apa adanya.
                $rows[] = [$hasReservation ? 'Note Order' : null, $line];
            }
        }

        $rows = array_values(array_filter($rows, fn ($r) => !in_array($r[0], $hide, true)));
    }

    // Baris tambahan dari luar (mis. Email reservasi): hanya yang ADA isinya yang tampil.
    $prependRows = collect($prependRows ?? [])
        ->filter(fn ($r) => filled($r[1] ?? null))
        ->values()
        ->all();
    $rows = array_merge($prependRows, $rows);

    $wrapClass = $tone === 'blue'
        ? 'text-blue-700 dark:text-blue-200 text-xs mt-2 bg-blue-100/60 dark:bg-blue-900/30 rounded-lg px-3 py-2 text-center'
        : 'text-xs text-slate-500 dark:text-slate-400 mt-1';
    $center = $tone === 'blue';
    $extraCount = max(0, count($rows) - $limit);
@endphp
@if($rows)
    {{-- data-notes-key: penanda stabil (dari isi teks) supaya status "Selengkapnya" bisa diingat saat dashboard menyegarkan diri. --}}
    <div class="{{ $wrapClass }}" data-order-notes data-notes-key="{{ md5($raw . '|' . implode(',', $hide) . '|' . json_encode($prependRows)) }}">
        <div class="space-y-0.5">
            @foreach($rows as $i => $row)
                <div class="flex gap-1.5 {{ $center ? 'justify-center text-center' : '' }} {{ $i >= $limit ? 'hidden order-notes-extra' : '' }} {{ $row[0] === 'Note Order' && $i > 0 ? 'pt-1.5' : '' }}">
                    @if($row[0])
                        <span class="{{ $center ? '' : 'w-24' }} flex-shrink-0 opacity-70">{{ $row[0] }}</span>
                        <span class="flex-shrink-0 opacity-70">:</span>
                    @endif
                    <span class="min-w-0 {{ $center ? '' : 'flex-1' }} break-words whitespace-pre-line font-medium">{{ $row[1] }}</span>
                </div>
            @endforeach
        </div>
        @if($extraCount > 0)
            <button type="button" data-more-label="Selengkapnya (+{{ $extraCount }})" class="relative z-10 mt-1 font-semibold underline cursor-pointer {{ $center ? 'block mx-auto' : '' }}">Selengkapnya (+{{ $extraCount }})</button>
        @endif
    </div>
@endif
