<x-layout title="Panel Operator">
    @push('styles')
    <style>
        /* Isi order (notes) boleh multi-baris (operator/pelanggan pakai Enter).
           whitespace-pre-line = Enter tetap jadi baris baru, spasi berlebih
           tetap dilipat. Dipasang di span-nya (bukan <p>) supaya newline
           indentasi Blade di antara span & tombol "Selengkapnya" tidak ikut
           kebaca sebagai baris baru. */
        .order-notes-short, .order-notes-full, .order-notes-cell, .ticket-order-text { white-space: pre-line; overflow-wrap: anywhere; }
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
                // Simpen tab yang lagi aktif ke URL hash (replaceState, BUKAN
                // location.hash langsung -- biar gak nambah histori
                // back-button tiap ganti tab, dan gak ada efek "loncat" ke
                // atas halaman) DAN ke sessionStorage.
                //
                // Dua-duanya perlu: hash doang ternyata gak cukup -- form
                // yang submit BENERAN ke server (bukan lewat fetch AJAX, mis.
                // "Tambah Reservasi") bikin browser pindah ke URL redirect
                // dari server, dan URL itu TIDAK PERNAH bisa bawa hash (hash
                // murni urusan browser, server sama sekali gak tau/gak bisa
                // ikut nyantumin). Makanya abis nambah reservasi/aksi apapun
                // yang masih lewat submit form biasa, tab selalu balik ke
                // "Antrian Loket" walau operator lagi ada di tab lain.
                // sessionStorage inilah yang jadi jaring pengaman: dibaca
                // lagi di bawah kalau hash kosong pas halaman dimuat ulang.
                history.replaceState(null, '', '#' + target);
                try { sessionStorage.setItem('papyrus-operator-active-tab', target); } catch (e) {}
            });
        });

        const hashTab = window.location.hash.replace('#', '');
        let storedTab = null;
        try { storedTab = sessionStorage.getItem('papyrus-operator-active-tab'); } catch (e) {}
        const initialTab = (hashTab && document.getElementById(hashTab)) ? hashTab
            : (storedTab && document.getElementById(storedTab)) ? storedTab
            : null;
        if (initialTab) {
            switchTab(initialTab);
            history.replaceState(null, '', '#' + initialTab);
        }

        // ============ Filter GET (Cari/Terapkan Filter/Reset/pagination) &
        // filter Lokasi — sekarang juga lewat fetch(), BUKAN navigasi biasa,
        // supaya tetap live-update tanpa reload sama sekali. Sebelumnya cuma
        // aksi POST (Panggil dkk) yang di-AJAX-kan; form/link GET masih bikin
        // browser pindah halaman penuh walau hasilnya cuma beda query string
        // di halaman yang sama. ============
        // Penanda "ada aksi user (filter/pagination/tombol) yang baru mulai".
        // Polling yang START sebelum aksi user tapi SELESAI sesudahnya
        // membawa data versi LAMA (tanpa filter baru) -- kalau ditempel,
        // hasil filter operator tertimpa balik sampai polling berikutnya.
        // Jadi respons polling dibuang kalau penandanya sudah berubah.
        let userPatchSeq = 0;

        async function fetchAndPatchUrl(url) {
            userPatchSeq++;
            // URL diganti DULU (bukan setelah fetch selesai) supaya polling
            // yang mulai selama request ini berjalan sudah memakai filter baru.
            history.replaceState(null, '', url);
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            showFlashFromDoc(doc);
            applyDashboardPatch(doc);
        }

        async function submitGetFormAjax(form) {
            if (form.dataset.submitting === '1') return;
            form.dataset.submitting = '1';
            // Form filter di-submit = pilihan sudah "disimpan": lepas penanda
            // dirty, kalau tidak hasil filternya sendiri ikut dilewati patch.
            delete form.dataset.dirty;
            delete form.dataset.dirtyAt;
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

        // (Fungsi submitLocationFilter dihapus -- filter "Lokasi:" sudah gak
        // ada lagi di UI, dashboard sekarang selalu nampilin semua lokasi
        // yang operator ini kelola.)

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
            if (!newStatsEl) return false;
            const newIdsRaw = (newStatsEl.dataset.waitingIds || '').split(',').filter(Boolean);
            const newIds = new Set(newIdsRaw);

            const freshlyArrived = newIdsRaw.filter(pair => !lastKnownWaitingIds.has(pair));
            if (freshlyArrived.length > 0) {
                const ticketNumber = freshlyArrived[0].split(':')[1] || null;
                alertNewTicket(ticketNumber);
            }
            lastKnownWaitingIds = newIds;
            return freshlyArrived.length > 0;
        }

        // ============ Bel "ting" untuk SEMUA aktivitas di dashboard ============
        // Bunyi tiap kali ada yang berubah: tiket baru di daftar tunggu, masuk
        // Siap-siap, di-Ready, masuk sesi foto, edit, pembayaran, selesai/batal,
        // reservasi baru/check-in -- dari siapa pun (termasuk klik kita sendiri),
        // biar semua staf tahu ada pergerakan. Deteksinya dari "sidik jari"
        // aktivitas hari ini (data-activity di #stats-section, lihat
        // OperatorController::buildActivityKeys): kunci yang BARU muncul sejak
        // refresh sebelumnya = ada aktivitas. Jalan lewat polling juga, jadi
        // gak tergantung Reverb.
        //
        // Aturan biar gak berisik/dobel:
        //  - bel panggilan (3x beep) & alert tiket baru sudah punya bunyi
        //    sendiri -> "ting" dilewati kalau itu baru bunyi (<2,5 dtk lalu);
        //  - jeda minimal 1,2 dtk antar "ting" (banyak perubahan sekaligus = 1 bunyi);
        //  - bisa dimatikan lewat tombol "Bel aktivitas" (disimpan di browser).
        // (waktu bel panggilan terakhir dicatat di layout: window._lastLoudBellAt)
        window._lastLoudBellAt = window._lastLoudBellAt || 0;

        function activityTingEnabled() {
            try { return localStorage.getItem('papyrusActivityTing') !== 'off'; } catch (e) { return true; }
        }

        // Dua nada lonceng pendek "ting-ting" (E6 lalu A6), tiap nada = nada dasar
        // + overtone khas lonceng yang meredam cepat. Beda dari bel panggilan.
        window.playActivityTing = function () {
            try {
                const ctx = getPapyrusAudioCtx();
                if (!ctx) return;
                const t0 = ctx.currentTime;
                [[1318.5, 0], [1760, 0.2]].forEach(([freq, offset]) => {
                    [[freq, 0.55], [freq * 2.76, 0.2]].forEach(([f, vol]) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(f, t0 + offset);
                        gain.gain.setValueAtTime(0.0001, t0 + offset);
                        gain.gain.exponentialRampToValueAtTime(vol, t0 + offset + 0.01);
                        gain.gain.exponentialRampToValueAtTime(0.0001, t0 + offset + 0.9);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t0 + offset);
                        osc.stop(t0 + offset + 0.95);
                    });
                });
            } catch (e) {
                console.log('Activity ting not supported');
            }
        };

        let lastActivityKeys = new Set(
            (document.getElementById('stats-section')?.dataset.activity || '')
                .split(',').filter(Boolean)
        );
        let lastTingAt = 0;

        function detectActivityFromPatch(doc, alreadyAlerted) {
            const el = doc.getElementById('stats-section');
            // Respons tanpa atribut (halaman error / versi lama) -> jangan ubah apa-apa.
            if (!el || !el.hasAttribute('data-activity')) return;

            const keys = (el.dataset.activity || '').split(',').filter(Boolean);
            const fresh = keys.filter(k => !lastActivityKeys.has(k));
            lastActivityKeys = new Set(keys);
            if (fresh.length === 0) return;

            const now = Date.now();
            if (alreadyAlerted) return;                       // tiket baru: sudah ada bel sendiri
            if (now - (window._lastLoudBellAt || 0) < 2500) return;  // baru bunyi bel panggilan
            if (now - lastTingAt < 1200) return;              // jangan bertubi-tubi
            if (!activityTingEnabled()) return;

            lastTingAt = now;
            window.playActivityTing();
        }

        // ============ PENGINGAT (reminder) ============
        // Hanya 3 hal yang sering terlupa (lihat OperatorController::buildReminderPayload):
        //   ready   -> CS lupa klik "Ready" untuk pelanggan di Siap-siap
        //   payment -> pembayaran sudah selesai tapi lupa ditekan "Selesai" di app
        //   edit    -> fotografer lupa menyelesaikan edit foto
        // Kapan mulai & tiap berapa menit diulang diatur Admin (menu "Pengingat").
        // Tiap ~15 detik dicek: tiket yang sudah melewati batas "mulai setelah" dan belum
        // diingatkan dalam "interval" terakhir -> bunyi nada pengingat + pesan di pojok
        // layar (+ notifikasi browser kalau diizinkan). Tiket yang sudah diselesaikan
        // hilang dari data, jadi pengingatnya berhenti sendiri. Waktu dihitung pakai jam
        // SERVER (offset dari `now` di data), jadi tidak terpengaruh jam komputer yang salah.
        const REMINDER_STATE_KEY = 'papyrusReminderState:{{ auth()->id() }}';
        let reminderData = null;
        let reminderServerOffsetMs = 0;

        function readReminderData(doc) {
            try {
                const el = doc.getElementById('stats-section');
                if (!el || !el.hasAttribute('data-reminders')) return null;
                const d = JSON.parse(el.getAttribute('data-reminders'));
                return d && Array.isArray(d.items) && d.settings ? d : null;
            } catch (e) {
                return null;
            }
        }

        function loadReminderState() {
            try { return JSON.parse(sessionStorage.getItem(REMINDER_STATE_KEY) || '{}') || {}; } catch (e) { return {}; }
        }
        function saveReminderState(st) {
            try { sessionStorage.setItem(REMINDER_STATE_KEY, JSON.stringify(st)); } catch (e) {}
        }

        const REMINDER_TEXT = {
            ready:   { color: '#16a34a', icon: 'check-circle', title: 'Klik Ready', hint: 'Kalau pelanggannya sudah siap, klik Ready supaya bisa dipanggil fotografer ke studio.' },
            payment: { color: '#d97706', icon: 'credit-card', title: 'Selesaikan pembayaran', hint: 'Kalau pembayarannya sudah selesai, tekan Selesai di app.' },
            edit:    { color: '#7c3aed', icon: 'photo', title: 'Selesaikan edit foto', hint: 'Kalau fotonya sudah selesai diedit, tekan Selesai Edit.' },
        };

        // Nada pengingat: tiga nada segitiga (naik-turun-naik), lebih rendah & lebih lambat
        // dari "ting-ting" aktivitas supaya gampang dibedakan.
        window.playReminderChime = function () {
            try {
                const ctx = getPapyrusAudioCtx();
                if (!ctx) return;
                const t0 = ctx.currentTime;
                [[880, 0], [660, 0.4], [880, 0.8]].forEach(([f, o]) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(f, t0 + o);
                    gain.gain.setValueAtTime(0.0001, t0 + o);
                    gain.gain.exponentialRampToValueAtTime(0.6, t0 + o + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + o + 0.55);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(t0 + o);
                    osc.stop(t0 + o + 0.6);
                });
            } catch (e) {
                console.log('Reminder chime not supported');
            }
        };

        function reminderToastContainer() {
            let c = document.getElementById('reminder-toasts');
            if (!c) {
                c = document.createElement('div');
                c.id = 'reminder-toasts';
                c.style.cssText = 'position:fixed;top:72px;right:16px;z-index:70;display:flex;flex-direction:column;gap:8px;width:340px;max-width:calc(100vw - 32px);';
                document.body.appendChild(c);
            }
            return c;
        }

        function reminderLine(it) {
            return it.n + (it.c ? ' (' + it.c + ')' : '') + ' \u2014 ' + (it.mins < 1 ? '<1' : it.mins) + ' mnt';
        }

        // Teks pelanggan pakai textContent (bukan innerHTML) -- nama bisa berisi karakter apa saja.
        function showReminderToast(type, list) {
            const meta = REMINDER_TEXT[type];
            if (!meta) return;
            const container = reminderToastContainer();
            const old = document.getElementById('reminder-toast-' + type);
            if (old) old.remove();

            const toast = document.createElement('div');
            toast.id = 'reminder-toast-' + type;
            toast.style.cssText = 'background:#fff;color:#0f172a;border-left:5px solid ' + meta.color + ';border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.25);padding:10px 12px;font:14px/1.35 sans-serif;';

            const head = document.createElement('div');
            head.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:8px;font-weight:700;';
            const title = document.createElement('span');
            title.style.cssText = 'display:inline-flex;align-items:center;gap:6px;';
            title.innerHTML = window.papyrusIconSvg(meta.icon, '1.25em', meta.color); // ikon statis (bukan input user)
            const titleText = document.createElement('span');
            titleText.textContent = meta.title + ' (' + list.length + ')';
            title.appendChild(titleText);
            const close = document.createElement('button');
            close.type = 'button';
            close.innerHTML = window.papyrusIconSvg('x-mark', '1.1em');
            close.setAttribute('aria-label', 'Tutup pengingat');
            close.style.cssText = 'border:0;background:transparent;font-size:16px;cursor:pointer;color:#64748b;';
            close.addEventListener('click', () => toast.remove());
            head.appendChild(title);
            head.appendChild(close);

            const body = document.createElement('div');
            body.style.cssText = 'margin-top:4px;font-size:13px;';
            list.slice(0, 4).forEach((it) => {
                const row = document.createElement('div');
                row.textContent = reminderLine(it);
                body.appendChild(row);
            });
            if (list.length > 4) {
                const more = document.createElement('div');
                more.style.color = '#64748b';
                more.textContent = '+ ' + (list.length - 4) + ' tiket lagi';
                body.appendChild(more);
            }
            const hint = document.createElement('div');
            hint.style.cssText = 'margin-top:4px;font-size:12px;color:#475569;';
            hint.textContent = meta.hint;

            toast.appendChild(head);
            toast.appendChild(body);
            toast.appendChild(hint);
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentNode) toast.remove(); }, 40000);
        }

        function checkReminders() {
            if (!reminderData) return;
            const st = loadReminderState();
            const now = Date.now();
            const serverNowMs = now + reminderServerOffsetMs;
            const present = new Set();
            const due = {};

            reminderData.items.forEach((it) => {
                const key = it.t + ':' + it.id;
                present.add(key);
                const cfg = reminderData.settings[it.t];
                if (!cfg || !cfg.on) return;
                const elapsedMs = serverNowMs - it.since * 1000;
                if (elapsedMs < cfg.start * 60000) return;
                const last = st[key];
                if (last && now - last < cfg.every * 60000) return;
                (due[it.t] = due[it.t] || []).push(Object.assign({}, it, { mins: Math.floor(elapsedMs / 60000) }));
                st[key] = now;
            });

            // Tiket yang sudah diselesaikan: lupakan, supaya kalau muncul lagi dihitung dari awal.
            Object.keys(st).forEach((k) => { if (!present.has(k)) delete st[k]; });
            saveReminderState(st);

            const types = Object.keys(due);
            if (types.length === 0) return;

            types.forEach((t) => showReminderToast(t, due[t]));
            window.playReminderChime();
            window.vibrateDevice && window.vibrateDevice([150, 80, 150]);
            const first = due[types[0]];
            showNotificationCrossPlatform('\u23F0 ' + REMINDER_TEXT[types[0]].title, {
                body: first.slice(0, 3).map(reminderLine).join(', ') + (types.length > 1 ? ' (+ pengingat lain)' : ''),
                icon: '/images/logo-papyrus.png',
                tag: 'operator-reminder',
            });
        }

        function updateRemindersFromPatch(doc) {
            const d = readReminderData(doc);
            if (!d) return;
            reminderData = d;
            reminderServerOffsetMs = d.now * 1000 - Date.now();
            checkReminders();
        }

        (function initReminders() {
            const d = readReminderData(document);
            if (d) {
                reminderData = d;
                reminderServerOffsetMs = d.now * 1000 - Date.now();
            }
            setInterval(checkReminders, 15000);
            setTimeout(checkReminders, 4000);
        })();

        // Terapkan potongan HTML baru (hasil fetch) ke halaman yang sedang
        // terbuka TANPA reload — cuma id-id ini yang ditimpa isinya.
        function applyDashboardPatch(doc) {
            const newTicketAlerted = detectNewTicketsFromPatch(doc);
            detectActivityFromPatch(doc, newTicketAlerted);
            updateRemindersFromPatch(doc);

            const statusEl = document.getElementById('refresh-status');
            const activeTab = document.querySelector('.tab-btn.active');
            const activeTabId = activeTab ? activeTab.getAttribute('data-tab') : null;

            const refreshIds = ['stats-section', 'current-ticket-panel', 'status-loket-panel', 'tab-antrian', 'tab-reservasi', 'tab-reservasi-badge', 'tab-pelanggan'];
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
                // Cuma pertahankan panel yang lagi ada input/textarea BELUM
                // DISIMPAN di dalamnya. Sebelumnya ini cuma cek "lagi fokus
                // di situ SEKARANG" — begitu operator pencet Tab buat pindah
                // ke tombol Simpan (fokus lepas dari kotak Order sepersekian
                // detik), refresh 2 detik berikutnya nganggep kotak itu
                // "bukan lagi diketik" dan nimpa isinya pakai data lama dari
                // server, jadi tulisan yang belum sempat disimpan hilang.
                // Sekarang dilacak lewat flag data-dirty (di-set saat
                // ngetik, dilepas begitu form-nya disubmit), bukan cuma
                // status fokus sesaat.
                if (oldEl.querySelector('[data-dirty="1"]')) return;

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
                // Angka durasi yang lagi jalan (.waktu-live) BEDA di tiap fetch
                // (server ngitung "detik sekarang", browser ngitung sendiri
                // tiap detik) -- kalau ikut dibandingkan, panel yang berisi
                // tiket berjalan dianggap "berubah" TIAP polling dan seluruh
                // tabel dibangun ulang tiap 5 detik. Jadi teksnya diabaikan
                // di perbandingan; angkanya tetap hidup lewat ticker JS.
                const stripLive = (h) => h
                    .replace(/(<span class="waktu-live"[^>]*>)[^<]*(<\/span>)/g, '$1$2')
                    // Status buka/tutup "Selengkapnya" cuma ada di browser -- jangan dianggap data berubah
                    // (kalau ikut dibandingkan, panel dibangun ulang tiap polling dan kartu berkedip).
                    .replace(/ data-open="[01]"/g, '')
                    .replace(/ hidden( order-notes-extra)/g, '$1')
                    .replace(/(<button[^>]*data-more-label[^>]*>)[^<]*(<\/button>)/g, '$1$2')
                    // classList.toggle merapikan spasi di atribut class -- samakan supaya tidak dianggap beda.
                    .replace(/class="([^"]*)"/g, (m, c) => 'class="' + c.trim().replace(/\s+/g, ' ') + '"');
                if (stripLive(oldEl.innerHTML) !== stripLive(newEl.innerHTML)) {
                    updated = true;
                    // Tabel lebar di-scroll ke samping: innerHTML baru bikin
                    // scrollLeft balik ke 0 -- simpan & kembalikan.
                    const scrolls = Array.from(oldEl.querySelectorAll('.overflow-x-auto')).map(el => el.scrollLeft);
                    oldEl.innerHTML = newEl.innerHTML;
                    oldEl.querySelectorAll('.overflow-x-auto').forEach((el, i) => { if (scrolls[i]) el.scrollLeft = scrolls[i]; });
                }
            });

            if (activeTabId && document.querySelector(`[data-tab="${activeTabId}"]`)) {
                switchTab(activeTabId);
            }

            if (updated && statusEl) {
                statusEl.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
                statusEl.classList.add('text-green-600', 'dark:text-green-400');
                setTimeout(() => {
                    statusEl.textContent = 'Refresh otomatis aktif';
                    statusEl.classList.remove('text-green-600', 'dark:text-green-400');
                }, 2000);
            }

            // Checkbox "Tampilkan semua tanggal" (Reservasi & Tiket) butuh
            // JS buat kerja (ubah input tanggal jadi literal "all"), dan
            // sebelumnya dipasang lewat <script> yang nempel LANGSUNG di
            // dalam #tab-pelanggan. Masalahnya: <script> yang ikut ke-ganti
            // lewat innerHTML (persis yang kejadian di sini, tiap ~2 detik
            // auto-refresh) TIDAK PERNAH dieksekusi ulang oleh browser --
            // itu memang perilaku standar innerHTML, bukan bug DOM. Jadi
            // checkbox-nya cuma "hidup" beberapa detik pertama doang, abis
            // itu jadi checkbox mati (nge-klik gak ngapa-ngapain) begitu
            // auto-refresh pertama lewat. Sekarang di-sync ulang di sini
            // (dipanggil abis SETIAP patch, bukan cuma sekali di awal) --
            // lihat papyrusSyncAllDateCheckboxes & listener delegasi
            // globalnya di bawah, yang nempel di document (elemen yang
            // TIDAK PERNAH ke-replace), jadi tetap hidup selama-lamanya.
            papyrusSyncAllDateCheckboxes();

            return updated;
        }

        // Nyalain balik tampilan visual checkbox "Tampilkan semua tanggal"
        // (Reservasi & Tiket) abis DOM-nya di-patch ulang -- kalau
        // checkbox-nya lagi tercentang (server bilang begitu lewat atribut
        // checked, karena filter bdate/date=all lagi aktif), pastikan input
        // tanggal di sebelahnya juga ikut ditampilin sebagai "all" (bukan
        // balik nunjukin kalender kosong yang membingungkan).
        function papyrusSyncAllDateCheckboxes(root) {
            (root || document).querySelectorAll('.js-toggle-all-dates').forEach(checkbox => {
                const dateInput = document.getElementById(checkbox.dataset.target);
                if (!dateInput) return;
                if (checkbox.checked) {
                    if (dateInput.type !== 'text') {
                        dateInput.type = 'text';
                    }
                    dateInput.value = 'all';
                    dateInput.readOnly = true;
                    dateInput.classList.add('opacity-60');
                } else if (dateInput.type === 'text' && dateInput.value === 'all') {
                    // Checkbox barusan lepas centang tapi input masih dalam
                    // mode "all" dari sebelumnya -- balikin ke input tanggal
                    // normal & kosong (bukan maksa ke tanggal hari ini,
                    // biar operator yang pilih sendiri).
                    dateInput.type = 'date';
                    dateInput.readOnly = false;
                    dateInput.value = '';
                    dateInput.classList.remove('opacity-60');
                }
            });
        }

        // Delegasi di document (BUKAN di checkbox-nya langsung) -- listener
        // ini nempel ke elemen yang gak pernah ilang/ke-replace, jadi tetap
        // jalan biarpun checkbox aslinya udah berkali-kali diganti lewat
        // innerHTML patch. Kebalikannya, addEventListener yang dipasang
        // LANGSUNG ke checkbox bakal mati tiap kali panelnya di-refresh.
        document.addEventListener('change', function (e) {
            const checkbox = e.target.closest('.js-toggle-all-dates');
            if (!checkbox) return;
            const dateInput = document.getElementById(checkbox.dataset.target);
            if (!dateInput) return;
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
        });

        // Pilihan filter (Cari/Status/Tahap/Tanggal/centang "semua tanggal")
        // yang BELUM di-submit ikut ketimpa polling begitu fokus lepas dari
        // kolomnya (mis. klik di luar sebentar), karena formulirnya ada di
        // dalam panel yang di-refresh. Ditandai data-dirty (dilewati oleh
        // applyDashboardPatch) sampai di-submit -- dan kedaluwarsa otomatis
        // 60 detik biar panel gak beku selamanya kalau operator pergi.
        function markFilterFormDirty(e) {
            const form = e.target.closest && e.target.closest('#tab-pelanggan form[method="get" i]');
            if (!form) return;
            form.dataset.dirty = '1';
            form.dataset.dirtyAt = String(Date.now());
        }
        document.addEventListener('input', markFilterFormDirty);
        document.addEventListener('change', markFilterFormDirty);
        setInterval(() => {
            document.querySelectorAll('#tab-pelanggan form[data-dirty="1"]').forEach(f => {
                if (Date.now() - parseInt(f.dataset.dirtyAt || '0', 10) > 60000) {
                    delete f.dataset.dirty;
                    delete f.dataset.dirtyAt;
                }
            });
        }, 10000);

        // Sinkronin juga pas halaman pertama kali dimuat (bukan cuma abis
        // patch) -- kalau operator refresh manual saat filter "semua
        // tanggal" masih aktif di URL, checkbox-nya kerender checked=true
        // dari server, tapi input tanggalnya masih perlu di-switch ke mode
        // teks "all" di sisi client.
        papyrusSyncAllDateCheckboxes();


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
                const seqAtStart = userPatchSeq;
                const url = new URL(window.location.href);
                url.hash = '';
                url.searchParams.set('_', Date.now());
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                });
                if (!response.ok) return;
                if (response.redirected && new URL(response.url).pathname !== window.location.pathname) return;
                const html = await response.text();
                if (seqAtStart !== userPatchSeq) return; // ada aksi user selama polling jalan -> data ini basi
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
            userPatchSeq++;
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

        // Reverb (WebSocket real-time, lihat window.Echo di bawah) sekarang
        // jadi mekanisme UTAMA -- perubahan data langsung didorong seketika,
        // BUKAN nunggu polling. setInterval di sini cuma jaring pengaman
        // (mis. koneksi WebSocket sempat putus/reconnect) makanya intervalnya
        // dilonggarin dari 2 detik jadi 20 detik -- sebelumnya 2 detik bikin
        // dashboard kerasa "refresh terus" padahal Reverb-nya sendiri sudah
        // real-time.
        // Diturunin lagi ke 5 detik (dari 20 detik) -- 20 detik itu ASUMSI
        // Reverb (WebSocket) beneran konek dan jadi mesin utama, tapi itu
        // BELUM DIKONFIRMASI kepakai beneran di server produksi. Kalau
        // Reverb-nya gak konek (mis. port ke-block firewall), operator jadi
        // beneran nunggu sampai 20 detik buat lihat perubahan -- kerasa
        // "delay lama". 5 detik ini kompromi lebih aman selama status
        // Reverb belum dipastikan; begitu dikonfirmasi Reverb jalan mulus,
        // boleh dinaikin lagi.
        setInterval(refreshDashboard, 5000);
        refreshDashboard();

        // ============ Alert operator: suara + getar + notifikasi ============
        // Dipicu tiap kali ada TIKET BARU masuk ke antrian lokasi ini (bukan
        // saat operator sendiri yang memanggil, biar nggak berisik ulang-ulang).
        //
        // PENTING soal showNotificationCrossPlatform() di bawah: Chrome di
        // Android TIDAK mendukung `new Notification(...)` yang dipanggil
        // langsung dari script halaman -- di situ notifikasinya cuma diam
        // aja / gak nongol sama sekali, walau izin sudah granted (beda dari
        // Chrome desktop yang masih mengizinkan cara lama ini, makanya
        // sebelumnya kerasa "kok di PC bisa, di HP enggak"). Di Android,
        // notifikasi WAJIB lewat Service Worker punya
        // `registration.showNotification()` -- fungsi ini otomatis pakai
        // jalur itu kalau ada, dan fallback ke cara lama kalau SW belum siap
        // (mis. di browser lama/desktop tanpa SW aktif).
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

        function alertNewTicket(ticketNumber) {
            window.playNotificationSound && window.playNotificationSound();
            window.vibrateDevice && window.vibrateDevice([200, 80, 200]);
            showNotificationCrossPlatform('🎟️ Tiket baru masuk', {
                body: ticketNumber ? `Tiket ${ticketNumber} menunggu di antrian.` : 'Ada pelanggan baru mengambil tiket.',
                icon: '/images/logo-papyrus.png',
                vibrate: [200, 80, 200],
                tag: 'operator-new-ticket',
            });
        }

        // Tombol "Tes suara": bunyikan bel panggilan, lalu ucapkan contoh pengumuman.
        // Klik ini juga yang "membuka kunci" suara di browser (aturan autoplay), jadi
        // kalau suara terasa mati di perangkat ini, klik tombol ini sekali.
        (function setupTestSound() {
            const btn = document.getElementById('btn-test-sound');
            if (!btn) return;
            btn.addEventListener('click', () => {
                window.playNotificationSound && window.playNotificationSound();
                setTimeout(() => {
                    (window.papyrusSpeak || ((t, cb) => cb()))('Tes suara. Nomor antrian P P sepuluh, silakan menuju Studio satu.');
                }, 1900);
            });
        })();

        // Tombol "Bel aktivitas: ON/OFF" -- pilihan disimpan di browser (per komputer/HP).
        (function setupActivityTingToggle() {
            const btn = document.getElementById('btn-toggle-activity-ting');
            if (!btn) return;
            const stateEl = document.getElementById('activity-ting-state');
            const iconEl = document.getElementById('activity-ting-icon');
            function paint() {
                const on = activityTingEnabled();
                stateEl.textContent = on ? 'ON' : 'OFF';
                iconEl.innerHTML = window.papyrusIconSvg(on ? 'bell' : 'bell-slash', '1.15em');
                btn.style.opacity = on ? '1' : '0.6';
            }
            btn.addEventListener('click', () => {
                const turnOn = !activityTingEnabled();
                try { localStorage.setItem('papyrusActivityTing', turnOn ? 'on' : 'off'); } catch (e) {}
                paint();
                if (turnOn) window.playActivityTing(); // contoh bunyinya sekalian
            });
            paint();
        })();

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
                        showNotificationCrossPlatform('✅ Notifikasi operator aktif', {
                            body: 'Anda akan diberi tahu saat ada tiket baru masuk.',
                            icon: '/images/logo-papyrus.png',
                        });
                    }
                } catch (err) {
                    console.warn('Notification request failed:', err);
                }
            });
        }

        // ============ Pengumuman Suara (Text-to-Speech) — panggilan PA ============
        // Dipicu tiap kali ADA TIKET YANG DIPANGGIL di lokasi ini (siapapun
        // operatornya) -- bukan cuma tiket baru masuk seperti alertNewTicket()
        // di atas. Ini berfungsi kayak sistem pengumuman fisik: begitu ada
        // loket manapun manggil nomor antrian, komputer operator (yang
        // biasanya disambungkan ke speaker ruang tunggu) ikut bunyi bel +
        // ngomong "Nomor antrian X, menuju ke Y" -- sama seperti yang
        // pelanggan dengar di HP-nya sendiri.
        //
        // `kind` bedain kalimat: 'calling' (beneran dipanggil) vs
        // 'preparing' (siap-siap). 'serving'/'editing' SENGAJA gak lewat
        // fungsi ini sama sekali -- gak butuh aksi fisik pelanggan, cukup
        // refresh tampilan diam-diam (lihat listener Echo di bawah).
        // Antrian pengumuman -- SEBELUMNYA speechSynthesis.cancel() dipanggil
        // di SETIAP pemanggilan baru, yang artinya kalau operator manggil 2
        // tiket berdekatan (mis. "Siap-siap" nyusul abis "Panggil"),
        // pengumuman PERTAMA bisa KEPOTONG DI TENGAH KALIMAT begitu yang
        // kedua masuk. Sekarang dipakai antrian FIFO: tiap pengumuman WAJIB
        // selesai (event 'onend') dulu baru lanjut ke berikutnya.
        let operatorAnnounceQueue = [];
        let operatorAnnounceBusy = false;

        function announceCalledTicketOperator(ticketNumber, counterName, kind = 'calling', isRecall = false) {
            if (!ticketNumber) return;
            // Bel/"ting ting" (beep) TETAP bunyi buat SEMUA panggilan, baik
            // yang pertama kali maupun Panggil Ulang -- yang dihilangkan
            // CUMA suara TTS "silakan menuju ke ..."-nya. Notifikasi push ke
            // HP pelanggan juga tetap jalan seperti biasa (itu dikirim dari
            // backend, gak disentuh di sini).
            window.playNotificationSound && window.playNotificationSound();
            // TTS SENGAJA DIHILANGKAN buat panggilan PERTAMA kali
            // (isRecall === false) -- KHUSUS tahap Registrasi/Order (CS); pemanggil
            // memberi isRecall=true juga buat tahap Studio & Pembayaran, jadi
            // di sana TTS tetap bunyi. Panggil Ulang (isRecall === true) tetap
            // dapet TTS penuh seperti sebelumnya.
            if (kind === 'calling' && !isRecall) return;
            if (!('speechSynthesis' in window)) return;
            counterName = counterName ? counterName.replace(/\s*&\s*/g, ' dan ') : counterName; // "&" dibaca "dan" (TTS bisa membacanya "ampersand" atau melewatinya)
            const spoken = spokenTicketNumber(ticketNumber);
            const text = kind === 'preparing'
                ? (counterName
                    ? `Nomor antrian ${spoken}, silakan bersiap-siap di ruang ganti terdekat, untuk menuju ${counterName}.`
                    : `Nomor antrian ${spoken}, silakan bersiap-siap di ruang ganti terdekat.`)
                : `Nomor antrian ${spoken}, silakan menuju ke ${counterName || 'loket'}.`;

            if (operatorAnnounceQueue.length >= 5) {
                operatorAnnounceQueue.shift();
            }
            operatorAnnounceQueue.push(text);
            processOperatorAnnounceQueue();
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

        function processOperatorAnnounceQueue() {
            if (operatorAnnounceBusy || operatorAnnounceQueue.length === 0) return;
            operatorAnnounceBusy = true;
            const text = operatorAnnounceQueue.shift();
            setTimeout(() => {
                // papyrusSpeak = bicara dengan pengaman macet (lihat components/layout.blade.php)
                    (window.papyrusSpeak || ((txt, cb) => cb()))(text, () => {
                        operatorAnnounceBusy = false;
                        processOperatorAnnounceQueue();
                    });
            }, 2000);
        }

        // Fallback nama tujuan kalau data counter kebetulan gak kekirim --
        // SENGAJA TIDAK pakai kata generik "loket", tebak dari tahap
        // tiketnya sendiri biar tetap nyebut nama tempat yang konkret.
        function fallbackDestinationName(stage) {
            if (stage === 'payment') return 'Kasir';
            if (stage === 'registration') return 'CS';
            return 'Studio';
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
                .listen('.ticket.called', (e) => {
                    announceCalledTicketOperator(e.ticket?.ticket_number, e.counter?.name ?? fallbackDestinationName(e.ticket?.stage), 'calling', e.is_recall === true || e.ticket?.stage !== 'registration');
                    refreshDashboard();
                })
                .listen('.ticket.status.changed', (e) => {
                    // "Siap-siap" (preparing) ikut dibunyiin, sama kayak
                    // display TV -- lihat catatan di announceCalledTicketOperator().
                    if (e.ticket?.status === 'preparing') {
                        announceCalledTicketOperator(e.ticket?.ticket_number, e.counter?.name, 'preparing');
                    }
                    refreshDashboard();
                })
                .listen('.queue.updated', refreshDashboard);

            // Indikator "realtime-status" di header -- nunjukin status koneksi
            // WebSocket-nya beneran langsung tersambung atau nggak, biar gak
            // perlu buka DevTools tiap mau ngecek.
            (function () {
                const dot = document.getElementById('realtime-dot');
                const label = document.getElementById('realtime-label');
                const wrap = document.getElementById('realtime-status');
                if (!dot || !label || !wrap) return;
                wrap.classList.remove('hidden');

                function setState(state) {
                    const map = {
                        connected: ['bg-green-500', 'Live'],
                        connecting: ['bg-amber-400 animate-pulse', 'Menyambungkan…'],
                        unavailable: ['bg-red-500', 'Terputus (pakai polling 5d)'],
                        failed: ['bg-red-500', 'Terputus (pakai polling 5d)'],
                        disconnected: ['bg-red-500', 'Terputus (pakai polling 5d)'],
                    };
                    const [dotClass, text] = map[state] ?? ['bg-slate-300', state];
                    dot.className = 'w-1.5 h-1.5 rounded-full ' + dotClass;
                    label.textContent = text;
                }

                try {
                    const pusher = window.Echo.connector.pusher;
                    setState(pusher.connection.state);
                    pusher.connection.bind('state_change', (states) => setState(states.current));
                } catch (err) {
                    // Connector-nya beda (bukan Pusher-protocol) atau belum siap --
                    // gak fatal, cuma indikatornya diem di "Cek koneksi...".
                    console.warn('Gak bisa pantau status koneksi realtime:', err);
                }
            })();
        } else {
            const wrap = document.getElementById('realtime-status');
            const dot = document.getElementById('realtime-dot');
            const label = document.getElementById('realtime-label');
            if (wrap && dot && label) {
                wrap.classList.remove('hidden');
                dot.className = 'w-1.5 h-1.5 rounded-full bg-red-500';
                label.textContent = 'Terputus (pakai polling 5d)';
            }
        }

        window.refreshDashboard = refreshDashboard;

        // Batch Edit Ticket Checkboxes
        const selectAllBatch = document.getElementById('selectAllBatchTickets');
        const batchCheckboxes = document.querySelectorAll('.batch-ticket-checkbox');
        const batchEditButton = document.getElementById('batchEditButton');

        function updateBatchEditButton() {
            const checkedCount = document.querySelectorAll('.batch-ticket-checkbox:checked').length;
            if (batchEditButton) {
                batchEditButton.disabled = checkedCount === 0;
            }
            if (selectAllBatch) {
                selectAllBatch.checked = batchCheckboxes.length > 0 && checkedCount === batchCheckboxes.length;
            }
        }

        if (selectAllBatch) {
            selectAllBatch.addEventListener('change', function () {
                const isChecked = this.checked;
                batchCheckboxes.forEach(cb => cb.checked = isChecked);
                updateBatchEditButton();
            });
        }

        batchCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBatchEditButton);
        });

        // Initial state
        updateBatchEditButton();

        // Widget "WAKTU" (tabel Reservasi & Tiket di tab Data Pelanggan):
        // angka buat tahap yang MASIH BERJALAN harus jalan hidup per detik
        // (bukan cuma ke-update tiap kali refreshDashboard() ganti innerHTML
        // tab-pelanggan). Query ulang tiap tick (bukan cache referensi
        // elemen) karena innerHTML-nya diganti secara berkala.
        function formatDurationJs(totalSeconds) {
            totalSeconds = Math.max(0, Math.floor(totalSeconds));
            if (totalSeconds < 60) return totalSeconds + 's';
            const minutes = Math.floor(totalSeconds / 60);
            if (minutes < 60) return minutes + 'm ' + (totalSeconds % 60) + 's';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + 'h ' + (minutes % 60) + 'm';
            const days = Math.floor(hours / 24);
            return days + 'd ' + (hours % 24) + 'h';
        }
        setInterval(() => {
            document.querySelectorAll('.waktu-live').forEach((el) => {
                const start = parseInt(el.dataset.start, 10);
                if (!start) return;
                el.textContent = formatDurationJs((Date.now() - start) / 1000);
            });
        }, 1000);
    </script>

    @endpush
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <!-- Top Bar -->
        <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-20">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex items-center justify-between gap-2 h-14">
                    <div class="flex items-center gap-3 sm:gap-6 min-w-0 flex-1">
                        <div class="flex items-center gap-3 min-w-0 flex-shrink-0">
                            @php
                                $roleAvatarIcons = [
                                    'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
                                    'camera' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />',
                                    'user-group' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
                                    'eye' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
                                ];
                                $roleAvatar = match(true) {
                                    auth()->user()->isAdmin() => ['icon' => 'shield-check', 'gradient' => 'from-indigo-500 to-purple-500'],
                                    auth()->user()->isFotografer() => ['icon' => 'camera', 'gradient' => 'from-amber-500 to-orange-500'],
                                    auth()->user()->isOperator() => ['icon' => 'user-group', 'gradient' => 'from-sky-500 to-blue-500'],
                                    default => ['icon' => 'eye', 'gradient' => 'from-slate-400 to-slate-500'],
                                };
                            @endphp
                            <div class="w-9 h-9 bg-gradient-to-br {{ $roleAvatar['gradient'] }} rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0" title="Masuk sebagai {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white">{!! $roleAvatarIcons[$roleAvatar['icon']] !!}</svg>
                            </div>
                            <div class="min-w-0">
                                <h1 class="text-base font-bold text-slate-900 dark:text-white truncate">Panel {{ $counter->name ?? 'CS & Kasir' }}</h1>
                                {{-- Sebelumnya cuma "Semua Lokasi" (info yang gak berguna buat
                                     operator) -- sekarang nunjukin SIAPA yang login & perannya,
                                     biar jelas panel ini lagi dipegang siapa. Nama loket sudah
                                     ada di judul "Panel ..." di atasnya. --}}
                                @php
                                    $headerUser = auth()->user();
                                    $headerRole = match(true) {
                                        $headerUser->isAdmin() => 'Admin',
                                        $headerUser->isFotografer() => 'Fotografer',
                                        $headerUser->isOperator() => 'CS & Kasir',
                                        default => 'Viewer',
                                    };
                                @endphp
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate" title="Login sebagai {{ $headerUser->name }} ({{ $headerRole }})">
                                    {{ $counterMessage ?? ($headerUser->name . ' · ' . $headerRole) }}
                                </p>
                            </div>
                        </div>
                        {{-- Tautan "Dashboard / Jadwal Reservasi / Master Data Pelanggan" di sebelah judul panel sengaja dihapus. --}}
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" id="btn-test-sound" title="Tes bel dan suara pemanggilan di perangkat ini (sekaligus mengaktifkan suara di browser)" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors">
                            <x-papyrus-icon name="speaker-wave" size="1.15em" /><span>Tes suara</span>
                        </button>
                        <button type="button" id="btn-toggle-activity-ting" title="Bunyikan bel &quot;ting&quot; tiap ada aktivitas (daftar tunggu, siap-siap, sesi foto, dst)" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors">
                            <span id="activity-ting-icon" style="display:inline-flex;"><x-papyrus-icon name="bell" /></span>
                            <span>Bel aktivitas: <span id="activity-ting-state">ON</span></span>
                        </button>
                        <button type="button" id="btn-enable-operator-notif" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-lavender/15 hover:bg-lavender/25 text-lavender rounded-lg text-xs font-semibold transition-colors" title="Aktifkan suara & notifikasi tiket baru">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span>Aktifkan Notifikasi</span>
                        </button>
                        {{-- Tombol Refresh manual + teks "Auto-refresh ON"/"Updated ..."
                             sengaja disembunyikan dari tampilan -- bikin bingung
                             kayak halamannya reload tiap beberapa detik, padahal
                             live update-nya sudah jalan sendiri di background
                             (setInterval refreshDashboard, tiap 2 detik, AJAX,
                             cuma nempel bagian yang berubah -- BUKAN reload
                             halaman). Elemen #refresh-status tetap ada (class
                             "hidden", bukan dihapus) karena applyDashboardPatch()
                             di JS masih baca elemen ini buat tahu kapan ada
                             perubahan data -- kalau dihapus, gak crash sih, cuma
                             biar aman aja hidden. --}}
                        <span id="refresh-status" class="hidden">Refresh otomatis aktif</span>
                        {{-- Indikator kecil status koneksi real-time (Reverb/WebSocket)
                             -- biar kelihatan langsung tanpa buka DevTools apakah update
                             lagi jalan instan (Live) atau cuma andalin polling 5 detik
                             (mis. Reverb-nya gak konek, port ke-block firewall, dst). --}}
                        <span id="realtime-status" class="hidden sm:inline-flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500" title="Status koneksi real-time">
                            <span id="realtime-dot" class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                            <span id="realtime-label">Cek koneksi...</span>
                        </span>
                        @if($counter)
                            <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">{{ $counter->stageLabel() }}</span>
                            <form method="POST" action="{{ route('operator.counters.toggle-pause', $counter) }}" class="inline-flex">
                                @csrf
                                <input type="hidden" name="is_paused" value="{{ $counter->is_paused ? '0' : '1' }}">
                                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $counter->is_paused ? 'bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-400' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300' }}" title="{{ $counter->is_paused ? 'Loket lagi istirahat — klik buat aktif lagi' : 'Klik buat istirahat sebentar, gak akan dipanggilin pelanggan baru' }}">
                                    @if($counter->is_paused)
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3a2 2 0 002 2h2a2 2 0 002-2v-3M3 9a1 1 0 011-1h13a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V9zm14 0h2a2 2 0 012 2v1a2 2 0 01-2 2h-2M6 6V4a1 1 0 011-1h6a1 1 0 011 1v2"/></svg>
                                        <span class="hidden xs:inline">Istirahat — </span>Aktifkan Lagi
                                    @else
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="hidden xs:inline">Istirahat</span>
                                    @endif
                                </button>
                            </form>
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $counter->is_paused ? 'bg-amber-400' : 'bg-green-500 animate-pulse' }}" title="{{ $counter->is_paused ? 'Loket istirahat' : 'Loket aktif' }}"></span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{--
            Section Tabs (Antrian Loket/Sesi Foto, Reservasi Hari Ini, Data
            Pelanggan) -- satu-satunya baris navigasi di halaman Dashboard
            ini, sticky nempel PERSIS di bawah Top Bar (top-14, tinggi Top
            Bar 56px). z-10 (di bawah z-20 Top Bar).

            Sebelumnya ada baris KEDUA di atas ini juga (sub-nav "Dashboard /
            Jadwal Reservasi / Data Pelanggan", cuma buat role CS & Kasir) --
            dihapus karena bikin bingung: "Data Pelanggan" nongol 2x (beda
            perilaku pula -- satu pindah halaman, satu cuma ganti tab di
            halaman yang sama), dan "Dashboard" cuma nunjuk balik ke halaman
            yang lagi dibuka. "Jadwal Reservasi" (satu-satunya link di baris
            itu yang beneran halaman terpisah) tetap bisa diakses lewat
            Pintasan di sidebar kanan.
        --}}
        <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-14 z-10">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
                @if($counter)
                    <!-- Section Tabs -->
                    <div class="flex items-center gap-1 overflow-x-auto pt-2">
                        <button type="button" data-tab="tab-antrian" class="tab-btn active border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                            {{ $isFotografer ? 'Antrian Sesi Foto' : 'Antrian Loket' }}
                        </button>
                        @if(in_array('booking', $permissions))
                            <button type="button" data-tab="tab-reservasi" class="tab-btn border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                                Reservasi Hari Ini
                                {{-- Dibungkus id sendiri (selalu ada, isinya yang kosong/keisi)
                                     supaya ikut ke-refresh sama auto-refresh & AJAX aksi (lihat
                                     refreshIds di bawah) -- sebelumnya badge ini di luar semua
                                     id yang di-refresh, jadi angkanya nyangkut walau reservasi
                                     sudah di-check-in & daftarnya sudah kosong. --}}
                                <span id="tab-reservasi-badge">
                                    @if($todayBookings->isNotEmpty())
                                        <span class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ $todayBookings->count() }}</span>
                                    @endif
                                </span>
                            </button>
                        @endif
                        @if(in_array('customers', $permissions))
                            <button type="button" data-tab="tab-pelanggan" class="tab-btn border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                                Data Pelanggan
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-6">
            {{-- Filter "Lokasi:" dihapus sesuai permintaan -- dashboard sekarang
                 selalu nampilin semua lokasi yang operator ini kelola, gak ada
                 lagi opsi buat mempersempit ke 1 lokasi doang. --}}

            @error('counter_id')
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-lg p-3">
                    {{ $message }}
                </div>
            @enderror

            <!-- Stats -->
            <div id="stats-section" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" data-activity="{{ $activityKeys }}" data-reminders="{{ json_encode($reminderPayload) }}" data-waiting-ids="{{ collect($waitingByStage)->flatten(1)->map(fn($t) => $t->id . ':' . $t->ticket_number)->implode(',') }}">
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
                    <div id="operator-grid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 space-y-6">
                        <div id="tab-antrian" class="tab-panel space-y-6">
                        {{-- Urutan card di sini beda tergantung role loket:
                             - Fotografer (loket photo_session doang): Daftar Tunggu -> Antrian
                               Siap-siap -> baru Sesi Foto (card "TIKET AKTIF") di paling bawah.
                             - Selain itu (CS/Kasir/dll): urutan lama, Card "TIKET AKTIF"/Order
                               di atas duluan, baru daftar tunggunya.
                             (Digabung balik jadi 1 file dashboard.blade.php sesuai permintaan --
                             makanya blok "Current Ticket Panel" & "Waiting Queue" di bawah ini
                             SENGAJA muncul 2x, di dalam masing-masing cabang @if/@else, biar
                             urutannya bisa kebalik tanpa perlu partial/@include terpisah.) --}}
                        @php
                            // BUG YANG DIBENERIN: sebelumnya dicek dari config
                            // stages counter-nya (count($counter->stages)===1)
                            // -- ternyata gak reliable karena counter fotografer
                            // bisa aja disetel nangani lebih dari 1 stage. Yang
                            // lebih akurat: cek ROLE orangnya langsung pakai
                            // $isFotografer (dari $user->isFotografer() di
                            // controller), independen dari config counter-nya.
                            $isPhotoSessionOnlyCounter = $isFotografer;
                        @endphp
                        @if($isPhotoSessionOnlyCounter)
                        <!-- Waiting Queue -->
                        @if($counter && in_array('walkin', $permissions))
                            @php
                                $isSingleStageCounter = $counter && count($counter->stages ?? []) === 1;
                                $stagesToShow = $isSingleStageCounter ? $counter->stages : \App\Models\Ticket::STAGE_ORDER;
                                $stagesToShow = collect($stagesToShow)->values()->all();
                                // Sisipkan card virtual "Antrian Siap-siap" tepat
                                // setelah card "Sesi Foto" (photo_session) -- biar
                                // urutannya masuk akal ngikutin alur: Registrasi ->
                                // Siap-siap -> Sesi Foto -> Pembayaran. Ini BUKAN
                                // stage asli di DB (masih `photo_session` juga),
                                // cuma status `preparing`-nya yang beda -- lihat
                                // $preparingTickets dari OperatorController::dashboard().
                                $photoSessionIdx = array_search('photo_session', $stagesToShow, true);
                                if ($photoSessionIdx !== false) {
                                    array_splice($stagesToShow, $photoSessionIdx + 1, 0, ['photo_session_preparing']);
                                }
                            @endphp
                            <div id="waiting-queue-panel" class="space-y-6">
                                @foreach($stagesToShow as $stage)
                                    @php
                                        $isPreparingCard = $stage === 'photo_session_preparing';
                                        // Fotografer (loket yang nangani photo_session) boleh aksi
                                        // (Batalkan Siap-siap) di card ini. CS/Kasir/role lain yang
                                        // cuma numpang lihat (mis. loket registration+payment) HANYA
                                        // read-only -- gak ada tombol apa pun di card ini.
                                        $canActOnPreparing = $isPreparingCard && in_array('photo_session', $counter->stages ?? [], true);
                                        // Sama kayak $canActOnPreparing tapi berlaku buat SEMUA
                                        // stage (dipakai di card "Daftar Tunggu"/photo_session,
                                        // tempat tombol massal manggil Siap-siap sekarang berada).
                                        $canActOnPreparingHere = in_array('photo_session', $counter->stages ?? [], true);
                                        $stageTickets = $isPreparingCard ? $preparingTickets : ($waitingByStage[$stage] ?? collect());
                                        // Dipisah per lokasi (PP = walk-in langsung, BK = hasil
                                        // check-in reservasi) supaya gak ketuker jadi satu daftar
                                        // -- tiap lokasi dapat kotak & tombol "Panggil Antrian"
                                        // sendiri-sendiri, sesuai urutan tunggu di lokasi itu saja.
                                        $stageTicketGroups = $stageTickets->groupBy('location_id')->sortKeys();
                                    @endphp
                                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
                                            <div>
                                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $isPreparingCard ? 'Antrian Siap-siap' : ($isSingleStageCounter || $stage === 'photo_session' ? 'Daftar Tunggu' : 'Antrian ' . (\App\Models\Ticket::STAGE_LABELS[$stage] ?? $stage)) }}</h2>
                                                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ $stageTickets->count() }} {{ $isPreparingCard ? 'sedang siap-siap' : 'tiket menunggu' }}</p>
                                            </div>
                                            {{-- Tombol "Panggil Siap-siap" LANGSUNG dari card ini. Sengaja
                                                 TIDAK mensyaratkan loket lagi sibuk (currentTicket) -- Siap-siap
                                                 itu tahap WAJIB buat pelanggan bersiap (ganti baju, dsb)
                                                 sebelum masuk Sesi Foto, jadi tetap harus lewat sini walau
                                                 loket lagi kosong sama sekali (mis. tiket PERTAMA hari itu).
                                                 Cuma nongol kalau: belum ada yang lagi siap-siap DAN beneran
                                                 ADA tiket nunggu di "Daftar Tunggu" buat dipanggil -- syarat
                                                 ini SAMA PERSIS kayak yang dicek di
                                                 OperatorController::callNextToPrepare(), biar tombolnya gak
                                                 pernah nongol dalam kondisi yang bakal gagal/error kalau
                                                 diklik. --}}
                                            {{-- Tombol massal ini dipindah ke sini (card "Daftar Tunggu" /
                                                 photo_session) dari card "Antrian Siap-siap" -- biar SEMUA aksi
                                                 manggil (massal & per-tiket) ngumpul jadi satu tempat. Card
                                                 "Antrian Siap-siap" sekarang murni buat liat status + Batalkan/
                                                 Panggil Ulang. --}}
                                            @if($stage === 'photo_session' && $canActOnPreparingHere && $stageTickets->isNotEmpty())
                                                <form method="POST" action="{{ route('operator.call-next-prepare') }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Panggil Siap-siap
                                                    </button>
                                                </form>
                                            {{-- CS/Kasir (loket registration+payment, bukan studio) bisa BANTU
                                                 manggil "Siap-siap" juga -- beda dari tombol fotografer di atas,
                                                 SENGAJA GAK milih studio tujuan sama sekali (generik, "ke ruang
                                                 ganti" doang, gak nyebut nama studio), karena fotografer belum
                                                 ada yang manggil tiket ini ke studio manapun. Alasan fitur ini
                                                 ada: CS di depan bisa lihat kondisi ruang tunggu asli,
                                                 fotografer di dalam studio enggak. --}}
                                            @elseif($stage === 'photo_session' && !$canActOnPreparingHere && $isCsKasir && $stageTickets->isNotEmpty())
                                                <form method="POST" action="{{ route('operator.call-next-prepare-auto') }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Panggil Siap-siap (ke Ruang Ganti)
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                        @if($stageTickets->isEmpty())
                                            <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Tidak ada antrian untuk tahap ini.</div>
                                        @else
                                            @foreach($stageTicketGroups as $groupLocationId => $locTickets)
                                                @php $ticketLocation = $locTickets->first()->location; @endphp
                                                <div class="border-t border-slate-100 dark:border-slate-700 first:border-t-0">
                                                    <div class="px-6 py-3 bg-slate-50/70 dark:bg-slate-900/20 flex items-center justify-between gap-3 flex-wrap">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-block w-2 h-2 rounded-full {{ ($ticketLocation && $ticketLocation->offersWalkInTicket()) ? 'bg-blue-500' : 'bg-purple-500' }}"></span>
                                                            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $ticketLocation->name ?? 'Tanpa Lokasi' }}</h3>
                                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ $locTickets->count() }} {{ $isPreparingCard ? 'siap-siap' : 'menunggu' }}</span>
                                                        </div>
                                                        @if($isPreparingCard)
                                                            {{-- Card Siap-siap gak punya tombol "Panggil Antrian" di
                                                                 sini -- manggil siap-siap cuma bisa 1-per-1 lewat
                                                                 tombol kontekstual di panel "TIKET AKTIF" selagi
                                                                 fotografer lagi motret (lihat autoCallNextToPrepare()
                                                                 & tombol "Panggil Siap-siap Berikutnya"). Di sini
                                                                 cuma "Batalkan Siap-siap" per tiket kalau perlu. --}}
                                                        @elseif($stage === 'photo_session' && in_array($stage, $counter->stages ?? [], true))
                                                            {{-- Sesi Foto sengaja gak punya tombol "Panggil Antrian" (langsung
                                                                 ke Sesi Foto) di sini -- SEMUA panggilan buat tahap ini WAJIB
                                                                 lewat "Siap-siap" dulu, biar pelanggan sempat bersiap (ganti
                                                                 baju, dsb) sebelum masuk studio. Pakai tombol "Panggil
                                                                 Siap-siap" di card "Antrian Siap-siap" di atas (FIFO), atau
                                                                 tombol "Panggil Siap-siap" per-tiket di bawah. --}}
                                                        @elseif(!$currentTicket && in_array($stage, $counter->stages ?? [], true))
                                                            <form method="POST" action="{{ route('operator.call-next') }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="stage" value="{{ $stage }}" />
                                                                <input type="hidden" name="location_id" value="{{ $groupLocationId }}" />
                                                                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                                    Panggil Antrian
                                                                </button>
                                                            </form>
                                                        @elseif($stage === 'photo_session' && $isCsKasir)
                                                            <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold whitespace-nowrap">Panggil siap-siap per tiket di bawah</span>
                                                        @elseif(!$currentTicket)
                                                            <span class="text-xs text-slate-400 font-semibold whitespace-nowrap">Bukan tahap Anda</span>
                                                        @endif
                                                    </div>
                                                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                                        @foreach($locTickets->values() as $qIndex => $ticket)
                                                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 border border-slate-200 dark:border-slate-600 hover:border-blue-300 dark:hover:border-blue-700 transition-colors {{ $qIndex >= 6 ? 'hidden queue-card-extra' : '' }}" data-queue-group="queue-{{ $stage }}-{{ $groupLocationId }}">
                                                                <div class="flex items-start justify-between gap-2 mb-2">
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex-shrink-0" title="Urutan ke-{{ $qIndex + 1 }} dalam antrian {{ $ticketLocation->name ?? '' }} ini">{{ $qIndex + 1 }}</span>
                                                                        <span class="text-xl font-black text-slate-900 dark:text-white">{{ $ticket->ticket_number }}</span>
                                                                    </div>
                                                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $isPreparingCard ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }} whitespace-nowrap">{{ $isPreparingCard ? 'Siap-siap' : 'Menunggu' }}</span>
                                                                </div>
                                                                <p class="font-mono text-xs text-slate-400 dark:text-slate-500 truncate">{{ $ticket->order_code }}</p>
                                                                <p class="text-base font-bold text-slate-900 dark:text-white break-words leading-tight">{{ $ticket->customer_name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $ticket->customer_phone }}@if($ticket->customer_phone && $ticket->customer_email) &middot; @endif{{ $ticket->customer_email }}</p>
                                                                @include('operator.partials.order-notes', ['notes' => $ticket->notes, 'tone' => 'plain'])
                                                                @if($isPreparingCard)
                                                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $ticket->counter?->name ?? '-' }}</p>
                                                                    {{-- Info buat fotografer: kasih tau kalau tiket ini sempat
                                                                         "dipanggil"/"dipanggil ulang" duluan sama CS/Kasir dari
                                                                         depan -- sebelumnya gak ada info apa pun di sini, fotografer
                                                                         gak tau tiketnya udah di-nudge CS apa belum. Dicek dari
                                                                         role staf yang terakhir megang (servedBy) & counter
                                                                         hitungan berapa kali di-nudge ulang (prepare_recall_count)
                                                                         -- lihat Ticket::callToPrepare() & Ticket::recallPrepare(). --}}
                                                                    @if($ticket->servedBy && ($ticket->servedBy->isOperator() || $ticket->servedBy->isAdmin()))
                                                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5"><x-papyrus-icon name="hand-raised" /> Dipanggil CS {{ $ticket->servedBy->name }}</p>
                                                                    @endif
                                                                    @if($ticket->prepare_recall_count > 0)
                                                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                                                            <x-papyrus-icon name="arrow-path" /> Dipanggil ulang {{ $ticket->prepare_recall_count }}x oleh CS{{ $ticket->prepareRecalledBy ? ' (' . $ticket->prepareRecalledBy->name . ')' : '' }}
                                                                            @if($ticket->called_at) &middot; {{ $ticket->called_at->diffForHumans() }} @endif
                                                                        </p>
                                                                    @endif
                                                                    {{-- TIDAK ADA lock per studio (counter_id) di sini -- STUDIO
                                                                         MANA PUN boleh liat & mencet tombol "Panggil Sekarang" /
                                                                         "Batalkan Siap-siap" buat tiket Siap-siap MANA PUN, baik
                                                                         yang generik (CS yang mulai, counter_id kosong) maupun yang
                                                                         tadinya dipanggil studio lain (counter_id terisi). Studio
                                                                         yang beneran berhasil klik duluan yang "menang" -- lihat
                                                                         catatan panjang di OperatorController::callPreparingTicket()
                                                                         soal kenapa ini sengaja dibikin gak terkunci. --}}
                                                                    @if($canActOnPreparing)
                                                                        <p class="mt-3 text-xs font-semibold {{ $ticket->ready_at ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                                            @if($ticket->ready_at)
                                                                                <x-papyrus-icon name="check-circle" /> Ready dari CS{{ $ticket->readyBy ? ' (' . $ticket->readyBy->name . ')' : '' }} &middot; {{ $ticket->ready_at->diffForHumans() }} &mdash; siap dibawa ke studio
                                                                            @else
                                                                                <x-papyrus-icon name="clock" /> Belum Ready &mdash; menunggu CS memastikan pelanggannya sudah siap
                                                                            @endif
                                                                        </p>
                                                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                            {{-- Manual sepenuhnya -- fotografer yang mutusin kapan
                                                                                 mau naikkin tiket "Siap-siap" ini jadi "Dipanggil",
                                                                                 gak nempel ke event apa pun (dulu otomatis nempel di
                                                                                 Ticket::advanceStage(), sekarang dihapus). Cuma nongol
                                                                                 kalau loket lagi FREE (gak ada currentTicket) -- kalau
                                                                                 masih ada yang lagi dilayani, harus diselesaikan dulu. --}}
                                                                            @if(!$currentTicket && $ticket->ready_at)
                                                                                <form method="POST" action="{{ route('operator.call-preparing', $ticket) }}" class="flex-1">
                                                                                    @csrf
                                                                                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil Sekarang</button>
                                                                                </form>
                                                                            @endif
                                                                            <form method="POST" action="{{ route('operator.uncall', $ticket) }}" class="flex-1" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan Siap-siap untuk tiket {{ $ticket->ticket_number }}? Cocok dipakai kalau sesi ini bakal masih lama.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                                                @csrf
                                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Batalkan Siap-siap</button>
                                                                            </form>
                                                                        </div>
                                                                    {{-- CS/Kasir sekarang JUGA bisa Batalkan Siap-siap (dulu cuma bisa
                                                                         "nudge" via Panggil Ulang) -- berlaku buat tiket yang CS sendiri
                                                                         yang mulai (counter_id kosong) MAUPUN yang sudah dikunci ke
                                                                         studio tertentu oleh fotografer, karena CS di depan yang paling
                                                                         sering tau duluan kalau pelanggannya kabur/gak jadi. Lihat
                                                                         OperatorController::uncallTicket() & recallPreparingTicket(). --}}
                                                                    @elseif($isCsKasir)
                                                                        <div class="mt-3">
                                                                            @if($ticket->ready_at)
                                                                                <form method="POST" action="{{ route('operator.unmark-ready', $ticket) }}">
                                                                                    @csrf
                                                                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-green-100 text-green-800 border py-1.5 rounded-lg text-xs font-semibold transition" style="border-color: #86efac;"><x-papyrus-icon name="check-circle" /> Ready &middot; klik untuk batalkan</button>
                                                                                </form>
                                                                            @else
                                                                                <form method="POST" action="{{ route('operator.mark-ready', $ticket) }}">
                                                                                    @csrf
                                                                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm font-semibold transition"><x-papyrus-icon name="check-circle" /> Ready &mdash; siap dipanggil fotografer ke studio</button>
                                                                                </form>
                                                                            @endif
                                                                        </div>
                                                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                            <form method="POST" action="{{ route('operator.recall-preparing', $ticket) }}" class="flex-1" onsubmit="return confirmFormSubmit(this, { message: 'Panggil ulang tiket {{ $ticket->ticket_number }} buat bersiap-siap?', confirmText: 'Panggil Ulang' })">
                                                                                @csrf
                                                                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                                                                    Panggil Ulang
                                                                                </button>
                                                                            </form>
                                                                            <form method="POST" action="{{ route('operator.uncall', $ticket) }}" class="flex-1" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan Siap-siap untuk tiket {{ $ticket->ticket_number }}? Cocok dipakai kalau sesi ini bakal masih lama.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                                                @csrf
                                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Batalkan Siap-siap</button>
                                                                            </form>
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                @if($ticket->skip_count > 0)
                                                                    <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Lewati {{ $ticket->skip_count }}x</span>
                                                                    </div>
                                                                @endif
                                                                @if(in_array($ticket->status, ['waiting', 'calling'], true))
                                                                    <div class="flex items-center gap-2 flex-wrap mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                        {{-- Sesi Foto: SELALU lewat "Siap-siap" dulu -- pelanggan
                                                                             butuh waktu buat bersiap (ganti baju, dsb) sebelum masuk
                                                                             studio, jadi gak ada lagi jalan pintas "Panggil" langsung
                                                                             ke Sesi Foto buat tahap ini, baik loket lagi kosong
                                                                             maupun sibuk. Tahap lain (Registrasi/Pembayaran) tetap
                                                                             pakai "Panggil" langsung seperti biasa, karena gak butuh
                                                                             jeda bersiap. Sama persis kayak yang dicek backend di
                                                                             OperatorController::callSpecificToPrepare(), biar gak
                                                                             pernah nongol dalam kondisi yang bakal gagal kalau
                                                                             diklik. --}}
                                                                        @if($ticket->status === 'waiting' && $ticket->stage === 'photo_session' && in_array('photo_session', $counter->stages ?? [], true))
                                                                            <form method="POST" action="{{ route('operator.call-specific-prepare', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil Siap-siap</button>
                                                                            </form>
                                                                        @elseif($ticket->status === 'waiting' && $ticket->stage === 'photo_session' && $isCsKasir)
                                                                            {{-- CS/Kasir: sama kayak tombol fotografer di atas, tapi generik
                                                                                 (gak pilih studio tujuan) -- lihat OperatorController::
                                                                                 callSpecificToPrepareAuto(). --}}
                                                                            <form method="POST" action="{{ route('operator.call-specific-prepare-auto', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil ke Ruang Ganti</button>
                                                                            </form>
                                                                        @elseif(!$currentTicket && in_array($ticket->stage, $counter->stages ?? [], true))
                                                                            <form method="POST" action="{{ route('operator.call-specific', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                                <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil</button>
                                                                            </form>
                                                                        @endif
                                                                        @if(in_array($ticket->stage, $counter->stages ?? [], true) && in_array($ticket->status, ['calling', 'serving'], true))
                                                                            <form method="POST" action="{{ route('operator.skip', $ticket) }}" class="flex-1 min-w-[80px]" onsubmit="return confirmFormSubmit(this, { message: 'Lewati tiket {{ $ticket->ticket_number }}?', variant: 'primary', confirmText: 'Ya, Lewati' })">@csrf
                                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Lewati</button>
                                                                            </form>
                                                                        @endif
                                                                        @if(in_array($ticket->stage, $counter->stages ?? [], true))
                                                                            <form method="POST" action="{{ route('operator.tickets.destroy', $ticket) }}" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan tiket {{ $ticket->ticket_number }}?', variant: 'danger' })">@csrf @method('DELETE')
                                                                                <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 rounded-lg transition-colors" title="Batalkan">
                                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                </button>
                                                                            </form>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if($locTickets->count() > 6)
                                                        <div class="px-4 pb-4">
                                                            <button type="button" class="queue-load-more-btn w-full py-2 text-xs font-semibold text-lavender hover:text-dustyrose border border-dashed border-slate-300 dark:border-slate-600 rounded-lg transition-colors" data-queue-group="queue-{{ $stage }}-{{ $groupLocationId }}">
                                                                Muat {{ $locTickets->count() - 6 }} Lagi
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Card "Panggil Ulang" KHUSUS CS/Kasir -- nunjukin tiket yang
                             LAGI DIPANGGIL ke studio (status calling, stage photo_session)
                             beserta udah berapa lama, biar CS bisa bantu "nyodok" fotografer
                             yang mungkin belum ngeh/kelewat manggil pelanggannya -- fotografer
                             di dalam studio gak bisa mantau kondisi ruang tunggu kayak CS di
                             depan. Atribusi tetap ke fotografer aslinya (lihat recallTicket()
                             di OperatorController), CS cuma "membantu memicu ulang". --}}
                        @if($isCsKasir && $studioCallingTickets->isNotEmpty())
                            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden" id="studio-calling-panel">
                                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sedang Dipanggil ke Studio</h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Bantu panggil ulang kalau fotografer kelihatan belum ngeh</p>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($studioCallingTickets as $ct)
                                        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                                            <div>
                                                <p class="text-lg font-black text-slate-900 dark:text-white">{{ $ct->ticket_number }}</p>
                                                <p class="font-mono text-xs text-slate-400 dark:text-slate-500 truncate">{{ $ct->order_code }}</p>
                                                @if($ct->customer_name)
                                                    <p class="text-base font-bold text-slate-900 dark:text-white mt-0.5 break-words leading-tight">{{ $ct->customer_name }}</p>
                                                @endif
                                                @if($ct->customer_phone || $ct->customer_email)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $ct->customer_phone }}@if($ct->customer_phone && $ct->customer_email) &middot; @endif{{ $ct->customer_email }}</p>
                                                @endif
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                    {{ $ct->counter?->publicLabel() ?? 'Studio' }}
                                                    @if($ct->called_at)
                                                        &middot; <span class="studio-calling-elapsed" data-called-at="{{ $ct->called_at->timestamp }}">sejak {{ $ct->called_at->diffForHumans() }}</span>
                                                    @endif
                                                </p>
                                                @include('operator.partials.order-notes', ['notes' => $ct->notes, 'tone' => 'plain'])
                                            </div>
                                            <form method="POST" action="{{ route('operator.recall', $ct) }}" onsubmit="return confirmFormSubmit(this, { message: 'Panggil ulang tiket ' + '{{ $ct->ticket_number }}' + '?', confirmText: 'Panggil Ulang' })">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                                    Panggil Ulang
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Card "Sedang Diedit" -- info-only buat CS, nunjukin tiket yang
                             udah "Sesi Foto Selesai" tapi FOTONYA MASIH DIPROSES EDIT, biar
                             CS tau ada pembayaran yang "bakal nyusul" alih-alih tiket itu
                             tiba-tiba nongol di Antrian Pembayaran tanpa ada tanda-tanda
                             sebelumnya. Read-only -- CS belum bisa proses bayar sampai
                             fotografer beneran nandain editnya selesai. --}}
                        @include('operator.partials.photo-selection-panel')
                        @if($isCsKasir && $editingInProgressTickets->isNotEmpty())
                            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sedang Diedit</h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Foto masih diproses fotografer. Kalau pelanggan mau bayar duluan, pakai tombol Bayar Duluan.</p>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($editingInProgressTickets as $et)
                                        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                                            <div>
                                                <p class="text-lg font-black text-slate-900 dark:text-white">{{ $et->ticket_number }}</p>
                                                <p class="font-mono text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $et->order_code }}</p>
                                                <p class="text-base font-bold text-slate-900 dark:text-white mt-0.5 break-words leading-tight">{{ $et->customer_name ?? '-' }}</p>
                                                @if($et->customer_phone || $et->customer_email)
                                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $et->customer_phone }}@if($et->customer_phone && $et->customer_email) &middot; @endif{{ $et->customer_email }}</p>
                                                @endif
                                                @include('operator.partials.order-notes', ['notes' => $et->notes, 'tone' => 'plain'])
                                            </div>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                                Sedang Diedit
                                            </span>
                                            {{-- CS/Kasir bisa nolong pelanggan yang mau bayar duluan begitu
                                                 sesi foto selesai & masuk tahap edit (tanpa nunggu fotografer) --}}
                                            <div class="w-full sm:w-64">
                                                @php $payFirstTicket = $et; $payFirstRoute = 'operator.skip-to-payment-pending-edit'; $payFirstLabel = 'Pelanggan mau bayar duluan?'; @endphp
                                                @include('operator.partials.pay-first-buttons')
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Current Ticket Panel -->
                        {{-- Card ini SEKARANG cuma muncul kalau LAGI ADA tiket aktif
                             (dipanggil/dilayani) di loket ini. Dulu ada versi "kosong"-nya
                             (judul + badge TIKET AKTIF + pesan "Tidak ada tiket...") yang
                             tetap nongol pas kosong -- itu sengaja DIHAPUS TOTAL (bukan
                             cuma tombolnya), biar operator gak lihat card kosong yang gak
                             ngapa-ngapain; cukup pakai panel "Daftar Tunggu"/"Antrian ..."
                             di bawah buat manggil tiket. --}}
                        @if(($currentTicket) && (in_array('walkin', $permissions) || in_array('photo', $permissions)))
                            <div id="current-ticket-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ in_array('registration', $counter->stages ?? [], true) ? 'Order' : $counter->stageLabel() }}</h2>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">TIKET AKTIF</span>
                                </div>
                                <div class="p-4">
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 text-center mb-4">
                                            <p class="text-blue-600 dark:text-blue-300 font-medium mb-1 text-sm">
                                                @if($currentTicket->status === 'calling')
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>Memanggil...</span>
                                                @else
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></span>Sedang Dilayani</span>
                                                @endif
                                            </p>
                                            <p class="text-5xl font-black text-blue-900 dark:text-blue-200">{{ $currentTicket->ticket_number }}</p>
                                            <p class="font-mono text-blue-500 dark:text-blue-400 text-xs mt-2">{{ $currentTicket->order_code }}</p>
                                            @if($currentTicket->customer_name)
                                                <p class="text-blue-900 dark:text-blue-200 mt-0.5 text-xl font-bold break-words leading-tight">{{ $currentTicket->customer_name }}</p>
                                            @endif
                                            <p class="text-blue-500 dark:text-blue-400 text-xs mt-1">
                                                {{ $currentTicket->customer_phone }}
                                                @if($currentTicket->customer_email)
                                                    &middot; {{ $currentTicket->customer_email }}
                                                @endif
                                            </p>
                                            @include('operator.partials.order-notes', ['notes' => $currentTicket->notes, 'tone' => 'blue'])
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
                                            <form method="POST" action="{{ route('operator.uncall', $currentTicket) }}" class="mb-3" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan pemanggilan tiket ini? Tiket akan dikembalikan ke antrian tanpa menghukum pelanggan.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 py-2 rounded-lg font-medium transition-colors text-xs hover:bg-slate-50 dark:hover:bg-slate-600">
                                                    Salah Panggil? Batalkan Pemanggilan
                                                </button>
                                            </form>
                                        @endif
                                        @if($currentTicket->stage === 'photo_session' && $currentTicket->status === 'serving')
                                            {{-- Nampilin SEMUA tiket yang lagi Siap-siap buat loket ini --
                                                 TIDAK ADA LAGI batasan "cuma boleh 1", jadi bisa lebih dari 1
                                                 box sekaligus di sini (mis. ada 2 ruang ganti). --}}
                                            @foreach($counter->preparingTickets() as $preparing)
                                                <div class="mb-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3">
                                                    <p class="text-xs text-purple-700 dark:text-purple-300 font-semibold mb-1 flex items-center gap-1.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Sedang Siap-siap:
                                                    </p>
                                                    <p class="text-lg text-purple-900 dark:text-purple-200 font-black">{{ $preparing->ticket_number }}</p>
                                                @include('operator.partials.ticket-detail', ['ticket' => $preparing, 'withName' => true])
                                                <p class="text-xs font-semibold mt-1 {{ $preparing->ready_at ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">@if($preparing->ready_at) <x-papyrus-icon name="check-circle" /> Ready @else <x-papyrus-icon name="clock" /> Belum Ready @endif</p>
                                                    <form method="POST" action="{{ route('operator.uncall', $preparing) }}" class="mt-2" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan Siap-siap untuk tiket ini? Cocok dipakai kalau sesi ini bakal masih lama.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                        @csrf
                                                        <button type="submit" class="text-xs text-purple-600 dark:text-purple-400 underline">Belum, batalkan dulu (sesi masih lama)</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                            @if(($waitingByStage['photo_session'] ?? collect())->isNotEmpty())
                                                {{-- Cuma nongol kalau BENERAN ada tiket nunggu di Daftar Tunggu
                                                     buat dipanggil -- sebelumnya tombol ini selalu nongol begitu
                                                     belum ada yang siap-siap, walau Daftar Tunggu-nya kosong,
                                                     jadi diklik pun cuma dapat pesan info "tidak ada antrian",
                                                     buang-buang klik. SELALU nongol walau udah ada yang
                                                     siap-siap juga (TIDAK ADA lagi batasan "1 siap-siap per
                                                     loket") -- operator bebas manggil lagi kapan aja. Syarat
                                                     ini SAMA PERSIS kayak tombol header di card "Antrian
                                                     Siap-siap" & OperatorController::callNextToPrepare(). --}}
                                                <form method="POST" action="{{ route('operator.call-next-prepare') }}" class="mb-3">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-semibold transition-colors text-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Panggil Siap-siap Berikutnya
                                                    </button>
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
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Konfirmasi Pesanan
                                                    </button>
                                                </form>
                                            @else
                                                <p class="text-xs text-green-700 dark:text-green-400 mb-3 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                    Pesanan dikonfirmasi ({{ $currentTicket->order_code }}).
                                                </p>
                                                <form method="POST" action="{{ route('operator.complete-registration', $currentTicket) }}" class="mb-3" id="registration-form">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Selesaikan, Lanjut ke Sesi Foto</button>
                                                </form>
                                            @endif
                                        @elseif($currentTicket->stage === 'photo_session' && $currentTicket->status === 'calling')
                                            <form method="POST" action="{{ route('operator.start-serving', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>
                                                    Mulai Foto
                                                </button>
                                            </form>
                                        @elseif($currentTicket->stage === 'photo_session' && $currentTicket->status === 'serving')
                                            @if($currentTicket->isReservation())
                                                {{-- Khusus RESERVASI: sesudah foto WAJIB Pilih Foto dulu (ada timer), baru edit.
                                                     Tiket PP biasa tidak melihat tombol ini (lihat @else di bawah). --}}
                                                <form method="POST" action="{{ route('operator.finish-photo-session', $currentTicket) }}" class="mb-1">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                        Foto Selesai, Mulai Pilih Foto
                                                    </button>
                                                </form>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pelanggan reservasi memilih foto dulu (timer jalan). Setelah "Selesai Pilih Foto" baru lanjut ke edit.</p>
                                            @else
                                            <form method="POST" action="{{ route('operator.start-editing', $currentTicket) }}" class="mb-2">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>
                                                    Foto Selesai, Edit Sekarang
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('operator.finish-photo-session', $currentTicket) }}" class="mb-1">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-amber-500 text-amber-700 dark:text-amber-400 py-2.5 rounded-lg font-semibold transition-colors text-sm hover:bg-amber-50 dark:hover:bg-slate-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                                                    Foto Selesai, Nanti Diedit (Antri Dulu)
                                                </button>
                                            </form>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pilih "Edit Sekarang" kalau langsung diedit, atau "Antri Dulu" buat numpuk beberapa tiket lalu edit sekaligus nanti (lihat panel "Siap Diedit" di bawah).</p>
                                            @endif
                                        @elseif($currentTicket->stage === 'payment' && $currentTicket->status === 'calling')
                                            {{-- Sebelumnya tahap Pembayaran LANGSUNG lompat ke tombol
                                                 "Selesai" tanpa pernah lewat "Mulai Melayani" -- makanya
                                                 payment_served_at SELALU kosong dan "Proses Bayar" di
                                                 widget Waktu SELALU tampil "-" buat semua tiket, bukan
                                                 cuma tiket tertentu. Tombol ini nyamain pola yang sama
                                                 kayak Registrasi & Sesi Foto: pelanggan dipanggil dulu,
                                                 BARU beneran mulai dilayani begitu operator klik ini. --}}
                                            <form method="POST" action="{{ route('operator.start-serving', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                                    Mulai Layani Pembayaran
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('operator.complete', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    {{ match(true) {
                                                        $currentTicket->stage === 'payment' => 'Selesai (Bayar & Cetak)',
                                                        default => 'Selesaikan, Lanjut ke Pembayaran',
                                                    } }}
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('operator.skip', $currentTicket) }}" class="mb-4">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center justify-center bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Lewati (Panggil Nanti)</button>
                                        </form>
                                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Order</h4>
                                            @if($currentTicket->notes_locked_at)
                                                <p class="text-xs text-amber-600 dark:text-amber-400 mb-2 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                    {{-- Terkunci bisa kejadian di 2 momen beda: pas "Konfirmasi
                                                         Pesanan" (Registrasi) atau pas sesi foto dimulai
                                                         (Sesi Foto) -- pesannya disesuaikan biar gak nyesatin. --}}
                                                    @if($currentTicket->stage === 'registration')
                                                        Terkunci sejak pesanan dikonfirmasi — biar gak ke-klik/ke-edit gak sengaja.
                                                    @else
                                                        Terkunci sejak sesi foto dimulai — biar gak ke-klik/ke-edit gak sengaja.
                                                    @endif
                                                </p>
                                                <p class="whitespace-pre-line px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-sm mb-2">{{ $currentTicket->notes ?: '—' }}</p>
                                                <form method="POST" action="{{ route('operator.unlock-notes', $currentTicket) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-lavender hover:underline">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                        Buka Kunci buat Edit
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Nonaktif selama tiket masih status 'calling' (baru dipanggil,
                                                     belum diklik "Mulai Melayani"/"Mulai Foto"/dst) -- biar
                                                     operator fokus manggil/klaim pelanggannya dulu, catatan Order
                                                     baru bisa diisi begitu beneran mulai dilayani. --}}
                                                @php $orderNotYetActive = $currentTicket->status === 'calling'; @endphp
                                                @if($orderNotYetActive)
                                                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">Klik "Mulai Melayani"/"Mulai Foto" dulu buat isi catatan Order.</p>
                                                @endif
                                                <form method="POST" action="{{ route('operator.update-notes', $currentTicket) }}" class="order-autosave-form flex flex-col gap-1">
                                                    @csrf
                                                    <textarea name="notes" rows="1" placeholder="Order dari CS / Kasir / Fotografer..." @if($orderNotYetActive) disabled @endif class="autogrow-textarea order-textarea w-full min-h-[42px] px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm resize-none overflow-hidden disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-400 disabled:cursor-not-allowed">{{ $currentTicket->notes }}</textarea>
                                                    {{-- Gak ada lagi tombol "Simpan" manual -- tersimpan otomatis
                                                         sendiri begitu berhenti ngetik sebentar (lihat script
                                                         order-autosave-form di bawah). Toh sebelum lanjut ke tahap
                                                         berikutnya wajib "Konfirmasi Pesanan" dulu, jadi gak ada
                                                         resiko kelewat nyimpen. --}}
                                                    <span class="order-save-status text-xs text-slate-400 dark:text-slate-500 h-4"></span>
                                                </form>
                                                @if(!$orderNotYetActive && $currentTicket->location && $currentTicket->location->orderLineSuggestions())
                                                    <div class="flex flex-wrap gap-1.5 mt-2 order-suggestions">
                                                        @foreach($currentTicket->location->orderLineSuggestions() as $line)
                                                            <button type="button" data-order-line="{{ $line }}" class="order-suggestion-chip px-2.5 py-1 rounded-full text-xs font-medium bg-lavender/10 text-lavender hover:bg-lavender/20 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 border border-lavender/20 dark:border-slate-600 transition-colors">+ {{ $line }}</button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                </div>
                            </div>
                        @endif


        {{-- SECTION: KERJAAN DARI SHIFT SEBELUMNYA (Ambil Alih) --}}
        {{-- Tiket ready_for_edit/editing di studio yang lagi dipegang user ini,
             tapi ditinggal fotografer LAIN (mis. pulang malam, editnya belum
             kelar). Sengaja gak otomatis nyampur ke panel "Sedang Diedit"/
             "Siap Diedit" biasa -- staf yang masuk shift ini yang mutusin mau
             ambil alih atau biarin dulu (lihat OperatorController::takeoverTicket()). --}}
        @if(!empty($handoffTickets) && $handoffTickets->isNotEmpty())
            <div class="bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-200 dark:border-amber-800 overflow-hidden mb-6">
                <div class="p-4 border-b border-amber-200 dark:border-amber-800 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-amber-900 dark:text-amber-300 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Kerjaan dari Shift Sebelumnya
                        </h2>
                        <p class="text-amber-700 dark:text-amber-400 text-sm mt-0.5">{{ $handoffTickets->count() }} tiket edit belum selesai di studio ini, ditinggal staf lain</p>
                    </div>
                    <form method="POST" action="{{ route('operator.takeover-all') }}" onsubmit="return confirmFormSubmit(this, { message: 'Ambil alih semua ({{ $handoffTickets->count() }}) tiket ini?', variant: 'primary', confirmText: 'Ya, Ambil Semua' })">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white whitespace-nowrap">
                            Ambil Alih Semua
                        </button>
                    </form>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                                <th class="pb-2 font-semibold">Tiket</th>
                                <th class="pb-2 font-semibold">Pelanggan</th>
                                <th class="pb-2 font-semibold">Ditinggal</th>
                                <th class="pb-2 font-semibold">Status</th>
                                <th class="pb-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100 dark:divide-amber-800">
                            @foreach($handoffTickets as $ticket)
                                <tr>
                                    <td class="py-3 px-2 font-mono text-base text-amber-900 dark:text-amber-200 font-bold">{{ $ticket->ticket_number }}</td>
                                    <td class="py-3 px-2 text-amber-800 dark:text-amber-300">@include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true])
                                    </td>
                                    <td class="py-3 px-2 text-amber-700 dark:text-amber-400">{{ $ticket->servedBy->name ?? '-' }}, {{ $ticket->updated_at->format('H:i') }}</td>
                                    <td class="py-3 px-2 text-amber-700 dark:text-amber-400">{{ $ticket->customerStatusLabel() }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" action="{{ route('operator.takeover', $ticket) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-amber-400 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/30 whitespace-nowrap">
                                                Ambil Alih
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- SECTION: SUDAH DIBAYAR, EDIT BELUM SELESAI --}}
        {{-- Tiket yang tadinya dilewatin lewat "Lanjut ke Kasir — Edit Menyusul"
             (skipToPaymentPendingEdit()) -- sudah di kasir/selesai bayar, tapi
             editnya masih belum ditandai kelar. Di-scope ke editing_counter_id
             (bukan counter_id/served_by biasa) supaya tetap kelihatan siapapun
             yang lagi pegang studio ini. --}}
        @if(!empty($pendingEditTickets) && $pendingEditTickets->isNotEmpty())
            <div class="bg-sky-50 dark:bg-sky-900/10 rounded-2xl border border-sky-200 dark:border-sky-800 overflow-hidden mb-6">
                <div class="p-4 border-b border-sky-200 dark:border-sky-800">
                    <h2 class="text-lg font-bold text-sky-900 dark:text-sky-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Sudah Dibayar, Edit Belum Selesai
                    </h2>
                    <p class="text-sky-700 dark:text-sky-400 text-sm mt-0.5">{{ $pendingEditTickets->count() }} tiket sudah lewat kasir, hasil editnya masih menyusul</p>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-sky-700 dark:text-sky-400 uppercase tracking-wider">
                                <th class="pb-2 font-semibold">Tiket</th>
                                <th class="pb-2 font-semibold">Pelanggan</th>
                                <th class="pb-2 font-semibold">Status Saat Ini</th>
                                <th class="pb-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-100 dark:divide-sky-800">
                            @foreach($pendingEditTickets as $ticket)
                                <tr>
                                    <td class="py-3 px-2 font-mono text-sm text-sky-900 dark:text-sky-200">
                                        {{ $ticket->ticket_number }}
                                        {{-- Nomor tiket reset tiap hari (lihat ResetDailyQueue) -- tiket
                                             yang "nyebrang hari" dikasih label tanggal + kode order
                                             permanen (order_code, gak ikut reset) biar gak ketuker sama
                                             nomor yang sama persis punya pelanggan HARI INI. --}}
                                        @if(!$ticket->created_at->isToday())
                                            <span class="block text-xs font-normal text-sky-500">{{ $ticket->created_at->translatedFormat('d M') }} · {{ $ticket->order_code }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-sky-800 dark:text-sky-300">
                                        @include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true, 'part' => 'head'])
                                        {{-- Pilihan saat "Bayar Duluan": nunggu di tempat = prioritaskan --}}
                                        @if($ticket->edit_wait_preference === 'wait')
                                            <span class="block mt-0.5"><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Menunggu di tempat</span></span>
                                        @elseif($ticket->edit_wait_preference === 'leave')
                                            <span class="block mt-0.5"><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Pulang dulu, kabari saat selesai</span></span>
                                        @endif
                                        @include('operator.partials.ticket-detail', ['ticket' => $ticket, 'part' => 'rest'])
                                    </td>
                                    <td class="py-3 px-2 text-sky-700 dark:text-sky-400">{{ $ticket->customerStatusLabel() }}
                                        @include('operator.partials.edit-progress', ['ticket' => $ticket])
                                    </td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" action="{{ route('operator.mark-edit-completed', $ticket) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'Tandai edit tiket {{ $ticket->ticket_number }} sudah selesai? Pelanggan akan diberi notifikasi.', variant: 'primary', confirmText: 'Ya, Selesai' })">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white whitespace-nowrap">
                                                Edit Selesai
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- SECTION: SEDANG DIEDIT --}}
        {{-- Tiket yang statusnya 'editing' -- baik lewat "Edit Sekarang" langsung
             maupun lewat batch edit di panel "Siap Diedit" di bawah. Loket sudah
             bebas dipakai buat pelanggan berikutnya begitu tiket masuk sini
             (lihat Counter::currentTicket()), jadi panel ini adalah SATU-SATUNYA
             tempat tiket-tiket ini kelihatan & bisa diselesaikan. TANPA PANEL INI,
             tiket yang baru selesai difoto lalu diklik "Edit Sekarang" akan hilang
             sama sekali dari tampilan (bukan cuma dari "TIKET AKTIF"). --}}
        @if(!empty($editingTickets) && $editingTickets->isNotEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Sedang Diedit
                        </h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $editingTickets->count() }} tiket sedang diproses editnya</p>
                    </div>
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ $editingTickets->count() }}</span>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="pb-2 font-semibold">Tiket</th>
                                <th class="pb-2 font-semibold">Pelanggan</th>
                                <th class="pb-2 font-semibold">Studio</th>
                                <th class="pb-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($editingTickets as $ticket)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="py-3 px-2 font-mono text-base text-slate-900 dark:text-white font-bold">{{ $ticket->ticket_number }}</td>
                                    <td class="py-3 px-2 text-slate-700 dark:text-slate-300">@include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true])
                                    </td>
                                    <td class="py-3 px-2 text-slate-500 dark:text-slate-400">{{ $ticket->counter ? $ticket->counter->name : '-' }}</td>
                                    <td class="py-3 px-2 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('operator.complete', $ticket) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 hover:bg-green-700 text-white whitespace-nowrap">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Edit Selesai, Lanjut ke Pembayaran
                                            </button>
                                        </form>
                                        {{-- Jalur alternatif kalau editnya bakal masih lama (mis. baru
                                             kelar besok) tapi pelanggan udah mau pulang. Sengaja outline
                                             (bukan warna solid) supaya kelihatan ini opsional, bukan
                                             tombol utama -- lihat Ticket::skipToPaymentPendingEdit(). --}}
                                        <form method="POST" action="{{ route('operator.skip-to-payment-pending-edit', $ticket) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'Foto {{ $ticket->ticket_number }} belum selesai diedit. Pelanggan akan diarahkan ke kasir buat bayar duluan, hasil edit dikirim menyusul. Lanjutkan?', variant: 'primary', confirmText: 'Ya, Lanjutkan' })">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 whitespace-nowrap">
                                                Lanjut ke Kasir — Edit Menyusul
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- SECTION: TIKET SIAP DIEDIT (Batch Edit Foto) --}}
        @include('operator.partials.photo-selection-panel')
        @if(!empty($readyForEditTickets) && $readyForEditTickets->isNotEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Tiket Siap Diedit
                        </h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $readyForEditTickets->count() }} tiket menunggu proses edit</p>
                    </div>
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ $readyForEditTickets->count() }}</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('operator.batch-start-editing') }}" method="POST" id="batchEditForm">
                        @csrf
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pilih tiket yang siap diedit, lalu klik <strong>Proses Edit Batch</strong>.</p>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        <th class="pb-2 font-semibold w-10"><input type="checkbox" id="selectAllBatchTickets" class="form-checkbox h-4 w-4 text-purple-600 rounded border-slate-300"></th>
                                        <th class="pb-2 font-semibold">Tiket</th>
                                        <th class="pb-2 font-semibold">Pelanggan</th>
                                        <th class="pb-2 font-semibold">Studio</th>
                                        <th class="pb-2 font-semibold">Status</th>
                                        <th class="pb-2 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($readyForEditTickets as $ticket)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <td class="py-3 px-2">
                                                <input type="checkbox" name="tickets_to_edit[]" value="{{ $ticket->id }}" class="form-checkbox h-4 w-4 text-purple-600 rounded border-slate-300 batch-ticket-checkbox">
                                            </td>
                                            <td class="py-3 px-2 font-mono text-base text-slate-900 dark:text-white font-bold">{{ $ticket->ticket_number }}</td>
                                            <td class="py-3 px-2 text-slate-700 dark:text-slate-300">@include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true])
                                    </td>
                                            <td class="py-3 px-2 text-slate-500 dark:text-slate-400">{{ $ticket->counter ? $ticket->counter->name : '-' }}</td>
                                            <td class="py-3 px-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                                    {{ $ticket->customerStatusLabel() }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                                {{-- Penting: pakai atribut form="..." (HTML5), BUKAN <form> nested di
                                                     dalam <form id="batchEditForm">. Form gak boleh nested di HTML —
                                                     kalau dipaksa, browser bakal salah nangkep submit-nya (bisa malah
                                                     ke-submit ke form batch di luar, bukan ke aksi individual ini). --}}
                                                <button type="submit" form="startEdit{{ $ticket->id }}" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 border border-purple-300 text-purple-700 dark:border-purple-700 dark:text-purple-400 rounded-lg font-medium hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    Mulai Edit
                                                </button>
                                                <button type="submit" form="skipToPayment{{ $ticket->id }}" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                                    Edit Menyusul
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" id="batchEditButton" disabled class="btn btn-primary flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors bg-purple-600 hover:bg-purple-700 text-white disabled:bg-purple-300 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Proses Edit Batch
                            </button>
                        </div>
                    </form>

                    {{-- Form individual per tiket ("Mulai Edit" & "Edit Menyusul" per
                         baris), sengaja ditaruh DI LUAR <form id="batchEditForm"> di
                         atas supaya gak nested. Tombolnya di dalam tabel manggil form
                         ini lewat atribut form="...{id}". --}}
                    @foreach($readyForEditTickets as $ticket)
                        <form id="startEdit{{ $ticket->id }}" method="POST" action="{{ route('operator.start-editing', $ticket) }}" class="hidden">
                            @csrf
                        </form>
                        <form id="skipToPayment{{ $ticket->id }}" method="POST" action="{{ route('operator.skip-to-payment-pending-edit', $ticket) }}" class="hidden" onsubmit="return confirmFormSubmit(this, { message: 'Foto {{ $ticket->ticket_number }} belum selesai diedit. Pelanggan akan diarahkan ke kasir buat bayar duluan, hasil edit dikirim menyusul. Lanjutkan?', variant: 'primary', confirmText: 'Ya, Lanjutkan' })">
                            @csrf
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
                        @else
                        <!-- Current Ticket Panel -->
                        {{-- Card ini SEKARANG cuma muncul kalau LAGI ADA tiket aktif
                             (dipanggil/dilayani) di loket ini. Dulu ada versi "kosong"-nya
                             (judul + badge TIKET AKTIF + pesan "Tidak ada tiket...") yang
                             tetap nongol pas kosong -- itu sengaja DIHAPUS TOTAL (bukan
                             cuma tombolnya), biar operator gak lihat card kosong yang gak
                             ngapa-ngapain; cukup pakai panel "Daftar Tunggu"/"Antrian ..."
                             di bawah buat manggil tiket. --}}
                        @if(($currentTicket) && (in_array('walkin', $permissions) || in_array('photo', $permissions)))
                            <div id="current-ticket-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ in_array('registration', $counter->stages ?? [], true) ? 'Order' : $counter->stageLabel() }}</h2>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">TIKET AKTIF</span>
                                </div>
                                <div class="p-4">
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 text-center mb-4">
                                            <p class="text-blue-600 dark:text-blue-300 font-medium mb-1 text-sm">
                                                @if($currentTicket->status === 'calling')
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>Memanggil...</span>
                                                @else
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></span>Sedang Dilayani</span>
                                                @endif
                                            </p>
                                            <p class="text-5xl font-black text-blue-900 dark:text-blue-200">{{ $currentTicket->ticket_number }}</p>
                                            <p class="font-mono text-blue-500 dark:text-blue-400 text-xs mt-2">{{ $currentTicket->order_code }}</p>
                                            @if($currentTicket->customer_name)
                                                <p class="text-blue-900 dark:text-blue-200 mt-0.5 text-xl font-bold break-words leading-tight">{{ $currentTicket->customer_name }}</p>
                                            @endif
                                            <p class="text-blue-500 dark:text-blue-400 text-xs mt-1">
                                                {{ $currentTicket->customer_phone }}
                                                @if($currentTicket->customer_email)
                                                    &middot; {{ $currentTicket->customer_email }}
                                                @endif
                                            </p>
                                            @include('operator.partials.order-notes', ['notes' => $currentTicket->notes, 'tone' => 'blue'])
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
                                            <form method="POST" action="{{ route('operator.uncall', $currentTicket) }}" class="mb-3" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan pemanggilan tiket ini? Tiket akan dikembalikan ke antrian tanpa menghukum pelanggan.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 py-2 rounded-lg font-medium transition-colors text-xs hover:bg-slate-50 dark:hover:bg-slate-600">
                                                    Salah Panggil? Batalkan Pemanggilan
                                                </button>
                                            </form>
                                        @endif
                                        @if($currentTicket->stage === 'photo_session' && $currentTicket->status === 'serving')
                                            {{-- Nampilin SEMUA tiket yang lagi Siap-siap buat loket ini --
                                                 TIDAK ADA LAGI batasan "cuma boleh 1", jadi bisa lebih dari 1
                                                 box sekaligus di sini (mis. ada 2 ruang ganti). --}}
                                            @foreach($counter->preparingTickets() as $preparing)
                                                <div class="mb-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-3">
                                                    <p class="text-xs text-purple-700 dark:text-purple-300 font-semibold mb-1 flex items-center gap-1.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Sedang Siap-siap:
                                                    </p>
                                                    <p class="text-lg text-purple-900 dark:text-purple-200 font-black">{{ $preparing->ticket_number }}</p>
                                                @include('operator.partials.ticket-detail', ['ticket' => $preparing, 'withName' => true])
                                                <p class="text-xs font-semibold mt-1 {{ $preparing->ready_at ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">@if($preparing->ready_at) <x-papyrus-icon name="check-circle" /> Ready @else <x-papyrus-icon name="clock" /> Belum Ready @endif</p>
                                                    <form method="POST" action="{{ route('operator.uncall', $preparing) }}" class="mt-2" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan Siap-siap untuk tiket ini? Cocok dipakai kalau sesi ini bakal masih lama.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                        @csrf
                                                        <button type="submit" class="text-xs text-purple-600 dark:text-purple-400 underline">Belum, batalkan dulu (sesi masih lama)</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                            @if(($waitingByStage['photo_session'] ?? collect())->isNotEmpty())
                                                {{-- Cuma nongol kalau BENERAN ada tiket nunggu di Daftar Tunggu
                                                     buat dipanggil -- sebelumnya tombol ini selalu nongol begitu
                                                     belum ada yang siap-siap, walau Daftar Tunggu-nya kosong,
                                                     jadi diklik pun cuma dapat pesan info "tidak ada antrian",
                                                     buang-buang klik. SELALU nongol walau udah ada yang
                                                     siap-siap juga (TIDAK ADA lagi batasan "1 siap-siap per
                                                     loket") -- operator bebas manggil lagi kapan aja. Syarat
                                                     ini SAMA PERSIS kayak tombol header di card "Antrian
                                                     Siap-siap" & OperatorController::callNextToPrepare(). --}}
                                                <form method="POST" action="{{ route('operator.call-next-prepare') }}" class="mb-3">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-semibold transition-colors text-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Panggil Siap-siap Berikutnya
                                                    </button>
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
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Konfirmasi Pesanan
                                                    </button>
                                                </form>
                                            @else
                                                <p class="text-xs text-green-700 dark:text-green-400 mb-3 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                    Pesanan dikonfirmasi ({{ $currentTicket->order_code }}).
                                                </p>
                                                <form method="POST" action="{{ route('operator.complete-registration', $currentTicket) }}" class="mb-3" id="registration-form">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Selesaikan, Lanjut ke Sesi Foto</button>
                                                </form>
                                            @endif
                                        @elseif($currentTicket->stage === 'photo_session' && $currentTicket->status === 'calling')
                                            <form method="POST" action="{{ route('operator.start-serving', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>
                                                    Mulai Foto
                                                </button>
                                            </form>
                                        @elseif($currentTicket->stage === 'photo_session' && $currentTicket->status === 'serving')
                                            @if($currentTicket->isReservation())
                                                {{-- Khusus RESERVASI: sesudah foto WAJIB Pilih Foto dulu (ada timer), baru edit.
                                                     Tiket PP biasa tidak melihat tombol ini (lihat @else di bawah). --}}
                                                <form method="POST" action="{{ route('operator.finish-photo-session', $currentTicket) }}" class="mb-1">
                                                    @csrf
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                        Foto Selesai, Mulai Pilih Foto
                                                    </button>
                                                </form>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pelanggan reservasi memilih foto dulu (timer jalan). Setelah "Selesai Pilih Foto" baru lanjut ke edit.</p>
                                            @else
                                            <form method="POST" action="{{ route('operator.start-editing', $currentTicket) }}" class="mb-2">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>
                                                    Foto Selesai, Edit Sekarang
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('operator.finish-photo-session', $currentTicket) }}" class="mb-1">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-amber-500 text-amber-700 dark:text-amber-400 py-2.5 rounded-lg font-semibold transition-colors text-sm hover:bg-amber-50 dark:hover:bg-slate-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                                                    Foto Selesai, Nanti Diedit (Antri Dulu)
                                                </button>
                                            </form>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pilih "Edit Sekarang" kalau langsung diedit, atau "Antri Dulu" buat numpuk beberapa tiket lalu edit sekaligus nanti (lihat panel "Siap Diedit" di bawah).</p>
                                            @endif
                                        @elseif($currentTicket->stage === 'payment' && $currentTicket->status === 'calling')
                                            {{-- Sebelumnya tahap Pembayaran LANGSUNG lompat ke tombol
                                                 "Selesai" tanpa pernah lewat "Mulai Melayani" -- makanya
                                                 payment_served_at SELALU kosong dan "Proses Bayar" di
                                                 widget Waktu SELALU tampil "-" buat semua tiket, bukan
                                                 cuma tiket tertentu. Tombol ini nyamain pola yang sama
                                                 kayak Registrasi & Sesi Foto: pelanggan dipanggil dulu,
                                                 BARU beneran mulai dilayani begitu operator klik ini. --}}
                                            <form method="POST" action="{{ route('operator.start-serving', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                                                    Mulai Layani Pembayaran
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('operator.complete', $currentTicket) }}" class="mb-3">
                                                @csrf
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">
                                                    {{ match(true) {
                                                        $currentTicket->stage === 'payment' => 'Selesai (Bayar & Cetak)',
                                                        default => 'Selesaikan, Lanjut ke Pembayaran',
                                                    } }}
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('operator.skip', $currentTicket) }}" class="mb-4">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center justify-center bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Lewati (Panggil Nanti)</button>
                                        </form>
                                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Order</h4>
                                            @if($currentTicket->notes_locked_at)
                                                <p class="text-xs text-amber-600 dark:text-amber-400 mb-2 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                    {{-- Terkunci bisa kejadian di 2 momen beda: pas "Konfirmasi
                                                         Pesanan" (Registrasi) atau pas sesi foto dimulai
                                                         (Sesi Foto) -- pesannya disesuaikan biar gak nyesatin. --}}
                                                    @if($currentTicket->stage === 'registration')
                                                        Terkunci sejak pesanan dikonfirmasi — biar gak ke-klik/ke-edit gak sengaja.
                                                    @else
                                                        Terkunci sejak sesi foto dimulai — biar gak ke-klik/ke-edit gak sengaja.
                                                    @endif
                                                </p>
                                                <p class="whitespace-pre-line px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-sm mb-2">{{ $currentTicket->notes ?: '—' }}</p>
                                                <form method="POST" action="{{ route('operator.unlock-notes', $currentTicket) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-lavender hover:underline">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                        Buka Kunci buat Edit
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Nonaktif selama tiket masih status 'calling' (baru dipanggil,
                                                     belum diklik "Mulai Melayani"/"Mulai Foto"/dst) -- biar
                                                     operator fokus manggil/klaim pelanggannya dulu, catatan Order
                                                     baru bisa diisi begitu beneran mulai dilayani. --}}
                                                @php $orderNotYetActive = $currentTicket->status === 'calling'; @endphp
                                                @if($orderNotYetActive)
                                                    <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">Klik "Mulai Melayani"/"Mulai Foto" dulu buat isi catatan Order.</p>
                                                @endif
                                                <form method="POST" action="{{ route('operator.update-notes', $currentTicket) }}" class="order-autosave-form flex flex-col gap-1">
                                                    @csrf
                                                    <textarea name="notes" rows="1" placeholder="Order dari CS / Kasir / Fotografer..." @if($orderNotYetActive) disabled @endif class="autogrow-textarea order-textarea w-full min-h-[42px] px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm resize-none overflow-hidden disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-400 disabled:cursor-not-allowed">{{ $currentTicket->notes }}</textarea>
                                                    {{-- Gak ada lagi tombol "Simpan" manual -- tersimpan otomatis
                                                         sendiri begitu berhenti ngetik sebentar (lihat script
                                                         order-autosave-form di bawah). Toh sebelum lanjut ke tahap
                                                         berikutnya wajib "Konfirmasi Pesanan" dulu, jadi gak ada
                                                         resiko kelewat nyimpen. --}}
                                                    <span class="order-save-status text-xs text-slate-400 dark:text-slate-500 h-4"></span>
                                                </form>
                                                @if(!$orderNotYetActive && $currentTicket->location && $currentTicket->location->orderLineSuggestions())
                                                    <div class="flex flex-wrap gap-1.5 mt-2 order-suggestions">
                                                        @foreach($currentTicket->location->orderLineSuggestions() as $line)
                                                            <button type="button" data-order-line="{{ $line }}" class="order-suggestion-chip px-2.5 py-1 rounded-full text-xs font-medium bg-lavender/10 text-lavender hover:bg-lavender/20 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 border border-lavender/20 dark:border-slate-600 transition-colors">+ {{ $line }}</button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                </div>
                            </div>
                        @endif


        {{-- SECTION: KERJAAN DARI SHIFT SEBELUMNYA (Ambil Alih) --}}
        {{-- Tiket ready_for_edit/editing di studio yang lagi dipegang user ini,
             tapi ditinggal fotografer LAIN (mis. pulang malam, editnya belum
             kelar). Sengaja gak otomatis nyampur ke panel "Sedang Diedit"/
             "Siap Diedit" biasa -- staf yang masuk shift ini yang mutusin mau
             ambil alih atau biarin dulu (lihat OperatorController::takeoverTicket()). --}}
        @if(!empty($handoffTickets) && $handoffTickets->isNotEmpty())
            <div class="bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-200 dark:border-amber-800 overflow-hidden mb-6">
                <div class="p-4 border-b border-amber-200 dark:border-amber-800 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-amber-900 dark:text-amber-300 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Kerjaan dari Shift Sebelumnya
                        </h2>
                        <p class="text-amber-700 dark:text-amber-400 text-sm mt-0.5">{{ $handoffTickets->count() }} tiket edit belum selesai di studio ini, ditinggal staf lain</p>
                    </div>
                    <form method="POST" action="{{ route('operator.takeover-all') }}" onsubmit="return confirmFormSubmit(this, { message: 'Ambil alih semua ({{ $handoffTickets->count() }}) tiket ini?', variant: 'primary', confirmText: 'Ya, Ambil Semua' })">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white whitespace-nowrap">
                            Ambil Alih Semua
                        </button>
                    </form>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                                <th class="pb-2 font-semibold">Tiket</th>
                                <th class="pb-2 font-semibold">Pelanggan</th>
                                <th class="pb-2 font-semibold">Ditinggal</th>
                                <th class="pb-2 font-semibold">Status</th>
                                <th class="pb-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100 dark:divide-amber-800">
                            @foreach($handoffTickets as $ticket)
                                <tr>
                                    <td class="py-3 px-2 font-mono text-base text-amber-900 dark:text-amber-200 font-bold">{{ $ticket->ticket_number }}</td>
                                    <td class="py-3 px-2 text-amber-800 dark:text-amber-300">@include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true])
                                    </td>
                                    <td class="py-3 px-2 text-amber-700 dark:text-amber-400">{{ $ticket->servedBy->name ?? '-' }}, {{ $ticket->updated_at->format('H:i') }}</td>
                                    <td class="py-3 px-2 text-amber-700 dark:text-amber-400">{{ $ticket->customerStatusLabel() }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" action="{{ route('operator.takeover', $ticket) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-amber-400 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/30 whitespace-nowrap">
                                                Ambil Alih
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- SECTION: SUDAH DIBAYAR, EDIT BELUM SELESAI --}}
        {{-- Tiket yang tadinya dilewatin lewat "Lanjut ke Kasir — Edit Menyusul"
             (skipToPaymentPendingEdit()) -- sudah di kasir/selesai bayar, tapi
             editnya masih belum ditandai kelar. Di-scope ke editing_counter_id
             (bukan counter_id/served_by biasa) supaya tetap kelihatan siapapun
             yang lagi pegang studio ini. --}}
        @if(!empty($pendingEditTickets) && $pendingEditTickets->isNotEmpty())
            <div class="bg-sky-50 dark:bg-sky-900/10 rounded-2xl border border-sky-200 dark:border-sky-800 overflow-hidden mb-6">
                <div class="p-4 border-b border-sky-200 dark:border-sky-800">
                    <h2 class="text-lg font-bold text-sky-900 dark:text-sky-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Sudah Dibayar, Edit Belum Selesai
                    </h2>
                    <p class="text-sky-700 dark:text-sky-400 text-sm mt-0.5">{{ $pendingEditTickets->count() }} tiket sudah lewat kasir, hasil editnya masih menyusul</p>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-sky-700 dark:text-sky-400 uppercase tracking-wider">
                                <th class="pb-2 font-semibold">Tiket</th>
                                <th class="pb-2 font-semibold">Pelanggan</th>
                                <th class="pb-2 font-semibold">Status Saat Ini</th>
                                <th class="pb-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-100 dark:divide-sky-800">
                            @foreach($pendingEditTickets as $ticket)
                                <tr>
                                    <td class="py-3 px-2 font-mono text-sm text-sky-900 dark:text-sky-200">
                                        {{ $ticket->ticket_number }}
                                        {{-- Nomor tiket reset tiap hari (lihat ResetDailyQueue) -- tiket
                                             yang "nyebrang hari" dikasih label tanggal + kode order
                                             permanen (order_code, gak ikut reset) biar gak ketuker sama
                                             nomor yang sama persis punya pelanggan HARI INI. --}}
                                        @if(!$ticket->created_at->isToday())
                                            <span class="block text-xs font-normal text-sky-500">{{ $ticket->created_at->translatedFormat('d M') }} · {{ $ticket->order_code }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-sky-800 dark:text-sky-300">
                                        @include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true, 'part' => 'head'])
                                        {{-- Pilihan saat "Bayar Duluan": nunggu di tempat = prioritaskan --}}
                                        @if($ticket->edit_wait_preference === 'wait')
                                            <span class="block mt-0.5"><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Menunggu di tempat</span></span>
                                        @elseif($ticket->edit_wait_preference === 'leave')
                                            <span class="block mt-0.5"><span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Pulang dulu, kabari saat selesai</span></span>
                                        @endif
                                        @include('operator.partials.ticket-detail', ['ticket' => $ticket, 'part' => 'rest'])
                                    </td>
                                    <td class="py-3 px-2 text-sky-700 dark:text-sky-400">{{ $ticket->customerStatusLabel() }}
                                        @include('operator.partials.edit-progress', ['ticket' => $ticket])
                                    </td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" action="{{ route('operator.mark-edit-completed', $ticket) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'Tandai edit tiket {{ $ticket->ticket_number }} sudah selesai? Pelanggan akan diberi notifikasi.', variant: 'primary', confirmText: 'Ya, Selesai' })">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white whitespace-nowrap">
                                                Edit Selesai
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- SECTION: SEDANG DIEDIT --}}
        {{-- Tiket yang statusnya 'editing' -- baik lewat "Edit Sekarang" langsung
             maupun lewat batch edit di panel "Siap Diedit" di bawah. Loket sudah
             bebas dipakai buat pelanggan berikutnya begitu tiket masuk sini
             (lihat Counter::currentTicket()), jadi panel ini adalah SATU-SATUNYA
             tempat tiket-tiket ini kelihatan & bisa diselesaikan. TANPA PANEL INI,
             tiket yang baru selesai difoto lalu diklik "Edit Sekarang" akan hilang
             sama sekali dari tampilan (bukan cuma dari "TIKET AKTIF"). --}}
        @if(!empty($editingTickets) && $editingTickets->isNotEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Sedang Diedit
                        </h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $editingTickets->count() }} tiket sedang diproses editnya</p>
                    </div>
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ $editingTickets->count() }}</span>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="pb-2 font-semibold">Tiket</th>
                                <th class="pb-2 font-semibold">Pelanggan</th>
                                <th class="pb-2 font-semibold">Studio</th>
                                <th class="pb-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($editingTickets as $ticket)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td class="py-3 px-2 font-mono text-base text-slate-900 dark:text-white font-bold">{{ $ticket->ticket_number }}</td>
                                    <td class="py-3 px-2 text-slate-700 dark:text-slate-300">@include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true])
                                    </td>
                                    <td class="py-3 px-2 text-slate-500 dark:text-slate-400">{{ $ticket->counter ? $ticket->counter->name : '-' }}</td>
                                    <td class="py-3 px-2 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('operator.complete', $ticket) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 hover:bg-green-700 text-white whitespace-nowrap">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Edit Selesai, Lanjut ke Pembayaran
                                            </button>
                                        </form>
                                        {{-- Jalur alternatif kalau editnya bakal masih lama (mis. baru
                                             kelar besok) tapi pelanggan udah mau pulang. Sengaja outline
                                             (bukan warna solid) supaya kelihatan ini opsional, bukan
                                             tombol utama -- lihat Ticket::skipToPaymentPendingEdit(). --}}
                                        <form method="POST" action="{{ route('operator.skip-to-payment-pending-edit', $ticket) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'Foto {{ $ticket->ticket_number }} belum selesai diedit. Pelanggan akan diarahkan ke kasir buat bayar duluan, hasil edit dikirim menyusul. Lanjutkan?', variant: 'primary', confirmText: 'Ya, Lanjutkan' })">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 whitespace-nowrap">
                                                Lanjut ke Kasir — Edit Menyusul
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- SECTION: TIKET SIAP DIEDIT (Batch Edit Foto) --}}
        @include('operator.partials.photo-selection-panel')
        @if(!empty($readyForEditTickets) && $readyForEditTickets->isNotEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Tiket Siap Diedit
                        </h2>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $readyForEditTickets->count() }} tiket menunggu proses edit</p>
                    </div>
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">{{ $readyForEditTickets->count() }}</span>
                </div>
                <div class="p-4">
                    <form action="{{ route('operator.batch-start-editing') }}" method="POST" id="batchEditForm">
                        @csrf
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pilih tiket yang siap diedit, lalu klik <strong>Proses Edit Batch</strong>.</p>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        <th class="pb-2 font-semibold w-10"><input type="checkbox" id="selectAllBatchTickets" class="form-checkbox h-4 w-4 text-purple-600 rounded border-slate-300"></th>
                                        <th class="pb-2 font-semibold">Tiket</th>
                                        <th class="pb-2 font-semibold">Pelanggan</th>
                                        <th class="pb-2 font-semibold">Studio</th>
                                        <th class="pb-2 font-semibold">Status</th>
                                        <th class="pb-2 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($readyForEditTickets as $ticket)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                            <td class="py-3 px-2">
                                                <input type="checkbox" name="tickets_to_edit[]" value="{{ $ticket->id }}" class="form-checkbox h-4 w-4 text-purple-600 rounded border-slate-300 batch-ticket-checkbox">
                                            </td>
                                            <td class="py-3 px-2 font-mono text-base text-slate-900 dark:text-white font-bold">{{ $ticket->ticket_number }}</td>
                                            <td class="py-3 px-2 text-slate-700 dark:text-slate-300">@include('operator.partials.ticket-detail', ['ticket' => $ticket, 'withName' => true])
                                    </td>
                                            <td class="py-3 px-2 text-slate-500 dark:text-slate-400">{{ $ticket->counter ? $ticket->counter->name : '-' }}</td>
                                            <td class="py-3 px-2">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                                    {{ $ticket->customerStatusLabel() }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-2 text-right whitespace-nowrap">
                                                {{-- Penting: pakai atribut form="..." (HTML5), BUKAN <form> nested di
                                                     dalam <form id="batchEditForm">. Form gak boleh nested di HTML —
                                                     kalau dipaksa, browser bakal salah nangkep submit-nya (bisa malah
                                                     ke-submit ke form batch di luar, bukan ke aksi individual ini). --}}
                                                <button type="submit" form="startEdit{{ $ticket->id }}" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 border border-purple-300 text-purple-700 dark:border-purple-700 dark:text-purple-400 rounded-lg font-medium hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    Mulai Edit
                                                </button>
                                                <button type="submit" form="skipToPayment{{ $ticket->id }}" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                                    Edit Menyusul
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" id="batchEditButton" disabled class="btn btn-primary flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors bg-purple-600 hover:bg-purple-700 text-white disabled:bg-purple-300 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Proses Edit Batch
                            </button>
                        </div>
                    </form>

                    {{-- Form individual per tiket ("Mulai Edit" & "Edit Menyusul" per
                         baris), sengaja ditaruh DI LUAR <form id="batchEditForm"> di
                         atas supaya gak nested. Tombolnya di dalam tabel manggil form
                         ini lewat atribut form="...{id}". --}}
                    @foreach($readyForEditTickets as $ticket)
                        <form id="startEdit{{ $ticket->id }}" method="POST" action="{{ route('operator.start-editing', $ticket) }}" class="hidden">
                            @csrf
                        </form>
                        <form id="skipToPayment{{ $ticket->id }}" method="POST" action="{{ route('operator.skip-to-payment-pending-edit', $ticket) }}" class="hidden" onsubmit="return confirmFormSubmit(this, { message: 'Foto {{ $ticket->ticket_number }} belum selesai diedit. Pelanggan akan diarahkan ke kasir buat bayar duluan, hasil edit dikirim menyusul. Lanjutkan?', variant: 'primary', confirmText: 'Ya, Lanjutkan' })">
                            @csrf
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

                        <!-- Waiting Queue -->
                        @if($counter && in_array('walkin', $permissions))
                            @php
                                $isSingleStageCounter = $counter && count($counter->stages ?? []) === 1;
                                $stagesToShow = $isSingleStageCounter ? $counter->stages : \App\Models\Ticket::STAGE_ORDER;
                                $stagesToShow = collect($stagesToShow)->values()->all();
                                // Sisipkan card virtual "Antrian Siap-siap" tepat
                                // setelah card "Sesi Foto" (photo_session) -- biar
                                // urutannya masuk akal ngikutin alur: Registrasi ->
                                // Siap-siap -> Sesi Foto -> Pembayaran. Ini BUKAN
                                // stage asli di DB (masih `photo_session` juga),
                                // cuma status `preparing`-nya yang beda -- lihat
                                // $preparingTickets dari OperatorController::dashboard().
                                $photoSessionIdx = array_search('photo_session', $stagesToShow, true);
                                if ($photoSessionIdx !== false) {
                                    array_splice($stagesToShow, $photoSessionIdx + 1, 0, ['photo_session_preparing']);
                                }
                            @endphp
                            <div id="waiting-queue-panel" class="space-y-6">
                                @foreach($stagesToShow as $stage)
                                    @php
                                        $isPreparingCard = $stage === 'photo_session_preparing';
                                        // Fotografer (loket yang nangani photo_session) boleh aksi
                                        // (Batalkan Siap-siap) di card ini. CS/Kasir/role lain yang
                                        // cuma numpang lihat (mis. loket registration+payment) HANYA
                                        // read-only -- gak ada tombol apa pun di card ini.
                                        $canActOnPreparing = $isPreparingCard && in_array('photo_session', $counter->stages ?? [], true);
                                        // Sama kayak $canActOnPreparing tapi berlaku buat SEMUA
                                        // stage (dipakai di card "Daftar Tunggu"/photo_session,
                                        // tempat tombol massal manggil Siap-siap sekarang berada).
                                        $canActOnPreparingHere = in_array('photo_session', $counter->stages ?? [], true);
                                        $stageTickets = $isPreparingCard ? $preparingTickets : ($waitingByStage[$stage] ?? collect());
                                        // Dipisah per lokasi (PP = walk-in langsung, BK = hasil
                                        // check-in reservasi) supaya gak ketuker jadi satu daftar
                                        // -- tiap lokasi dapat kotak & tombol "Panggil Antrian"
                                        // sendiri-sendiri, sesuai urutan tunggu di lokasi itu saja.
                                        $stageTicketGroups = $stageTickets->groupBy('location_id')->sortKeys();
                                    @endphp
                                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
                                            <div>
                                                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $isPreparingCard ? 'Antrian Siap-siap' : ($isSingleStageCounter || $stage === 'photo_session' ? 'Daftar Tunggu' : 'Antrian ' . (\App\Models\Ticket::STAGE_LABELS[$stage] ?? $stage)) }}</h2>
                                                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ $stageTickets->count() }} {{ $isPreparingCard ? 'sedang siap-siap' : 'tiket menunggu' }}</p>
                                            </div>
                                            {{-- Tombol "Panggil Siap-siap" LANGSUNG dari card ini. Sengaja
                                                 TIDAK mensyaratkan loket lagi sibuk (currentTicket) -- Siap-siap
                                                 itu tahap WAJIB buat pelanggan bersiap (ganti baju, dsb)
                                                 sebelum masuk Sesi Foto, jadi tetap harus lewat sini walau
                                                 loket lagi kosong sama sekali (mis. tiket PERTAMA hari itu).
                                                 Cuma nongol kalau: belum ada yang lagi siap-siap DAN beneran
                                                 ADA tiket nunggu di "Daftar Tunggu" buat dipanggil -- syarat
                                                 ini SAMA PERSIS kayak yang dicek di
                                                 OperatorController::callNextToPrepare(), biar tombolnya gak
                                                 pernah nongol dalam kondisi yang bakal gagal/error kalau
                                                 diklik. --}}
                                            {{-- Tombol massal ini dipindah ke sini (card "Daftar Tunggu" /
                                                 photo_session) dari card "Antrian Siap-siap" -- biar SEMUA aksi
                                                 manggil (massal & per-tiket) ngumpul jadi satu tempat. Card
                                                 "Antrian Siap-siap" sekarang murni buat liat status + Batalkan/
                                                 Panggil Ulang. --}}
                                            @if($stage === 'photo_session' && $canActOnPreparingHere && $stageTickets->isNotEmpty())
                                                <form method="POST" action="{{ route('operator.call-next-prepare') }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Panggil Siap-siap
                                                    </button>
                                                </form>
                                            {{-- CS/Kasir (loket registration+payment, bukan studio) bisa BANTU
                                                 manggil "Siap-siap" juga -- beda dari tombol fotografer di atas,
                                                 SENGAJA GAK milih studio tujuan sama sekali (generik, "ke ruang
                                                 ganti" doang, gak nyebut nama studio), karena fotografer belum
                                                 ada yang manggil tiket ini ke studio manapun. Alasan fitur ini
                                                 ada: CS di depan bisa lihat kondisi ruang tunggu asli,
                                                 fotografer di dalam studio enggak. --}}
                                            @elseif($stage === 'photo_session' && !$canActOnPreparingHere && $isCsKasir && $stageTickets->isNotEmpty())
                                                <form method="POST" action="{{ route('operator.call-next-prepare-auto') }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                        Panggil Siap-siap (ke Ruang Ganti)
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                        @if($stageTickets->isEmpty())
                                            <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Tidak ada antrian untuk tahap ini.</div>
                                        @else
                                            @foreach($stageTicketGroups as $groupLocationId => $locTickets)
                                                @php $ticketLocation = $locTickets->first()->location; @endphp
                                                <div class="border-t border-slate-100 dark:border-slate-700 first:border-t-0">
                                                    <div class="px-6 py-3 bg-slate-50/70 dark:bg-slate-900/20 flex items-center justify-between gap-3 flex-wrap">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-block w-2 h-2 rounded-full {{ ($ticketLocation && $ticketLocation->offersWalkInTicket()) ? 'bg-blue-500' : 'bg-purple-500' }}"></span>
                                                            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $ticketLocation->name ?? 'Tanpa Lokasi' }}</h3>
                                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ $locTickets->count() }} {{ $isPreparingCard ? 'siap-siap' : 'menunggu' }}</span>
                                                        </div>
                                                        @if($isPreparingCard)
                                                            {{-- Card Siap-siap gak punya tombol "Panggil Antrian" di
                                                                 sini -- manggil siap-siap cuma bisa 1-per-1 lewat
                                                                 tombol kontekstual di panel "TIKET AKTIF" selagi
                                                                 fotografer lagi motret (lihat autoCallNextToPrepare()
                                                                 & tombol "Panggil Siap-siap Berikutnya"). Di sini
                                                                 cuma "Batalkan Siap-siap" per tiket kalau perlu. --}}
                                                        @elseif($stage === 'photo_session' && in_array($stage, $counter->stages ?? [], true))
                                                            {{-- Sesi Foto sengaja gak punya tombol "Panggil Antrian" (langsung
                                                                 ke Sesi Foto) di sini -- SEMUA panggilan buat tahap ini WAJIB
                                                                 lewat "Siap-siap" dulu, biar pelanggan sempat bersiap (ganti
                                                                 baju, dsb) sebelum masuk studio. Pakai tombol "Panggil
                                                                 Siap-siap" di card "Antrian Siap-siap" di atas (FIFO), atau
                                                                 tombol "Panggil Siap-siap" per-tiket di bawah. --}}
                                                        @elseif(!$currentTicket && in_array($stage, $counter->stages ?? [], true))
                                                            <form method="POST" action="{{ route('operator.call-next') }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="stage" value="{{ $stage }}" />
                                                                <input type="hidden" name="location_id" value="{{ $groupLocationId }}" />
                                                                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                                    Panggil Antrian
                                                                </button>
                                                            </form>
                                                        @elseif($stage === 'photo_session' && $isCsKasir)
                                                            <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold whitespace-nowrap">Panggil siap-siap per tiket di bawah</span>
                                                        @elseif(!$currentTicket)
                                                            <span class="text-xs text-slate-400 font-semibold whitespace-nowrap">Bukan tahap Anda</span>
                                                        @endif
                                                    </div>
                                                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                                        @foreach($locTickets->values() as $qIndex => $ticket)
                                                            <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 border border-slate-200 dark:border-slate-600 hover:border-blue-300 dark:hover:border-blue-700 transition-colors {{ $qIndex >= 6 ? 'hidden queue-card-extra' : '' }}" data-queue-group="queue-{{ $stage }}-{{ $groupLocationId }}">
                                                                <div class="flex items-start justify-between gap-2 mb-2">
                                                                    <div class="flex items-center gap-2">
                                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex-shrink-0" title="Urutan ke-{{ $qIndex + 1 }} dalam antrian {{ $ticketLocation->name ?? '' }} ini">{{ $qIndex + 1 }}</span>
                                                                        <span class="text-xl font-black text-slate-900 dark:text-white">{{ $ticket->ticket_number }}</span>
                                                                    </div>
                                                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $isPreparingCard ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }} whitespace-nowrap">{{ $isPreparingCard ? 'Siap-siap' : 'Menunggu' }}</span>
                                                                </div>
                                                                <p class="font-mono text-xs text-slate-400 dark:text-slate-500 truncate">{{ $ticket->order_code }}</p>
                                                                <p class="text-base font-bold text-slate-900 dark:text-white break-words leading-tight">{{ $ticket->customer_name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $ticket->customer_phone }}@if($ticket->customer_phone && $ticket->customer_email) &middot; @endif{{ $ticket->customer_email }}</p>
                                                                @include('operator.partials.order-notes', ['notes' => $ticket->notes, 'tone' => 'plain'])
                                                                @if($isPreparingCard)
                                                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $ticket->counter?->name ?? '-' }}</p>
                                                                    {{-- Info buat fotografer: kasih tau kalau tiket ini sempat
                                                                         "dipanggil"/"dipanggil ulang" duluan sama CS/Kasir dari
                                                                         depan -- sebelumnya gak ada info apa pun di sini, fotografer
                                                                         gak tau tiketnya udah di-nudge CS apa belum. Dicek dari
                                                                         role staf yang terakhir megang (servedBy) & counter
                                                                         hitungan berapa kali di-nudge ulang (prepare_recall_count)
                                                                         -- lihat Ticket::callToPrepare() & Ticket::recallPrepare(). --}}
                                                                    @if($ticket->servedBy && ($ticket->servedBy->isOperator() || $ticket->servedBy->isAdmin()))
                                                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5"><x-papyrus-icon name="hand-raised" /> Dipanggil CS {{ $ticket->servedBy->name }}</p>
                                                                    @endif
                                                                    @if($ticket->prepare_recall_count > 0)
                                                                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                                                            <x-papyrus-icon name="arrow-path" /> Dipanggil ulang {{ $ticket->prepare_recall_count }}x oleh CS{{ $ticket->prepareRecalledBy ? ' (' . $ticket->prepareRecalledBy->name . ')' : '' }}
                                                                            @if($ticket->called_at) &middot; {{ $ticket->called_at->diffForHumans() }} @endif
                                                                        </p>
                                                                    @endif
                                                                    {{-- TIDAK ADA lock per studio (counter_id) di sini -- STUDIO
                                                                         MANA PUN boleh liat & mencet tombol "Panggil Sekarang" /
                                                                         "Batalkan Siap-siap" buat tiket Siap-siap MANA PUN, baik
                                                                         yang generik (CS yang mulai, counter_id kosong) maupun yang
                                                                         tadinya dipanggil studio lain (counter_id terisi). Studio
                                                                         yang beneran berhasil klik duluan yang "menang" -- lihat
                                                                         catatan panjang di OperatorController::callPreparingTicket()
                                                                         soal kenapa ini sengaja dibikin gak terkunci. --}}
                                                                    @if($canActOnPreparing)
                                                                        <p class="mt-3 text-xs font-semibold {{ $ticket->ready_at ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                                            @if($ticket->ready_at)
                                                                                <x-papyrus-icon name="check-circle" /> Ready dari CS{{ $ticket->readyBy ? ' (' . $ticket->readyBy->name . ')' : '' }} &middot; {{ $ticket->ready_at->diffForHumans() }} &mdash; siap dibawa ke studio
                                                                            @else
                                                                                <x-papyrus-icon name="clock" /> Belum Ready &mdash; menunggu CS memastikan pelanggannya sudah siap
                                                                            @endif
                                                                        </p>
                                                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                            {{-- Manual sepenuhnya -- fotografer yang mutusin kapan
                                                                                 mau naikkin tiket "Siap-siap" ini jadi "Dipanggil",
                                                                                 gak nempel ke event apa pun (dulu otomatis nempel di
                                                                                 Ticket::advanceStage(), sekarang dihapus). Cuma nongol
                                                                                 kalau loket lagi FREE (gak ada currentTicket) -- kalau
                                                                                 masih ada yang lagi dilayani, harus diselesaikan dulu. --}}
                                                                            @if(!$currentTicket && $ticket->ready_at)
                                                                                <form method="POST" action="{{ route('operator.call-preparing', $ticket) }}" class="flex-1">
                                                                                    @csrf
                                                                                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil Sekarang</button>
                                                                                </form>
                                                                            @endif
                                                                            <form method="POST" action="{{ route('operator.uncall', $ticket) }}" class="flex-1" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan Siap-siap untuk tiket {{ $ticket->ticket_number }}? Cocok dipakai kalau sesi ini bakal masih lama.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                                                @csrf
                                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Batalkan Siap-siap</button>
                                                                            </form>
                                                                        </div>
                                                                    {{-- CS/Kasir sekarang JUGA bisa Batalkan Siap-siap (dulu cuma bisa
                                                                         "nudge" via Panggil Ulang) -- berlaku buat tiket yang CS sendiri
                                                                         yang mulai (counter_id kosong) MAUPUN yang sudah dikunci ke
                                                                         studio tertentu oleh fotografer, karena CS di depan yang paling
                                                                         sering tau duluan kalau pelanggannya kabur/gak jadi. Lihat
                                                                         OperatorController::uncallTicket() & recallPreparingTicket(). --}}
                                                                    @elseif($isCsKasir)
                                                                        <div class="mt-3">
                                                                            @if($ticket->ready_at)
                                                                                <form method="POST" action="{{ route('operator.unmark-ready', $ticket) }}">
                                                                                    @csrf
                                                                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-green-100 text-green-800 border py-1.5 rounded-lg text-xs font-semibold transition" style="border-color: #86efac;"><x-papyrus-icon name="check-circle" /> Ready &middot; klik untuk batalkan</button>
                                                                                </form>
                                                                            @else
                                                                                <form method="POST" action="{{ route('operator.mark-ready', $ticket) }}">
                                                                                    @csrf
                                                                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-sm font-semibold transition"><x-papyrus-icon name="check-circle" /> Ready &mdash; siap dipanggil fotografer ke studio</button>
                                                                                </form>
                                                                            @endif
                                                                        </div>
                                                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                            <form method="POST" action="{{ route('operator.recall-preparing', $ticket) }}" class="flex-1" onsubmit="return confirmFormSubmit(this, { message: 'Panggil ulang tiket {{ $ticket->ticket_number }} buat bersiap-siap?', confirmText: 'Panggil Ulang' })">
                                                                                @csrf
                                                                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                                                                    Panggil Ulang
                                                                                </button>
                                                                            </form>
                                                                            <form method="POST" action="{{ route('operator.uncall', $ticket) }}" class="flex-1" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan Siap-siap untuk tiket {{ $ticket->ticket_number }}? Cocok dipakai kalau sesi ini bakal masih lama.', variant: 'danger', confirmText: 'Ya, Batalkan' })">
                                                                                @csrf
                                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Batalkan Siap-siap</button>
                                                                            </form>
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                @if($ticket->skip_count > 0)
                                                                    <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Lewati {{ $ticket->skip_count }}x</span>
                                                                    </div>
                                                                @endif
                                                                @if(in_array($ticket->status, ['waiting', 'calling'], true))
                                                                    <div class="flex items-center gap-2 flex-wrap mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                        {{-- Sesi Foto: SELALU lewat "Siap-siap" dulu -- pelanggan
                                                                             butuh waktu buat bersiap (ganti baju, dsb) sebelum masuk
                                                                             studio, jadi gak ada lagi jalan pintas "Panggil" langsung
                                                                             ke Sesi Foto buat tahap ini, baik loket lagi kosong
                                                                             maupun sibuk. Tahap lain (Registrasi/Pembayaran) tetap
                                                                             pakai "Panggil" langsung seperti biasa, karena gak butuh
                                                                             jeda bersiap. Sama persis kayak yang dicek backend di
                                                                             OperatorController::callSpecificToPrepare(), biar gak
                                                                             pernah nongol dalam kondisi yang bakal gagal kalau
                                                                             diklik. --}}
                                                                        @if($ticket->status === 'waiting' && $ticket->stage === 'photo_session' && in_array('photo_session', $counter->stages ?? [], true))
                                                                            <form method="POST" action="{{ route('operator.call-specific-prepare', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil Siap-siap</button>
                                                                            </form>
                                                                        @elseif($ticket->status === 'waiting' && $ticket->stage === 'photo_session' && $isCsKasir)
                                                                            {{-- CS/Kasir: sama kayak tombol fotografer di atas, tapi generik
                                                                                 (gak pilih studio tujuan) -- lihat OperatorController::
                                                                                 callSpecificToPrepareAuto(). --}}
                                                                            <form method="POST" action="{{ route('operator.call-specific-prepare-auto', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil ke Ruang Ganti</button>
                                                                            </form>
                                                                        @elseif(!$currentTicket && in_array($ticket->stage, $counter->stages ?? [], true))
                                                                            <form method="POST" action="{{ route('operator.call-specific', $ticket) }}" class="flex-1 min-w-[80px]">@csrf
                                                                                <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil</button>
                                                                            </form>
                                                                        @endif
                                                                        @if(in_array($ticket->stage, $counter->stages ?? [], true) && in_array($ticket->status, ['calling', 'serving'], true))
                                                                            <form method="POST" action="{{ route('operator.skip', $ticket) }}" class="flex-1 min-w-[80px]" onsubmit="return confirmFormSubmit(this, { message: 'Lewati tiket {{ $ticket->ticket_number }}?', variant: 'primary', confirmText: 'Ya, Lewati' })">@csrf
                                                                                <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Lewati</button>
                                                                            </form>
                                                                        @endif
                                                                        @if(in_array($ticket->stage, $counter->stages ?? [], true))
                                                                            <form method="POST" action="{{ route('operator.tickets.destroy', $ticket) }}" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan tiket {{ $ticket->ticket_number }}?', variant: 'danger' })">@csrf @method('DELETE')
                                                                                <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 rounded-lg transition-colors" title="Batalkan">
                                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                                </button>
                                                                            </form>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if($locTickets->count() > 6)
                                                        <div class="px-4 pb-4">
                                                            <button type="button" class="queue-load-more-btn w-full py-2 text-xs font-semibold text-lavender hover:text-dustyrose border border-dashed border-slate-300 dark:border-slate-600 rounded-lg transition-colors" data-queue-group="queue-{{ $stage }}-{{ $groupLocationId }}">
                                                                Muat {{ $locTickets->count() - 6 }} Lagi
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Card "Panggil Ulang" KHUSUS CS/Kasir -- nunjukin tiket yang
                             LAGI DIPANGGIL ke studio (status calling, stage photo_session)
                             beserta udah berapa lama, biar CS bisa bantu "nyodok" fotografer
                             yang mungkin belum ngeh/kelewat manggil pelanggannya -- fotografer
                             di dalam studio gak bisa mantau kondisi ruang tunggu kayak CS di
                             depan. Atribusi tetap ke fotografer aslinya (lihat recallTicket()
                             di OperatorController), CS cuma "membantu memicu ulang". --}}
                        @if($isCsKasir && $studioCallingTickets->isNotEmpty())
                            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden" id="studio-calling-panel">
                                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sedang Dipanggil ke Studio</h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Bantu panggil ulang kalau fotografer kelihatan belum ngeh</p>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($studioCallingTickets as $ct)
                                        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                                            <div>
                                                <p class="text-lg font-black text-slate-900 dark:text-white">{{ $ct->ticket_number }}</p>
                                                <p class="font-mono text-xs text-slate-400 dark:text-slate-500 truncate">{{ $ct->order_code }}</p>
                                                @if($ct->customer_name)
                                                    <p class="text-base font-bold text-slate-900 dark:text-white mt-0.5 break-words leading-tight">{{ $ct->customer_name }}</p>
                                                @endif
                                                @if($ct->customer_phone || $ct->customer_email)
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $ct->customer_phone }}@if($ct->customer_phone && $ct->customer_email) &middot; @endif{{ $ct->customer_email }}</p>
                                                @endif
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                    {{ $ct->counter?->publicLabel() ?? 'Studio' }}
                                                    @if($ct->called_at)
                                                        &middot; <span class="studio-calling-elapsed" data-called-at="{{ $ct->called_at->timestamp }}">sejak {{ $ct->called_at->diffForHumans() }}</span>
                                                    @endif
                                                </p>
                                                @include('operator.partials.order-notes', ['notes' => $ct->notes, 'tone' => 'plain'])
                                            </div>
                                            <form method="POST" action="{{ route('operator.recall', $ct) }}" onsubmit="return confirmFormSubmit(this, { message: 'Panggil ulang tiket ' + '{{ $ct->ticket_number }}' + '?', confirmText: 'Panggil Ulang' })">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                                    Panggil Ulang
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Card "Sedang Diedit" -- sama kayak di cabang fotografer di atas,
                             lihat catatan lengkap di sana. --}}
                        @include('operator.partials.photo-selection-panel')
                        @if($isCsKasir && $editingInProgressTickets->isNotEmpty())
                            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sedang Diedit</h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Foto masih diproses fotografer. Kalau pelanggan mau bayar duluan, pakai tombol Bayar Duluan.</p>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($editingInProgressTickets as $et)
                                        <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                                            <div>
                                                <p class="text-lg font-black text-slate-900 dark:text-white">{{ $et->ticket_number }}</p>
                                                <p class="font-mono text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $et->order_code }}</p>
                                                <p class="text-base font-bold text-slate-900 dark:text-white mt-0.5 break-words leading-tight">{{ $et->customer_name ?? '-' }}</p>
                                                @if($et->customer_phone || $et->customer_email)
                                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $et->customer_phone }}@if($et->customer_phone && $et->customer_email) &middot; @endif{{ $et->customer_email }}</p>
                                                @endif
                                                @include('operator.partials.order-notes', ['notes' => $et->notes, 'tone' => 'plain'])
                                            </div>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                                Sedang Diedit
                                            </span>
                                            {{-- CS/Kasir bisa nolong pelanggan yang mau bayar duluan begitu
                                                 sesi foto selesai & masuk tahap edit (tanpa nunggu fotografer) --}}
                                            <div class="w-full sm:w-64">
                                                @php $payFirstTicket = $et; $payFirstRoute = 'operator.skip-to-payment-pending-edit'; $payFirstLabel = 'Pelanggan mau bayar duluan?'; @endphp
                                                @include('operator.partials.pay-first-buttons')
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
                                            <!-- Main info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ $booking->location->code ?? '-' }}</span>
                                                    <span class="font-bold text-slate-900 dark:text-white">{{ $booking->customer_name }}</span>
                                                    @if($booking->order_code)
                                                        <span class="font-mono font-semibold text-slate-600 dark:text-slate-300 ml-1">{{ $booking->order_code }}</span>
                                                    @endif
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusPillColors[$booking->status] ?? 'bg-slate-100 text-slate-700' }}">
                                                        {{ $statusPillLabels[$booking->status] ?? ucfirst($booking->status) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                                    {{ $booking->customer_phone }}
                                                    {{-- Kode reservasi sistem (BK-2026...) disembunyikan; yang tampil: nomor tiket (kalau sudah ada). --}}
                                                    @if($booking->ticket)
                                                        <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                                                        <a href="{{ route('tickets.show', $booking->ticket) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">{{ $booking->ticket->ticket_number }}</a>
                                                    @endif
                                                    @if($booking->serviceCategory && !$booking->package_name)
                                                        <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                                                        {{ $booking->serviceCategory->name }}
                                                    @endif
                                                </p>
                                                {{-- Detail per baris (Paket, Jumlah Orang, Tambahan, Note Order), "Selengkapnya" kalau panjang. --}}
                                                @include('operator.partials.order-notes', ['notes' => $booking->ticketOrderText(), 'hide' => ['Kode Order'], 'tone' => 'plain', 'prependRows' => [['Email', $booking->customer_email]]])
                                                @if($booking->status === 'cancelled' && $booking->dp_forfeited)
                                                    <p class="text-xs font-semibold text-red-600 dark:text-red-400 mt-0.5">DP hangus</p>
                                                @endif
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-2 flex-shrink-0 sm:justify-end">
                                                @if($canAct && $isToday)
                                                    <form method="POST" action="{{ route('operator.bookings.call', $booking) }}" onsubmit="return confirmFormSubmit(this, { title: 'Check-in Reservasi', message: 'Check-in reservasi {{ $booking->customer_name }}? Tiket dibuat dan langsung masuk antrian Studio (tidak lewat CS).', variant: 'primary', confirmText: 'Ya, Check-in' })">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Check-in</button>
                                                    </form>
                                                @elseif($canAct)
                                                    <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap">Check-in saat hari H</span>
                                                @endif
                                                @if($canAct)
                                                    @php
                                                        $bookingEditData = [
                                                            "id" => $booking->id,
                                                            "code" => $booking->ticket?->ticket_number ?? '',
                                                            "customer_name" => $booking->customer_name,
                                                            "customer_phone" => $booking->customer_phone,
                                                            "customer_email" => $booking->customer_email,
                                                            "booking_date" => $booking->booking_date->format("Y-m-d"),
                                                            "booking_time" => is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format("H:i"),
                                                            "notes" => $booking->notes,
                                                            "package_name" => $booking->package_name,
                                                            "people_count" => $booking->people_count,
                                                            "costume_changes" => $booking->costume_changes,
                                                            "extras" => $booking->extras ?? [],
                                                            "dp_amount" => $booking->dp_amount,
                                                            "order_code" => $booking->order_code,
                                                        ];
                                                    @endphp
                                                    <button type="button" onclick='openEditBookingModal(@json($bookingEditData))' class="px-3 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap hover:bg-slate-50 dark:hover:bg-slate-700">Edit</button>
                                                    <form method="POST" action="{{ route('operator.bookings.destroy', $booking) }}" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan reservasi {{ $booking->customer_name }}?', variant: 'danger' })">
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
                        <div id="status-loket-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
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
                                                        // Khusus loket CS (Registrasi/Pembayaran) yang lagi gak
                                                        // ada operatornya, disembunyikan dari panel ini biar gak
                                                        // numpuk banyak "Tidak aktif" begitu loket CS-nya jadi
                                                        // banyak (CS & Kasir, CS 2, CS 3, dst). Loket lain
                                                        // (mis. Studio buat fotografer) tetap ditampilkan
                                                        // walau lagi kosong, karena masih relevan buat dilihat
                                                        // ketersediaan studionya.
                                                        $isCsCounter = $c->handlesStage('registration') || $c->handlesStage('payment');
                                                    @endphp
                                                    @continue($isCsCounter && !$c->currentOperator)
                                                    @php
                                                        $cCurrent = $c->currentTicket();
                                                        $cPreparing = $c->preparingTickets();
                                                        $cEditing = $c->editingTickets();
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
                                                        @if($c->is_paused)
                                                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-600 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3a2 2 0 002 2h2a2 2 0 002-2v-3M3 9a1 1 0 011-1h13a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V9zm14 0h2a2 2 0 012 2v1a2 2 0 01-2 2h-2M6 6V4a1 1 0 011-1h6a1 1 0 011 1v2"/></svg>
                                                                Istirahat
                                                            </div>
                                                        @elseif($cCurrent)
                                                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-600 text-xs">
                                                                <span class="font-bold text-slate-900 dark:text-white">{{ $cCurrent->ticket_number }}</span>
                                                                <span class="text-slate-500 dark:text-slate-400"> — {{ $cCurrent->customerStatusLabel() }}</span>
                                                            </div>
                                                            <div class="mt-1.5 pt-1.5 border-t border-dashed border-slate-200 dark:border-slate-600">
                                                                @php $waktuTicket = $cCurrent; @endphp
                                                                @include('operator.partials.waktu-cell')
                                                            </div>
                                                        @elseif($cEditing->isNotEmpty())
                                                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-600 text-xs text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                                Sedang edit {{ $cEditing->count() }} tiket
                                                            </div>
                                                        @elseif($c->currentOperator)
                                                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-600 text-xs text-slate-400 dark:text-slate-500">Kosong, siap menerima</div>
                                                        @endif
                                                        {{-- $cPreparing sekarang koleksi (bisa lebih dari 1 tiket
                                                             sekaligus siap-siap buat loket ini) -- tampilin yang
                                                             pertama, plus penanda "+N lagi" kalau ada lebih. --}}
                                                        @if($cPreparing->isNotEmpty())
                                                            <div class="mt-1 text-xs text-purple-600 dark:text-purple-400 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                                                Siap-siap: <span class="font-semibold">{{ $cPreparing->first()->ticket_number }}</span>
                                                                @if($cPreparing->count() > 1)
                                                                    <span class="text-purple-400">+{{ $cPreparing->count() - 1 }} lagi</span>
                                                                @endif
                                                            </div>
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
                                {{-- Buat tiket manual atas nama pelanggan -- dipakai CS pas
                                     pelanggan gak sempat/gak bisa scan QR sendiri (mis. gak
                                     bawa HP, sinyal jelek). --}}
                                <a href="{{ route('tickets.create', $location) }}" target="_blank" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                    <span>Tambah Tiket</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </a>
                                <button type="button" onclick="openDisplayPickerModal()" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                    <span>Display</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2v10a2 2 0 002 2z"/></svg>
                                </button>
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
                                @if(auth()->user()->isOperator() || auth()->user()->isFotografer() || auth()->user()->isAdmin())
                                    <a href="{{ route('operator.release-counter') }}" class="flex items-center justify-between w-full border border-dashed border-slate-300 dark:border-slate-600 hover:border-slate-400 text-slate-500 dark:text-slate-400 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Ganti Studio</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
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
                                            <input type="text" name="bq" value="{{ $bookingSearch }}" placeholder="Nama, telepon, email, kode order, atau nomor tiket..." class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                            <input type="date" name="bdate" id="booking-date-input" value="{{ $bookingDate !== 'all' ? $bookingDate : '' }}" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                            <input type="checkbox" id="booking-date-all" class="js-toggle-all-dates" data-target="booking-date-input" {{ $bookingDate === 'all' ? 'checked' : '' }}>
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
                            </div>
                            @if($custBookings->isEmpty())
                                <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">Tidak ada reservasi yang cocok dengan filter ini.</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm responsive-table">
                                        <thead>
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan &amp; Kode Order</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jadwal</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Paket</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[180px]">Waktu</th>
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
                                                    {{-- Nama + kode order dalam SATU kolom (sama seperti isian "Nama Pelanggan & Kode Order" di form);
                                                         kode reservasi sistem dibuat kecil di bawahnya sebagai rujukan. --}}
                                                    <td class="px-5 py-3.5" data-label="Pelanggan &amp; Kode Order">
                                                        <div class="flex flex-col">
                                                            <span class="font-medium text-slate-900 dark:text-white">{{ $booking->customer_name }}@if($booking->order_code) <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">{{ $booking->order_code }}</span>@endif</span>
                                                            @if($booking->ticket)
                                                                <a href="{{ route('tickets.show', $booking->ticket) }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline mt-0.5">Tiket {{ $booking->ticket->ticket_number }}</a>
                                                            @endif
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
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300" data-label="Paket">
                                                        <span class="font-medium">{{ $booking->package_name ?: ($booking->serviceCategory->name ?? '-') }}</span>
                                                        @if($booking->people_count || $booking->costume_changes > 0 || $booking->dp_amount > 0)
                                                            <span class="block text-slate-500 dark:text-slate-400">{{ implode(' · ', array_filter([$booking->people_count ? $booking->people_count . ' orang' : null, $booking->costume_changes > 0 ? '+' . $booking->costume_changes . ' kostum' : null, $booking->dp_amount > 0 ? $booking->dpLabel() : null])) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-5 py-3.5" data-label="Status">
                                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                            {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-sm" data-label="Waktu">
                                                        @php $waktuTicket = $booking->ticket; @endphp
                                                        @include('operator.partials.waktu-cell')
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right rt-actions" data-label="Aksi">
                                                        <div class="flex items-center justify-end gap-2">
                                                            @if($canManageBookings)
                                                                @if(in_array($booking->status, ['pending', 'confirmed']) && $booking->booking_date->isToday())
                                                                    <form method="POST" action="{{ route('operator.bookings.call', $booking) }}" class="inline" onsubmit="return confirmFormSubmit(this, { title: 'Check-in Reservasi', message: 'Check-in reservasi {{ $booking->customer_name }}? Tiket langsung masuk antrian Studio.', variant: 'primary', confirmText: 'Ya, Check-in' })">@csrf
                                                                        <button type="submit" class="px-2.5 py-1.5 bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white rounded-md text-xs font-semibold transition-colors">Check-in</button>
                                                                    </form>
                                                                @endif
                                                                {{-- Edit: semua status (data bisa dibetulkan walau reservasinya sudah check-in/selesai/batal).
                                                                      Hapus: cuma yang masih aktif (pending/confirmed) -- sama seperti Hapus tiket, artinya membatalkan. --}}
                                                                    @php
                                                        $bookingEditData = [
                                                            "id" => $booking->id,
                                                            "code" => $booking->ticket?->ticket_number ?? '',
                                                            "customer_name" => $booking->customer_name,
                                                            "customer_phone" => $booking->customer_phone,
                                                            "customer_email" => $booking->customer_email,
                                                            "booking_date" => $booking->booking_date->format("Y-m-d"),
                                                            "booking_time" => is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format("H:i"),
                                                            "notes" => $booking->notes,
                                                            "package_name" => $booking->package_name,
                                                            "people_count" => $booking->people_count,
                                                            "costume_changes" => $booking->costume_changes,
                                                            "extras" => $booking->extras ?? [],
                                                            "dp_amount" => $booking->dp_amount,
                                                            "order_code" => $booking->order_code,
                                                        ];
                                                    @endphp
                                                    <button type="button" onclick='openEditBookingModal(@json($bookingEditData))' class="inline-block px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-md text-xs font-semibold transition-colors">Edit</button>
                                                                 {{-- Hapus SELALU aktif (aktif / sudah check-in / selesai / batal). Tiketnya tidak ikut dihapus. --}}
                                                                <form method="POST" action="{{ route('operator.bookings.remove', $booking) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'Hapus permanen reservasi {{ $booking->customer_name }}?@if($booking->ticket) Tiket {{ $booking->ticket->ticket_number }} tetap ada, tapi tidak lagi tercatat sebagai reservasi.@endif Tindakan ini tidak bisa dibatalkan.', variant: 'danger', confirmText: 'Ya, Hapus' })">@csrf @method('DELETE')
                                                                    <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition-colors">Hapus</button>
                                                                </form>
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
                                                <option value="preparing" {{ $custStatus === 'preparing' ? 'selected' : '' }}>Siap-siap</option>
                                                <option value="serving" {{ $custStatus === 'serving' ? 'selected' : '' }}>Sedang Dilayani</option>
                                                <option value="ready_for_edit" {{ $custStatus === 'ready_for_edit' ? 'selected' : '' }}>Menunggu Edit</option>
                                                <option value="editing" {{ $custStatus === 'editing' ? 'selected' : '' }}>Sedang Diedit</option>
                                                <option value="skipped" {{ $custStatus === 'skipped' ? 'selected' : '' }}>Dilewati</option>
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
                                            <input type="checkbox" id="cust-date-all" class="js-toggle-all-dates" data-target="cust-date-input" {{ $custDate === 'all' ? 'checked' : '' }}>
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
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahap</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[180px]">Waktu</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dilayani Oleh</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Order</th>
                                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                            @foreach($custTickets as $ct)
                                                @php
                                                    $statusClasses = [
                                                        'waiting' => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600',
                                                        'calling' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                                        'serving' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                                        'editing' => 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800',
                                                        'completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                                        'cancelled' => 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                                        'preparing' => 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800',
                                                        'ready_for_edit' => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600',
                                                        'skipped' => 'bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800',
                                                    ];
                                                    $statusLabels = [
                                                        'preparing' => 'Siap-siap',
                                                        'ready_for_edit' => 'Menunggu Edit',
                                                        'skipped' => 'Dilewati',
                                                        'waiting' => 'Menunggu',
                                                        'calling' => 'Dipanggil',
                                                        'serving' => 'Dilayani',
                                                        'editing' => 'Diedit',
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
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300" data-label="Tahap">{{ $ct->stageLabel() }}</td>
                                                    <td class="px-5 py-3.5" data-label="Status">
                                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold {{ $statusClasses[$ct->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                            {{ $statusLabels[$ct->status] ?? ucfirst($ct->status) }}
                                                        </span>
                                                        {{-- Estimasi cuma buat operator (internal) -- SENGAJA tidak
                                                             ditampilkan ke pelanggan supaya bukan jadi janji/patokan
                                                             yang bisa meleset. --}}
                                                        @if($ct->status === 'waiting' && $ct->estimated_wait_time)
                                                            <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">≈ {{ $ct->estimated_wait_time }} menit (internal)</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-5 py-3.5 text-sm align-top" data-label="Waktu">
                                                        @php $waktuTicket = $ct; @endphp
                                                        @include('operator.partials.waktu-cell')
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300 min-w-[190px] align-top" data-label="Dilayani Oleh">
                                                        @php $dilayaniTicket = $ct; @endphp
                                                        @include('operator.partials.dilayani-cell')
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300 align-middle" style="min-width: 160px; max-width: 260px;" data-label="Order">
                                                        <span class="order-notes-cell block" title="{{ $ct->notes }}">{{ $ct->notes ?: '-' }}</span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right rt-actions" data-label="Aksi">
                                                         {{-- Edit data (nama/telepon/email/Order): SEMUA tahap & status, termasuk yang sudah
                                                              selesai/batal. Batalkan: SEMUA tahap & status selama sesi belum selesai
                                                              (Ticket::canBeCancelled()). --}}
                                                         <div class="flex items-center justify-end gap-2">
                                                             <button type="button" onclick='openEditTicketModal({!! json_encode(["id" => $ct->id, "ticket_number" => $ct->ticket_number, "order_code" => $ct->order_code, "customer_name" => $ct->customer_name, "customer_phone" => $ct->customer_phone, "customer_email" => $ct->customer_email, "notes" => $ct->notes, "notes_locked" => (bool) $ct->notes_locked_at, "notes_unlockable" => $ct->canUnlockNotesBy(auth()->user())], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) !!})' class="inline-block px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-md text-xs font-semibold transition-colors">Edit</button>
                                                             @if($ct->canBeCancelled())
                                                                 <form method="POST" action="{{ route('operator.tickets.destroy', $ct) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'Batalkan tiket {{ $ct->ticket_number }}? Tiket yang dibatalkan tidak bisa dilanjutkan lagi.', variant: 'danger', confirmText: 'Ya, Batalkan' })">@csrf @method('DELETE')
                                                                     <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition-colors">Batalkan</button>
                                                                 </form>
                                                                 @if(auth()->user()->isAdmin())
                                                                     <form method="POST" action="{{ route('operator.tickets.remove', $ct) }}" class="inline" onsubmit="return confirmFormSubmit(this, { message: 'HAPUS PERMANEN tiket {{ $ct->ticket_number }}? Datanya hilang dari riwayat dan laporan, dan tidak bisa dikembalikan. Kalau cuma mau membatalkan, pakai tombol Batalkan.', variant: 'danger', confirmText: 'Ya, Hapus Permanen' })">@csrf @method('DELETE')
                                                                         <button type="submit" class="px-2.5 py-1.5 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md text-xs font-semibold transition-colors">Hapus</button>
                                                                     </form>
                                                                 @endif
                                                             @endif
                                                         </div>
                                                         @if($ct->counter && !$ct->canBeCancelled())
                                                             <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $ct->counter->name }}</span>
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
        <!-- Modal Pilih Lokasi Display: dari tombol "Display" di Pintasan,
             operator bisa pilih mau nampilin display cuma buat lokasi ini
             (PP saja / BK saja) atau gabungan semua lokasi (buat TV ruang
             tunggu bareng). Tiap pilihan buka tab baru, modalnya sendiri
             gak perlu nutup otomatis. -->
        <div id="display-picker-modal-backdrop" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target === this) closeDisplayPickerModal()">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Pilih Lokasi Display</h2>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Mau buka display buat lokasi mana?</p>
                    </div>
                    <button type="button" onclick="closeDisplayPickerModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4 space-y-2">
                    @foreach($locations as $displayLocation)
                        <a href="{{ route('locations.display', $displayLocation) }}" target="_blank" onclick="closeDisplayPickerModal()" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500 text-slate-700 dark:text-slate-300 px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                            <span>{{ $displayLocation->name }}</span>
                            <span class="text-xs font-normal text-slate-400 dark:text-slate-500">{{ $displayLocation->code }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('locations.display-all') }}" target="_blank" onclick="closeDisplayPickerModal()" class="flex items-center justify-between w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                        <span>Semua Lokasi</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
            </div>
        </div>

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

                <form id="booking-store-form" method="POST" action="{{ route('operator.bookings.store') }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan &amp; Kode Order</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" maxlength="255" placeholder="mis. Naya JG035474" required
                            class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <p class="text-xs text-slate-400 mt-1">Tulis nama lalu kode order dalam satu isian. Contoh: Naya JG035474</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                                inputmode="numeric" maxlength="16" placeholder="081234567890"
                                pattern="\+?[0-9]{8,15}" title="Hanya angka (boleh diawali +), 8-15 digit"
                                oninput="this.value = this.value.replace(/(?!^\+)[^0-9]/g, '')"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <p class="text-xs text-slate-400 mt-1">Hanya angka, boleh diawali +62.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email (opsional)</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                                inputmode="email" placeholder="nama@email.com" maxlength="255"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    {{-- Detail reservasi sesuai format booking WA (diisi CS/operator). --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jumlah Orang di Foto</label>
                            <input type="number" name="people_count" value="{{ old('people_count') }}" min="1" max="{{ config('booking.max_people', 500) }}" inputmode="numeric" placeholder="mis. 6"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tambahan (opsional)</label>
                        <input type="hidden" name="extras_submitted" value="1">
                        <details class="extras-dropdown">
                            <summary class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white cursor-pointer flex items-center justify-between list-none">
                                <span data-extras-summary class="truncate">Pilih tambahan</span>
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mt-1 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 p-2 space-y-1">
                                @forelse(\App\Models\BookingExtra::activeNames() as $opt)
                                    <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 dark:hover:bg-slate-600 cursor-pointer text-sm text-slate-700 dark:text-slate-200">
                                        <input type="checkbox" name="extras[]" value="{{ $opt }}" {{ in_array($opt, (array) old('extras', []), true) ? 'checked' : '' }} onchange="papyrusUpdateExtrasSummary(this)" class="rounded border-slate-300">
                                        {{ $opt }}
                                    </label>
                                @empty
                                    <p class="px-2 py-1.5 text-xs text-slate-400">Belum ada pilihan. Admin bisa menambahkannya di menu Tambahan Reservasi.</p>
                                @endforelse
                            </div>
                        </details>
                        <p class="text-xs text-slate-400 mt-1">Boleh pilih lebih dari satu.</p>
                    </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilihan Paket</label>
                        <select name="package_name" id="add-package-select"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="">Belum pilih paket</option>
                            @foreach(\App\Models\BookingPackage::activeNames() as $pk)
                                <option value="{{ $pk }}" {{ old('package_name') === $pk ? 'selected' : '' }}>{{ $pk }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Field "Layanan" dihapus dari tampilan -- field "Order"
                         (catatan bebas) di bawah sudah cukup buat nyatet detail
                         pesanan, gak perlu dropdown terpisah lagi. Kolom
                         service_category_id di database dibiarin null buat
                         reservasi yang dibuat lewat form ini (kolomnya nullable,
                         gak dipakai di laporan/admin manapun). --}}
                    <div>
                        {{-- Field "Lokasi" dihapus dari tampilan sepenuhnya (bukan
                             dikondisiin lagi kayak sebelumnya) -- lokasi reservasi
                             selalu ikut lokasi operator yang lagi login, dikirim
                             diam-diam lewat hidden input. --}}
                        <input type="hidden" name="location_id" value="{{ old('location_id') ?? optional($bookingLocations->first())->id ?? optional($location)->id }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Note Order (opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Catatan pesanan (mis. 2x cetak 4R, 1x album)..." class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none">{{ old('notes') }}</textarea>
                        <p class="text-xs text-slate-400 mt-1">Ikut kebawa otomatis ke tiket begitu reservasi ini check-in — gak perlu ditulis ulang.</p>
                    </div>
                    @if($errors->any())
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-lg p-3">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">Simpan Reservasi</button>
                        <button type="button" onclick="closeBookingModal(true)" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-lg font-semibold transition-colors">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Reservasi: pola sama persis dengan Tambah Reservasi di
             atas (mengambang, fetch async, gak reload halaman) -- bedanya
             field-nya diisi dari data booking yang mana yang lagi diedit. -->
        <div id="edit-booking-modal-backdrop" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target === this) closeEditBookingModal()">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Edit Reservasi <span id="edit-booking-code" class="text-slate-400 font-normal"></span></h2>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Ubah detail reservasi di bawah ini.</p>
                    </div>
                    <button type="button" onclick="closeEditBookingModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form id="booking-update-form" method="POST" action="" data-booking-id="" class="p-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan &amp; Kode Order</label>
                        <input type="text" name="customer_name" maxlength="255" placeholder="mis. Naya JG035474" required
                            class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <p class="text-xs text-slate-400 mt-1">Tulis nama lalu kode order dalam satu isian. Contoh: Naya JG035474</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                            <input type="tel" name="customer_phone" required
                                inputmode="numeric" maxlength="16" placeholder="081234567890"
                                pattern="\+?[0-9]{8,15}" title="Hanya angka (boleh diawali +), 8-15 digit"
                                oninput="this.value = this.value.replace(/(?!^\+)[^0-9]/g, '')"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <p class="text-xs text-slate-400 mt-1">Hanya angka, boleh diawali +62.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email (opsional)</label>
                            <input type="email" name="customer_email"
                                inputmode="email" placeholder="nama@email.com" maxlength="255"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    {{-- Detail reservasi sesuai format booking WA (diisi CS/operator). --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Jumlah Orang di Foto</label>
                            <input type="number" name="people_count" value="" min="1" max="{{ config('booking.max_people', 500) }}" inputmode="numeric" placeholder="mis. 6"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tambahan (opsional)</label>
                        <input type="hidden" name="extras_submitted" value="1">
                        <details class="extras-dropdown">
                            <summary class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white cursor-pointer flex items-center justify-between list-none">
                                <span data-extras-summary class="truncate">Pilih tambahan</span>
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="mt-1 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 p-2 space-y-1">
                                @forelse(\App\Models\BookingExtra::activeNames() as $opt)
                                    <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 dark:hover:bg-slate-600 cursor-pointer text-sm text-slate-700 dark:text-slate-200">
                                        <input type="checkbox" name="extras[]" value="{{ $opt }}" onchange="papyrusUpdateExtrasSummary(this)" class="rounded border-slate-300">
                                        {{ $opt }}
                                    </label>
                                @empty
                                    <p class="px-2 py-1.5 text-xs text-slate-400">Belum ada pilihan. Admin bisa menambahkannya di menu Tambahan Reservasi.</p>
                                @endforelse
                            </div>
                        </details>
                        <p class="text-xs text-slate-400 mt-1">Boleh pilih lebih dari satu.</p>
                    </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilihan Paket</label>
                        <select name="package_name" id="edit-package-select"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="">Belum pilih paket</option>
                            @foreach(\App\Models\BookingPackage::activeNames() as $pk)
                                <option value="{{ $pk }}">{{ $pk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Note Order (opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Catatan pesanan (mis. 2x cetak 4R, 1x album)..." class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none"></textarea>
                    </div>
                    {{-- Sama kayak di form Edit Tiket -- box ini cuma ada
                         kalau beneran ada error, biar pengecekan .bg-red-50
                         yang DI-SCOPE ke form ini (lihat JS di bawah) gak
                         salah kira "ada error" gara-gara elemen merah LAIN
                         di halaman (badge "Dibatalkan", tombol "Batalkan"). --}}
                    @if($errors->any())
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-lg p-3">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">Simpan Perubahan</button>
                        <button type="button" onclick="closeEditBookingModal(true)" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-lg font-semibold transition-colors">Batal</button>
                    </div>
                </form>
            </div>
        </div>


        <script>
            // Gabungin pilihan dropdown Jam + Menit jadi satu nilai HH:MM di
            // hidden input booking_time yang beneran dikirim ke server.
            function papyrusSyncTimeSelect() {
                const h = document.getElementById('booking-hour-select')?.value || '';
                const m = document.getElementById('booking-minute-select')?.value || '';
                const hidden = document.getElementById('booking-time-hidden');
                if (hidden) hidden.value = (h && m) ? (h + ':' + m) : '';
            }

            // Ringkasan isi dropdown "Tambahan" (mis. "Kostum, Pose") di baris summary-nya.
            // "Selengkapnya (+N)" / "Sembunyikan" untuk daftar isi order (partial order-notes).
            // Dashboard menyegarkan isinya otomatis, jadi kartu yang sedang DIBUKA diingat (per isi
            // order) dan dibuka lagi otomatis setelah tiap penyegaran -- tidak menutup sendiri.
            const papyrusOpenNotes = (() => {
                try { return new Set(JSON.parse(sessionStorage.getItem('papyrusOpenNotes') || '[]')); }
                catch (e) { return new Set(); }
            })();

            function papyrusSaveOpenNotes() {
                try { sessionStorage.setItem('papyrusOpenNotes', JSON.stringify([...papyrusOpenNotes])); } catch (e) {}
            }

            function papyrusApplyOrderNotesState(box, open) {
                box.querySelectorAll('.order-notes-extra').forEach(el => el.classList.toggle('hidden', !open));
                const btn = box.querySelector('button[data-more-label]');
                if (btn) {
                    btn.dataset.open = open ? '1' : '0';
                    const label = open ? 'Sembunyikan' : btn.dataset.moreLabel;
                    if (btn.textContent !== label) btn.textContent = label;
                }
            }

            function papyrusToggleOrderNotes(btn) {
                const box = btn.closest('[data-order-notes]');
                if (!box) return;
                const open = btn.dataset.open !== '1';
                const key = box.dataset.notesKey;
                if (key) { open ? papyrusOpenNotes.add(key) : papyrusOpenNotes.delete(key); papyrusSaveOpenNotes(); }
                // Semua kartu dengan isi yang sama (mis. tata letak lain) ikut sinkron.
                document.querySelectorAll('[data-order-notes]').forEach(el => {
                    if (!key || el.dataset.notesKey === key) papyrusApplyOrderNotesState(el, open);
                });
            }

            // Pulihkan status buka/tutup setelah dashboard mengganti isi halaman.
            function papyrusRestoreOrderNotes() {
                document.querySelectorAll('[data-order-notes]').forEach(box => {
                    const want = papyrusOpenNotes.has(box.dataset.notesKey);
                    const btn = box.querySelector('button[data-more-label]');
                    if (btn && (btn.dataset.open === '1') !== want) papyrusApplyOrderNotesState(box, want);
                });
            }
            // Klik "Selengkapnya": delegasi di document & dibaca saat DITEKAN (pointerdown), bukan saat
            // dilepas -- supaya tetap bisa diklik walau kartunya diganti ulang oleh penyegaran otomatis
            // di antara tekan dan lepas. click dipakai hanya untuk keyboard (detail === 0).
            document.addEventListener('pointerdown', function (e) {
                const b = e.target.closest && e.target.closest('button[data-more-label]');
                if (!b) return;
                e.preventDefault();
                papyrusToggleOrderNotes(b);
            }, true);
            document.addEventListener('click', function (e) {
                const b = e.target.closest && e.target.closest('button[data-more-label]');
                if (b && e.detail === 0) papyrusToggleOrderNotes(b);
            }, true);

            (function () {
                let queued = false;
                const schedule = () => {
                    if (queued) return;
                    queued = true;
                    requestAnimationFrame(() => { queued = false; papyrusRestoreOrderNotes(); });
                };
                new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
                document.addEventListener('DOMContentLoaded', papyrusRestoreOrderNotes);
                papyrusRestoreOrderNotes();
            })();

            function papyrusUpdateExtrasSummary(el) {
                const box = el.closest('.extras-dropdown');
                if (!box) return;
                const picked = [...box.querySelectorAll('input[type="checkbox"]:checked')].map(c => c.value);
                const label = box.querySelector('[data-extras-summary]');
                if (label) label.textContent = picked.length ? picked.join(', ') : 'Pilih tambahan';
            }

            // Perbarui teks ringkasan "Tambahan" sesuai checkbox yang sedang tercentang
            // (dipakai setelah isi form diganti ulang / dipulihkan dari draft).
            function papyrusRefreshExtrasSummary(form) {
                const first = form && form.querySelector('input[name="extras[]"]');
                if (first) papyrusUpdateExtrasSummary(first);
            }

            function papyrusSetExtras(form, values) {
                const list = Array.isArray(values) ? values : [];
                const box = form.querySelector('.extras-dropdown');
                if (!box) return;
                const holder = box.querySelector('input[name="extras[]"]')?.closest('div') || box.querySelector('div');
                // Pilihan yang sudah tersimpan di reservasi tapi sudah dihapus/nonaktif di Admin:
                // tetap ditampilkan (dicentang) supaya TIDAK hilang diam-diam saat disimpan.
                box.querySelectorAll('[data-extra-temp]').forEach(el => el.remove());
                list.forEach(v => {
                    const exists = [...box.querySelectorAll('input[name="extras[]"]')].some(c => c.value === v);
                    if (!exists && holder) {
                        const lbl = document.createElement('label');
                        lbl.dataset.extraTemp = '1';
                        lbl.className = 'flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 dark:hover:bg-slate-600 cursor-pointer text-sm text-slate-700 dark:text-slate-200';
                        const cb = document.createElement('input');
                        cb.type = 'checkbox'; cb.name = 'extras[]'; cb.value = v;
                        cb.className = 'rounded border-slate-300';
                        cb.addEventListener('change', () => papyrusUpdateExtrasSummary(cb));
                        lbl.appendChild(cb);
                        lbl.appendChild(document.createTextNode(' ' + v));
                        holder.appendChild(lbl);
                    }
                });
                const boxes = box.querySelectorAll('input[name="extras[]"]');
                boxes.forEach(c => { c.checked = list.includes(c.value); });
                if (boxes[0]) papyrusUpdateExtrasSummary(boxes[0]);
                else {
                    const label = box.querySelector('[data-extras-summary]');
                    if (label) label.textContent = 'Pilih tambahan';
                }
            }

            function papyrusResetBookingForm() {
                // Sengaja BUKAN form.reset() bawaan -- kalau form ini pernah
                // diganti innerHTML-nya gara-gara validasi gagal (lihat
                // cabang error di submit handler bawah), atribut value="..."
                // hasil old() yang lagi kepasang jadi "default value" BARU di
                // mata browser, jadi form.reset() malah balikin ke isian
                // gagal terakhir, BUKAN ke kosong. Di sini tiap field
                // di-kosongin manual biar dijamin beneran bersih.
                const form = document.getElementById('booking-store-form');
                if (!form) return;
                form.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"], textarea').forEach(el => { el.value = ''; });
                const dateInput = form.querySelector('[name="booking_date"]');
                if (dateInput) dateInput.value = dateInput.getAttribute('min') || '';
                const hourSel = document.getElementById('booking-hour-select');
                const minSel = document.getElementById('booking-minute-select');
                if (hourSel) hourSel.selectedIndex = 0;
                if (minSel) minSel.selectedIndex = 0;
                if (typeof papyrusSyncTimeSelect === 'function') papyrusSyncTimeSelect();
                papyrusSetExtras(form, []);
                form.querySelectorAll('select[name="package_name"]').forEach(el => { el.selectedIndex = 0; });
                form.querySelectorAll('input[name="people_count"]').forEach(el => { el.value = ''; });
            }

            function openDisplayPickerModal() {
                const backdrop = document.getElementById('display-picker-modal-backdrop');
                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
            }

            function closeDisplayPickerModal() {
                const backdrop = document.getElementById('display-picker-modal-backdrop');
                backdrop.classList.add('hidden');
                backdrop.classList.remove('flex');
            }

            // Penyimpanan form "Tambah Reservasi" (penanda terbuka + draft isian) dipisah
            // PER USER. Dulu kuncinya global di localStorage browser: penanda "terbuka"
            // yang tertinggal dari sesi/operator sebelumnya (mis. form ditutup pakai X di
            // versi lama, atau staf lain lupa menutup lalu logout) membuat form reservasi
            // MUNCUL OTOMATIS begitu operator berikutnya masuk -- dan draft pelanggan
            // operator sebelumnya ikut terbawa.
            const PAPYRUS_BOOKING_OPEN_KEY = 'papyrus-booking-modal-open:{{ auth()->id() }}';
            const PAPYRUS_BOOKING_DRAFT_KEY = 'papyrus-booking-draft:{{ auth()->id() }}';
            try {
                // bersihkan kunci versi lama (global, tidak terikat user)
                localStorage.removeItem('papyrus-booking-modal-open');
                localStorage.removeItem('papyrus-booking-draft');
            } catch (e) {}

            /** 'reload' = halaman di-refresh (F5 / location.reload); 'navigate' = masuk/buka baru. */
            function papyrusNavigationType() {
                try {
                    const nav = performance.getEntriesByType('navigation')[0];
                    if (nav && nav.type) return nav.type;
                    if (performance.navigation) return performance.navigation.type === 1 ? 'reload' : 'navigate';
                } catch (e) {}
                return 'navigate';
            }

            /**
             * Form reservasi dibuka otomatis lagi HANYA kalau: halaman sedang di-REFRESH,
             * form memang masih terbuka saat itu, dan ada isian yang layak dipulihkan.
             * Masuk/login/buka dashboard baru (navigate) TIDAK pernah membukanya.
             */
            function papyrusShouldReopenBookingModal(hasDraftContent, openFlag, navType) {
                return hasDraftContent && openFlag === '1' && navType === 'reload';
            }

            function openBookingModal() {
                const backdrop = document.getElementById('booking-modal-backdrop');
                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                document.body.style.overflow = 'hidden';
                try { localStorage.setItem(PAPYRUS_BOOKING_OPEN_KEY, '1'); } catch (e) {}
            }
            /**
             * @param {boolean} isCancel - true kalau operator EKSPLISIT klik
             * "Batal" (atau habis berhasil "Simpan Reservasi") -- di kasus
             * ini form & draft-nya SENGAJA dikosongin. false (default) kalau
             * modal ditutup lewat cara yang gampang kepencet TIDAK SENGAJA
             * (tombol X, klik di luar kotak, tombol Esc) -- di kasus ini
             * form & draft TETAP dipertahankan apa adanya, jadi begitu
             * "Tambah Reservasi" dibuka lagi, isian yang tadi diketik masih
             * ada, tinggal dilanjutkan.
             */
            function closeBookingModal(isCancel = false) {
                const backdrop = document.getElementById('booking-modal-backdrop');
                backdrop.classList.add('hidden');
                backdrop.classList.remove('flex');
                document.body.style.overflow = '';

                // Penanda "modal sedang terbuka" SELALU dihapus begitu modal ditutup
                // (X / Esc / klik di luar / Batal). Sebelumnya penanda ini sengaja
                // dibiarkan setelah soft-close, akibatnya: operator menutup form,
                // lalu refresh halaman -> form reservasi MUNCUL LAGI padahal sudah
                // ditutup. Sekarang form cuma dibuka otomatis setelah refresh kalau
                // memang SEDANG terbuka saat refresh (isiannya dipulihkan dari draft).
                try { localStorage.removeItem(PAPYRUS_BOOKING_OPEN_KEY); } catch (e) {}

                if (isCancel) {
                    papyrusResetBookingForm();
                    if (typeof papyrusClearBookingDraft === 'function') papyrusClearBookingDraft();
                }
                // Soft-close (bukan cancel): draft isian TETAP disimpan -- begitu
                // "Tambah Reservasi" dibuka lagi secara manual, isian tadi masih ada.
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeBookingModal();
                    closeEditBookingModal();
                    closeEditTicketModal();
                    closeDisplayPickerModal();
                }
            });

            // "Muat Lebih Banyak" di card Daftar Tunggu/Antrian Registrasi --
            // pakai event delegation di document (bukan nempel langsung ke
            // tombolnya), biar TETAP jalan walau kartu ini di-refresh berkala
            // (innerHTML ditimpa ulang tiap beberapa detik). Sengaja
            // client-side doang (bukan fetch data baru) -- semua tiket udah
            // ke-render di HTML dari awal, cuma disembunyikan (class
            // `hidden`), jadi klik ini instan tanpa nunggu request.
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.queue-load-more-btn');
                if (!btn) return;
                const group = btn.dataset.queueGroup;
                document.querySelectorAll(`.queue-card-extra[data-queue-group="${group}"]`).forEach(el => {
                    el.classList.remove('hidden', 'queue-card-extra');
                });
                btn.remove();
            });

            // Tombol "Selengkapnya" di isi order (notes) kartu antrian --
            // sama polanya kayak "Muat Lebih Banyak" di atas: event
            // delegation, murni tampilan (teks lengkapnya udah ada di HTML
            // dari awal, cuma disembunyikan), jadi tetap jalan walau kartu
            // di-refresh berkala & instan tanpa nunggu request.
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.order-notes-toggle');
                if (!btn) return;
                const line = btn.closest('.order-notes-line');
                if (!line) return;
                line.querySelector('.order-notes-short')?.classList.add('hidden');
                line.querySelector('.order-notes-full')?.classList.remove('hidden');
                btn.remove();
            });

            // Update teks "sejak X menit lalu" di card "Sedang Dipanggil ke
            // Studio" (panel Panggil Ulang khusus CS) tiap detik, tanpa perlu
            // reload/refresh dari server.
            function formatElapsed(seconds) {
                if (seconds < 60) return `sejak ${seconds} detik lalu`;
                const minutes = Math.floor(seconds / 60);
                if (minutes < 60) return `sejak ${minutes} menit lalu`;
                const hours = Math.floor(minutes / 60);
                return `sejak ${hours} jam ${minutes % 60} menit lalu`;
            }
            function updateStudioCallingElapsed() {
                document.querySelectorAll('.studio-calling-elapsed').forEach((el) => {
                    const calledAt = parseInt(el.dataset.calledAt, 10);
                    if (!calledAt) return;
                    const seconds = Math.max(0, Math.floor(Date.now() / 1000) - calledAt);
                    el.textContent = formatElapsed(seconds);
                });
            }
            setInterval(updateStudioCallingElapsed, 1000);

            // ============ Modal Edit Reservasi ============
            function papyrusSyncEditTimeSelect() {
                const h = document.getElementById('edit-booking-hour-select')?.value || '';
                const m = document.getElementById('edit-booking-minute-select')?.value || '';
                const hidden = document.getElementById('edit-booking-time-hidden');
                if (hidden) hidden.value = (h && m) ? (h + ':' + m) : '';
            }

            /**
             * @param {object} booking - {id, code, customer_name, customer_phone, customer_email, booking_date, booking_time, notes}
             *
             * Field CUMA diisi ulang dari data server kalau ini booking BEDA
             * dari yang lagi kebuka di modal (dicek lewat data-booking-id).
             * Jadi kalau modal ke-close TIDAK SENGAJA (X/klik luar/Esc) lalu
             * "Edit" diklik lagi buat RESERVASI YANG SAMA, perubahan yang
             * tadi diketik tapi belum sempat "Simpan Perubahan" tetap ada,
             * bukan ketimpa data lama dari server. Buka booking LAIN tetap
             * diisi fresh seperti biasa, gak ada nyampur data antar reservasi.
             */
            function openEditBookingModal(booking) {
                const backdrop = document.getElementById('edit-booking-modal-backdrop');
                const form = document.getElementById('booking-update-form');

                if (form.dataset.bookingId !== String(booking.id)) {
                    form.action = '/operator/bookings/' + booking.id;
                    form.dataset.bookingId = String(booking.id);
                    document.getElementById('edit-booking-code').textContent = booking.code || '';
                    form.querySelector('[name="customer_name"]').value = [booking.customer_name, booking.order_code].filter(Boolean).join(' ');
                    form.querySelector('[name="customer_phone"]').value = booking.customer_phone || '';
                    form.querySelector('[name="customer_email"]').value = booking.customer_email || '';
                    form.querySelector('[name="notes"]').value = booking.notes || '';
                    form.querySelector('[name="people_count"]').value = booking.people_count || '';
                    // Paket: kalau nama paket tersimpan sudah gak ada di daftar (mis. data
                    // lama / daftar diubah), tambahkan sebagai pilihan supaya TIDAK hilang
                    // diam-diam saat disimpan.
                    const pkgSel = form.querySelector('[name="package_name"]');
                    const pkg = booking.package_name || '';
                    if (pkg && ![...pkgSel.options].some(o => o.value === pkg)) {
                        pkgSel.add(new Option(pkg, pkg));
                    }
                    pkgSel.value = pkg;
                    papyrusSetExtras(form, booking.extras);
                }

                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            /**
             * @param {boolean} isCancel - true kalau eksplisit klik "Batal"
             * (atau berhasil disimpan) -- di kasus ini data-booking-id
             * dikosongin, jadi lain kali "Edit" diklik (biarpun buat booking
             * yang sama), field-nya dipaksa diisi ulang fresh dari server,
             * bukan nyisa isian yang tadi dibatalin. false (default, soft
             * close) mempertahankan isian form apa adanya.
             */
            function closeEditBookingModal(isCancel = false) {
                const backdrop = document.getElementById('edit-booking-modal-backdrop');
                backdrop.classList.add('hidden');
                backdrop.classList.remove('flex');
                document.body.style.overflow = '';
                if (isCancel) {
                    document.getElementById('booking-update-form').dataset.bookingId = '';
                }
            }

            // Submit "Simpan Perubahan" lewat fetch juga -- pola identik
            // dengan "Tambah Reservasi" di atas, biar gak ada reload halaman
            // & tab yang lagi aktif gak keganggu.
            (function () {
                const updateForm = document.getElementById('booking-update-form');
                if (!updateForm) return;

                updateForm.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    if (updateForm.dataset.submitting === '1') return;
                    updateForm.dataset.submitting = '1';
                    const submitBtn = updateForm.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    try {
                        const response = await fetch(updateForm.action, {
                            method: 'POST', // Laravel method-spoofing lewat @method('PUT') di dalam form
                            body: new FormData(updateForm),
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        });
                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');

                        if (typeof showFlashFromDoc === 'function') showFlashFromDoc(doc);

                        // updateBooking() balikin lewat back()/back()->withInput(),
                        // bukan JSON -- gagal (validasi ATAU BookingCreationException-
                        // style error) sama-sama nongol lewat #flash-error atau
                        // kotak .bg-red-50. PENTING: .bg-red-50 di-scope ke DALAM
                        // freshForm (form hasil fetch ini doang), BUKAN ke seluruh
                        // halaman -- dashboard selalu punya elemen lain yang
                        // warnanya kebetulan sama (badge "Dibatalkan", tombol
                        // "Batalkan"), jadi kalau dicek se-halaman, hasErrors
                        // selalu true walau simpan-nya SUKSES.
                        const freshForm = doc.getElementById('booking-update-form');
                        const flashErrorEl = doc.getElementById('flash-error');
                        const hasFlashError = !!(flashErrorEl && flashErrorEl.textContent.trim());
                        const hasValidationErrors = !!(freshForm && freshForm.querySelector('.bg-red-50'));
                        const hasErrors = hasFlashError || hasValidationErrors;
                        if (hasErrors) {
                            // Modal TETAP kebuka dengan isian apa adanya (gak
                            // direset) -- operator tinggal benerin lalu submit
                            // ulang, gak perlu ngetik ulang dari nol. Pesan
                            // errornya ditampilin lewat toast biar operator
                            // tau KENAPA gagal, bukan cuma diam aja.
                            if (!hasFlashError && hasValidationErrors && typeof window.showToast === 'function') {
                                const msg = freshForm.querySelector('.bg-red-50').textContent.trim();
                                window.showToast(msg, 'error');
                            }
                            if (submitBtn) submitBtn.disabled = false;
                        } else {
                            if (typeof applyDashboardPatch === 'function') applyDashboardPatch(doc);
                            closeEditBookingModal(true);
                        }
                    } catch (err) {
                        console.warn('Simpan perubahan reservasi gagal dikirim via fetch, jatuh ke submit biasa:', err);
                        updateForm.submit();
                        return;
                    } finally {
                        delete updateForm.dataset.submitting;
                    }
                });
            })();

            // Kirim "Tambah Reservasi" lewat fetch() juga (bukan submit form
            // biasa) -- sebelumnya form ini satu-satunya aksi di dashboard
            // yang masih pindah halaman penuh (submit -> redirect dari
            // server). Akibatnya: hash tab di URL ikut ke-reset ke kosong
            // pas redirect (server sama sekali gak tau/gak bisa nyimpen
            // hash), jadi begitu reservasi berhasil ditambah, tampilan
            // "loncat" balik ke tab "Antrian Loket" walau operator lagi ada
            // di tab "Reservasi Hari Ini". Sekarang jalan lewat fetch supaya
            // gak ada reload sama sekali -- tab yang lagi aktif otomatis
            // tetap seperti semula.
            (function () {
                const bookingForm = document.getElementById('booking-store-form');
                if (!bookingForm) return;

                bookingForm.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    if (bookingForm.dataset.submitting === '1') return;
                    bookingForm.dataset.submitting = '1';
                    const submitBtn = bookingForm.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    let serverResponded = false;
                    try {
                        const response = await fetch(bookingForm.action, {
                            method: 'POST',
                            body: new FormData(bookingForm),
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        });
                        const html = await response.text();
                        serverResponded = true;
                        const doc = new DOMParser().parseFromString(html, 'text/html');

                        if (typeof showFlashFromDoc === 'function') showFlashFromDoc(doc);
                        if (typeof applyDashboardPatch === 'function') applyDashboardPatch(doc);

                        const freshForm = doc.getElementById('booking-store-form');
                        // Gagal bisa dari 2 sumber: (1) validasi Laravel biasa
                        // (muncul sebagai .bg-red-50 DI DALAM form ini), ATAU
                        // (2) BookingCreationException dari BookingService --
                        // "Jam yang dipilih sudah lewat", "jam ini sudah
                        // dipakai reservasi lain", "baru saja penuh dibooking
                        // orang lain", dst. Kasus (2) itu session-flash biasa
                        // (session('error')) yang dirender di #flash-error di
                        // LUAR form ini, jadi sebelumnya nggak kedeteksi sama
                        // sekali oleh pengecekan .bg-red-50 -- akibatnya modal
                        // ditutup & draft dihapus SEOLAH-OLAH berhasil,
                        // padahal reservasinya nggak beneran kesimpen.
                        const flashErrorEl = doc.getElementById('flash-error');
                        const hasFlashError = !!(flashErrorEl && flashErrorEl.textContent.trim());
                        const hasValidationErrors = !!(freshForm && freshForm.querySelector('.bg-red-50'));
                        const hasErrors = hasValidationErrors || hasFlashError;
                        if (hasErrors) {
                            // Gagal (validasi ATAU BookingCreationException):
                            // ganti isi form dengan versi baru (baik dua-duanya
                            // sama-sama bawa isian yang tadi diketik operator
                            // lewat withInput()/old()), modal tetap kebuka,
                            // draft TETAP kesimpen -- belum beneran berhasil.
                            if (freshForm) bookingForm.innerHTML = freshForm.innerHTML;
                            papyrusRefreshExtrasSummary(bookingForm);
                            openBookingModal();
                        } else {
                            // Berhasil: closeBookingModal(true) sekarang
                            // otomatis ngosongin form & buang draft-nya juga
                            // (lihat definisinya di atas), jadi cukup panggil
                            // ini aja. Sengaja pakai `true` (bukan soft-close)
                            // di sini -- reservasi SUDAH tersimpan di server,
                            // jadi draft lokalnya memang sudah gak relevan
                            // lagi & wajib dikosongin.
                            closeBookingModal(true);
                        }
                    } catch (err) {
                        // Kalau server SUDAH menjawab, reservasinya sudah diproses (tersimpan atau
                        // ditolak) dan yang error cuma pengolahan tampilan -- JANGAN kirim ulang
                        // (bisa bikin reservasi dobel), cukup muat ulang halaman.
                        if (serverResponded) {
                            console.warn('Tambah reservasi: server sudah menjawab, muat ulang halaman:', err);
                            location.reload();
                            return;
                        }
                        console.warn('Tambah reservasi gagal dikirim via fetch, jatuh ke submit biasa:', err);
                        bookingForm.submit();
                        return;
                    } finally {
                        delete bookingForm.dataset.submitting;
                        const btn = bookingForm.querySelector('button[type="submit"]');
                        if (btn) btn.disabled = false;
                    }
                });
            })();

            // Kalau habis submit ternyata ada validation error dari form
            // reservasi ini, buka lagi modalnya otomatis (biar pesan
            // errornya kelihatan, bukan ilang gitu aja di halaman dashboard).
            @if(($errors->any() || session('error')) && old('customer_name'))
                document.addEventListener('DOMContentLoaded', openBookingModal);
            @endif

            // Draft otomatis buat form "Tambah Reservasi" -- isian yang
            // BELUM diklik "Simpan Reservasi" disimpen ke localStorage
            // setiap kali operator ngetik/ganti apapun, jadi kalau kepencet
            // refresh (sengaja atau nggak) sebelum sempet nyimpen, data yang
            // tadi diisi gak ilang. Begitu berhasil "Simpan Reservasi",
            // draft-nya otomatis dibuang (lihat cabang sukses di atas).
            (function () {
                const DRAFT_KEY = PAPYRUS_BOOKING_DRAFT_KEY;
                const OPEN_KEY = PAPYRUS_BOOKING_OPEN_KEY;
                const form = document.getElementById('booking-store-form');
                if (!form) return;

                function fieldVal(name) {
                    return form.querySelector(`[name="${name}"]`)?.value || '';
                }

                function collectDraft() {
                    return {
                        customer_name: fieldVal('customer_name'),
                        customer_phone: fieldVal('customer_phone'),
                        customer_email: fieldVal('customer_email'),
                        people_count: fieldVal('people_count'),
                        package_name: fieldVal('package_name'),
                        // string (bukan array) supaya draft kosong tetap terbaca "kosong"
                        extras: [...form.querySelectorAll('input[name="extras[]"]:checked')].map(c => c.value).join('|'),
                        notes: fieldVal('notes'),
                    };
                }

                function saveDraft() {
                    try { localStorage.setItem(DRAFT_KEY, JSON.stringify(collectDraft())); } catch (e) {}
                }

                window.papyrusClearBookingDraft = function () {
                    try {
                        localStorage.removeItem(DRAFT_KEY);
                        localStorage.removeItem(OPEN_KEY);
                    } catch (e) {}
                };

                function setVal(name, value) {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el && value) el.value = value;
                }

                function restoreDraft() {
                    let draft = null;
                    try {
                        const raw = localStorage.getItem(DRAFT_KEY);
                        if (raw) draft = JSON.parse(raw);
                    } catch (e) {}
                    // Draft kosong (semua isian blank) bukan draft -- jangan dipulihkan.
                    if (!draft || !Object.values(draft).some((v) => v)) return false;

                    setVal('customer_name', draft.customer_name);
                    setVal('customer_phone', draft.customer_phone);
                    setVal('customer_email', draft.customer_email);
                    setVal('people_count', draft.people_count);
                    setVal('package_name', draft.package_name);
                    setVal('notes', draft.notes);
                    papyrusSetExtras(form, draft.extras ? String(draft.extras).split('|') : []);
                    return true;
                }

                // Delegasi lewat form (bukan pasang listener per-input) --
                // tetep jalan walau isi form ini pernah diganti innerHTML-nya
                // (lihat cabang error di atas), soalnya event input/change
                // dari anak manapun tetap nyampe (bubbling) ke form-nya.
                form.addEventListener('input', saveDraft);
                form.addEventListener('change', saveDraft);

                const restored = restoreDraft();
                let openFlag = null;
                try { openFlag = localStorage.getItem(OPEN_KEY); } catch (e) {}
                if (papyrusShouldReopenBookingModal(restored, openFlag, papyrusNavigationType())) {
                    document.addEventListener('DOMContentLoaded', openBookingModal);
                } else {
                    // Bukan refresh (mis. baru masuk) -> penanda lama dibuang supaya tidak
                    // menggantung dan membuka form di kunjungan berikutnya.
                    try { localStorage.removeItem(OPEN_KEY); } catch (e) {}
                }
            })();

        </script>
    @endif

    <!-- Modal Edit Tiket: SENGAJA di luar blok @@if($canManageBookings)
         di atas -- tombol edit (pensil) tiket muncul buat SEMUA role yang
         nangani tahap tiket itu (CS, Fotografer, dst), bukan cuma yang bisa
         kelola reservasi (Operator/Admin). Sebelumnya modal & JS-nya ikut
         ke-skip pas $canManageBookings false, jadi tombolnya ada tapi
         function-nya nggak ke-render -> "openEditTicketModal is not defined".
         Sekarang modal ini SELALU dirender, otorisasi tetap dicek di server
         (lihat OperatorController::editTicket/updateTicket). -->
        <!-- Modal Edit Tiket: pola sama persis dengan Edit Reservasi di atas
             (mengambang, fetch async, gak reload halaman) -- dulu ini
             halaman terpisah (operator.tickets.edit), sekarang jadi modal
             biar operator gak usah pindah halaman cuma buat benerin
             nama/telepon/catatan tiket. Field "Layanan" (service_category_id)
             SENGAJA DIHAPUS dari sini (sesuai permintaan), gak lagi
             ditampilkan/diproses sama sekali. -->
        <div id="edit-ticket-modal-backdrop" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" onclick="if(event.target === this) closeEditTicketModal()">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Edit Tiket <span id="edit-ticket-number" class="text-slate-400 font-normal"></span></h2>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Kode Order <span id="edit-ticket-order-code" class="font-mono font-semibold text-slate-700 dark:text-slate-200"></span> &middot; Ubah detail tiket di bawah ini.</p>
                    </div>
                    <button type="button" onclick="closeEditTicketModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form id="ticket-update-form" method="POST" action="" data-ticket-id="" class="p-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan</label>
                        <input type="text" name="customer_name" required
                            class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                            <input type="tel" name="customer_phone" required
                                inputmode="numeric" maxlength="16" placeholder="081234567890"
                                pattern="\+?[0-9]{8,15}" title="Hanya angka (boleh diawali +), 8-15 digit"
                                oninput="this.value = this.value.replace(/(?!^\+)[^0-9]/g, '')"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input type="email" name="customer_email"
                                inputmode="email" placeholder="nama@email.com" maxlength="255"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Order</label>
                        {{-- Ditampilin cuma kalau tiketnya lagi kebuka dalam
                             keadaan "notes_locked" -- lihat toggle di
                             openEditTicketModal() di bawah. Disembunyikan
                             (bukan dihapus) dari awal, biar gak butuh
                             request tambahan buat nampilin/nyembunyiin. --}}
                        <div id="edit-ticket-notes-lock-notice" class="hidden mb-2 flex items-center justify-between gap-3 rounded-lg border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-900/20 px-3 py-2">
                            <p class="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                {{-- Teksnya diganti JS sesuai notes_unlockable
                                     -- lihat openEditTicketModal(). Kalau
                                     tiketnya belum diklaim/dipanggil operator
                                     yang login, tombol "Buka Kunci" gak
                                     ditampilin sama sekali (soalnya PASTI
                                     ditolak server -- lihat
                                     OperatorController::unlockNotes), jadi
                                     keterangannya diganti biar jelas kenapa. --}}
                                <span id="edit-ticket-notes-lock-text">Catatan ini terkunci</span>
                            </p>
                            <button type="button" id="edit-ticket-unlock-notes-btn" onclick="unlockEditTicketNotes()" class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-lavender hover:underline whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                Buka Kunci
                            </button>
                        </div>
                        <textarea id="edit-ticket-notes-field" name="notes" rows="3" class="autogrow-textarea w-full px-4 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none overflow-hidden disabled:bg-slate-100 disabled:dark:bg-slate-800 disabled:text-slate-500 disabled:dark:text-slate-400"></textarea>
                    </div>
                    {{-- Box ini SENGAJA cuma dirender kalau beneran ada error
                         ($errors->any()), bukan div kosong yang di-hidden --
                         soalnya JS di bawah nyari elemen .bg-red-50 KHUSUS DI
                         DALAM form ini buat mastiin submit ini gagal/berhasil.
                         Kalau div-nya selalu ada (walau hidden), pengecekan
                         itu bakal SELALU nganggep ada error. --}}
                    @if($errors->any())
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 text-sm rounded-lg p-3">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">Simpan Perubahan</button>
                        <button type="button" onclick="closeEditTicketModal(true)" class="bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-6 py-2.5 rounded-lg font-semibold transition-colors">Batal</button>
                    </div>
                </form>
                {{-- Form kosong khusus buat tombol "Buka Kunci" di atas --
                     cuma nampung token CSRF, action-nya diisi JS pas
                     diklik (id tiketnya baru ketahuan saat itu). SENGAJA di
                     LUAR </form> ticket-update-form persis di atas --
                     sebelumnya form ini ke-taruh NESTED di dalam form itu,
                     dan <form> di dalam <form> itu HTML tidak valid: browser
                     diam-diam "membuang" tag <form> yang bersarang itu pas
                     parsing, jadi document.getElementById(
                     'ticket-unlock-notes-form') balik null dan tombolnya
                     gagal terus (ketangkep di catch, makanya munculnya toast
                     generik "Gagal buka kunci, coba lagi." bukan error asli
                     dari server). --}}
                <form id="ticket-unlock-notes-form" method="POST" action="" class="hidden">
                    @csrf
                </form>
            </div>
        </div>

    <script>
            // ============ Modal Edit Tiket ============
            /**
             * @param {object} ticket - {id, ticket_number, customer_name, customer_phone, customer_email, notes, notes_locked, notes_unlockable}
             *
             * Field CUMA diisi ulang dari data server kalau ini tiket BEDA
             * dari yang lagi kebuka di modal (dicek lewat data-ticket-id) --
             * sama pola kayak openEditBookingModal(), biar gak sengaja
             * ke-close (X/klik luar/Esc) lalu "Edit" diklik lagi buat TIKET
             * YANG SAMA gak ketimpa data lama dari server.
             */
            function openEditTicketModal(ticket) {
                const backdrop = document.getElementById('edit-ticket-modal-backdrop');
                const form = document.getElementById('ticket-update-form');

                if (form.dataset.ticketId !== String(ticket.id)) {
                    form.action = '/operator/tickets/' + ticket.id;
                    form.dataset.ticketId = String(ticket.id);
                    document.getElementById('edit-ticket-number').textContent = ticket.ticket_number || '';
                    document.getElementById('edit-ticket-order-code').textContent = ticket.order_code || '-';
                    form.querySelector('[name="customer_name"]').value = ticket.customer_name || '';
                    form.querySelector('[name="customer_phone"]').value = ticket.customer_phone || '';
                    form.querySelector('[name="customer_email"]').value = ticket.customer_email || '';
                    // Textarea "Order" pakai class autogrow-textarea (lihat
                    // components/layout.blade.php) yang cuma resize otomatis
                    // pas ada event 'input' (ketikan user) -- diisi manual
                    // kayak gini gak memicu event itu, jadi catatan
                    // multi-baris bisa kepotong (ke-hidden overflow) sampai
                    // user ngeklik/ngetik dulu. Trigger event-nya manual.
                    const notesEl = form.querySelector('[name="notes"]');
                    notesEl.value = ticket.notes || '';
                    notesEl.dispatchEvent(new Event('input'));
                }

                // Dicek TIAP kali modal dibuka (di luar guard di atas), beda
                // dari field lain di atas -- gemboknya bisa berubah kapan
                // aja (mis. fotografer klik "Buka Kunci buat Edit" di kartu
                // tiket) walau operator lagi ngintip tiket yang sama lagi.
                // Server tetap jadi penjaga terakhir (lihat updateTicket()),
                // ini cuma buat operator gak bingung napa perubahan Order-nya
                // gak nempel walau form-nya kelihatan bisa diklik.
                const notesField = document.getElementById('edit-ticket-notes-field');
                const notesLockNotice = document.getElementById('edit-ticket-notes-lock-notice');
                const notesLockText = document.getElementById('edit-ticket-notes-lock-text');
                const unlockBtn = document.getElementById('edit-ticket-unlock-notes-btn');
                notesField.disabled = !!ticket.notes_locked;
                notesLockNotice.classList.toggle('hidden', !ticket.notes_locked);
                if (ticket.notes_locked) {
                    // Tombol "Buka Kunci" cuma ditampilin kalau tiket ini
                    // beneran lagi "dipegang" (served_by) operator yang
                    // login -- itu aturan yang sama dipakai server (lihat
                    // OperatorController::unlockNotes). Kalau ditampilin
                    // terus buat tiket yang belum diklaim SIAPA PUN, tombol
                    // itu PASTI ketolak server ("Tiket ini bukan milik
                    // Anda"), jadi malah bikin bingung -- lebih baik gak usah
                    // ditampilin, keterangannya diganti biar jelas kenapa.
                    unlockBtn.classList.toggle('hidden', !ticket.notes_unlockable);
                    notesLockText.textContent = ticket.notes_unlockable
                        ? 'Catatan ini terkunci'
                        : 'Catatan ini terkunci — panggil/klaim tiket ini dulu buat bisa buka kuncinya';
                }

                backdrop.classList.remove('hidden');
                backdrop.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            /**
             * @param {boolean} isCancel - true kalau eksplisit klik "Batal"
             * (atau berhasil disimpan) -- data-ticket-id dikosongin, jadi
             * lain kali "Edit" diklik (biarpun buat tiket yang sama),
             * field-nya dipaksa diisi ulang fresh dari server. false
             * (default, soft close) mempertahankan isian form apa adanya.
             */
            function closeEditTicketModal(isCancel = false) {
                const backdrop = document.getElementById('edit-ticket-modal-backdrop');
                backdrop.classList.add('hidden');
                backdrop.classList.remove('flex');
                document.body.style.overflow = '';
                if (isCancel) {
                    document.getElementById('ticket-update-form').dataset.ticketId = '';
                }
            }

            /**
             * Tombol "Buka Kunci buat Edit" DI DALAM modal -- sama persis
             * jalurnya (operator.unlock-notes) dengan tombol yang udah ada
             * di kartu tiket, cuma dipanggil dari sini biar operator gak
             * perlu nutup modal, cari kartu tiketnya, klik di sana, terus
             * buka modal edit lagi dari awal. Modal TETAP kebuka abis ini,
             * cuma textarea Order-nya jadi bisa diketik.
             */
            async function unlockEditTicketNotes() {
                const form = document.getElementById('ticket-update-form');
                const ticketId = form.dataset.ticketId;
                if (!ticketId) return;

                const btn = document.getElementById('edit-ticket-unlock-notes-btn');
                if (btn) btn.disabled = true;

                try {
                    const unlockForm = document.getElementById('ticket-unlock-notes-form');
                    unlockForm.action = '/operator/tickets/' + ticketId + '/notes/unlock';

                    const response = await fetch(unlockForm.action, {
                        method: 'POST',
                        body: new FormData(unlockForm),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    });
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    if (typeof showFlashFromDoc === 'function') showFlashFromDoc(doc);

                    const flashErrorEl = doc.getElementById('flash-error');
                    const hasFlashError = !!(flashErrorEl && flashErrorEl.textContent.trim());
                    if (hasFlashError) {
                        // Contoh kasusnya kena ini: tiket lagi dipegang
                        // operator lain (served_by beda), unlockNotes() di
                        // server nolak dengan pesan "Tiket ini bukan milik
                        // Anda." -- toast-nya udah otomatis muncul lewat
                        // showFlashFromDoc di atas, tinggal re-enable tombol.
                        if (btn) btn.disabled = false;
                        return;
                    }

                    // Sukses -- buka textarea-nya, sembunyiin notice, dan
                    // sinkronin daftar tiket di belakang modal (biar kartu
                    // tiketnya juga gak lagi kelihatan terkunci kalau modal
                    // ini ditutup tanpa nge-save apa pun).
                    document.getElementById('edit-ticket-notes-field').disabled = false;
                    document.getElementById('edit-ticket-notes-lock-notice').classList.add('hidden');
                    if (typeof applyDashboardPatch === 'function') applyDashboardPatch(doc);
                } catch (err) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Gagal buka kunci, coba lagi.', 'error');
                    }
                    if (btn) btn.disabled = false;
                }
            }

            (function () {
                const updateForm = document.getElementById('ticket-update-form');
                if (!updateForm) return;

                updateForm.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    if (updateForm.dataset.submitting === '1') return;
                    updateForm.dataset.submitting = '1';
                    const submitBtn = updateForm.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    try {
                        const response = await fetch(updateForm.action, {
                            method: 'POST', // Laravel method-spoofing lewat @method('PUT') di dalam form
                            body: new FormData(updateForm),
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        });
                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');

                        if (typeof showFlashFromDoc === 'function') showFlashFromDoc(doc);

                        // PENTING: pengecekan .bg-red-50 di bawah ini SENGAJA
                        // di-scope ke DALAM form hasil fetch ini (freshForm),
                        // BUKAN ke seluruh halaman (doc.querySelector) --
                        // dashboard selalu punya elemen lain yang warnanya
                        // kebetulan sama (badge status "Dibatalkan", tombol
                        // "Batalkan" tiket, dst), jadi kalau dicek se-halaman,
                        // hasErrors selalu true walau simpan-nya SUKSES, dan
                        // modal jadi kelihatan "nyangkut" gak pernah nutup
                        // padahal datanya udah beneran kesimpen.
                        const freshForm = doc.getElementById('ticket-update-form');
                        const flashErrorEl = doc.getElementById('flash-error');
                        const hasFlashError = !!(flashErrorEl && flashErrorEl.textContent.trim());
                        const hasValidationErrors = !!(freshForm && freshForm.querySelector('.bg-red-50'));
                        const hasErrors = hasFlashError || hasValidationErrors;
                        if (hasErrors) {
                            // Modal TETAP kebuka dengan isian apa adanya (gak
                            // direset) -- operator tinggal benerin lalu submit
                            // ulang, gak perlu ngetik ulang dari nol. Pesan
                            // errornya ditampilin lewat toast biar operator
                            // tau KENAPA gagal, bukan cuma diam aja.
                            if (!hasFlashError && hasValidationErrors && typeof window.showToast === 'function') {
                                const msg = freshForm.querySelector('.bg-red-50').textContent.trim();
                                window.showToast(msg, 'error');
                            }
                            if (submitBtn) submitBtn.disabled = false;
                        } else {
                            if (typeof applyDashboardPatch === 'function') applyDashboardPatch(doc);
                            closeEditTicketModal(true);
                        }
                    } catch (err) {
                        console.warn('Simpan perubahan tiket gagal dikirim via fetch, jatuh ke submit biasa:', err);
                        updateForm.submit();
                        return;
                    } finally {
                        delete updateForm.dataset.submitting;
                    }
                });
            })();
    </script>
</x-layout>