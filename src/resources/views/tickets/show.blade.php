<x-layout :title="'Tiket ' . $ticket->ticket_number">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="{{ route('home') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600">Beranda</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-slate-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-slate-900 dark:text-white font-medium">Tiket {{ $ticket->ticket_number }}</span>
                    </li>
                </ol>
            </nav>

            {{-- Tracker lama (Registrasi, Siap-siap, Sesi Foto, ...) cuma buat tiket PP. Tiket RESERVASI memakai
                 progres sendiri (Sesi foto, Pilih foto, Pembayaran, Edit, Siap diambil) di dalam tiket,
                 supaya tidak ada dua indikator proses. --}}
            @unless($ticket->isReservation())
            <!-- Stage Tracker -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-4 sm:p-6 mb-6">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4 text-center">Progres Pesanan Anda</p>
                <div id="ticket-stage-tracker" class="flex items-start justify-between gap-0.5 sm:gap-1">
                    @php
                        if ($ticket->stage === 'registration') {
                            $currentIndex = 0;
                        } elseif ($ticket->stage === 'photo_session') {
                            $currentIndex = match ($ticket->status) {
                                'calling' => 2,
                                'serving', 'editing', 'ready_for_edit' => 3,
                                default => 1, // waiting / preparing
                            };
                        } else {
                            $currentIndex = 4;
                        }

                        $nodes = [
                            ['label' => 'Registrasi'],
                            ['label' => 'Siap-siap'],
                            ['label' => 'Sesi Foto'],
                            ['label' => $currentIndex > 3 ? 'Foto Selesai' : 'Proses Foto'],
                            ['label' => 'Pembayaran & Ambil Cetak Foto'],
                        ];

                        $isDone = $ticket->status === 'completed';
                    @endphp
                    @foreach($nodes as $i => $node)
                        @php
                            $isCallingRegHere = $i === 0 && $ticket->stage === 'registration' && $ticket->status === 'calling';
                            $isServingRegHere = $i === 0 && $ticket->stage === 'registration' && $ticket->status === 'serving';
                            $isPreparingHere = $i === 1 && $ticket->stage === 'photo_session' && $ticket->status === 'preparing';
                            // BUG YANG DIBENERIN: dulu status 'waiting' DAN
                            // 'preparing' sama-sama masuk $currentIndex=1,
                            // jadi node "Siap-siap" nyala aktif walau
                            // fotografer BELUM benar-benar manggil "Siap-siap"
                            // (masih di Daftar Tunggu biasa). Sekarang
                            // dibedain: 'waiting' di sini render sebagai node
                            // netral (belum nyala), BUKAN ikut gaya "aktif".
                            $isWaitingForPhotoHere = $i === 1 && $ticket->stage === 'photo_session' && $ticket->status === 'waiting';
                            $isCallingHere = $i === 2 && $ticket->stage === 'photo_session' && $ticket->status === 'calling';
                            $isServingHere = $i === 3 && $ticket->stage === 'photo_session' && $ticket->status === 'serving';
                            $isEditingHere = $i === 3 && $ticket->stage === 'photo_session' && $ticket->status === 'editing';
                            $isReadyForEditHere = $i === 3 && $ticket->stage === 'photo_session' && $ticket->status === 'ready_for_edit';
                            $isCallingPayHere = $i === 4 && $ticket->stage === 'payment' && $ticket->status === 'calling';
                            $isServingPayHere = $i === 4 && $ticket->stage === 'payment' && $ticket->status === 'serving';
                            // Sama kayak $isWaitingForPhotoHere -- sebelum
                            // beneran dipanggil kasir, node ini SENGAJA
                            // dibikin abu-abu netral, bukan biru muda
                            // (yang keliatan kayak "lagi aktif") padahal
                            // masih di antrian tunggu, belum dipanggil.
                            $isWaitingForPaymentHere = $i === 4 && $ticket->stage === 'payment' && $ticket->status === 'waiting';
                            $nodeDone = $i < $currentIndex || ($i === $currentIndex && $isDone);
                        @endphp
                        <div class="flex-1 flex flex-col items-center relative min-w-0">
                            @if($i > 0)
                                <div class="absolute top-3.5 sm:top-4 right-1/2 w-full h-1 -z-10 {{ $i <= $currentIndex ? 'bg-blue-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                            @endif
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0
                                {{ ($isCallingRegHere || $isCallingPayHere) ? 'bg-blue-500 text-white animate-pulse' : ($isServingRegHere ? 'bg-amber-500 text-white animate-pulse' : ($isPreparingHere ? 'bg-purple-600 text-white animate-pulse' : ($isWaitingForPhotoHere ? 'bg-slate-200 dark:bg-slate-700 text-slate-400' : ($isCallingHere ? 'bg-blue-500 text-white animate-pulse' : ($isServingHere ? 'bg-amber-500 text-white animate-pulse' : ($isEditingHere ? 'bg-indigo-500 text-white animate-pulse' : ($isReadyForEditHere ? 'bg-slate-400 text-white' : ($isServingPayHere ? 'bg-amber-500 text-white animate-pulse' : ($isWaitingForPaymentHere ? 'bg-slate-200 dark:bg-slate-700 text-slate-400' : ($nodeDone ? 'bg-blue-600 text-white' : ($i === $currentIndex ? 'bg-blue-100 border-2 border-blue-600 text-blue-600' : 'bg-slate-200 dark:bg-slate-700 text-slate-400'))))))))))) }}">
                                @if($isCallingRegHere || $isCallingHere || $isCallingPayHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                @elseif($isPreparingHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                @elseif($isServingRegHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                @elseif($isServingHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                @elseif($isEditingHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                @elseif($isReadyForEditHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V4a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                @elseif($isServingPayHere)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11a1 1 0 01-1 1H5a1 1 0 01-1-1L5 9z"/></svg>
                                @elseif($nodeDone)
                                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </div>
                            <p class="text-[10px] sm:text-xs text-center mt-2 font-medium leading-tight break-words {{ ($isCallingRegHere || $isCallingPayHere) ? 'text-blue-600' : ($isServingRegHere ? 'text-amber-600' : ($isPreparingHere ? 'text-purple-600' : ($isWaitingForPhotoHere ? 'text-slate-400' : ($isCallingHere ? 'text-blue-600' : ($isServingHere ? 'text-amber-600' : ($isEditingHere ? 'text-indigo-600' : ($isReadyForEditHere ? 'text-slate-500' : ($isServingPayHere ? 'text-amber-600' : ($isWaitingForPaymentHere ? 'text-slate-400' : ($i === $currentIndex ? 'text-blue-600' : 'text-slate-400')))))))))) }}">{{ $node['label'] }}</p>
                            @if($isCallingRegHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-blue-500 font-semibold mt-0.5">Dipanggil</p>
                            @elseif($isServingRegHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-amber-600 font-semibold mt-0.5">Konsultasi</p>
                            @elseif($isPreparingHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-purple-500 font-semibold mt-0.5">Bersiap-siap!</p>
                            @elseif($isWaitingForPhotoHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-slate-400 font-semibold mt-0.5">Menunggu antrian</p>
                            @elseif($isCallingHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-blue-500 font-semibold mt-0.5">Menuju studio</p>
                            @elseif($isServingHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-amber-600 font-semibold mt-0.5">Sedang difoto</p>
                            @elseif($isEditingHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-indigo-600 font-semibold mt-0.5">Sedang diedit</p>
                            @elseif($isReadyForEditHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-slate-500 font-semibold mt-0.5">Menunggu diedit</p>
                            @elseif($isServingPayHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-amber-600 font-semibold mt-0.5">Di kasir</p>
                            @elseif($isCallingPayHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-blue-500 font-semibold mt-0.5">Dipanggil</p>
                            @elseif($isWaitingForPaymentHere)
                                <p class="text-[9px] sm:text-[10px] text-center text-slate-400 font-semibold mt-0.5">Menunggu dipanggil</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endunless

            <!-- Main Ticket Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Ticket Header with Ticket Number -->
                <div class="relative bg-gradient-to-br from-lavender to-dustyrose text-white p-8 text-center">
                    <!-- Decorative Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <circle cx="5" cy="5" r="1" fill="currentColor"/>
                            </pattern>
                            <rect width="100" height="100" fill="url(#grid)"/>
                        </svg>
                    </div>
                    
                    <div class="relative">
                        <p class="text-blue-200 text-sm font-medium uppercase tracking-wider mb-2">{{ $ticket->location->name }}</p>
                        <h1 class="text-6xl font-black tracking-tight">{{ $ticket->ticket_number }}</h1>
                        @if($ticket->customer_name)
                            <p class="text-blue-200 mt-3 text-lg">{{ $ticket->customer_name }}</p>
                        @endif
                        <p class="text-blue-200 mt-1 text-sm">Kode Order: <span class="font-semibold text-white">{{ $ticket->order_code }}</span></p>
                        {{-- Detail orderan -- SELALU ditampilkan buat semua tiket
                             (baik PP walk-in biasa maupun BK hasil check-in
                             reservasi), gak lagi digembok di balik status
                             "confirmed_at". order_code sendiri sudah ada sejak
                             tiket dibuat (murni dari ID, lihat
                             Ticket::getOrderCodeAttribute()), begitu juga
                             notes/paket layanan kalau kebetulan udah keisi dari
                             awal (mis. dari catatan reservasi) -- jadi gak ada
                             alasan buat nyembunyiin section ini nunggu CS klik
                             "Konfirmasi Pesanan" dulu. confirmed_at cuma
                             dipakai buat gate INTERNAL (tombol lanjut ke Sesi
                             Foto), bukan buat nentuin apa yang pelanggan boleh
                             lihat. --}}
                        <div class="mt-4 bg-white/10 rounded-xl px-4 py-3 text-center" id="ticket-order-detail">
                            <p class="text-blue-200 text-xs uppercase tracking-wider mb-2">
                                Detail Order
                                @unless($ticket->confirmed_at)
                                    <span class="block text-white/60 text-[10px] normal-case font-normal">Menunggu konfirmasi CS</span>
                                @endunless
                            </p>
                            @php $orderRows = $ticket->orderRows(true); @endphp
                            @if($ticket->serviceCategory)
                                <p class="text-white font-semibold text-sm">{{ $ticket->serviceCategory->name }}</p>
                            @endif
                            {{-- "Label : isi" per baris, titik dua sejajar, blok rata tengah. Catatan diberi jarak
                                 sebagai kelompok terpisah. Kode order custom (KO ...) milik CS tidak tampil di sini. --}}
                            @if($orderRows)
                                <table class="mx-auto mt-1 text-left text-sm leading-snug">
                                    @foreach($orderRows as $row)
                                        @php $gap = $row[0] === 'Catatan' && !$loop->first ? 'pt-2' : ''; @endphp
                                        <tr class="align-top">
                                            @if($row[0])
                                                <td class="pr-1 whitespace-nowrap text-white/70 {{ $gap }}">{{ $row[0] }}</td>
                                                <td class="px-1 text-white/70 {{ $gap }}">:</td>
                                                <td class="text-white font-medium break-words whitespace-pre-line {{ $gap }}">{{ $row[1] }}</td>
                                            @else
                                                <td colspan="3" class="text-center text-white font-medium break-words whitespace-pre-line">{{ $row[1] }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </table>
                            @elseif(!$ticket->serviceCategory)
                                <p class="text-white/70 text-sm italic">Belum ada catatan pesanan.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Ticket Punch Holes -->
                    <div class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-1/2">
                        <div class="w-8 h-8 bg-slate-50 dark:bg-slate-900 rounded-full"></div>
                    </div>
                    <div class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-1/2">
                        <div class="w-8 h-8 bg-slate-50 dark:bg-slate-900 rounded-full"></div>
                    </div>
                </div>

                <!-- Dashed Line Separator -->
                <div class="px-6">
                    <div class="border-t-2 border-dashed border-slate-200 dark:border-slate-700"></div>
                </div>

                <!-- Status Section -->
                <div id="ticket-status-section" class="p-6" data-status="{{ $ticket->status }}" data-called-at="{{ $ticket->called_at?->timestamp }}" data-stage="{{ $ticket->stage }}" data-ticket-number="{{ $ticket->ticket_number }}" data-counter-name="{{ $ticket->counter?->publicLabel() ?? match($ticket->stage) { 'payment' => 'Kasir', 'registration' => 'CS', default => 'Studio' } }}">
                    <div class="text-center mb-6">
                        {{-- Progres khusus RESERVASI: Sesi foto -> Pilih foto -> Pembayaran -> Edit -> Siap diambil.
                             Edit bisa berhari-hari, jadi estimasi + catatan progres dari petugas tampil di sini. --}}
                        @if($ticket->isReservation() && !in_array($ticket->status, ['cancelled', 'skipped'], true))
                            @php
                                $rsSteps = $ticket->reservationSteps();
                                $rsNote = collect($rsSteps)->first(fn ($x) => $x['note'] && $x['state'] !== 'done');
                            @endphp
                            <div class="mb-6" id="reservation-progress">
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4 text-center">Progres Pesanan Anda</p>
                                <div class="flex items-start mx-auto" style="max-width:440px">
                                    @foreach($rsSteps as $i => $rs)
                                        @php
                                            $dotBg = $rs['state'] === 'done' ? '#1D9E75' : ($rs['state'] === 'current' ? '#7F77DD' : 'transparent');
                                            $dotBorder = $rs['state'] === 'todo' ? '1px solid #94a3b8' : '1px solid transparent';
                                            $dotColor = $rs['state'] === 'todo' ? '#94a3b8' : '#fff';
                                        @endphp
                                        <div class="flex-1 flex flex-col items-center relative text-center" style="min-width:0">
                                            @if(!$loop->last)
                                                <span class="absolute" style="top:13px;left:50%;width:100%;height:2px;background:{{ $rs['state'] === 'done' ? '#1D9E75' : '#cbd5e1' }}"></span>
                                            @endif
                                            <span class="relative inline-flex items-center justify-center text-xs font-bold" style="width:28px;height:28px;border-radius:9999px;background:{{ $dotBg }};border:{{ $dotBorder }};color:{{ $dotColor }}">{{ $rs['state'] === 'done' ? '✓' : $i + 1 }}</span>
                                            <span class="mt-1.5 leading-tight {{ $rs['state'] === 'current' ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400' }}" style="font-size:11px">{{ $rs['label'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if($rsNote)
                                    <div class="mt-4 mx-auto rounded-xl px-4 py-2.5 text-sm" style="max-width:440px;background:rgba(127,119,221,.12);color:#3C3489">
                                        <span class="font-semibold">{{ $rsNote['label'] }}</span><br>{{ $rsNote['note'] }}
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if($ticket->isSelectingPhotos())
                            {{-- Tahap Pilih Foto (khusus reservasi) + timer hitung mundur --}}
                            <div class="inline-flex flex-col items-center justify-center text-center gap-2 bg-violet-50 dark:bg-violet-900/20 border-2 border-violet-200 dark:border-violet-800 text-violet-800 dark:text-violet-300 px-6 py-3 rounded-2xl">
                                <span class="font-semibold text-lg text-center">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            @if($ticket->photoSelectionState() === 'selecting')
                                <div class="mt-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Sisa waktu pilih foto</p>
                                    <p class="font-mono text-4xl font-bold text-violet-700 dark:text-violet-300" data-select-end="{{ $ticket->select_ends_at?->getTimestamp() }}">--:--</p>
                                </div>
                            @endif
                            @if($ticket->customerStatusHint())
                                <p class="mt-4 text-slate-600 dark:text-slate-400 text-sm max-w-md mx-auto">{{ $ticket->customerStatusHint() }}</p>
                            @endif
                        @elseif($ticket->status === 'waiting')
                            <div class="inline-flex items-center bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 px-6 py-3 rounded-2xl">
                                <div class="w-3 h-3 bg-amber-500 rounded-full mr-3 animate-pulse"></div>
                                <span class="font-semibold text-lg">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            @if($ticket->customerStatusHint())
                                <p class="mt-4 text-slate-600 dark:text-slate-400 text-sm max-w-md mx-auto">{{ $ticket->customerStatusHint() }}</p>
                            @endif
                            @if($ticket->stage === 'payment' && $ticket->isEditPending())
                                <p class="mt-4 text-amber-700 dark:text-amber-400 text-sm">Foto Anda boleh dibayar sekarang, hasil editnya masih diproses dan akan dikirim/diberi tahu menyusul.</p>
                            @endif
                        @elseif($ticket->status === 'calling')
                            <div class="inline-flex items-center bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-6 py-3 rounded-2xl animate-pulse">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                <span class="font-bold text-lg">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            <p class="mt-4 text-green-700 dark:text-green-400 font-medium animate-bounce flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                Silakan menuju ke {{ $ticket->counter?->publicLabel() ?? match($ticket->stage) { 'payment' => 'Kasir', 'registration' => 'CS', default => 'Studio' } }} sekarang!
                            </p>
                        @elseif($ticket->status === 'preparing')
                            <div class="inline-flex flex-col items-center justify-center text-center gap-2 bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-200 dark:border-purple-800 text-purple-800 dark:text-purple-300 px-6 py-3 rounded-2xl animate-pulse">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                <span class="font-semibold text-lg text-center">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            <p class="mt-4 text-purple-700 dark:text-purple-400 text-sm flex flex-col items-center justify-center text-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                Silakan bersiap-siap (ganti baju, dsb) di ruang ganti terdekat{{-- Blade TIDAK meng-compile @if/@else/@endif yang nempel langsung ke huruf/tanda sebelumnya (dianggap bagian email/teks biasa) -> bocor mentah ke layar. Makanya pakai ternary, bukan directive. --}}{{ $ticket->counter ? ', lalu masuk ke ' . $ticket->counter->publicLabel() . ' begitu dipanggil' : '. Nanti akan dipanggil begitu ada studio yang siap' }}.
                            </p>
                        @elseif($ticket->status === 'serving')
                            <div class="inline-flex items-center bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 px-6 py-3 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                <span class="font-semibold text-lg">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            @if($ticket->customerStatusHint())
                                <p class="mt-4 text-slate-600 dark:text-slate-400 text-sm max-w-md mx-auto">{{ $ticket->customerStatusHint() }}</p>
                            @endif
                        @elseif($ticket->status === 'editing')
                            <div class="inline-flex items-center bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-300 px-6 py-3 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                <span class="font-semibold text-lg">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            <p class="mt-4 text-indigo-700 dark:text-indigo-400 text-sm">Pengambilan foto sudah selesai, hasilnya sedang diedit fotografer. Mohon tunggu sebentar.</p>
                        @elseif($ticket->status === 'ready_for_edit')
                            <div class="inline-flex items-center bg-slate-100 dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 px-6 py-3 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                                <span class="font-semibold text-lg">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            <p class="mt-4 text-slate-500 dark:text-slate-400 text-sm">Pengambilan foto Anda sudah selesai. Fotografer akan mulai mengedit sebentar lagi.</p>
                        @elseif($ticket->status === 'completed')
                            <div class="inline-flex items-center bg-slate-100 dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 px-6 py-3 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3 text-green-600 dark:text-green-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                <span class="font-semibold text-lg">{{ $ticket->customerStatusLabel() }}</span>
                            </div>
                            @if($ticket->isEditPending())
                                <p class="mt-4 text-slate-500 dark:text-slate-400 text-sm">Pembayaran sudah tuntas. Hasil edit foto Anda masih diproses dan akan diberi tahu terpisah begitu siap diambil.</p>
                            @elseif($ticket->isReadyForPickup())
                                <p class="mt-4 text-sky-600 dark:text-sky-400 text-sm font-medium"><x-papyrus-icon name="archive-box" size="1.2em" /> Hasil edit foto Anda sudah jadi. Silakan datang kapan saja untuk mengambilnya, sebutkan Kode Order <strong>{{ $ticket->order_code }}</strong> ke kasir.</p>
                            @endif
                        @elseif($ticket->status === 'skipped')
                            <div class="inline-flex items-center bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-6 py-3 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                <span class="font-semibold text-lg">Dilewati</span>
                            </div>
                            <p class="mt-4 text-red-600 dark:text-red-400 text-sm">Tiket Anda dilewati karena tidak hadir saat dipanggil.</p>
                        @elseif($ticket->status === 'cancelled')
                            <div class="inline-flex items-center bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-6 py-3 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-3"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                <span class="font-semibold text-lg">Dibatalkan</span>
                            </div>
                            @if($ticket->skip_count > 0)
                                <p class="mt-4 text-red-600 dark:text-red-400 text-sm">Tiket dibatalkan otomatis karena tidak hadir saat dipanggil ({{ $ticket->skip_count }}x).</p>
                            @endif
                        @endif
                    </div>

                    @if($ticket->status === 'waiting')
                        <!-- Position Card -->
                        <div id="position-section" class="mb-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 text-center border border-blue-100 dark:border-blue-800">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 text-sm mb-1">Posisi Antrian</p>
                                <p class="text-4xl font-black text-blue-600 dark:text-blue-400" id="position">{{ $ticket->position }}</p>
                                {{-- Estimasi menit sengaja TIDAK ditampilkan ke pelanggan --
                                     biar gak jadi patokan/janji yang bisa meleset. Nilainya
                                     ($ticket->estimated_wait_time) tetap ada di data, cuma
                                     dipakai internal buat operator. --}}
                            </div>
                        </div>

                        <!-- Progress hint -->
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-xl p-4 mb-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-amber-800 dark:text-amber-300 text-sm font-medium">Tetap pantau halaman ini</p>
                                    <p class="text-amber-700 dark:text-amber-400 text-sm mt-1">Anda akan mendapat notifikasi saat giliran tiba.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Enable Notification Button -->
                        <div id="notif-permission-box" class="mb-6" style="display:none;">
                            <button type="button" id="btn-enable-notif"
                                class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-3 rounded-xl font-semibold text-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                Aktifkan Notifikasi Panggilan
                            </button>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 text-center">Agar HP/laptop Anda berbunyi &amp; muncul notifikasi saat giliran Anda dipanggil</p>
                        </div>
                        <div id="notif-denied-box" class="mb-6 text-center" style="display:none;">
                            <p class="text-xs text-red-500 dark:text-red-400">Notifikasi diblokir. Status tetap akan berubah otomatis di halaman ini, tapi tanpa suara/pop-up. Aktifkan lewat pengaturan izin situs di browser jika ingin mengaktifkannya.</p>
                        </div>

                        {{-- Muncul SEKALI doang, tepat setelah izin notifikasi berhasil
                             diaktifkan, khusus Android -- ini "1 langkah lagi" biar
                             notifnya bisa se-reliable WhatsApp. WA sendiri gak
                             kena batasan battery optimization karena udah
                             di-whitelist duluan sama pabrikan HP; app manapun yang
                             baru (termasuk ini) gak dapat previlese itu otomatis,
                             jadi pelanggan perlu di-ajak whitelist manual sekali.
                             Disembunyikan permanen lewat localStorage biar gak
                             muncul berulang tiap kunjungan. --}}
                        <div id="battery-tip-box" class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4" style="display:none;">
                            <p class="text-emerald-800 dark:text-emerald-300 text-sm font-semibold mb-1"><x-papyrus-icon name="check-circle" size="1.2em" /> Notifikasi aktif! Satu langkah lagi biar gak telat</p>
                            <p class="text-emerald-700 dark:text-emerald-400 text-sm mb-2">Supaya notifikasinya sereliable WhatsApp, matikan juga "Battery Optimization" untuk Chrome:</p>
                            <ol class="text-emerald-700 dark:text-emerald-400 text-sm list-decimal list-inside space-y-1 mb-2">
                                <li>Buka <strong>Pengaturan HP → Aplikasi → Chrome → Baterai</strong></li>
                                <li>Pilih <strong>"Tidak Dibatasi" / "Unrestricted"</strong></li>
                            </ol>
                            <button type="button" id="battery-tip-dismiss" class="text-xs text-emerald-600 dark:text-emerald-400 underline">Sudah, sembunyikan</button>
                        </div>

                        {{-- Khusus iPhone/Safari: notifikasi TIDAK BISA jalan sama sekali
                             kalau halaman ini cuma dibuka lewat tab Safari biasa. Harus
                             di-"Add to Home Screen" dulu supaya jadi app standalone. Box
                             ini ditampilkan/disembunyikan lewat JS (deteksi iOS + belum
                             standalone) di bagian bawah halaman. --}}
                        <div id="ios-add-home-box" class="mb-6 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl p-4" style="display:none;">
                            <p class="text-sky-800 dark:text-sky-300 text-sm font-semibold mb-2"><x-papyrus-icon name="device-phone-mobile" size="1.2em" /> Supaya iPhone Anda bisa bunyi saat dipanggil</p>
                            <p class="text-sky-700 dark:text-sky-400 text-sm mb-2">Notifikasi di iPhone hanya bisa aktif kalau halaman ini ditambahkan ke Layar Utama:</p>
                            <ol class="text-sky-700 dark:text-sky-400 text-sm list-decimal list-inside space-y-1">
                                <li>Tap tombol <strong>Share</strong> (kotak dengan panah ke atas) di bar bawah Safari</li>
                                <li>Pilih <strong>"Add to Home Screen"</strong></li>
                                <li>Buka lagi tiket ini dari ikon di Layar Utama, lalu aktifkan notifikasi</li>
                            </ol>
                        </div>
                    @endif

                    <!-- Ticket Details -->
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Detail Tiket</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700">
                                <dt class="text-slate-500 dark:text-slate-400 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    Lokasi
                                </dt>
                                <dd class="font-semibold text-slate-900 dark:text-white">{{ $ticket->location->name }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700">
                                <dt class="text-slate-500 dark:text-slate-400 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Waktu Ambil
                                </dt>
                                <dd class="font-semibold text-slate-900 dark:text-white">{{ $ticket->created_at->format('H:i, d M Y') }}</dd>
                            </div>
                            @if($ticket->called_at)
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-700">
                                    <dt class="text-slate-500 dark:text-slate-400 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        Waktu Dipanggil
                                    </dt>
                                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $ticket->called_at->format('H:i') }}</dd>
                                </div>
                            @endif
                            @if($ticket->completed_at)
                                <div class="flex justify-between items-center py-2">
                                    <dt class="text-slate-500 dark:text-slate-400 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Waktu Selesai
                                    </dt>
                                    <dd class="font-semibold text-slate-900 dark:text-white">{{ $ticket->completed_at->format('H:i') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                </div>

                <!-- Actions -->
                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 border-t border-slate-200 dark:border-slate-700">
                    <!-- QR Code -->
                    <div class="text-center mb-4">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Scan QR untuk cek status</p>
                        <div id="qrcode" class="inline-block"></div>
                    </div>

                    <a href="{{ route('locations.show', $ticket->location) }}" class="flex items-center justify-center text-blue-600 hover:text-blue-700 font-semibold">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Lihat Antrian Lokasi
                    </a>
                </div>

                @if($ticket->status === 'completed' && !$ticket->feedback)
                    <!-- Feedback Form -->
                    <div class="p-6 border-t border-slate-200 dark:border-slate-700 bg-blue-50 dark:bg-blue-900/20">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 text-center flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-yellow-400 flex-shrink-0"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" clip-rule="evenodd" /></svg>
                            Berikan Penilaian
                        </h3>
                        <form method="POST" action="{{ route('feedback.store', $ticket) }}" x-data="{ rating: 0, hoverRating: 0 }">
                            @csrf
                            <div class="text-center mb-4">
                                <div class="inline-flex space-x-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button"
                                            @click="rating = {{ $i }}"
                                            @mouseenter="hoverRating = {{ $i }}"
                                            @mouseleave="hoverRating = 0"
                                            class="transition-transform hover:scale-110 focus:outline-none"
                                            :class="(hoverRating || rating) >= {{ $i }} ? 'text-yellow-400' : 'text-slate-300 dark:text-slate-600'">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-9 h-9"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" clip-rule="evenodd" /></svg>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" :value="rating">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2" x-show="rating > 0" x-text="['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'][rating]"></p>
                            </div>
                            <div class="mb-4">
                                <textarea name="comment" rows="2" placeholder="Komentar (opsional)..."
                                    class="w-full px-4 py-3 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-3 rounded-xl font-semibold transition-colors disabled:opacity-50"
                                :disabled="rating === 0">
                                Kirim Feedback
                            </button>
                        </form>
                    </div>
                @endif

                @if($ticket->feedback)
                    <div class="p-6 border-t border-slate-200 dark:border-slate-700 bg-green-50 dark:bg-green-900/20 text-center">
                        <p class="text-green-800 dark:text-green-300 font-semibold mb-1 flex items-center justify-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Terima kasih atas feedback Anda!
                        </p>
                        <div class="flex items-center justify-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-6 h-6 {{ $i <= $ticket->feedback->rating ? 'text-yellow-400' : 'text-slate-300 dark:text-slate-600' }}"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006Z" clip-rule="evenodd" /></svg>
                            @endfor
                        </div>
                        @if($ticket->feedback->comment)
                            <p class="text-slate-600 dark:text-slate-400 text-sm mt-2 italic">"{{ $ticket->feedback->comment }}"</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Bookmark Notice -->
            @if($ticket->status === 'waiting')
                <div class="mt-6 text-center">
                    <p class="text-slate-500 dark:text-slate-400 text-sm flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        Simpan halaman ini untuk memantau status antrian Anda
                    </p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Hitung mundur timer Pilih Foto (elemen [data-select-end] ikut diganti saat status disegarkan).
        setInterval(function () {
            document.querySelectorAll('[data-select-end]').forEach(function (el) {
                var end = parseInt(el.getAttribute('data-select-end'), 10);
                if (!end) return;
                var left = end - Math.floor(Date.now() / 1000);
                if (left <= 0) {
                    el.textContent = 'Waktu habis';
                    el.classList.add('text-red-600');
                    return;
                }
                el.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
            });
        }, 1000);
    </script>
    <script type="module">
        // Generate QR Code
        if (document.getElementById('qrcode') && typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById('qrcode'), {
                text: '{{ route("tickets.show", $ticket) }}',
                width: 100,
                height: 100,
                colorDark: '#1e293b',
                colorLight: '#ffffff',
            });
        }

        // ============ Push subscription (PWA — works even if tab/app is closed) ============
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
        }

        async function subscribeToPush() {
            const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
            if (!vapidKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
                return; // Browser tidak mendukung Push API, atau VAPID key belum diset server — cukup andalkan Notification API biasa + polling.
            }
            try {
                const registration = await navigator.serviceWorker.ready;
                let subscription = await registration.pushManager.getSubscription();
                if (!subscription) {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapidKey),
                    });
                }
                await fetch('{{ route("tickets.push-subscribe", $ticket) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(subscription.toJSON()),
                });
            } catch (err) {
                console.warn('Push subscription failed:', err);
            }
        }

        // ============ iOS Safari: wajib "Add to Home Screen" dulu ============
        // Web Push TIDAK BISA jalan di Safari kalau halaman dibuka dari tab
        // biasa (bukan app yang di-install ke Home Screen). Ini gratis, tidak
        // butuh layanan pihak ketiga — cuma edukasi ke pelanggan.
        function isIosSafariNotStandalone() {
            const ua = window.navigator.userAgent;
            const isIos = /iPad|iPhone|iPod/.test(ua) || (ua.includes('Mac') && 'ontouchend' in document);
            const isStandalone = window.navigator.standalone === true
                || window.matchMedia('(display-mode: standalone)').matches;
            return isIos && !isStandalone;
        }
        const iosAddHomeBox = document.getElementById('ios-add-home-box');
        if (iosAddHomeBox && isIosSafariNotStandalone()) {
            iosAddHomeBox.style.display = '';
        }

        // ============ Wake Lock: layar tetap menyala selama menunggu ============
        // Supaya kalau pelanggan duduk nunggu sambil buka halaman tiket ini,
        // HP-nya tidak auto-lock — jadi beep/getar saat status berubah tetap
        // kerasa (karena halamannya tetap aktif di layar, bukan di-background).
        // Gratis, bawaan browser, tidak butuh permission tambahan dari user.
        let wakeLock = null;
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch (err) {
                // Gagal (mis. baterai low-power mode) — tidak masalah, cuma fallback.
                console.log('Wake Lock tidak aktif:', err.message);
            }
        }
        requestWakeLock();
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) requestWakeLock();
        });

        // Chrome di Android TIDAK mendukung `new Notification(...)` yang
        // dipanggil langsung dari script halaman -- notifikasinya diam aja,
        // walau izin sudah granted (beda dari Chrome desktop yang masih
        // mengizinkan cara lama ini). Wajib lewat Service Worker punya
        // `registration.showNotification()` supaya konsisten muncul di
        // desktop maupun mobile.
        function showNotificationCrossPlatform(title, options) {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            if (navigator.serviceWorker && navigator.serviceWorker.ready) {
                navigator.serviceWorker.ready.then((registration) => {
                    registration.showNotification(title, options);
                }).catch(() => {
                    new Notification(title, options);
                });
            } else {
                new Notification(title, options);
            }
        }

        // ============ Notification permission button ============
        const notifBox = document.getElementById('notif-permission-box');
        const notifDeniedBox = document.getElementById('notif-denied-box');
        const btnEnableNotif = document.getElementById('btn-enable-notif');

        function updateNotifBoxUI() {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'default' && notifBox) {
                notifBox.style.display = '';
            } else if (Notification.permission === 'denied' && notifDeniedBox) {
                notifDeniedBox.style.display = '';
            } else if (Notification.permission === 'granted') {
                // Sudah pernah diizinkan sebelumnya (kunjungan ulang) — pastikan
                // subscription tetap terdaftar di server tanpa perlu klik lagi.
                subscribeToPush();
            }
        }
        updateNotifBoxUI();

        if (btnEnableNotif) {
            btnEnableNotif.addEventListener('click', async () => {
                try {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        notifBox.style.display = 'none';
                        await subscribeToPush();
                        window.playNotificationSound && window.playNotificationSound();
                        window.vibrateDevice && window.vibrateDevice([150, 80, 150]);
                        showNotificationCrossPlatform('✅ Notifikasi aktif', {
                            body: 'Anda akan diberi tahu saat giliran Anda dipanggil.',
                            icon: '/favicon.ico',
                        });
                        showBatteryTipIfAndroid();
                    } else if (permission === 'denied') {
                        notifBox.style.display = 'none';
                        if (notifDeniedBox) notifDeniedBox.style.display = '';
                    }
                } catch (err) {
                    console.warn('Notification request failed:', err);
                }
            });
        }

        // ============ Tip "matikan battery optimization" (Android saja) ============
        function showBatteryTipIfAndroid() {
            const isAndroid = /Android/.test(navigator.userAgent);
            const alreadyDismissed = localStorage.getItem('papyrus_battery_tip_dismissed') === '1';
            const box = document.getElementById('battery-tip-box');
            if (isAndroid && !alreadyDismissed && box) {
                box.style.display = '';
            }
        }
        const batteryTipDismissBtn = document.getElementById('battery-tip-dismiss');
        if (batteryTipDismissBtn) {
            batteryTipDismissBtn.addEventListener('click', () => {
                localStorage.setItem('papyrus_battery_tip_dismissed', '1');
                document.getElementById('battery-tip-box').style.display = 'none';
            });
        }
        // Kalau pelanggan sudah pernah kasih izin sebelumnya (kunjungan ulang,
        // permission langsung 'granted' tanpa perlu klik tombol lagi), tetap
        // tawarin tip ini sekali kalau belum pernah di-dismiss.
        if ('Notification' in window && Notification.permission === 'granted') {
            showBatteryTipIfAndroid();
        }

        // ============ Tab title blink when called ============
        const originalTitle = document.title;
        let blinkTimer = null;
        function startTitleBlink() {
            if (blinkTimer) return;
            let toggle = false;
            blinkTimer = setInterval(() => {
                document.title = toggle ? originalTitle : '🔔 GILIRAN ANDA DIPANGGIL!';
                toggle = !toggle;
            }, 1000);
        }
        function stopTitleBlink() {
            if (blinkTimer) {
                clearInterval(blinkTimer);
                blinkTimer = null;
                document.title = originalTitle;
            }
        }
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) stopTitleBlink();
        });

        // ============ Suara pengumuman (Text-to-Speech) ============
        // Diucapkan SETELAH bunyi bel (playNotificationSound, yang total
        // durasinya ~1.9 detik -- 3x beep 0.5 detik berjeda 0.7 detik),
        // biar gak numpuk/tabrakan sama beep-nya. Pakai Web Speech API
        // bawaan browser -- gratis, gak perlu file audio/rekaman apapun.
        //
        // Ini HP pelanggan sendiri (beda dari display TV ruang tunggu),
        // jadi semua status yang relevan buat pelanggan itu sendiri boleh
        // ikut bunyi + diucapkan -- bukan cuma pas dipanggil, tapi juga
        // siap-siap, lagi difoto, dan lagi diedit.
        const STATUS_ALERT_TEXT = {
            calling: (num, counter) => `Nomor antrian ${num}, silakan menuju ke ${counter}.`,
            preparing: (num, counter) => counter
                ? `Nomor antrian ${num}, silakan bersiap-siap di ruang ganti terdekat, untuk menuju ${counter}.`
                : `Nomor antrian ${num}, silakan bersiap-siap di ruang ganti terdekat.`,
            serving: (num, counter) => counter
                ? `Sesi Anda di ${counter} sedang berlangsung.`
                : `Sesi Anda sedang berlangsung.`,
            editing: () => `Foto Anda sedang diedit, mohon tunggu sebentar.`,
        };
        const STATUS_ALERT_TITLE = {
            calling: '🔔 Giliran Anda!',
            preparing: '👋 Bersiap-siap!',
            serving: '📸 Sesi Dimulai',
            editing: '🎨 Foto Sedang Diedit',
        };

        // Antrian pengumuman -- SEBELUMNYA speechSynthesis.cancel() dipanggil
        // di SETIAP pemanggilan baru. Di halaman tiket pribadi ini risikonya
        // lebih kecil (biasanya cuma tiket MILIK SENDIRI yang berubah status),
        // tapi tetap bisa kejadian kalau status loncat cepat (preparing lalu
        // calling gak lama kemudian) -- pengumuman pertama bisa KEPOTONG di
        // tengah kalimat. Sekarang dipakai antrian FIFO: tiap pengumuman
        // WAJIB selesai (event 'onend') dulu baru lanjut ke berikutnya.
        let ticketAnnounceQueue = [];
        let ticketAnnounceBusy = false;

        // Nama loket buat PELANGGAN. Idealnya sudah label publik dari server
        // (display_label); ini jaring pengaman kalau yang masuk masih nama
        // internal: buang angka di belakang untuk CS/Kasir ("CS & Kasir 3" ->
        // "CS & Kasir"). Studio & nama custom dibiarkan apa adanya.
        function simplifyCounterName(counterName) {
            if (!counterName) return counterName;
            const stripped = counterName.replace(/\s*\d+\s*$/, '').trim();
            if (stripped !== counterName && /^(?:CS|Kasir|&|dan|\s)+$/i.test(stripped)) {
                return stripped;
            }
            return counterName;
        }

        // isRecall=false artinya TTS panggilan PERTAMA dimatikan -- pemanggil
        // cuma ngasih false buat tahap Registrasi (CS). Studio & Pembayaran
        // tetap disuarakan. Bel, getar & notifikasi selalu jalan.
        // Status lain (preparing/serving/editing) tetap diucapkan seperti biasa.
        function announceCalledTicket(status, ticketNumber, counterName, isRecall = false) {
            if (status === 'calling' && !isRecall) return;
            if (!('speechSynthesis' in window) || !ticketNumber) return;
            const buildText = STATUS_ALERT_TEXT[status];
            if (!buildText) return;
            let simplifiedCounter = simplifyCounterName(counterName);
            // "&" dibaca "dan" (TTS bisa membacanya "ampersand" atau melewatinya)
            if (simplifiedCounter) simplifiedCounter = simplifiedCounter.replace(/\s*&\s*/g, ' dan ');
            // Diucapkan huruf/angka per karakter (tanpa tanda "-") biar
            // lebih jelas kedengeran, mis. "PP-042" -> "P P empat puluh dua".
            const spoken = spokenTicketNumber(ticketNumber);
            const text = buildText(spoken, simplifiedCounter);

            if (ticketAnnounceQueue.length >= 5) {
                ticketAnnounceQueue.shift();
            }
            ticketAnnounceQueue.push(text);
            processTicketAnnounceQueue();
        }

        // Ucapan nomor tiket: huruf dieja satu-satu, angkanya dibaca utuh
        // sebagai bilangan. "PP-010" -> "P P sepuluh", "PP-004" -> "P P empat",
        // "PB-112" -> "P B seratus dua belas". Format di luar pola
        // (huruf + angka) dibaca per karakter seperti dulu.
        function spokenTicketNumber(ticketNumber) {
            const raw = String(ticketNumber || '');
            const m = raw.match(/^([A-Za-z]+)[^A-Za-z0-9]*([0-9]+)$/);
            if (!m) {
                return raw.replace(/[^a-zA-Z0-9]/g, '').split('').join(' ');
            }
            const satuan = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
            const terbilang = (n) => {
                if (n < 10) return satuan[n];
                if (n === 10) return 'sepuluh';
                if (n === 11) return 'sebelas';
                if (n < 20) return satuan[n - 10] + ' belas';
                if (n < 100) return satuan[Math.floor(n / 10)] + ' puluh' + (n % 10 ? ' ' + satuan[n % 10] : '');
                if (n < 200) return 'seratus' + (n % 100 ? ' ' + terbilang(n % 100) : '');
                if (n < 1000) return satuan[Math.floor(n / 100)] + ' ratus' + (n % 100 ? ' ' + terbilang(n % 100) : '');
                if (n < 2000) return 'seribu' + (n % 1000 ? ' ' + terbilang(n % 1000) : '');
                if (n < 1000000) return terbilang(Math.floor(n / 1000)) + ' ribu' + (n % 1000 ? ' ' + terbilang(n % 1000) : '');
                return String(n).split('').map((d) => satuan[Number(d)]).join(' ');
            };
            return m[1].toUpperCase().split('').join(' ') + ' ' + terbilang(parseInt(m[2], 10));
        }

        function processTicketAnnounceQueue() {
            if (ticketAnnounceBusy || ticketAnnounceQueue.length === 0) return;
            ticketAnnounceBusy = true;
            const text = ticketAnnounceQueue.shift();
            setTimeout(() => {
                // papyrusSpeak = bicara dengan pengaman macet (lihat components/layout.blade.php)
                    (window.papyrusSpeak || ((txt, cb) => cb()))(text, () => {
                        ticketAnnounceBusy = false;
                        processTicketAnnounceQueue();
                    });
            }, 2000);
        }

        // ============ Central alert trigger (sound + notif + blink) ============
        // `status` menentukan bunyi bel + judul notif + kalimat TTS mana
        // yang dipakai (lihat STATUS_ALERT_TEXT/STATUS_ALERT_TITLE di atas).
        // Kalau status gak dikenali (belum ada di kedua map itu), diam saja.
        function triggerCalledAlert(status, notifyData, isRecall = false) {
            if (!STATUS_ALERT_TEXT[status]) return;

            if (window.playNotificationSound) window.playNotificationSound();
            if (window.vibrateDevice) window.vibrateDevice([300, 200, 300, 200, 300, 400, 300, 200, 300, 200, 300]);

            // Kalau notifyData gak ada (dipicu dari jalur polling, bukan Echo),
            // ambil ticket number/nama loket dari DOM sebagai fallback.
            const statusEl = document.getElementById('ticket-status-section');
            const ticketNumber = notifyData?.ticket_number || statusEl?.getAttribute('data-ticket-number');
            const counterName = simplifyCounterName(notifyData?.counter_name || statusEl?.getAttribute('data-counter-name'));

            if ('Notification' in window && Notification.permission === 'granted') {
                showNotificationCrossPlatform(STATUS_ALERT_TITLE[status] || '🔔 Update Tiket', {
                    body: STATUS_ALERT_TEXT[status](ticketNumber || '', counterName),
                    icon: '/favicon.ico',
                    vibrate: [300, 200, 300, 200, 300, 400, 300, 200, 300, 200, 300],
                    requireInteraction: true,
                });
            }
            if (document.hidden) startTitleBlink();

            announceCalledTicket(status, ticketNumber, counterName, isRecall);
        }

        // ============ Polling (works with or without Echo/Reverb) ============
        let refreshTimer;
        let lastKnownStatus = '{{ $ticket->status }}';
        let lastKnownCalledAt = '{{ $ticket->called_at?->timestamp }}';

        async function refreshTicketStatus(forceAlert = false, notifyData = null, forceStatus = 'calling', forceRecall = false) {
            if (forceAlert) triggerCalledAlert(forceStatus, notifyData, forceRecall);

            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    });
                    if (!response.ok) return;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    ['ticket-status-section', 'ticket-stage-tracker', 'position-section', 'ticket-order-detail'].forEach(id => {
                        const newEl = doc.getElementById(id);
                        const oldEl = document.getElementById(id);
                        if (newEl && oldEl) {
                            oldEl.innerHTML = newEl.innerHTML;
                            oldEl.style.display = '';
                        } else if (!newEl && oldEl) {
                            // Elemen ini cuma dirender kondisional (mis. Posisi
                            // Antrian cuma muncul selagi status masih 'waiting').
                            // Begitu statusnya lewat dari itu, sembunyikan supaya
                            // gak nyangkut nampilin info basi.
                            oldEl.style.display = 'none';
                        }
                    });

                    // Detect status transition ourselves so alert fires even if
                    // Echo/Reverb never connects (pure polling fallback).
                    //
                    // Status SAJA tidak cukup: kalau operator klik "Panggil Ulang",
                    // status tiket tetap 'calling' (tidak berubah dari sebelumnya),
                    // jadi perbandingan status doang gagal mendeteksi panggilan
                    // kedua/ketiga dst. Makanya dibandingkan juga `called_at` —
                    // setiap kali Panggil/Panggil Ulang ditekan, waktu ini selalu
                    // berubah walau status-nya sama.
                    const newStatusEl = doc.getElementById('ticket-status-section');
                    const newStatus = newStatusEl?.getAttribute('data-status');
                    const newCalledAt = newStatusEl?.getAttribute('data-called-at');
                    const newStage = newStatusEl?.getAttribute('data-stage');

                    // Semua status yang butuh alert (bel + suara + notif) di
                    // HP pelanggan -- beda dari display TV yang cuma bunyi
                    // buat 'calling'/'preparing', lihat STATUS_ALERT_TEXT di atas.
                    const ALERT_STATUSES = ['calling', 'preparing', 'serving', 'editing'];
                    const justChangedToAlertStatus = ALERT_STATUSES.includes(newStatus) && newStatus !== lastKnownStatus;
                    // Panggil Ulang: status sama, called_at berubah. Berlaku juga
                    // buat 'preparing' (Panggil Ulang CS di Antrian Siap-siap).
                    const recalledWhileSameStatus = ['calling', 'preparing'].includes(newStatus)
                        && newStatus === lastKnownStatus
                        && newCalledAt && String(newCalledAt) !== String(lastKnownCalledAt);

                    if (justChangedToAlertStatus || recalledWhileSameStatus) {
                        // Pindah status = panggilan PERTAMA (isRecall=false);
                        // status sama tapi called_at berubah = Panggil Ulang.
                        // TTS panggilan pertama dimatikan HANYA di tahap Registrasi (CS).
                        triggerCalledAlert(newStatus, null, !justChangedToAlertStatus || newStage !== 'registration');
                    }

                    if (newStatus) lastKnownStatus = newStatus;
                    if (newCalledAt) lastKnownCalledAt = newCalledAt;
                } catch (err) {
                    console.warn('Realtime refresh failed:', err);
                }
            }, 300);
        }

        // Sama kayak di operator/dashboard -- Reverb (Echo, di bawah) yang
        // utama, polling ini cuma jaring pengaman kalau WebSocket-nya putus.
        setInterval(() => refreshTicketStatus(), 5000);

        // ============ Echo/Reverb (instant push, when available) ============
        if (window.Echo) {
            window.Echo.channel('ticket.{{ $ticket->id }}')
                .listen('.ticket.called', (e) => {
                    // Broadcast ini dikirim ulang setiap kali Panggil/Panggil Ulang
                    // ditekan (lihat Ticket::call()), jadi selalu forceAlert=true di
                    // sini tanpa syarat apapun — beda dengan jalur polling di atas
                    // yang harus membandingkan called_at karena tidak dapat sinyal
                    // "event baru" secara langsung, cuma snapshot HTML terakhir.
                    lastKnownStatus = 'calling';
                    // String detik utuh, SAMA FORMAT dengan data-called-at dari server --
                    // sebelumnya angka pecahan vs string, jadi polling selalu ngira
                    // "called_at berubah" dan bunyi DUA KALI tiap panggilan.
                    lastKnownCalledAt = e.ticket?.called_at ? String(Math.floor(new Date(e.ticket.called_at).getTime() / 1000)) : lastKnownCalledAt;
                    // Fallback (kalau data counter kebetulan gak kekirim) TIDAK
                    // pakai kata generik "loket" -- tebak nama tempatnya dari
                    // tahap tiketnya sendiri, biar tetap sebut nama konkret.
                    const fallbackDestination = e.ticket?.stage === 'payment' ? 'Kasir' : (e.ticket?.stage === 'registration' ? 'CS' : 'Studio');
                    refreshTicketStatus(true, {
                        ticket_number: e.ticket?.ticket_number,
                        counter_name: e.counter?.public_label ?? e.counter?.name ?? fallbackDestination
                    }, 'calling', e.is_recall === true || e.ticket?.stage !== 'registration');
                })
                .listen('.ticket.status.changed', (e) => {
                    // Gak perlu forceAlert=true di sini -- pengecekan
                    // justChangedToAlertStatus di dalam refreshTicketStatus()
                    // (jalur fetch di bawah) sudah otomatis mancing
                    // triggerCalledAlert() begitu status barunya termasuk
                    // ALERT_STATUSES (preparing/serving/editing), walau
                    // lewat Echo maupun polling biasa.
                    refreshTicketStatus();
                });

            window.Echo.channel('location.{{ $ticket->location_id }}')
                .listen('.queue.updated', (e) => {
                    refreshTicketStatus();
                });
        }
    </script>
    @endpush
</x-layout>
