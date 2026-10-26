<x-layout title="Panel Operator">
    @push('styles')
    <style>
        /* Tabel Reservasi & Tiket di tab Data Pelanggan: di layar sempit,
           kolom-kolom disusun jadi kartu bertumpuk (satu field per baris,
           label di atas nilainya) daripada dipaksa scroll horizontal. */
        @media (max-width: 767px) {
            .responsive-table thead { display: none; }
            .responsive-table, .responsive-table tbody, .responsive-table tr, .responsive-table td {
                display: block;
                width: 100%;
            }
            .responsive-table tr {
                padding: 1rem 1.25rem;
            }
            .responsive-table td {
                padding: 0.3rem 0 !important;
                text-align: left !important;
                border: none !important;
            }
            .responsive-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: 0.65rem;
                font-weight: 600;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #94a3b8;
                margin-bottom: 0.2rem;
            }
            .responsive-table td.rt-actions {
                text-align: left !important;
            }
            .responsive-table td.rt-actions > div {
                justify-content: flex-start !important;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        const grid = document.getElementById('operator-grid');
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');

        function switchTab(targetId) {
            tabs.forEach(tab => {
                tab.classList.remove('active', 'border-blue-600', 'text-blue-600', 'dark:text-blue-400');
                tab.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            });
            panels.forEach(panel => panel.classList.add('hidden'));
            const targetTab = document.querySelector(`[data-tab="${targetId}"]`);
            const targetPanel = document.getElementById(targetId);
            if (targetTab) {
                targetTab.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                targetTab.classList.add('active', 'border-blue-600', 'text-blue-600', 'dark:text-blue-400');
            }
            if (targetPanel) targetPanel.classList.remove('hidden');
            if (grid) grid.classList.toggle('hidden', targetId === 'tab-pelanggan');
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-tab');
                switchTab(target);
            });
        });

        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            switchTab(hash);
        }

        // ============ Filter GET (Cari/Terapkan Filter/Reset/pagination) &
        // filter Lokasi — sekarang juga lewat fetch(), BUKAN navigasi biasa,
        // supaya tetap live-update tanpa reload sama sekali. Sebelumnya cuma
        // aksi POST (Panggil dkk) yang di-AJAX-kan; form/link GET masih bikin
        // browser pindah halaman penuh walau hasilnya cuma beda query string
        // di halaman yang sama. ============
        async function fetchAndPatchUrl(url) {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            showFlashFromDoc(doc);
            applyDashboardPatch(doc);
            // Ganti URL address bar TANPA reload, biar filter yang lagi aktif
            // tetap kesimpen kalau operator refresh manual / share link.
            history.replaceState(null, '', url);
        }

        async function submitGetFormAjax(form) {
            if (form.dataset.submitting === '1') return;
            form.dataset.submitting = '1';
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const hash = (form.getAttribute('action') || '').split('#')[1]
                || document.querySelector('.tab-btn.active')?.getAttribute('data-tab') || '';
            const baseUrl = form.action.split('#')[0].split('?')[0];
            const params = new URLSearchParams(new FormData(form)).toString();
            const url = baseUrl + (params ? '?' + params : '') + (hash ? '#' + hash : '');

            try {
                await fetchAndPatchUrl(url);
            } catch (err) {
                console.warn('Filter gagal dikirim via fetch, jatuh ke submit biasa:', err);
                form.submit();
                return;
            } finally {
                if (submitBtn) submitBtn.disabled = false;
                delete form.dataset.submitting;
            }
        }

        // Filter Lokasi ada di luar #operator-grid (dipakai di semua tab),
        // jadi harus disuntik manual hash tab yang lagi aktif sebelum submit
        // — kalau tidak, ganti lokasi selalu balik ke tab "Antrian Loket"
        // walau operator lagi buka tab "Reservasi Hari Ini"/"Data Pelanggan".
        async function submitLocationFilter(select) {
            const activeTab = document.querySelector('.tab-btn.active');
            const targetId = activeTab ? activeTab.getAttribute('data-tab') : 'tab-antrian';
            const baseUrl = select.form.action.split('#')[0].split('?')[0];
            const params = new URLSearchParams(new FormData(select.form)).toString();
            const url = baseUrl + (params ? '?' + params : '') + '#' + targetId;
            try {
                await fetchAndPatchUrl(url);
            } catch (err) {
                console.warn('Ganti lokasi gagal via fetch, jatuh ke submit biasa:', err);
                select.form.action = url;
                select.form.submit();
            }
        }

        // Set berisi "id:no_antrian" tiket yang sedang menunggu, dipakai untuk
        // mendeteksi tiket BARU lewat polling (fallback kalau Echo/Reverb mati/
        // gagal connect — lihat WebSocket error di console kalau `reverb:start`
        // belum dijalankan). Sebelumnya alert suara+getar tiket baru CUMA
        // dipicu lewat Echo, jadi kalau Reverb tidak jalan (misal di lokal
        // pakai `php artisan serve` tanpa `php artisan reverb:start`), operator
        // TIDAK PERNAH dengar suara/getar sama sekali walau tiket baru terus
        // masuk — tampilan tetap ter-update (via polling 2 detik), tapi bisu.
        let lastKnownWaitingIds = new Set(
            (document.getElementById('stats-section')?.dataset.waitingIds || '')
                .split(',').filter(Boolean)
        );

        function detectNewTicketsFromPatch(doc) {
            const newStatsEl = doc.getElementById('stats-section');
            if (!newStatsEl) return;
            const newIdsRaw = (newStatsEl.dataset.waitingIds || '').split(',').filter(Boolean);
            const newIds = new Set(newIdsRaw);

            const freshlyArrived = newIdsRaw.filter(pair => !lastKnownWaitingIds.has(pair));
            if (freshlyArrived.length > 0) {
                const ticketNumber = freshlyArrived[0].split(':')[1] || null;
                alertNewTicket(ticketNumber);
            }
            lastKnownWaitingIds = newIds;
        }

        // Terapkan potongan HTML baru (hasil fetch) ke halaman yang sedang
        // terbuka TANPA reload — cuma id-id ini yang ditimpa isinya.
        function applyDashboardPatch(doc) {
            detectNewTicketsFromPatch(doc);

            const statusEl = document.getElementById('refresh-status');
            const activeTab = document.querySelector('.tab-btn.active');
            const activeTabId = activeTab ? activeTab.getAttribute('data-tab') : null;

            const refreshIds = ['stats-section', 'current-ticket-panel', 'tab-antrian', 'tab-reservasi', 'tab-pelanggan'];
            let updated = false;
            const selection = window.getSelection();
            const hasActiveSelection = selection && selection.rangeCount > 0 && !selection.isCollapsed;

            refreshIds.forEach(id => {
                const oldEl = document.getElementById(id);
                const newEl = doc.getElementById(id);
                if (!oldEl || !newEl) return;

                // Cuma pertahankan fokus untuk elemen yang BISA DIKETIK
                // (input/textarea/select) — biar operator yang lagi ngetik
                // di kotak Cari tidak keputus. Sebelumnya cek ini ikut
                // menangkap <a> (link Reset/pagination), yang otomatis
                // dapat fokus browser saat diklik — akibatnya patch hasil
                // klik Reset SELALU dibatalkan sendiri di sini, jadi
                // filter kelihatan tidak ke-reset walau request-nya
                // sebenarnya sudah berhasil.
                const active = document.activeElement;
                const isEditableFocus = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
                if (isEditableFocus && oldEl.contains(active) && active !== document.body) return;

                // Jangan sentuh panel yang lagi ada teks ter-select di
                // dalamnya (operator lagi coba copy, mis. Kode Order/kontak)
                // — kalaupun datanya berubah, biarkan dulu, biar seleksinya
                // gak keputus di tengah jalan. Update susulan tetap jalan di
                // siklus refresh berikutnya begitu seleksi dilepas.
                if (hasActiveSelection && oldEl.contains(selection.anchorNode)) return;

                // Kalau isinya identik, jangan timpa innerHTML sama sekali —
                // sebelumnya baris ini jalan TANPA SYARAT tiap 2 detik walau
                // datanya sama persis, yang diam-diam membatalkan seleksi
                // teks operator terus-menerus meski tidak ada apa pun yang
                // benar-benar berubah.
                if (oldEl.innerHTML !== newEl.innerHTML) {
                    updated = true;
                    oldEl.innerHTML = newEl.innerHTML;
                }
            });

            if (activeTabId && document.querySelector(`[data-tab="${activeTabId}"]`)) {
                switchTab(activeTabId);
            }

            if (updated && statusEl) {
                statusEl.textContent = 'Updated ' + new Date().toLocaleTimeString();
                statusEl.classList.add('text-green-600', 'dark:text-green-400');
                setTimeout(() => {
                    statusEl.textContent = 'Auto-refresh ON';
                    statusEl.classList.remove('text-green-600', 'dark:text-green-400');
                }, 2000);
            }

            return updated;
        }

        // Tampilkan flash message ("Memanggil ulang tiket PP-001", dst) yang
        // ikut terbawa di potongan HTML hasil aksi, sebagai toast auto-hilang.
        function showFlashFromDoc(doc) {
            const flashMap = { success: 'flash-success', error: 'flash-error', info: 'flash-info' };
            Object.entries(flashMap).forEach(([type, id]) => {
                const el = doc.getElementById(id);
                if (el && el.textContent.trim()) {
                    window.showToast && window.showToast(el.textContent.trim(), type);
                }
            });
        }

        async function refreshDashboard() {
            try {
                // PENTING: pertahankan query string yang lagi aktif (filter
                // Cari/Status/Tahap/Tanggal/Lokasi, dkk) — jangan di-strip.
                // Sebelumnya baris ini buang semua query string sebelum
                // fetch ulang, jadi walau filter baru saja berhasil
                // diterapkan, auto-refresh 2 detik berikutnya diam-diam
                // nge-fetch versi TANPA filter dan menimpa hasilnya —
                // makanya tanggal yang difilter kelihatan "balik lagi"
                // ke hari ini, dan data yang lagi dicari tiba-tiba hilang
                // waktu mau di-copy.
                const url = new URL(window.location.href);
                url.hash = '';
                url.searchParams.set('_', Date.now());
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                });
                if (!response.ok) return;
                if (response.redirected && new URL(response.url).pathname !== window.location.pathname) return;
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                applyDashboardPatch(doc);
            } catch (err) {
                console.warn('Auto-refresh dashboard failed:', err);
            }
        }

        // ============ Kirim aksi (Panggil/Panggil Ulang/Selesai/Lewati/dst)
        // lewat fetch(), bukan submit form biasa — supaya tampilan TIDAK
        // reload/refresh, cuma bagian yang berubah saja yang diperbarui
        // (live update), sama seperti auto-refresh 2 detik di atas. ============
        async function submitFormAjax(form) {
            if (form.dataset.submitting === '1') return;
            form.dataset.submitting = '1';

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST', // @method('DELETE') dikirim sebagai field _method, method HTTP asli tetap POST
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                showFlashFromDoc(doc);
                applyDashboardPatch(doc);
            } catch (err) {
                console.warn('Aksi gagal dikirim via fetch, jatuh ke submit biasa:', err);
                form.submit();
                return;
            } finally {
                if (submitBtn) submitBtn.disabled = false;
                delete form.dataset.submitting;
            }
        }

        // Delegasi: semua form di dalam panel operator (Panggil, Panggil
        // Ulang, Mulai Melayani, Selesai, Lewati, Check-in Reservasi, Batalkan,
        // filter Cari/Status/Tahap/Tanggal, dst) otomatis dikirim lewat AJAX,
        // baik POST (aksi) maupun GET (filter) — form di luar panel operator
        // tidak disentuh, tetap jalan normal.
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!(form.closest('#operator-grid') || form.closest('#tab-pelanggan'))) return;

            // Kalau sudah di-preventDefault duluan oleh onsubmit="return confirm(...)"
            // (artinya operator klik Cancel di dialog konfirmasi), jangan lanjut kirim.
            if (e.defaultPrevented) return;

            const method = form.method.toUpperCase();
            if (method === 'GET') {
                e.preventDefault();
                submitGetFormAjax(form);
                return;
            }
            if (method !== 'POST') return;

            e.preventDefault();
            submitFormAjax(form);
        });

        // Link "Reset" & pagination (arahnya ke halaman operator yang sama,
        // cuma beda query string/hash) juga dicegat & dikirim via fetch —
        // supaya konsisten, tidak reload. Link ke halaman LAIN (Edit tiket,
        // QR Order, Display, dst) tetap navigasi normal seperti biasa.
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            if (!(link.closest('#operator-grid') || link.closest('#tab-pelanggan'))) return;
            if (link.target && link.target !== '' && link.target !== '_self') return;

            let url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (err) {
                return;
            }
            if (url.pathname !== window.location.pathname) return; // beda halaman, biarkan navigasi normal

            e.preventDefault();
            link.blur();
            fetchAndPatchUrl(url.href).catch(err => {
                console.warn('Link gagal dikirim via fetch, jatuh ke navigasi biasa:', err);
                window.location.href = url.href;
            });
        });

        setInterval(refreshDashboard, 2000);
        refreshDashboard();

        // ============ Alert operator: suara + getar + notifikasi ============
        // Dipicu tiap kali ada TIKET BARU masuk ke antrian lokasi ini (bukan
        // saat operator sendiri yang memanggil, biar nggak berisik ulang-ulang).
        function alertNewTicket(ticketNumber) {
            window.playNotificationSound && window.playNotificationSound();
            window.vibrateDevice && window.vibrateDevice([200, 80, 200]);
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('🎟️ Tiket baru masuk', {
                    body: ticketNumber ? `Tiket ${ticketNumber} menunggu di antrian.` : 'Ada pelanggan baru mengambil tiket.',
                    icon: '/images/logo-papyrus.png',
                    tag: 'operator-new-ticket',
                });
            }
        }

        // Tombol aktifkan notifikasi (biar tetap kedengaran/notif walau
        // operator lagi pindah tab/aplikasi lain, selama browser tetap terbuka).
        const btnEnableOperatorNotif = document.getElementById('btn-enable-operator-notif');
        function updateOperatorNotifUI() {
            if (!btnEnableOperatorNotif || !('Notification' in window)) return;
            btnEnableOperatorNotif.style.display = Notification.permission === 'default' ? '' : 'none';
        }
        updateOperatorNotifUI();
        if (btnEnableOperatorNotif) {
            btnEnableOperatorNotif.addEventListener('click', async () => {
                try {
                    const permission = await Notification.requestPermission();
                    updateOperatorNotifUI();
                    if (permission === 'granted') {
                        window.playNotificationSound && window.playNotificationSound();
                        new Notification('✅ Notifikasi operator aktif', {
                            body: 'Anda akan diberi tahu saat ada tiket baru masuk.',
                            icon: '/images/logo-papyrus.png',
                        });
                    }
                } catch (err) {
                    console.warn('Notification request failed:', err);
                }
            });
        }

        if (window.Echo) {
            window.Echo.channel('location.{{ $location->id }}')
                // Sengaja TIDAK panggil alertNewTicket() langsung di sini —
                // refreshDashboard() di bawah akan memicu applyDashboardPatch()
                // -> detectNewTicketsFromPatch() yang sudah menangani alert
                // tiket baru secara seragam (baik lewat Echo maupun polling
                // biasa). Kalau dipanggil dobel di sini, suara/getar akan
                // bunyi 2x tiap ada tiket baru saat Reverb menyala.
                .listen('.ticket.created', refreshDashboard)
                .listen('.ticket.called', refreshDashboard)
                .listen('.ticket.status.changed', refreshDashboard)
                .listen('.queue.updated', refreshDashboard);
        }

        window.refreshDashboard = refreshDashboard;
    </script>
    @endpush
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <!-- Top Bar -->
        <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-20">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex items-center justify-between gap-2 h-14">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            @if(file_exists(public_path('images/logo-papyrus.png')))
                                <img src="{{ asset('images/logo-papyrus.png') }}" alt="Papyrus Photo" class="w-full h-full object-contain">
                            @else
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-base font-bold text-slate-900 dark:text-white truncate">Panel {{ $counter->name ?? 'CS & Kasir' }}</h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $counterMessage ?? 'Semua Lokasi' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" id="btn-enable-operator-notif" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-lavender/15 hover:bg-lavender/25 text-lavender rounded-lg text-xs font-semibold transition-colors" title="Aktifkan suara & notifikasi tiket baru">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span>Aktifkan Notifikasi</span>
                        </button>
                        <span id="refresh-status" class="text-[11px] text-slate-400 dark:text-slate-500 hidden sm:inline">Auto-refresh ON</span>
                        <button type="button" onclick="window.refreshDashboard && window.refreshDashboard()" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors" title="Refresh data sekarang">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="hidden xs:inline sm:inline">Refresh</span>
                        </button>
                        @if($counter)
                            <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">{{ $counter->stageLabel() }}</span>
                            <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse flex-shrink-0" title="Loket aktif"></span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-6">
            <form method="GET" action="{{ route('operator.dashboard') }}" class="mb-6">
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <div class="flex items-center gap-2">
                        <label for="location-filter" class="text-sm font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">Lokasi:</label>
                        <select id="location-filter" name="location_id" onchange="submitLocationFilter(this)" class="px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ (request('location_id') == $loc->id) ? 'selected' : '' }}>{{ $loc->name }} ({{ $loc->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="date" value="{{ request('date', now()->toDateString()) }}" />
                    <input type="hidden" name="q" value="{{ request('q', '') }}" />
                    <input type="hidden" name="status" value="{{ request('status', '') }}" />
                    <input type="hidden" name="stage" value="{{ request('stage', '') }}" />
                </div>
            </form>

            <!-- Stats -->
            <div id="stats-section" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" data-waiting-ids="{{ collect($waitingByStage)->flatten(1)->map(fn($t) => $t->id . ':' . $t->ticket_number)->implode(',') }}">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total Lokasi</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $todayStats['total_locations'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total Tiket Hari Ini</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $todayStats['total'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Sedang Dilayani</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $todayStats['serving'] }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Selesai</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $todayStats['served'] }}</p>
                </div>
            </div>

            @if(!$counter)
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden" id="no-counter-panel">
                    <div class="p-10 text-center">
                        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Loket Belum Siap</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto">{{ $counterMessage ?? 'Menunggu loket tersedia untuk role Anda.' }}</p>
                        <p class="text-slate-400 dark:text-slate-500 text-sm mt-4">Halaman ini akan dimuat ulang secara otomatis.</p>
                    </div>
                </div>
            @else
                    <!-- Section Tabs -->
                    <div class="flex items-center gap-1 mb-6 border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
                        <button type="button" data-tab="tab-antrian" class="tab-btn active border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                            {{ $isFotografer ? 'Antrian Sesi Foto' : 'Antrian Loket' }}
                        </button>
                        @if($counter && in_array('booking', $permissions))
                            <button type="button" data-tab="tab-reservasi" class="tab-btn border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                                Reservasi Hari Ini
                                @if($todayBookings->isNotEmpty())
                                    <span class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ $todayBookings->count() }}</span>
                                @endif
                            </button>
                        @endif
                        @if(in_array('customers', $permissions))
                            <button type="button" data-tab="tab-pelanggan" class="tab-btn border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                                Data Pelanggan
                            </button>
                        @endif
                    </div>

                    <div id="operator-grid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 space-y-6">
                        <div id="tab-antrian" class="tab-panel space-y-6">
                        <!-- Current Ticket Panel -->
                        @if(in_array('walkin', $permissions) || in_array('photo', $permissions))
                            <div id="current-ticket-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $counter->stageLabel() }}</h2>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">TIKET AKTIF</span>
                                </div>
                                <div class="p-4">
                                    @if($currentTicket)
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 text-center mb-4">
                                            <p class="text-blue-600 dark:text-blue-300 font-medium mb-1 text-sm">
                                                @if($currentTicket->status === 'calling')
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>Memanggil...</span>
                                                @else
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></span>Sedang Dilayani</span>
                                                @endif
                                            </p>
                                            <p class="text-5xl font-black text-blue-900 dark:text-blue-200">{{ $currentTicket->ticket_number }}</p>
                                            @if($currentTicket->customer_name)
                                                <p class="text-blue-600 dark:text-blue-300 mt-2 text-base">{{ $currentTicket->customer_name }}</p>
                                            @endif
                                            <p class="text-blue-500 dark:text-blue-400 text-xs mt-1">
                                                {{ $currentTicket->customer_phone }}
                                                @if($currentTicket->customer_email)
                                                    &middot; {{ $currentTicket->customer_email }}
                                                @endif
                                            </p>
                                            @if($currentTicket->skip_count > 0)
                                                <p class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    Dilewati {{ $currentTicket->skip_count }}x
                                                    @if($currentTicket->skip_count + 1 >= \App\Models\Ticket::MAX_SKIPS)
                                                        &middot; akan dibatalkan otomatis jika dilewati lagi
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                        @if($currentTicket->status === 'calling')
                                            <form method="POST" action="{{ route('operator.recall', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-blue-600 dark:border-blue-500 text-blue-600 dark:text-blue-400 py-2.5 rounded-lg font-semibold transition-colors text-sm hover:bg-blue-50 dark:hover:bg-slate-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                    Panggil Ulang
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($currentTicket->status, ['calling', 'serving']))
                                            <form method="POST" action="{{ route('operator.uncall', $currentTicket) }}" class="mb-3" onsubmit="return confirm('Batalkan pemanggilan tiket ini? Tiket akan dikembalikan ke antrian tanpa menghukum pelanggan.')">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 py-2 rounded-lg font-medium transition-colors text-xs hover:bg-slate-50 dark:hover:bg-slate-600">
                                                    Salah Panggil? Batalkan Pemanggilan
                                                </button>
                                            </form>
                                        @endif
                                        @if($currentTicket->stage === 'photo_session' && $currentTicket->status === 'serving')
                                            @if($counter->preparingTicket())
                                                @php $preparing = $counter->preparingTicket(); @endphp
                                                <div class="mb-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3">
                                                    <p class="text-xs text-purple-700 dark:text-purple-300 font-semibold mb-1">👋 Siap-siap berikutnya:</p>
                                                    <p class="text-sm text-purple-900 dark:text-purple-200 font-bold">{{ $preparing->ticket_number }} — {{ $preparing->customer_name }}</p>
                                                    <form method="POST" action="{{ route('operator.uncall', $preparing) }}" class="mt-2" onsubmit="return confirm('Batalkan Siap-siap untuk tiket ini?')">
                                                        @csrf
                                                        <button type="submit" class="text-xs text-purple-600 dark:text-purple-400 underline">Batalkan Siap-siap</button>
                                                    </form>
                                                </div>
                                            @else
                                                <form method="POST" action="{{ route('operator.call-next-prepare') }}" class="mb-3">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-semibold transition-colors text-sm">👋 Panggil Siap-siap Berikutnya</button>
                                                </form>
                                            @endif
                                        @endif
                                        @if($currentTicket->stage === 'registration')
                                            @if($currentTicket->status === 'calling')
                                                <form method="POST" action="{{ route('operator.start-serving', $currentTicket) }}" class="mb-3">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Mulai Melayani</button>
                                                </form>
                                            @elseif(!$currentTicket->confirmed_at)
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Isi kolom "Order" di bawah sesuai kebutuhan pelanggan. Begitu pesanan sudah fix, klik Konfirmasi Pesanan.</p>
                                                <form method="POST" action="{{ route('operator.confirm-order', $currentTicket) }}" class="mb-3">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">✅ Konfirmasi Pesanan</button>
                                                </form>
                                            @else
                                                <p class="text-xs text-green-700 dark:text-green-400 mb-3">✅ Pesanan dikonfirmasi ({{ $currentTicket->order_code }}).</p>
                                                <form method="POST" action="{{ route('operator.complete-registration', $currentTicket) }}" class="mb-3" id="registration-form">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Selesaikan, Lanjut ke Sesi Foto</button>
                                                </form>
                                            @endif
                                        @else
                                            <form method="POST" action="{{ route('operator.complete', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">{{ $currentTicket->stage === 'payment' ? 'Selesai (Bayar & Cetak)' : 'Selesaikan, Lanjut ke Pembayaran' }}</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('operator.skip', $currentTicket) }}" class="mb-4">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center justify-center bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Lewati (Panggil Nanti)</button>
                                        </form>
                                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Order</h4>
                                            @if($currentTicket->notes_locked_at && !auth()->user()->isAdmin())
                                                <p class="text-xs text-amber-600 dark:text-amber-400 mb-2">🔒 Terkunci sejak sesi foto dimulai — hubungi Admin kalau perlu diubah.</p>
                                                <p class="whitespace-pre-line px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-sm">{{ $currentTicket->notes ?: '—' }}</p>
                                            @else
                                                <form method="POST" action="{{ route('operator.update-notes', $currentTicket) }}" class="flex gap-2 items-end">
                                                    @csrf
                                                    <textarea name="notes" rows="1" placeholder="Order dari CS / Kasir / Fotografer..." class="autogrow-textarea order-textarea flex-1 px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm resize-none overflow-hidden">{{ $currentTicket->notes }}</textarea>
                                                    <button type="submit" class="px-3 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-xs font-semibold">Simpan</button>
                                                </form>
                                                @if($currentTicket->location && $currentTicket->location->orderLineSuggestions())
                                                    <div class="flex flex-wrap gap-1.5 mt-2 order-suggestions">
                                                        @foreach($currentTicket->location->orderLineSuggestions() as $line)
                                                            <button type="button" data-order-line="{{ $line }}" class="order-suggestion-chip px-2.5 py-1 rounded-full text-xs font-medium bg-lavender/10 text-lavender hover:bg-lavender/20 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 border border-lavender/20 dark:border-slate-600 transition-colors">+ {{ $line }}</button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @else
                                        @php
                                            $autoStages = array_diff($counter->stages ?? [], ['payment']);
                                            $autoWaitingCount = collect($autoStages)->sum(fn($s) => $waitingByStage[$s]->count() ?? 0);
                                            $paymentWaitingCount = in_array('payment', $counter->stages ?? [], true) ? ($waitingByStage['payment']->count() ?? 0) : 0;
                                            $totalWaitingCount = $autoWaitingCount + $paymentWaitingCount;
                                        @endphp
                                        <div class="text-center py-12">
                                            @if($autoWaitingCount === 0 && $paymentWaitingCount > 0)
                                                <p class="text-slate-500 dark:text-slate-400 mb-2">Antrian Registrasi kosong.</p>
                                                <p class="text-slate-500 dark:text-slate-400 mb-6">Ada <strong>{{ $paymentWaitingCount }}</strong> tiket menunggu Pembayaran & Cetak Foto.</p>
                                            @else
                                                <p class="text-slate-500 dark:text-slate-400 mb-6">Tidak ada tiket yang sedang dilayani di tahap <strong>{{ $counter->stageLabel() }}</strong></p>
                                            @endif
                                            <form method="POST" action="{{ route('operator.call-next') }}">
                                                @csrf
                                                <input type="hidden" name="location_id" value="{{ request('location_id') }}" />
                                                <button type="submit"
                                                    @if($totalWaitingCount === 0) disabled @endif
                                                    class="inline-flex items-center justify-center w-full sm:w-auto bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 disabled:bg-slate-300 disabled:cursor-not-allowed text-white px-6 sm:px-10 py-4 sm:py-5 rounded-2xl font-bold text-base sm:text-lg transition-colors shadow-lg shadow-blue-200 disabled:shadow-none">Panggil Antrian Berikutnya</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Waiting Queue -->
                        @if($counter && in_array('walkin', $permissions))
                            @php
                                $isSingleStageCounter = $counter && count($counter->stages ?? []) === 1;
                                $stagesToShow = $isSingleStageCounter ? $counter->stages : \App\Models\Ticket::STAGE_ORDER;
                            @endphp
                            <div id="waiting-queue-panel" class="space-y-6">
                                @foreach($stagesToShow as $stage)
                                    @php $stageTickets = $waitingByStage[$stage] ?? collect(); @endphp
                                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                            <div>
                                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $isSingleStageCounter ? 'Daftar Tunggu' : 'Antrian ' . (\App\Models\Ticket::STAGE_LABELS[$stage] ?? $stage) }}</h2>
                                                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ $stageTickets->count() }} tiket menunggu</p>
                                            </div>
                                            @if(!$currentTicket && $stageTickets->isNotEmpty() && in_array($stage, $counter->stages ?? [], true))
                                                <form method="POST" action="{{ route('operator.call-next') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="stage" value="{{ $stage }}" />
                                                    <input type="hidden" name="location_id" value="{{ request('location_id') }}" />
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                        Panggil Antrian
                                                    </button>
                                                </form>
                                            @elseif(!$currentTicket && $stageTickets->isNotEmpty())
                                                <span class="text-xs text-slate-400 font-semibold whitespace-nowrap">Bukan tahap Anda</span>
                                            @endif
                                        </div>
                                        @if($stageTickets->isEmpty())
                                            <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Tidak ada antrian untuk tahap ini.</div>
                                        @else
                                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                                @foreach($stageTickets as $qIndex => $ticket)
                                                    @php $ticketLocation = $ticket->location; @endphp
                                                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 border border-slate-200 dark:border-slate-600 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                                                        <div class="flex items-start justify-between gap-2 mb-2">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex-shrink-0" title="Urutan ke-{{ $qIndex + 1 }} dalam antrian ini">{{ $qIndex + 1 }}</span>
                                                                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $ticket->ticket_number }}</span>
                                                            </div>
                                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 whitespace-nowrap">Menunggu</span>
                                                        </div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $ticket->customer_name }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $ticket->customer_phone }}</p>
                                                        <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                                            @if($ticketLocation)
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ $ticketLocation->name }}</span>
                                                            @endif
                                                            @if($ticket->skip_count > 0)
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Lewati {{ $ticket->skip_count }}x</span>
                                                            @endif
                                                        </div>
                                                        @if(in_array($ticket->status, ['waiting', 'calling'], true))
                                                            <div class="flex items-center gap-2 flex-wrap mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                @if(!$currentTicket && in_array($ticket->stage, $counter->stages ?? [], true))
                                                                    <form method="POST" action="{{ route('operator.call-specific', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                        <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil</button>
                                                                    </form>
                                                                @endif
                                                                @if(in_array($ticket->stage, $counter->stages ?? [], true) && in_array($ticket->status, ['calling', 'serving'], true))
                                                                    <form method="POST" action="{{ route('operator.skip', $ticket) }}" class="flex-1 min-w-[80px]" onsubmit="return confirm('Lewati tiket {{ $ticket->ticket_number }}?')">@csrf
                                                                        <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Lewati</button>
                                                                    </form>
                                                                @endif
                                                                @if(in_array($ticket->stage, $counter->stages ?? [], true))
                                                                    <a href="{{ route('operator.tickets.edit', $ticket) }}" class="p-2 bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-500 transition-colors" title="Edit">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                                    </a>
                                                                    <form method="POST" action="{{ route('operator.tickets.destroy', $ticket) }}" onsubmit="return confirm('Batalkan tiket {{ $ticket->ticket_number }}?')">@csrf @method('DELETE')
                                                                        <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 rounded-lg transition-colors" title="Batalkan">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                        @if($counter && in_array('booking', $permissions))
                        <div id="tab-reservasi" class="tab-panel hidden space-y-4">
                        <!-- Reservasi Hari Ini -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Reservasi Hari Ini</h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $todayBookings->count() }} reservasi aktif</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 flex-shrink-0">Reservasi</span>
                                @if($canManageBookings)
                                    <button type="button" onclick="openBookingModal()" class="px-3 py-1.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-xs font-semibold transition-colors flex-shrink-0">Tambah Reservasi</button>
                                @endif
                            </div>

                            @if($todayBookings->isEmpty())
                                <div class="p-10 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada reservasi untuk hari ini.</div>
                            @else
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($todayBookings as $booking)
                                        @php
                                            $statusPillColors = [
                                                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                'confirmed' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                                'checked_in' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            ];
                                            $statusPillLabels = [
                                                'pending' => 'Menunggu Konfirmasi',
                                                'confirmed' => 'Akan datang',
                                                'checked_in' => 'Sudah Check-in',
                                                'cancelled' => 'Dibatalkan',
                                            ];
                                            $canAct = in_array($booking->status, ['pending', 'confirmed'], true);
                                            $isToday = $booking->booking_date->isToday();
                                            $timeLabel = is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format('H:i');
                                        @endphp
                                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                            <!-- Time badge -->
                                            <div class="flex-shrink-0 bg-purple-600 text-white rounded-xl px-3.5 py-2 text-center leading-tight w-fit">
                                                <span class="block text-[11px] opacity-80">{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d/m') }}</span>
                                                <span class="block text-sm font-bold">{{ $timeLabel }}</span>
                                            </div>

                                            <!-- Main info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ $booking->location->code ?? '-' }}</span>
                                                    <span class="font-bold text-slate-900 dark:text-white">{{ $booking->customer_name }}</span>
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusPillColors[$booking->status] ?? 'bg-slate-100 text-slate-700' }}">
                                                        {{ $statusPillLabels[$booking->status] ?? ucfirst($booking->status) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                                    {{ $booking->customer_phone }}
                                                    <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                                                    @if($booking->ticket)
                                                        <a href="{{ route('tickets.show', $booking->ticket) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $booking->booking_code }}</a>
                                                    @else
                                                        <span class="font-medium text-slate-600 dark:text-slate-300">{{ $booking->booking_code }}</span>
                                                    @endif
                                                    @if($booking->serviceCategory)
                                                        <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                                                        {{ $booking->serviceCategory->name }}
                                                    @endif
                                                </p>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-2 flex-shrink-0 sm:justify-end">
                                                @if($canAct && $isToday)
                                                    <form method="POST" action="{{ route('operator.bookings.call', $booking) }}" onsubmit="return confirm('Check-in dan panggil reservasi {{ $booking->booking_code }}? Ini akan membuat tiket baru dan memanggil ke loket Anda.')">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Check-in & Panggil</button>
                                                    </form>
                                                @elseif($canAct)
                                                    <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap">Panggil saat hari H</span>
                                                @endif
                                                @if($canAct)
                                                    <form method="POST" action="{{ route('operator.bookings.destroy', $booking) }}" onsubmit="return confirm('Batalkan reservasi {{ $booking->booking_code }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-red-100 dark:bg-slate-700 dark:hover:bg-red-900/30 text-slate-600 hover:text-red-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Batalkan</button>
                                                    </form>
                                                @endif
                                                @if($booking->ticket)
                                                    <a href="{{ route('tickets.show', $booking->ticket) }}" class="px-3 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Lihat Tiket</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        </div><!-- /#tab-reservasi -->
                        @endif
                        </div><!-- /.md:col-span-2 -->

                    <!-- Sidebar -->
                    <div class="space-y-4 md:sticky md:top-20">
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Status Loket</h3>
                                <span class="text-xs text-slate-400">Langsung</span>
                            </div>
                            <div class="p-3">
                                <div class="space-y-2">
                                    @foreach($counters->groupBy('location.name') as $locationName => $locationCounters)
                                        <div class="mb-2">
                                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ $locationName }}</p>
                                            <div class="space-y-1.5">
                                                @foreach($locationCounters as $c)
                                                    @php
                                                        $cCurrent = $c->currentTicket();
                                                        $cPreparing = $c->preparingTicket();
                                                    @endphp
                                                    <div class="p-2.5 rounded-lg {{ $c->currentOperator ? 'bg-green-50 dark:bg-green-900/20' : 'bg-slate-50 dark:bg-slate-700/50' }}">
                                                        <div class="flex items-center justify-between">
                                                            <div class="min-w-0">
                                                                <span class="font-semibold text-slate-900 dark:text-white text-xs truncate block">{{ $c->name }}</span>
                                                                <span class="block text-xs text-blue-600 truncate">{{ $c->stageLabel() }}</span>
                                                                @if($c->currentOperator)
                                                                    <span class="block text-xs text-slate-500 dark:text-slate-400 truncate">{{ $c->currentOperator->name }}</span>
                                                                @else
                                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Tidak aktif</span>
                                                                @endif
                                                            </div>
                                                            @if($c->currentOperator)
                                                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse flex-shrink-0 ml-2"></span>
                                                            @else
                                                                <span class="w-2 h-2 bg-slate-300 dark:bg-slate-600 rounded-full flex-shrink-0 ml-2"></span>
                                                            @endif
                                                        </div>
                                                        @if($cCurrent)
                                                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-600 text-xs">
                                                                <span class="font-bold text-slate-900 dark:text-white">{{ $cCurrent->ticket_number }}</span>
                                                                <span class="text-slate-500 dark:text-slate-400"> — {{ $cCurrent->customerStatusLabel() }}</span>
                                                            </div>
                                                        @elseif($c->currentOperator)
                                                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-600 text-xs text-slate-400 dark:text-slate-500">Kosong, siap menerima</div>
                                                        @endif
                                                        @if($cPreparing)
                                                            <div class="mt-1 text-xs text-purple-600 dark:text-purple-400">👋 Siap-siap: <span class="font-semibold">{{ $cPreparing->ticket_number }}</span></div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-100 dark:border-slate-700"><h3 class="font-bold text-slate-900 dark:text-white text-sm">Pintasan</h3></div>
                            <div class="p-3 space-y-2">
                                <a href="{{ route('locations.scan', $location) }}" target="_blank" class="flex items-center justify-between w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                    <span>QR Order</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v2m0 14v2M4 12H2m20 0h-2m-2.5-6.5L17 7m-12 10l-1.5 1.5M17 17l1.5 1.5M7 7L5.5 5.5"/></svg>
                                </a>
                                <a href="{{ route('locations.display', $location) }}" target="_blank" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                    <span>Display</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2v10a2 2 0 002 2z"/></svg>
                                </a>
                                @if(in_array('customers', $permissions))
                                    <button type="button" onclick="document.querySelector('[data-tab=&quot;tab-pelanggan&quot;]')?.click()" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Data Pelanggan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </button>
                                @endif
                                @if(auth()->user()->isOperator() || auth()->user()->isAdmin())
                                    <a href="{{ route('operator.booking-slots') }}" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Jadwal Reservasi</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2v12a2 2 0 002 2z"/></svg>
                                    </a>
                                @endif
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('operator.choose-location') }}" class="flex items-center justify-between w-full border border-dashed border-slate-300 dark:border-slate-600 hover:border-slate-400 text-slate-500 dark:text-slate-400 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Ganti Lokasi (Admin)</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>

                <!-- Data Pelanggan -->
                @if(in_array('customers', $permissions))
                    <div id="tab-pelanggan" class="tab-panel hidden mt-8 space-y-6">
                        <!-- Reservasi Section -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Reservasi</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $custBookings->count() }} reservasi ditemukan</p>
                                </div>
                                @if($canManageBookings)
                                    <button type="button" onclick="openBookingModal()" class="px-3 py-1.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-xs font-semibold transition-colors">Tambah Reservasi</button>
                                @endif
                            </div>
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <form method="GET" action="{{ route('operator.dashboard') }}#tab-pelanggan" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                                            <input type="text" name="bq" value="{{ $bookingSearch }}" placeholder="Nama, telepon, email, atau no. reservasi..." class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                            <input type="date" name="bdate" id="booking-date-input" value="{{ $bookingDate !== 'all' ? $bookingDate : '' }}" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                            <input type="checkbox" id="booking-date-all" {{ $bookingDate === 'all' ? 'checked' : '' }}>
                                            Tampilkan semua tanggal (tidak dibatasi hari ini)
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-sm font-semibold transition-colors">Terapkan Filter</button>
                                            @if($bookingSearch || $bookingDate !== now()->toDateString())
                                                <a href="{{ route('operator.dashboard') }}#tab-pelanggan" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors">Reset</a>
                                            @endif
                                        </div>
                                    </div>
                                </form>
                                <script>
                                    (function() {
                                        const checkbox = document.getElementById('booking-date-all');
                                        const dateInput = document.getElementById('booking-date-input');
                                        if (!checkbox || !dateInput) return;
                                        function applyState() {
                                            if (checkbox.checked) {
                                                dateInput.dataset.prevValue = dateInput.type === 'date' ? dateInput.value : (dateInput.dataset.prevValue || '');
                                                dateInput.type = 'text';
                                                dateInput.value = 'all';
                                                dateInput.readOnly = true;
                                                dateInput.classList.add('opacity-60');
                                            } else {
                                                dateInput.type = 'date';
                                                dateInput.readOnly = false;
                                                dateInput.value = dateInput.dataset.prevValue || '';
                                                dateInput.classList.remove('opacity-60');
                                            }
                                        }
                                        checkbox.addEventListener('change', applyState);
                                        if (checkbox.checked) applyState();
                                    })();
                                </script>
                            </div>
                            @if($custBookings->isEmpty())
                                <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">Tidak ada reservasi yang cocok dengan filter ini.</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm responsive-table">
                                        <thead>
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kode</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jadwal</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Layanan</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                            @foreach($custBookings as $booking)
                                                @php
                                                    $statusClasses = [
                                                        'pending' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                                        'confirmed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                                        'checked_in' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                                        'cancelled' => 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                                    ];
                                                    $statusLabels = [
                                                        'pending' => 'Menunggu',
                                                        'confirmed' => 'Terkonfirmasi',
                                                        'checked_in' => 'Check-in',
                                                        'cancelled' => 'Dibatalkan',
                                                    ];
                                                @endphp
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                                                    <td class="px-5 py-3.5" data-label="Kode">
                                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $booking->booking_code }}</span>
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Pelanggan">
                                                        <div class="flex flex-col">
                                                            <span class="font-medium text-slate-900 dark:text-white">{{ $booking->customer_name }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Kontak">
                                                        <div class="flex flex-col text-xs">
                                                            <span class="text-slate-600 dark:text-slate-300">{{ $booking->customer_phone }}</span>
                                                            <span class="text-slate-500 dark:text-slate-400">{{ $booking->customer_email ?? '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Jadwal">
                                                        <div class="flex flex-col text-xs">
                                                            <span class="text-slate-600 dark:text-slate-300">{{ $booking->booking_date->format('d M Y') }}</span>
                                                            <span class="text-slate-500 dark:text-slate-400">{{ is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format('H:i') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300" data-label="Layanan">{{ $booking->serviceCategory->name ?? '-' }}</td>
                                                    <td class="px-5 py-3.5" data-label="Status">
                                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                            {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right rt-actions" data-label="Aksi">
                                                        <div class="flex items-center justify-end gap-2">
                                                            @if($canManageBookings)
                                                                @if(in_array($booking->status, ['pending', 'confirmed']) && $booking->booking_date->isToday())
                                                                    <form method="POST" action="{{ route('operator.bookings.call', $booking) }}" class="inline" onsubmit="return confirm('Check-in dan panggil reservasi {{ $booking->booking_code }}?')">@csrf
                                                                        <button type="submit" class="px-2.5 py-1.5 bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white rounded-md text-xs font-semibold transition-colors">Panggil</button>
                                                                    </form>
                                                                @endif
                                                                @if(in_array($booking->status, ['pending', 'confirmed']))
                                                                    <a href="{{ route('operator.bookings.edit', $booking) }}" class="inline-block px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-md text-xs font-semibold transition-colors">Edit</a>
                                                                    <form method="POST" action="{{ route('operator.bookings.destroy', $booking) }}" class="inline" onsubmit="return confirm('Batalkan reservasi {{ $booking->booking_code }}?')">@csrf @method('DELETE')
                                                                        <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition-colors">Batal</button>
                                                                    </form>
                                                                @endif
                                                            @endif
                                                            @if($booking->ticket)
                                                                <a href="{{ route('tickets.show', $booking->ticket) }}" class="inline-block px-2.5 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-md text-xs font-semibold transition-colors">Lihat Tiket</a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 px-6 pb-4">
                                    {{ $custBookings->appends(['bq' => $bookingSearch, 'bdate' => $bookingDate])->links() }}
                                </div>
                            @endif
                        </div>

                    <!-- Tiket Section -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tiket</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $custTickets->count() }} tiket ditemukan</p>
                            </div>
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <form method="GET" action="{{ route('operator.dashboard') }}#tab-pelanggan" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                                            <input type="text" name="q" value="{{ $custSearch }}" placeholder="Nama, telepon, email, atau no. tiket..." class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                            <select name="status" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                                <option value="" {{ $custStatus === '' ? 'selected' : '' }}>Semua Status</option>
                                                <option value="waiting" {{ $custStatus === 'waiting' ? 'selected' : '' }}>Menunggu</option>
                                                <option value="calling" {{ $custStatus === 'calling' ? 'selected' : '' }}>Dipanggil</option>
                                                <option value="serving" {{ $custStatus === 'serving' ? 'selected' : '' }}>Sedang Dilayani</option>
                                                <option value="completed" {{ $custStatus === 'completed' ? 'selected' : '' }}>Selesai</option>
                                                <option value="cancelled" {{ $custStatus === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tahap</label>
                                            <select name="stage" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                                <option value="" {{ $custStage === '' ? 'selected' : '' }}>Semua Tahap</option>
                                                @foreach(\App\Models\Ticket::STAGE_ORDER as $stage)
                                                    <option value="{{ $stage }}" {{ $custStage === $stage ? 'selected' : '' }}>{{ \App\Models\Ticket::STAGE_LABELS[$stage] ?? $stage }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                            <input type="date" name="date" id="cust-date-input" value="{{ $custDate !== 'all' ? $custDate : '' }}" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                            <input type="checkbox" id="cust-date-all" {{ $custDate === 'all' ? 'checked' : '' }}>
                                            Tampilkan semua tanggal — cocok dipakai kalau cari pakai Kode Order (No. Antrian reset tiap hari, Kode Order tidak)
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-sm font-semibold transition-colors">Terapkan Filter</button>
                                            @if($custSearch || $custStatus || $custStage || $custDate !== now()->toDateString())
                                                <a href="{{ route('operator.dashboard') }}#tab-pelanggan" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors">Reset</a>
                                            @endif
                                        </div>
                                    </div>
                                </form>
                                <script>
                                    // Ticket_number ("PP-001") reset tiap hari, jadi kalau mau cari
                                    // pakai Kode Order (ORD-000123, tidak pernah reset) lintas hari,
                                    // filter tanggal harus dilepas. Checkbox ini yang ngurus itu: kalau
                                    // dicentang, input tanggal diganti nilainya jadi literal "all"
                                    // (makanya type sementara diubah ke text, karena input type="date"
                                    // menolak nilai selain format tanggal).
                                    (function() {
                                        const checkbox = document.getElementById('cust-date-all');
                                        const dateInput = document.getElementById('cust-date-input');
                                        if (!checkbox || !dateInput) return;
                                        function applyState() {
                                            if (checkbox.checked) {
                                                dateInput.dataset.prevValue = dateInput.type === 'date' ? dateInput.value : (dateInput.dataset.prevValue || '');
                                                dateInput.type = 'text';
                                                dateInput.value = 'all';
                                                dateInput.readOnly = true;
                                                dateInput.classList.add('opacity-60');
                                            } else {
                                                dateInput.type = 'date';
                                                dateInput.readOnly = false;
                                                dateInput.value = dateInput.dataset.prevValue || '';
                                                dateInput.classList.remove('opacity-60');
                                            }
                                        }
                                        checkbox.addEventListener('change', applyState);
                                        if (checkbox.checked) applyState();
                                    })();
                                </script>
                            </div>
                            @if($custTickets->isEmpty())
                                <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">Tidak ada tiket yang cocok dengan filter ini.</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm responsive-table">
                                        <thead>
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">No. Antrian</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kode Order</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Lokasi</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahap</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Order</th>
                                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                            @foreach($custTickets as $ct)
                                                @php
                                                    $ticketLocation = $ct->location;
                                                    $statusClasses = [
                                                        'waiting' => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600',
                                                        'calling' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                                        'serving' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                                        'completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                                        'cancelled' => 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                                    ];
                                                    $statusLabels = [
                                                        'waiting' => 'Menunggu',
                                                        'calling' => 'Dipanggil',
                                                        'serving' => 'Dilayani',
                                                        'completed' => 'Selesai',
                                                        'cancelled' => 'Dibatalkan',
                                                    ];
                                                @endphp
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                                                    <td class="px-5 py-3.5" data-label="No. Antrian">
                                                        <div class="flex flex-col">
                                                            <span class="font-semibold text-slate-900 dark:text-white">{{ $ct->ticket_number }}</span>
                                                            @if($ct->skip_count > 0)
                                                                <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-0.5">Lewati {{ $ct->skip_count }}x</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Kode Order">
                                                        <span class="font-mono text-xs text-slate-500 dark:text-slate-400" title="Kode permanen, tidak reset tiap hari — beda dengan No. Antrian">{{ $ct->order_code }}</span>
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Pelanggan">
                                                        <span class="font-medium text-slate-900 dark:text-white">{{ $ct->customer_name }}</span>
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Kontak">
                                                        <div class="flex flex-col text-xs">
                                                            <span class="text-slate-600 dark:text-slate-300">{{ $ct->customer_phone }}</span>
                                                            <span class="text-slate-500 dark:text-slate-400">{{ $ct->customer_email ?? '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300" data-label="Lokasi">
                                                        @if($ticketLocation)
                                                            <span class="inline-block px-2 py-1 rounded-md bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ $ticketLocation->name }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300" data-label="Tahap">{{ $ct->stageLabel() }}</td>
                                                    <td class="px-5 py-3.5" data-label="Status">
                                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold {{ $statusClasses[$ct->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                            {{ $statusLabels[$ct->status] ?? ucfirst($ct->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300 max-w-[200px]" data-label="Order">
                                                        <span class="line-clamp-2" title="{{ $ct->notes }}">{{ $ct->notes ?: '-' }}</span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right rt-actions" data-label="Aksi">
                                                        @if(in_array($ct->status, ['waiting', 'calling'], true) && in_array($ct->stage, auth()->user()->stagesForRole(), true))
                                                            <div class="flex items-center justify-end gap-2">
                                                                <a href="{{ route('operator.tickets.edit', $ct) }}" class="inline-block px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-md text-xs font-semibold transition-colors">Edit</a>
                                                                <form method="POST" action="{{ route('operator.tickets.destroy', $ct) }}" class="inline" onsubmit="return confirm('Batalkan tiket {{ $ct->ticket_number }}?')">@csrf @method('DELETE')
                                                                    <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition-colors">Hapus</button>
                                                                </form>
                                                            </div>
                                                        @elseif($ct->counter)
                                                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $ct->counter->name }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 px-6 pb-4">
                                    {{ $custTickets->appends(['q' => $custSearch, 'status' => $custStatus, 'stage' => $custStage, 'date' => $custDate])->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if($canManageBookings ?? false)
        <!-- Modal Tambah Reservasi: mengambang di atas layar, gak pindah halaman -->
        <div id="booking-modal-backdrop" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target === this) closeBookingModal()">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Tambah Reservasi</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Buat reservasi manual untuk pelanggan.</p>
                    </div>
                    <button type="button" onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('operator.bookings.store') }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                            class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                            <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                            <input type="date" name="booking_date" value="{{ old('booking_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jam</label>
                            <input type="time" name="booking_time" value="{{ old('booking_time') }}" required
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Layanan</label>
                        <select name="service_category_id" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="">- Pilih Layanan -</option>
                            @foreach($serviceCategories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                        <select name="location_id" class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            @foreach($bookingLocations ?? [] as $loc)
                                <option value="{{ $loc->id }}" {{ (old('location_id') ?? optional($location)->id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($errors->any())
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-lg p-3">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">Simpan Reservasi</button>
                        <button type="button" onclick="closeBookingModal()" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-lg font-semibold transition-colors">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openBookingModal() {
                const backdrop = document.getElementById('booking-modal-backdrop');
                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
            function closeBookingModal() {
                const backdrop = document.getElementById('booking-modal-backdrop');
                backdrop.classList.add('hidden');
                backdrop.classList.remove('flex');
                document.body.style.overflow = '';
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeBookingModal();
            });
            // Kalau habis submit ternyata ada validation error dari form
            // reservasi ini, buka lagi modalnya otomatis (biar pesan
            // errornya kelihatan, bukan ilang gitu aja di halaman dashboard).
            @if($errors->any() && (old('customer_name') || old('booking_date')))
                document.addEventListener('DOMContentLoaded', openBookingModal);
            @endif
        </script>
    @endif
</x-layout>