<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($location->name)]); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="<?php echo e(route('locations.index')); ?>" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo e($location->name); ?></h1>
                <p class="text-slate-600 dark:text-slate-400"><?php echo e($location->description); ?></p>
            </div>
            <?php if($location->offersWalkInTicket()): ?>
                <a href="<?php echo e(route('tickets.create', $location)); ?>" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium">
                    Ambil Tiket
                </a>
            <?php elseif($location->offersBooking()): ?>
                <a href="<?php echo e(route('bookings.check')); ?>" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium">
                    Cek Reservasi
                </a>
            <?php endif; ?>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Currently Serving -->
            <div class="lg:col-span-2">
                <div id="currently-serving-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sedang Dilayani</h2>
                    
                    <span id="call-state" hidden data-calls="<?php echo e(json_encode($currentlyServing->where('status', 'calling')->map(fn ($t) => ['n' => $t->ticket_number, 'at' => $t->called_at?->getTimestamp() ?? 0, 'l' => $t->counter?->publicLabel()])->values())); ?>"></span>

                    <?php if($currentlyServing->isEmpty()): ?>
                        <p class="text-slate-500 dark:text-slate-400 text-center py-8">Belum ada yang dilayani</p>
                    <?php else: ?>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <?php $__currentLoopData = $currentlyServing; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-2xl font-bold text-green-700 dark:text-green-400"><?php echo e($ticket->ticket_number); ?></span>
                                        <span class="text-sm text-green-600 dark:text-green-400"><?php echo e($ticket->counter?->publicLabel()); ?></span>
                                    </div>
                                    <?php if($ticket->customer_name): ?>
                                        <p class="text-green-600 dark:text-green-400 text-sm mt-1"><?php echo e($ticket->customer_name); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Waiting Queue -->
                <div id="waiting-queue-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mt-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        Antrian Menunggu
                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">(<?php echo e($waitingTickets->count()); ?> orang)</span>
                    </h2>

                    <?php if($waitingTickets->isEmpty()): ?>
                        <p class="text-slate-500 dark:text-slate-400 text-center py-8">Tidak ada antrian</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php $__currentLoopData = $waitingTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-center justify-between py-3 <?php echo e(!$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : ''); ?>">
                                    <div class="flex items-center space-x-4">
                                        <span class="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-sm font-medium text-slate-600 dark:text-slate-300">
                                            <?php echo e($index + 1); ?>

                                        </span>
                                        <div>
                                            <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($ticket->ticket_number); ?></span>
                                            <?php if($ticket->customer_name): ?>
                                                <span class="text-slate-500 dark:text-slate-400 text-sm ml-2"><?php echo e($ticket->customer_name); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div id="sidebar-panel" class="space-y-6">
                <!-- Location Info -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Informasi Lokasi</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Kode</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo e($location->code); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Jam Operasional</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">
                                <?php echo e(\Carbon\Carbon::parse($location->open_time)->format('H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($location->close_time)->format('H:i')); ?>

                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Loket Aktif</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo e($location->counters->count()); ?></dd>
                        </div>
                    </dl>
                </div>

                <!-- Counters -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Loket</h3>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $location->counters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $counter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between py-2 <?php echo e(!$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : ''); ?>">
                                <span class="font-medium text-slate-900 dark:text-white"><?php echo e($counter->name); ?></span>
                                <?php if($counter->currentOperator): ?>
                                    <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-1 rounded">Aktif</span>
                                <?php else: ?>
                                    <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2 py-1 rounded">Tidak Aktif</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Display Link -->
                <a href="<?php echo e(route('locations.display', $location)); ?>" target="_blank" class="block bg-blue-900 hover:bg-blue-800 dark:bg-blue-950 dark:hover:bg-blue-900 text-white text-center py-3 rounded-lg font-medium">
                    Buka Tampilan Display
                </a>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script type="module">
        // ============ Pengumuman suara (BUG LAMA: halaman ini dengar event
        // .ticket.called lewat Echo tapi cuma refresh tampilan, tidak pernah
        // bunyi sama sekali). Disamakan dengan pola di locations/display.blade.php
        // dan tickets/show.blade.php -- bel (helper bersama dari x-layout)
        // + TTS sebut nomor & tujuan, plus "Siap-siap" (preparing) yang
        // sebelumnya juga belum ditangani sama sekali di halaman ini. ============
        // Fallback nama tujuan kalau data counter kebetulan gak kekirim --
        // SENGAJA TIDAK pakai kata generik "loket", tebak dari tahap
        // tiketnya sendiri biar tetap nyebut nama tempat yang konkret.
        function fallbackDestinationName(stage) {
            if (stage === 'payment') return 'Kasir';
            if (stage === 'registration') return 'CS';
            return 'Studio';
        }

        // Antrian pengumuman -- SEBELUMNYA speechSynthesis.cancel() dipanggil
        // di SETIAP pemanggilan baru, yang artinya kalau ada 2 tiket
        // dipanggil berdekatan, pengumuman PERTAMA bisa KEPOTONG DI TENGAH
        // KALIMAT begitu yang kedua masuk. Sekarang dipakai antrian FIFO:
        // tiap pengumuman WAJIB selesai (event 'onend') dulu baru lanjut ke
        // berikutnya.
        let locationPageAnnounceQueue = [];
        let locationPageAnnounceBusy = false;

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

        function announceCalledOnLocationPage(ticketNumber, counterName, kind = 'calling', isRecall = false) {
            // Bel TETAP bunyi buat SEMUA panggilan; TTS-nya aja yang
            // dihilangkan buat panggilan PERTAMA (bukan Panggil Ulang).
            if (window.playNotificationSound) window.playNotificationSound();
            if (kind === 'calling' && !isRecall) return;
            if (!('speechSynthesis' in window) || !ticketNumber) return;
            counterName = simplifyCounterNameForAnnouncement(counterName);
            counterName = counterName ? counterName.replace(/\s*&\s*/g, ' dan ') : counterName; // "&" dibaca "dan" (TTS bisa membacanya "ampersand" atau melewatinya)
            const spoken = spokenTicketNumber(ticketNumber);
            const text = kind === 'preparing'
                ? `Nomor antrian ${spoken}, silakan bersiap-siap di ruang ganti terdekat, untuk menuju ${counterName}.`
                : `Nomor antrian ${spoken}, silakan menuju ke ${counterName || 'loket'}.`;

            if (locationPageAnnounceQueue.length >= 5) {
                locationPageAnnounceQueue.shift();
            }
            locationPageAnnounceQueue.push(text);
            processLocationPageAnnounceQueue();
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

        function processLocationPageAnnounceQueue() {
            if (locationPageAnnounceBusy || locationPageAnnounceQueue.length === 0) return;
            locationPageAnnounceBusy = true;
            const text = locationPageAnnounceQueue.shift();
            setTimeout(() => {
                // papyrusSpeak = bicara dengan pengaman macet (lihat components/layout.blade.php)
                    (window.papyrusSpeak || ((txt, cb) => cb()))(text, () => {
                        locationPageAnnounceBusy = false;
                        processLocationPageAnnounceQueue();
                    });
            }, 1200);
        }

        let refreshTimer;

        async function refreshQueue() {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    });
                    if (!response.ok) return;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    announceNewCallsFromPollOnPage(doc);

                    ['currently-serving-panel', 'waiting-queue-panel', 'sidebar-panel'].forEach(id => {
                        const newEl = doc.getElementById(id);
                        const oldEl = document.getElementById(id);
                        if (newEl && oldEl) {
                            oldEl.innerHTML = newEl.innerHTML;
                        }
                    });
                } catch (err) {
                    console.warn('Realtime refresh failed:', err);
                }
            }, 400);
        }

        // ============ Bel/suara CADANGAN lewat polling ============
        // Sebelumnya bel & suara di halaman ini HANYA dipicu event WebSocket: kalau Reverb
        // putus, panggilan baru tampil di layar tapi tidak berbunyi. Sekarang tiap polling
        // dibandingkan daftar panggilan (#call-state); yang baru dan belum diumumkan lewat
        // WebSocket (jendela 30 dtk, biar tidak dobel) dibunyikan. Aturan lama tetap:
        // bel untuk semua panggilan, suara hanya untuk Panggil Ulang (nomor yang sudah
        // pernah tampil). Siap-siap tidak tersedia di halaman ini, jadi hanya 'calling'.
        const seenCallKeys = new Set();
        const seenCallNumbers = new Set();
        const wsCalledAt = new Map();
        let callsPrimed = false;

        function markCalledByWebSocket(ticketNumber) {
            if (!ticketNumber) return;
            wsCalledAt.set(ticketNumber, Date.now());
            seenCallNumbers.add(ticketNumber);
        }

        function announceNewCallsFromPollOnPage(doc) {
            let calls = null;
            try {
                const el = doc.getElementById('call-state');
                calls = el ? JSON.parse(el.getAttribute('data-calls')) : null;
            } catch (e) {
                calls = null;
            }
            if (!Array.isArray(calls)) return;

            const now = Date.now();
            calls.forEach((c) => {
                const key = c.n + ':' + c.at;
                if (seenCallKeys.has(key)) return;
                seenCallKeys.add(key);
                const isRecall = seenCallNumbers.has(c.n);
                seenCallNumbers.add(c.n);
                if (!callsPrimed) return;                              // pemuatan pertama: panggilan lama jangan diulang
                const ws = wsCalledAt.get(c.n);
                if (ws && now - ws < 30000) return;                    // sudah diumumkan lewat WebSocket
                announceCalledOnLocationPage(c.n, c.l || fallbackDestinationName('photo_session'), 'calling', isRecall);
            });
            callsPrimed = true;
            if (seenCallKeys.size > 500) seenCallKeys.clear();
        }
        announceNewCallsFromPollOnPage(document); // catat panggilan yang sudah ada saat halaman dibuka

        // Sama kayak di halaman lain -- Reverb (Echo, di bawah) yang utama,
        // polling ini cuma jaring pengaman kalau WebSocket-nya putus.
        setInterval(refreshQueue, 5000);

        if (window.Echo) {
            window.Echo.channel('location.<?php echo e($location->id); ?>')
                .listen('.ticket.created', refreshQueue)
                .listen('.ticket.called', (e) => {
                    markCalledByWebSocket(e.ticket?.ticket_number);
                    // Bel TETAP bunyi buat semua panggilan (di dalam fungsi);
                    // TTS-nya aja yang cuma jalan kalau ini Panggil Ulang.
                    announceCalledOnLocationPage(e.ticket?.ticket_number, (e.counter?.public_label ?? e.counter?.name) ?? fallbackDestinationName(e.ticket?.stage), 'calling', e.is_recall === true || e.ticket?.stage !== 'registration');
                    refreshQueue();
                })
                .listen('.ticket.status.changed', (e) => {
                    // "Siap-siap" (preparing) sebelumnya gak pernah bunyi
                    // sama sekali di halaman ini -- disamakan dengan
                    // dashboard operator & layar display.
                    if (e.ticket?.status === 'preparing') {
                        announceCalledOnLocationPage(e.ticket?.ticket_number, (e.counter?.public_label ?? e.counter?.name) ?? fallbackDestinationName(e.ticket?.stage), 'preparing');
                    }
                    refreshQueue();
                });
        }
    </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $attributes = $__attributesOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__attributesOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $component = $__componentOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__componentOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/locations/show.blade.php ENDPATH**/ ?>