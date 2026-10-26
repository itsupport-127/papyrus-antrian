<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="google" content="notranslate">

    <title>Display - <?php echo e($location->name); ?></title>

    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,700,800" rel="stylesheet" />

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        /* Panel Pembayaran: kolom "Lokasi" isinya "CS & Kasir" (lebih panjang dari
           angka studio), jadi di md / lg / TV tabelnya dilebarkan & kolom Lokasi
           dibikin lebih lega supaya gak kepotong "CS & ...". Selektor digandakan
           (.x.x) biar menang dari kelas lebar Tailwind tanpa perlu build ulang. */
        @media (min-width: 768px) and (max-width: 1023px) {
            .pay-table.pay-table { width: 270px; }
            .pay-table .pay-col-nomor.pay-col-nomor { width: 44%; }
            .pay-table .pay-col-lokasi.pay-col-lokasi { width: 56%; }
        }
        @media (min-width: 1024px) and (max-width: 1279px) {
            .pay-table.pay-table { width: 190px; }
            .pay-table .pay-col-nomor.pay-col-nomor { width: 44%; }
            .pay-table .pay-col-lokasi.pay-col-lokasi { width: 56%; }
        }
        @media (min-width: 1180px) and (max-width: 1400px) and (max-height: 820px) {
            .pay-table.pay-table { width: 190px; }
            .pay-table .pay-col-nomor.pay-col-nomor { width: 44%; }
            .pay-table .pay-col-lokasi.pay-col-lokasi { width: 56%; }
        }
        body { font-family: 'Outfit', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-[#F8F8F8] text-slate-900 min-h-screen">
    <div id="audio-lock-badge" style="display:none; position:fixed; right:10px; bottom:10px; z-index:9999; background:rgba(15,23,42,.88); color:#fff; font:600 13px/1.3 sans-serif; padding:8px 12px; border-radius:10px; pointer-events:none;"><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'speaker-x-mark','size' => '1.2em']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'speaker-x-mark','size' => '1.2em']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Suara belum aktif &mdash; klik layar sekali</div>
    <div class="min-h-screen flex flex-col p-3 px-4 sm:p-4 sm:px-6 md:p-6 md:px-9 lg:p-8 lg:px-12 tv:p-4 tv:px-10">
        <!-- Header -->
        
        <header class="flex flex-col md:grid md:grid-cols-[1fr_minmax(0,48rem)_1fr] items-center gap-3 md:gap-4 mb-6 sm:mb-8 md:mb-12 lg:mb-16 tv:mb-6">
            <div class="flex items-center justify-between w-full md:w-auto md:justify-self-start gap-3">
                
                <img src="<?php echo e(asset('images/logo-papyrus-flat.png')); ?>" alt="" class="h-8 sm:h-10 md:h-12 lg:h-16 xl:h-20 tv:h-9 w-auto object-contain shrink-0">

                
                <div class="flex md:hidden text-right shrink-0 flex-col items-end gap-0 mr-[-1rem] sm:mr-[-1.5rem]">
                    <div class="flex items-center gap-1.5 bg-[#D6DEFF] rounded-l-full pl-2.5 pr-3 py-2">
                        <svg width="16" height="16" viewBox="0 0 36 36" fill="none"><circle cx="18" cy="18" r="15" stroke="#2E3B8E" stroke-width="3"/><path d="M18 9V18.5L24 21.5" stroke="#2E3B8E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span id="clock-mobile" class="font-display text-sm font-bold text-[#2E3B8E] tabular-nums">--:--:--</span>
                    </div>
                    <p id="date-mobile" class="font-display text-[10px] font-bold text-[#2E3B8E] bg-white rounded-l-full px-3 py-1 shadow-sm -mt-1">--</p>
                </div>
            </div>

            
            <div class="w-full min-w-0 text-center px-0 md:px-4">
                
                <span class="relative inline-block max-w-full align-middle">
                    <span class="absolute -left-1 sm:-left-1.5 md:-left-2 lg:-left-2.5 tv:-left-1 -right-1 sm:-right-1.5 md:-right-2 lg:-right-2.5 tv:-right-1 bottom-0 h-1.5 sm:h-2 md:h-2.5 lg:h-[14px] tv:h-2 bg-[#E9E8E8] -z-10"></span>
                    <h1 class="relative z-10 font-display text-lg sm:text-xl md:text-3xl lg:text-4xl xl:text-[44px] tv:text-4xl font-bold text-[#2E3B8E] truncate">Antrian <?php echo e($location->name); ?></h1>
                </span>
                <?php if($location->description): ?>
                    <p class="text-slate-400 text-xs sm:text-sm tv:text-[11px] mt-1.5 md:mt-2 tv:mt-1"><?php echo e($location->description); ?></p>
                <?php endif; ?>
            </div>

            
            <div class="hidden md:flex md:justify-self-end text-right shrink-0 flex-col items-end mr-[-1rem] sm:mr-[-1.5rem] md:mr-[-2.25rem] lg:mr-[-3rem] tv:mr-[-2.5rem]">
                <div class="flex items-center gap-2 bg-[#D6DEFF] rounded-l-full pl-4 pr-16 py-3.5 lg:pl-3 lg:pr-12 lg:py-2 xl:pl-6 xl:pr-24 xl:py-5 tv:pl-3 tv:pr-12 tv:py-2">
                    <svg width="24" height="24" viewBox="0 0 36 36" fill="none" class="lg:w-5 lg:h-5 xl:w-9 xl:h-9 tv:!w-5 tv:!h-5"><circle cx="18" cy="18" r="15" stroke="#2E3B8E" stroke-width="3"/><path d="M18 9V18.5L24 21.5" stroke="#2E3B8E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span id="clock" class="font-display text-lg lg:text-lg xl:text-[32px] tv:text-lg font-bold text-[#2E3B8E] tabular-nums">--:--:--</span>
                </div>
                <p id="date" class="font-display text-xs lg:text-[11px] xl:text-[17px] tv:text-[11px] font-bold text-[#2E3B8E] bg-white rounded-l-full pl-5 pr-16 lg:pl-4 lg:pr-12 xl:pl-6 xl:pr-24 tv:pl-4 tv:pr-12 py-1 shadow-sm -mt-1.5 lg:-mt-1 xl:-mt-2 tv:-mt-1">--</p>
            </div>
        </header>

        
        <?php
            $stageGroups = [
                'photo_session_preparing' => [
                    'label' => 'Siap-Siap', 'sublabel' => 'Bersiap Sebelum Sesi Foto',
                    'panel' => 'bg-[#FFEFD6]/[0.71]',
                    'pill_text' => 'text-[#B1720A]',
                    'card' => 'bg-[#FCC871]',
                    'card_text' => 'text-[#3B3222]',
                    'card_subtext' => 'text-[#5C4F38]',
                    // Badge 2 bagian sesuai file final: pil label "STUDIO" +
                    // lingkaran nomor studio terpisah (bukan 1 pil teks polos
                    // "Studio 1" kayak versi sebelumnya).
                    'badge_pill' => 'bg-[#6773C0] text-white',
                    'badge_circle' => 'bg-[#2E3B8E] text-white',
                    // Warna khusus buat "Antrian berikutnya" + pil hitungan +
                    // tabel 2 warna (Nomor putih, Studio warna tint) -- ini
                    // BUKAN warna yang sama dengan pill_text (yang lebih
                    // terang/aksen), tapi warna gelap netral + tint pucat,
                    // sesuai file final (v14_1091/v14_1092/v19_1315 dst).
                    'label_text' => 'text-[#3B3422]',
                    'count_bg' => 'bg-[#FFF7F0]',
                    'table_tint' => 'bg-[#FFFBF0]',
                    'table_tint_hex' => '#FFFBF0',
                    'table_bg' => 'bg-white/70',
                    'table_border' => 'border-[#F0DCB8]',
                    'table_head' => 'text-[#8A6D3B]',
                    'dot_accent' => 'bg-[#FFAE00]',
                    'first_row' => 'text-[#B1720A]',
                    'muted_row' => 'text-[#3B3222]',
                    'empty_card' => 'bg-white/60 text-[#B1720A]/70',
                ],
                'photo_session' => [
                    'label' => 'Masuk Studio', 'sublabel' => 'Sesi Foto',
                    'panel' => 'bg-[#D6DEFF]',
                    'pill_text' => 'text-[#2E3B8E]',
                    'card' => 'bg-[#2E3B8E]',
                    'card_text' => 'text-white',
                    'card_subtext' => 'text-[#C7D0F5]',
                    'badge_pill' => 'bg-[#FF9F00] text-white',
                    'badge_circle' => 'bg-[#B0720A] text-white',
                    'label_text' => 'text-[#22273B]',
                    'count_bg' => 'bg-[#F0F7FF]',
                    'table_tint' => 'bg-[#E7F6FF]',
                    'table_tint_hex' => '#E7F6FF',
                    'table_bg' => 'bg-white/70',
                    'table_border' => 'border-[#C3CDF7]',
                    'table_head' => 'text-[#4A5AA8]',
                    'dot_accent' => 'bg-[#0033FF]',
                    'first_row' => 'text-[#0033FF]',
                    'muted_row' => 'text-[#2E3B8E]',
                    'empty_card' => 'bg-white/60 text-[#2E3B8E]/70',
                ],
                'payment' => [
                    'label' => 'Antrian Pembayaran', 'sublabel' => 'Pembayaran & Sesi Cetak',
                    'panel' => 'bg-[#E4FFFB]',
                    'pill_text' => 'text-[#2A453C]',
                    'card' => 'bg-[#AAEAE1]',
                    'card_text' => 'text-[#2A453C]',
                    'card_subtext' => 'text-[#416355]',
                    'badge' => 'text-[#376760]',
                    // Pil "CS & Kasir" -- dibungkus pil seperti badge studio (putih #FFFFFF).
                    'badge_pill' => 'bg-[#FFFFFF] text-[#376760]',
                    'label_text' => 'text-[#223B28]',
                    'count_bg' => 'bg-[#F0FFF8]',
                    'table_tint' => 'bg-[#E7FFEC]',
                    'table_tint_hex' => '#E7FFEC',
                    'table_bg' => 'bg-white/70',
                    'table_border' => 'border-[#C8F0E3]',
                    'table_head' => 'text-[#5A8A7D]',
                    'dot_accent' => 'bg-[#00A896]',
                    'first_row' => 'text-[#00A896]',
                    'muted_row' => 'text-[#2A453C]',
                    'empty_card' => 'bg-white/60 text-[#2A453C]/70',
                ],
            ];

            // Layar ini KHUSUS 1 lokasi ($location) -- kalau butuh tampilan
            // gabungan semua lokasi (PP + BK) sekaligus, pakai halaman
            // terpisah di /display (lihat locations/display-all.blade.php).
            $callingByStage = $location->tickets()
                ->whereIn('status', ['calling', 'serving'])
                ->whereIn('stage', ['photo_session', 'payment'])
                ->with('counter')
                ->get()
                ->groupBy('stage');

            $waitingByStage = $location->tickets()
                ->where('status', 'waiting')
                ->whereIn('stage', ['photo_session', 'payment'])
                ->orderByRaw('COALESCE(last_skipped_at, created_at)')
                ->get()
                ->groupBy('stage');

            // Tiket yang lagi "Siap-siap" (status preparing) -- ini BUKAN
            // hasil groupBy stage biasa karena semuanya stage-nya sama
            // (photo_session), yang beda cuma status-nya. Lihat
            // docs/ALUR-ANTRIAN-STUDIO.md bagian "Siap-siap (pipelining)".
            $preparingTickets = $location->tickets()
                ->where('stage', 'photo_session')
                ->where('status', 'preparing')
                ->with('counter')
                ->get();


            // Tiket "edit dulu" -- foto sudah selesai tapi masih di fotografer (siap
            // diedit / sedang diedit). Mereka BELUM di tahap Pembayaran (stage-nya
            // masih photo_session, status ready_for_edit/editing), jadi sebelumnya
            // gak nongol di antrian mana pun di layar ini (cuma kehitung di "foto
            // sedang diproses"). Padahal habis diedit mereka bakal bayar -- jadi
            // ditampilkan di "Antrian Berikutnya" Pembayaran, di BELAKANG tiket yang
            // sudah siap dibayar, dengan label "Edit Foto".
            $editingTickets = $location->tickets()
                ->where('stage', 'photo_session')
                ->whereIn('status', [\App\Models\Ticket::STATUS_READY_FOR_EDIT, \App\Models\Ticket::STATUS_EDITING])
                ->notSelectingPhotos()
                ->orderByRaw('COALESCE(ready_for_edit_at, photo_completed_at, created_at)')
                ->get();

            $processingCount = $location->tickets()
                ->whereIn('status', [\App\Models\Ticket::STATUS_READY_FOR_EDIT, \App\Models\Ticket::STATUS_EDITING])
                ->notSelectingPhotos()
                ->count();
        ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col pt-6 sm:pt-7 md:pt-9 lg:pt-11 tv:pt-7">
            <div id="queue-board" class="grid grid-cols-1 md:portrait:grid-cols-1 md:landscape:grid-cols-3 lg:grid-cols-3 tv:grid-cols-3 gap-4 sm:gap-5 lg:gap-6 tv:gap-3 flex-1 md:min-h-0">
                    
                    <?php
                        $callEvents = collect();
                        foreach ($callingByStage as $stg => $list) {
                            foreach ($list as $t) {
                                if ($t->status === 'calling') {
                                    $callEvents->push(['k' => 'calling', 'n' => $t->ticket_number, 'at' => $t->called_at?->getTimestamp() ?? 0, 'l' => $t->counter?->publicLabel(), 's' => $stg]);
                                }
                            }
                        }
                        foreach ($preparingTickets as $t) {
                            $callEvents->push(['k' => 'preparing', 'n' => $t->ticket_number, 'at' => $t->called_at?->getTimestamp() ?? 0, 'l' => $t->counter?->publicLabel(), 's' => 'photo_session']);
                        }
                    ?>
                    <span id="call-state" hidden data-calls="<?php echo e(json_encode($callEvents->values())); ?>"></span>
                <?php $__currentLoopData = $stageGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stageKey => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $calling = $stageKey === 'photo_session_preparing' ? $preparingTickets : $callingByStage->get($stageKey, collect());
                        // Kolom "Siap-siap" numpang lihat antrian tunggu yang SAMA
                        // dengan kolom "Sesi Foto" (photo_session) -- masuk akal
                        // karena orang yang sama itu jugalah yang berikutnya bakal
                        // dipanggil "Siap-siap", sebelum akhirnya masuk studio.
                        //
                        // Khusus kolom "Antrian Masuk Studio": "Antrian
                        // Berikutnya" CUMA nampilin yang statusnya SUDAH
                        // "preparing" (Siap-siap) -- orang yang belum
                        // di-siap-siapin (masih di baris tunggu biasa) SENGAJA
                        // TIDAK dimasukin ke sini, karena mereka belum tentu
                        // beneran jadi berikutnya masuk studio ini (siap-siap
                        // dulu baru dipanggil ke studio). Kalau ada BANYAK
                        // studio, tiap barisnya dikasih label nama studio biar
                        // jelas punya studio mana.
                        $waiting = match ($stageKey) {
                            'photo_session_preparing' => $waitingByStage->get('photo_session', collect()),
                            'photo_session' => $preparingTickets,
                            'payment' => $waitingByStage->get('payment', collect())->concat($editingTickets),
                            default => $waitingByStage->get($stageKey, collect()),
                        };
                        // Tabel "Antrian Berikutnya" dibagi 2 sub-kolom
                        // (kiri/kanan) biar muat lebih banyak dalam satu
                        // layar tanpa perlu scroll -- 18 tiket berikutnya
                        // (9 per sub-kolom), sisanya cukup diwakili lewat
                        // badge "N antrian menunggu" di atas tabel.
                        // Dikasih nomor urut (0-based) SEBELUM di-chunk jadi beberapa
                        // kolom, biar nanti pas render tiap baris tau posisinya di
                        // urutan keseluruhan (bukan cuma posisi di dalam kolomnya
                        // sendiri) -- ini dipakai buat gradasi terang/redup titik bulat
                        // di bawah (makin deket giliran dipanggil = titiknya makin
                        // terang).
                        // SEMUA nomor antrian ditampilkan, nggak dibatasi/dipotong --
                        // kalau 1 kolom (9 baris) nggak cukup, otomatis nambah kolom
                        // lagi ke kanan (lihat $waitingChunks di bawah & grid-template-
                        // columns dinamis di elemen waiting-tickets).
                        $waitingNumbered = $waiting->values()->map(function ($ticket, $idx) {
                            $ticket->_queue_position = $idx;
                            return $ticket;
                        });
                        $waitingTotal = $waitingNumbered->count();
                        $waitingChunks = $waitingNumbered->chunk(9)->values();
                        $waitingColumnCount = max(1, $waitingChunks->count());
                        // Kalau antriannya rame banget sampai butuh lebih dari 2 kolom,
                        // teks di tabel dikecilin dikit lagi khusus di layar TV, biar
                        // semua kolom & baris tetap muat tanpa nge-wrap/overflow.
                        $waitingDense = $waitingColumnCount > 2;
                    ?>
                    <div class="<?php echo e($meta['panel']); ?> rounded-[1.75rem] sm:rounded-[2.25rem] lg:rounded-[1.75rem] xl:rounded-[3.375rem] tv:rounded-[1.75rem] p-4 sm:p-5 md:p-6 lg:p-3 tv:p-3 pt-14 sm:pt-16 md:pt-20 lg:pt-14 xl:pt-[110px] tv:pt-14 relative flex flex-col h-full md:min-h-0">
                        <div class="absolute -top-5 sm:-top-6 md:-top-8 lg:-top-6 xl:-top-10 tv:-top-6 left-1/2 -translate-x-1/2 bg-white rounded-2xl lg:rounded-2xl xl:rounded-[26px] tv:rounded-2xl px-4 sm:px-5 md:px-6 lg:px-6 xl:px-8 tv:px-6 py-1.5 sm:py-2 md:py-2.5 lg:py-2.5 xl:py-3 min-h-[44px] sm:min-h-[50px] md:min-h-[60px] lg:min-h-[58px] xl:min-h-[92px] tv:min-h-[58px] text-center shadow-sm whitespace-nowrap">
                            <h2 class="font-display text-lg sm:text-xl md:text-2xl lg:text-2xl xl:text-[48px] tv:text-2xl font-bold <?php echo e($meta['pill_text']); ?>"><?php echo e($meta['label']); ?></h2>
                            <p class="text-[10px] sm:text-xs md:text-sm lg:text-sm xl:text-[20px] tv:text-sm -mt-0.5 <?php echo e($meta['pill_text']); ?>"><?php echo e($meta['sublabel']); ?></p>
                        </div>

                        
                        <div class="flex-1 flex flex-col pt-2.5 sm:pt-3 md:pt-3.5 lg:pt-4 tv:pt-3">
                        <div class="calling-tickets grid gap-5 sm:gap-6 md:gap-7 lg:gap-4 tv:gap-4 mb-5 sm:mb-6 md:mb-7 lg:mb-2 tv:mb-2" data-stage="<?php echo e($stageKey); ?>">
                            <?php if($calling->isEmpty()): ?>
                                <div class="<?php echo e($meta['empty_card']); ?> rounded-[3.375rem] tv:rounded-3xl p-5 tv:p-2.5 text-center">
                                    <p class="text-sm tv:text-sm font-medium">Tidak ada yang dipanggil</p>
                                </div>
                            <?php else: ?>
                                <?php $__currentLoopData = $calling; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        // Pisahin "Studio 1" jadi label "STUDIO" + nomor "1" buat
                                        // badge 2-bagian sesuai file final. Kalau nama counter-nya
                                        // gak diakhiri angka (format lain / kosong), badge_circle
                                        // TIDAK ditampilin -- daripada nebak2 nomor yang salah,
                                        // cukup tampilin nama counter-nya polos di pil label.
                                        $counterName = $ticket->counter?->publicLabel();
                                        preg_match('/^(.*?)\s*(\d+)$/', trim((string) $counterName), $counterParts);
                                    ?>
                                    <div class="<?php echo e($meta['card']); ?> relative rounded-[1.75rem] sm:rounded-[2.25rem] lg:rounded-2xl xl:rounded-[3.375rem] tv:rounded-2xl pt-4 sm:pt-5 md:pt-6 lg:pt-2.5 tv:pt-2.5 pb-3 sm:pb-4 md:pb-5 lg:pb-2 tv:pb-2 px-3 sm:px-4 md:px-5 lg:px-2.5 tv:px-2.5 text-center">
                                        
                                        <?php if (! ($stageKey === 'photo_session_preparing' && !$ticket->counter)): ?>
                                        <?php if($stageKey === 'payment'): ?>
                                            <span class="absolute top-0 -translate-y-[45%] right-1 sm:right-1.5 md:right-2 lg:right-1.5 tv:right-1.5 rounded-full px-2 sm:px-2.5 md:px-3 tv:px-2.5 py-1 sm:py-1.5 tv:py-1 text-[9px] sm:text-[10px] md:text-[11px] lg:text-[13px] tv:text-[13px] font-normal uppercase <?php echo e($meta['badge_pill']); ?>"><?php echo e($counterName ?? 'CS & Kasir'); ?></span>
                                        <?php elseif($counterParts): ?>
                                            
                                            <div class="absolute top-0 -translate-y-[45%] right-1 sm:right-1.5 md:right-2 lg:right-1 tv:right-1 flex items-center">
                                                <span class="inline-flex items-center leading-none rounded-full pl-2.5 sm:pl-3 md:pl-3.5 lg:pl-3 tv:pl-3 pr-5 sm:pr-6 md:pr-7 lg:pr-6 tv:pr-6 py-1 sm:py-1.5 lg:py-1 tv:py-1 text-[9px] sm:text-[10px] md:text-[11px] lg:text-[13px] tv:text-[13px] font-normal uppercase <?php echo e($meta['badge_pill']); ?>"><?php echo e($counterParts[1] ?: 'Studio'); ?></span>
                                                <span class="-ml-5 sm:-ml-6 md:-ml-7 lg:-ml-6 tv:-ml-6 w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 lg:w-7 lg:h-7 tv:!w-7 tv:!h-7 shrink-0 rounded-full flex items-center justify-center leading-none text-sm sm:text-base md:text-[16px] lg:text-[15px] tv:text-[15px] font-normal <?php echo e($meta['badge_circle']); ?>"><?php echo e($counterParts[2]); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="absolute top-0 -translate-y-[45%] right-1 sm:right-1.5 md:right-2 lg:right-1.5 tv:right-1.5 rounded-full px-2 sm:px-2.5 md:px-3 tv:px-2.5 py-1 sm:py-1.5 tv:py-1 text-[9px] sm:text-[10px] md:text-[11px] lg:text-[13px] tv:text-[13px] font-normal uppercase <?php echo e($meta['badge_pill']); ?>"><?php echo e($counterName ?? '-'); ?></span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <p class="font-display text-2xl sm:text-3xl md:text-4xl lg:text-xl xl:text-[52px] tv:text-xl font-extrabold <?php echo e($meta['card_text']); ?> mt-3 sm:mt-4 tv:mt-1"><?php echo e($ticket->ticket_number); ?></p>
                                        <?php if($ticket->customer_name): ?>
                                            <p class="text-[10px] sm:text-xs md:text-sm lg:text-xs xl:text-[17px] tv:text-xs leading-tight font-medium mt-0.5 <?php echo e($meta['card_subtext']); ?>"><?php echo e($ticket->customer_name); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between gap-2 mb-2 tv:mb-1">
                            <p class="text-[11px] sm:text-xs md:text-sm lg:text-sm xl:text-[17px] tv:text-sm font-normal whitespace-nowrap <?php echo e($meta['label_text']); ?>">Antrian berikutnya</p>
                            <span class="waiting-count shrink-0 text-[9px] sm:text-[10px] md:text-[11px] lg:text-[11px] xl:text-[13px] tv:text-xs font-normal rounded-full px-2 sm:px-2.5 md:px-3 lg:px-2.5 xl:px-3 tv:px-2.5 py-1 tv:py-1 <?php echo e($meta['count_bg']); ?> <?php echo e($meta['label_text']); ?> shadow-sm" data-stage="<?php echo e($stageKey); ?>"><?php echo e($waiting->count()); ?> antrian menunggu</span>
                        </div>

                        <div class="waiting-tickets flex items-start gap-2 sm:gap-2.5 md:gap-3 tv:gap-1.5 flex-1" data-stage="<?php echo e($stageKey); ?>">
                            <?php if($waiting->isEmpty()): ?>
                                <p class="text-xs sm:text-sm tv:text-sm py-2 tv:py-1 <?php echo e($meta['pill_text']); ?>/70">Tidak ada antrian</p>
                            <?php else: ?>
                                <?php $__currentLoopData = $waitingChunks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chunk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    
                                    
                                    <div class="<?php echo e($stageKey === 'payment' ? 'pay-table ' : ''); ?>w-[210px] sm:w-[240px] md:w-[220px] lg:w-[160px] xl:w-[300px] tv:w-[160px] shrink-0 relative flex pl-1.5 sm:pl-2 md:pl-2.5 tv:pl-1.5">
                                        <?php if($chunk->isNotEmpty()): ?>
                                            
                                            <span class="absolute left-1.5 sm:left-2 md:left-2.5 tv:left-1.5 right-0 top-0 bottom-0 <?php echo e($meta['table_tint']); ?> rounded-2xl tv:rounded-xl pointer-events-none"></span>
                                            <div class="<?php echo e($stageKey === 'payment' ? 'pay-col-nomor ' : ''); ?>w-3/5 bg-white relative z-10 rounded-2xl tv:rounded-xl p-2.5 sm:p-3 md:p-3.5 lg:p-1.5 tv:p-1.5">
                                                <div class="text-[9px] sm:text-[10px] md:text-[11px] lg:text-xs <?php echo e($waitingDense ? 'tv:text-[8px]' : 'tv:text-xs'); ?> font-medium pb-1.5 sm:pb-2 tv:pb-0.5 mb-1 sm:mb-1.5 tv:mb-0.5 border-b <?php echo e($meta['table_border']); ?> <?php echo e($meta['table_head']); ?> text-center">Nomor</div>
                                                <div class="space-y-1 sm:space-y-1.5 tv:space-y-0.5">
                                                    <?php $__currentLoopData = $chunk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            // Gradasi terang/redup: posisi 0 (paling atas paling
                                                            // deket giliran) = terang penuh (1.0), makin ke bawah
                                                            // makin redup, minimal 0.3 biar tetap kebaca.
                                                            $fadeOpacity = $waitingTotal > 1
                                                                ? max(0.3, 1 - ($ticket->_queue_position / ($waitingTotal - 1)) * 0.7)
                                                                : 1;
                                                            $isFirst = $ticket->_queue_position === 0 && !in_array($ticket->status, ['ready_for_edit', 'editing'], true);
                                                        ?>
                                                        <div class="flex items-center gap-2.5 sm:gap-3 md:gap-3.5 tv:gap-1.5 text-[9px] sm:text-[11px] md:text-[12px] lg:text-[14px] <?php echo e($waitingDense ? 'tv:text-[9px]' : 'tv:text-sm'); ?> font-bold <?php echo e($isFirst ? $meta['first_row'] : $meta['muted_row']); ?>">
                                                            
                                                            <span class="-ml-[15px] sm:-ml-[18px] md:-ml-[21px] tv:!-ml-[12px] w-[5px] sm:w-[6px] md:w-[7px] tv:!w-[6px] h-2.5 sm:h-3 md:h-3.5 tv:!h-3 overflow-hidden inline-block shrink-0">
                                                                <span class="block w-2.5 h-2.5 sm:w-3 sm:h-3 md:w-3.5 md:h-3.5 tv:!w-3 tv:!h-3 rounded-full <?php echo e($meta['dot_accent']); ?>" style="opacity: <?php echo e($fadeOpacity); ?>"></span>
                                                            </span>
                                                            <?php echo e($ticket->ticket_number); ?>

                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                            <div class="<?php echo e($stageKey === 'payment' ? 'pay-col-lokasi ' : ''); ?>w-2/5 relative z-10 p-2.5 sm:p-3 md:p-3.5 lg:p-1.5 tv:p-1.5 text-center">
                                                <div class="text-[9px] sm:text-[10px] md:text-[11px] lg:text-xs <?php echo e($waitingDense ? 'tv:text-[8px]' : 'tv:text-xs'); ?> font-medium pb-1.5 sm:pb-2 tv:pb-0.5 mb-1 sm:mb-1.5 tv:mb-0.5 border-b <?php echo e($meta['table_border']); ?> <?php echo e($meta['table_head']); ?>"><?php echo e($stageKey === 'payment' ? 'Lokasi' : 'Studio'); ?></div>
                                                <div class="space-y-1 sm:space-y-1.5 tv:space-y-0.5">
                                                    <?php $__currentLoopData = $chunk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        
                                                        <div class="text-[9px] sm:text-[11px] md:text-[12px] lg:text-[14px] <?php echo e($waitingDense ? 'tv:text-[9px]' : 'tv:text-sm'); ?> font-bold truncate <?php echo e($meta['muted_row']); ?>"><?php echo e($stageKey === 'payment' ? (in_array($ticket->status, ['ready_for_edit', 'editing'], true) ? 'Edit Foto' : 'CS & Kasir') : ($ticket->counter?->publicLabel() ? trim(str_ireplace('Studio', '', $ticket->counter->publicLabel())) : '-')); ?></div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>

                        <?php if($stageKey === 'photo_session' && $processingCount > 0): ?>
                            
                            <p id="processing-count" class="text-[10px] sm:text-xs tv:text-[8px] mt-2 sm:mt-3 tv:mt-1 pt-2 sm:pt-3 tv:pt-1 border-t <?php echo e($meta['table_border']); ?> <?php echo e($meta['pill_text']); ?>/80"><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'printer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'printer']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> <?php echo e($processingCount); ?> foto sedang diproses editing</p>
                        <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </main>
    </div>


    <script type="module">
        // Clock
        function updateClock() {
            const now = new Date();
            const pad = (n) => n.toString().padStart(2, '0');
            const timeText = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            // toLocaleDateString('id-ID', {month:'long'}) hasilnya "September" utuh
            // (dan month:'short' bawaan browser malah pakai singkatan Inggris,
            // bukan Indonesia) -- jadi bulan pendeknya di-mapping manual biar
            // hasilnya persis "Sep" sesuai desain, bukan "September" atau "Sept.".
            const hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulanPendekIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dateText = `${hariIndo[now.getDay()]}, ${now.getDate()} ${bulanPendekIndo[now.getMonth()]} ${now.getFullYear()}`;

            // Ada 2 versi jam/tanggal di markup (desktop & mobile, lihat header)
            // -- keduanya diisi bareng di sini, browser cuma nampilin salah
            // satu lewat CSS (hidden/flex) sesuai lebar layar.
            ['clock', 'clock-mobile'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.textContent = timeText;
            });
            ['date', 'date-mobile'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.textContent = dateText;
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ============ Bunyi bel (BUG LAMA: file /sounds/ding.mp3 tidak
        // pernah ada di project ini, jadi selama ini SELALU gagal bunyi
        // diam-diam lewat .catch(() => {})). Diganti nada dibangkitkan
        // langsung lewat WebAudio -- gratis, tidak butuh file apa pun,
        // tidak akan pernah 404. ============
        // ============ Suara layar display -- SATU AudioContext yang dipakai ulang ============
        // BUG LAMA: playDisplayBell() membuat `new AudioContext()` BARU tiap bel.
        // Context yang dibuat tanpa sentuhan/klik user langsung 'suspended' dan bisu
        // TANPA ERROR (aturan autoplay browser), dan Chrome juga membatasi jumlah
        // context aktif -- setelah banyak panggilan, bel bisa berhenti bunyi sama
        // sekali. Itu sebabnya bunyi di TV "kadang ada kadang tidak".
        // Sekarang: satu context dipakai terus, di-resume tiap kali perlu, dan
        // dibuka kuncinya lewat klik/sentuh APA PUN di layar (listener tidak dilepas,
        // jadi kalau browser mengunci lagi, klik berikutnya membukanya lagi).
        // Selama context belum 'running' muncul lencana kecil "Suara belum aktif"
        // di pojok layar -- supaya ketahuan KENAPA bisu (mis. TV baru dinyalakan
        // dan belum ada yang klik), bukan diam-diam gagal.
        let displayAudioCtx = null;
        function getDisplayAudioCtx() {
            if (!displayAudioCtx) {
                try {
                    displayAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    displayAudioCtx.onstatechange = updateAudioLockBadge;
                } catch (e) {
                    return null;
                }
            }
            if (displayAudioCtx.state === 'suspended') {
                displayAudioCtx.resume().then(updateAudioLockBadge).catch(() => {});
            }
            return displayAudioCtx;
        }
        function updateAudioLockBadge() {
            const badge = document.getElementById('audio-lock-badge');
            if (!badge) return;
            const locked = !displayAudioCtx || displayAudioCtx.state !== 'running';
            badge.style.display = locked ? 'block' : 'none';
        }
        ['click', 'touchstart', 'keydown', 'pointerdown'].forEach((ev) => {
            document.addEventListener(ev, () => { getDisplayAudioCtx(); updateAudioLockBadge(); }, { passive: true });
        });
        document.addEventListener('visibilitychange', () => { if (!document.hidden) { getDisplayAudioCtx(); updateAudioLockBadge(); } });
        getDisplayAudioCtx();               // dibuat dari awal (suspended sampai ada klik)
        setInterval(updateAudioLockBadge, 3000);
        updateAudioLockBadge();

        // Bicara (text-to-speech) dengan PENGAMAN MACET -- salinan dari
        // components/layout.blade.php (halaman ini berdiri sendiri, tidak memakai layout).
        window.papyrusSpeak = function (text, onDone) {
            let finished = false;
            let timer = null;
            const done = () => {
                if (finished) return;
                finished = true;
                clearTimeout(timer);
                window._papyrusUtterance = null;
                if (onDone) onDone();
            };
            try {
                if (!('speechSynthesis' in window)) { done(); return; }
                const synth = window.speechSynthesis;
                if (synth.paused) synth.resume();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.9;
                utterance.volume = 1;
                utterance.onend = utterance.onerror = done;
                window._papyrusUtterance = utterance;
                timer = setTimeout(() => {
                    try { synth.cancel(); } catch (e) {}
                    done();
                }, 12000 + String(text).length * 120);
                synth.speak(utterance);
            } catch (e) {
                console.log('Text-to-speech tidak didukung:', e);
                done();
            }
        };

        function playDisplayBell() {
            try {
                const ctx = getDisplayAudioCtx();
                if (!ctx) return;
                const t = ctx.currentTime;
                [0, 0.15].forEach((offset) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.setValueAtTime(988, t + offset);
                    // Gain 0.85 = batas aman sebelum clipping (lihat catatan di
                    // components/layout.blade.php soal volume).
                    gain.gain.setValueAtTime(0.85, t + offset);
                    gain.gain.exponentialRampToValueAtTime(0.01, t + offset + 0.4);
                    osc.start(t + offset);
                    osc.stop(t + offset + 0.4);
                });
            } catch (e) {
                console.log('Web Audio tidak didukung:', e);
            }
        }

        // ============ Pengumuman suara -- layar ini KHUSUS dipasang di
        // ruang tunggu, jadi paling pas jadi sumber PA sungguhan. Nama
        // pelanggan SENGAJA TIDAK disebut di sini (beda dengan di HP
        // pelanggan sendiri) karena ini speaker publik, didengar semua
        // orang yang lagi nunggu -- lihat pembahasan privasi sebelumnya.
        //
        // `kind` bedain kalimat yang diucapkan: 'calling' (beneran dipanggil
        // ke loket) vs 'preparing' (siap-siap, loket masih dipakai tiket
        // sebelumnya). Sengaja TIDAK ada 'serving'/'editing' di sini --
        // display cuma perlu bunyi begitu pelanggan HARUS PINDAH TEMPAT
        // (ke CS/studio/kasir atau bersiap-siap), bukan tiap status internal
        // yang gak butuh aksi fisik apapun dari pelanggan. ============
        // Fallback nama tujuan kalau data counter kebetulan gak kekirim --
        // SENGAJA TIDAK pakai kata generik "loket", tebak dari tahap
        // tiketnya sendiri biar tetap nyebut nama tempat yang konkret.
        function fallbackDestinationName(stage) {
            if (stage === 'payment') return 'Kasir';
            if (stage === 'registration') return 'CS';
            return 'Studio';
        }

        // Antrian pengumuman -- SEBELUMNYA speechSynthesis.cancel() dipanggil
        // di SETIAP pemanggilan baru. Efeknya: kalau ada 2 tiket dipanggil
        // berdekatan (mis. 2 studio manggil hampir bersamaan, atau "Siap-siap"
        // nyusul pas "Dipanggil" lagi diomongin), pengumuman yang PERTAMA bisa
        // KEPOTONG DI TENGAH KALIMAT begitu yang kedua masuk -- pelanggan bisa
        // aja cuma denger separuh kalimat, gak sempet denger nomor tiket atau
        // nama studio tujuannya sama sekali. Sekarang dipakai antrian FIFO:
        // tiap pengumuman WAJIB selesai (event 'onend') dulu baru lanjut ke
        // yang berikutnya, gak ada lagi yang kepotong paksa.
        let displayAnnounceQueue = [];
        let displayAnnounceBusy = false;

        // Nomor loket (CS 1, CS 2, Kasir 1, Kasir 2, dst) itu penanda INTERNAL
        // buat staf -- pelanggan gak perlu tau nomornya, cukup tau kategorinya
        // ("CS" atau "Kasir") aja. Studio SENGAJA TIDAK disederhanakan di sini
        // (beda dari CS/Kasir) karena nomor studio itu lokasi fisik yang
        // memang harus diketahui pelanggan buat nentuin ruang mana yang dituju.
        function simplifyCounterNameForAnnouncement(name) {
            if (!name) return name;
            // Nama loket beneran bisa "CS 3", "Kasir 2", TAPI JUGA bentuk
            // gabungan kayak "CS & Kasir 3" (satu loket ngerangkep dua
            // fungsi, nomor di belakang cuma pembeda fisik ke berapa) --
            // makanya di sini cuma buang angka di paling belakang, BUKAN
            // nyederhanain jadi "CS" doang, biar "CS & Kasir 3" jadi
            // "CS & Kasir" (bukan cuma "CS"). Kalau sisa teksnya bukan cuma
            // kombinasi kata CS/Kasir/&/dan (mis. "Kasir Utama", "CS Depan"
            // -- gak ada angka buat dibuang, atau nama custom lain), nama
            // aslinya dipakai apa adanya.
            const stripped = name.replace(/\s*\d+\s*$/, '').trim();
            if (stripped !== name && /^(?:CS|Kasir|&|dan|\s)+$/i.test(stripped)) {
                return stripped;
            }
            return name;
        }

        function announceOnDisplay(ticketNumber, counterName, kind = 'calling', spokenName = null, eventKey = null) {
            if (!('speechSynthesis' in window) || !ticketNumber) return;
            counterName = simplifyCounterNameForAnnouncement(counterName);
            counterName = counterName ? counterName.replace(/\s*&\s*/g, ' dan ') : counterName; // "&" dibaca "dan" (TTS bisa membacanya "ampersand" atau melewatinya)
            const spoken = spokenTicketNumber(ticketNumber);
            // Tiket reservasi: yang dipanggil NAMA (maks. 2 kata); tiket biasa: "Nomor antrian P P dua satu".
            const who = spokenName ? spokenName : `Nomor antrian ${spoken}`;
            // Pilih Foto (khusus reservasi): diarahkan ke CS & Kasir untuk dipandu memilih foto di PC preview.
            const SELECTING_TEXT = (w) => `${w}, silakan menuju ke CS dan Kasir untuk dipandu memilih foto di komputer preview.`;
            const text = kind === 'selecting'
                ? SELECTING_TEXT(who)
                : kind === 'preparing'
                ? (counterName
                    ? `${who}, silakan bersiap-siap di ruang ganti terdekat, untuk menuju ${counterName}.`
                    : `${who}, silakan bersiap-siap di ruang ganti terdekat.`)
                : `${who}, silakan menuju ke ${counterName || 'loket'}.`;

            // ANTI-DOBEL: siaran yang SAMA tidak diucapkan dua kali (event kembar, WebSocket + polling,
            // atau 2 tab layar). Kalau siarannya punya penanda unik (eventKey = event_at), yang dihitung
            // "sama" cuma siaran dengan penanda itu -- jadi Panggil Ulang (siaran baru) SELALU bersuara,
            // berapa pun jaraknya dari panggilan sebelumnya. Tanpa eventKey (mis. polling) dipakai
            // patokan kalimat yang sama dalam 15 detik.
            window._displaySpoken = window._displaySpoken || new Map();
            const _nowTs = Date.now();
            window._displaySpoken.forEach((t, k) => { if (_nowTs - t > 120000) window._displaySpoken.delete(k); });
            const _guardKey = eventKey ? ('ev:' + ticketNumber + ':' + eventKey) : ('tx:' + text);
            const _guardWin = eventKey ? 60000 : 15000;
            if (window._displaySpoken.has(_guardKey) && _nowTs - window._displaySpoken.get(_guardKey) < _guardWin) return;
            window._displaySpoken.set(_guardKey, _nowTs);

            // Jaga-jaga kalau banyak banget panggilan numpuk berturut-turut
            // (jarang terjadi, tapi kalau iya jangan sampai antriannya
            // kepanjangan dan pengumuman lama BANGET baru kebunyi menitan
            // kemudian) -- simpan cuma 5 yang paling baru.
            if (displayAnnounceQueue.length >= 5) {
                displayAnnounceQueue.shift();
            }
            displayAnnounceQueue.push(text);
            processDisplayAnnounceQueue();
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

        function processDisplayAnnounceQueue() {
            if (displayAnnounceBusy || displayAnnounceQueue.length === 0) return;
            displayAnnounceBusy = true;
            const text = displayAnnounceQueue.shift();
            // Jeda 1.2 detik SEBELUM ngomong -- ngasih waktu bunyi "ting"
            // (playDisplayBell(), ~0.55 detik) selesai dulu biar gak numpuk
            // sama suara. Jeda ini cuma dipakai buat pengumuman PALING DEPAN
            // di antrian; begitu satu selesai (onend), yang berikutnya
            // langsung diproses tanpa nunggu bel lagi.
            setTimeout(() => {
                // papyrusSpeak = bicara dengan pengaman macet (didefinisikan di atas)
                    window.papyrusSpeak(text, () => {
                        displayAnnounceBusy = false;
                        processDisplayAnnounceQueue();
                    });
            }, 1200);
        }


        // ============ Pengumuman CADANGAN lewat polling ============
        // BUG LAMA: bel & suara pemanggilan di layar ini HANYA dipicu event WebSocket
        // (Reverb). Polling 5 detik cuma menyegarkan tampilan -- jadi kalau Reverb putus
        // (di dashboard operator tertulis "Terputus (pakai polling)") atau window.Echo tidak
        // ada, layar tetap MENAMPILKAN panggilan baru tapi DIAM. Itu yang bikin suara TV
        // "kadang ada kadang tidak".
        // Sekarang tiap polling dibandingkan daftar panggilan (#call-state) dengan yang
        // sudah diumumkan; panggilan baru yang belum diumumkan lewat WebSocket langsung
        // dibunyikan (bel + suara). Kalau WebSocket jalan normal, event-nya lebih dulu
        // mencatat nomor tiketnya, jadi polling TIDAK mengumumkan dobel (jendela 30 detik).
        // Perbedaan kecil: panggilan Registrasi memang tidak punya suara di layar ini.
        const announcedCalls = new Set();
        const wsAnnouncedAt = new Map(); // nomor tiket -> waktu (ms) diumumkan lewat WebSocket
        let callsPrimed = false;

        function markAnnouncedByWebSocket(ticketNumber) {
            if (ticketNumber) wsAnnouncedAt.set(ticketNumber, Date.now());
        }

        function readCalls(doc) {
            try {
                const el = doc.getElementById('call-state');
                if (!el) return null;
                const calls = JSON.parse(el.getAttribute('data-calls'));
                return Array.isArray(calls) ? calls : null;
            } catch (e) {
                return null;
            }
        }

        function announceNewCallsFromPoll(doc) {
            const calls = readCalls(doc);
            if (!calls) return;
            const now = Date.now();
            const fresh = [];
            calls.forEach((c) => {
                const key = c.n + ':' + c.k + ':' + c.at;
                if (announcedCalls.has(key)) return;
                announcedCalls.add(key);
                if (!callsPrimed) return;                              // pemuatan pertama: panggilan lama jangan diulang
                const ws = wsAnnouncedAt.get(c.n);
                if (ws && now - ws < 30000) return;                    // sudah diumumkan lewat WebSocket
                fresh.push(c);
            });
            callsPrimed = true;
            wsAnnouncedAt.forEach((t, n) => { if (now - t > 120000) wsAnnouncedAt.delete(n); });
            if (announcedCalls.size > 500) announcedCalls.clear();
            if (fresh.length === 0) return;

            playDisplayBell();
            fresh.forEach((c) => {
                if (c.k === 'selecting') {
                    announceOnDisplay(c.n, null, 'selecting');
                } else if (c.k === 'preparing') {
                    announceOnDisplay(c.n, c.l, 'preparing');
                } else {
                    announceOnDisplay(c.n, c.l || fallbackDestinationName(c.s), 'calling');
                }
            });
        }
        announceNewCallsFromPoll(document); // catat panggilan yang sudah ada saat halaman dibuka

        // Auto-refresh nyata via AJAX: ambil ulang halaman ini, lalu tukar
        // hanya fragment yang berubah — tidak ada reload/kedip layar penuh,
        // dan tidak butuh aksi manual apa pun.
        let pollTimer;

        async function refreshDisplay() {
            clearTimeout(pollTimer);
            pollTimer = setTimeout(async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    });
                    if (!response.ok) return;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    announceNewCallsFromPoll(doc);
                    const newBoard = doc.getElementById('queue-board');
                    const oldBoard = document.getElementById('queue-board');
                    if (newBoard && oldBoard) {
                        oldBoard.innerHTML = newBoard.innerHTML;
                    }
                    const newTotal = doc.getElementById('total-waiting-count');
                    const oldTotal = document.getElementById('total-waiting-count');
                    if (newTotal && oldTotal) {
                        oldTotal.textContent = newTotal.textContent;
                    }
                } catch (err) {
                    console.warn('Realtime refresh failed:', err);
                }
            }, 300);
        }

        // Sama kayak di halaman lain -- Reverb (Echo, di bawah) yang utama,
        // polling ini cuma jaring pengaman kalau WebSocket-nya putus.
        setInterval(refreshDisplay, 5000);

        // WebSocket listeners. window.Echo bisa jadi belum tersedia jika
        // broadcasting (Laravel Reverb) belum dikonfigurasi/dijalankan —
        // jangan sampai itu membuat halaman ini error. Polling berkala di
        // atas tetap menjaga tampilan ini otomatis ter-update.
        if (window.Echo) {
            window.Echo.channel('location.<?php echo e($location->id); ?>')
                .listen('.ticket.called', (e) => {
                    // Bel/"ting ting" TETAP bunyi buat SEMUA panggilan. Konsisten sama dashboard
                    // CS/Kasir (announceCalledTicketOperator()).
                    markAnnouncedByWebSocket(e.ticket?.ticket_number);
                    playDisplayBell();
                    // TTS dimatikan HANYA buat panggilan pertama tahap Registrasi (CS).
                    // Studio & Pembayaran tetap disuarakan, Panggil Ulang selalu.
                    if (e.is_recall === true || e.ticket?.stage !== 'registration') {
                        announceOnDisplay(e.ticket?.ticket_number, (e.counter?.public_label ?? e.counter?.name) ?? fallbackDestinationName(e.ticket?.stage), 'calling', null, e.event_at);
                    }
                    refreshDisplay();
                })
                .listen('.ticket.status.changed', (e) => {
                    // "Siap-siap" (preparing) juga dibunyiin di sini -- beda
                    // dari 'calling' yang punya event sendiri (.ticket.called)
                    // di atas. 'serving'/'editing' SENGAJA dibiarkan diam,
                    // lihat catatan di announceOnDisplay().
                    if (e.ticket?.status === 'preparing') {
                        markAnnouncedByWebSocket(e.ticket?.ticket_number);
                        playDisplayBell();
                        announceOnDisplay(e.ticket?.ticket_number, e.counter?.public_label ?? e.counter?.name, 'preparing', null, e.event_at);
                    }
                    // Pilih Foto (reservasi): CS memanggil -> bel + suara. Panggil Ulang = select_called_at baru
                    // = penanda baru, jadi bunyi lagi. Kejadian yang sama (event kembar) tetap sekali.
                    if (e.ticket?.select_called_at && !e.ticket?.select_started_at && !e.ticket?.select_done_at) {
                        const selKey = e.ticket.ticket_number + ':' + e.ticket.select_called_at;
                        if (!window._announcedSelect) window._announcedSelect = new Set();
                        if (!window._announcedSelect.has(selKey)) {
                            window._announcedSelect.add(selKey);
                            markAnnouncedByWebSocket(e.ticket.ticket_number);
                            playDisplayBell();
                            announceOnDisplay(e.ticket.ticket_number, null, 'selecting');
                        }
                    }
                    refreshDisplay();
                })
                .listen('.queue.updated', (e) => {
                    refreshDisplay();
                });
        } else {
            console.warn('Realtime (Echo) belum aktif; menggunakan auto-refresh berkala.');
        }
    </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/locations/display.blade.php ENDPATH**/ ?>