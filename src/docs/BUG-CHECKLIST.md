# Bug Checklist — Alur Antrian Studio

Gabungan dari review manual + masukan AI lain. Status per 24 Juli 2026.

| # | Bug / risiko | Status | Keterangan |
|---|---|:---:|---|
| 1 | Order dibuat sebelum pesanan fix (nomor antrian dianggap order final) | ✅ Sudah fix | `confirmed_at` + guard di `completeRegistration()` + tombol "✅ Konfirmasi Pesanan" di dashboard operator. |
| 2 | Dua kasir/CS buka & edit tiket yang sama bersamaan | 🟡 Perlu diverifikasi | Secara struktur harusnya aman (tiket cuma "dimiliki" 1 counter setelah `call()`), tapi belum ada test/guard eksplisit di level UI kalau ada 2 tab browser terbuka ke tiket yang sama. |
| 3 | Dua fotografer/loket klik "Panggil Selanjutnya" bersamaan → dapat tiket sama | ✅ Sudah fix | `OperatorController::callNext` & `callSpecific` pakai `DB::transaction()` + `lockForUpdate()`. |
| 4 | Fotografer sudah panggil/mulai tapi ternyata gak jadi motret (salah panggil, dsb) | ✅ Sudah fix | `Ticket::uncall()` + tombol "Salah Panggil? Batalkan Pemanggilan" — TANPA nambah `skip_count`. |
| 5 | Kasir/CS ubah item pesanan (`notes`) setelah sesi foto sudah mulai | ✅ Sudah fix | `notes_locked_at` diisi otomatis di `startServing()`. Selama terisi, cuma **Admin** yang bisa edit `notes` (guard di `updateNotes()` + view jadi read-only). |
| 6 | Pelanggan tutup halaman status, harus tetap bisa buka lagi pakai QR/link | ✅ Sudah aman | `tickets.show` gak butuh session, bisa dibuka ulang kapan saja lewat URL/QR. |
| 7 | GPS tidak akurat / izin lokasi ditolak / browser gak support / bukan HTTPS | ✅ Sudah fix | `tickets/create.blade.php`: pesan berbeda untuk tiap kasus + tombol "coba lagi". Server juga validasi ulang jaraknya (bukan cuma percaya client). |
| 8 | Reservasi bisa dibuat/dibatalkan/di-check-in sendiri oleh pelanggan (harusnya full-admin) | ✅ Sudah fix | `BookingController::store/checkIn/cancel` sekarang staff-only; pelanggan cuma bisa `bookings.check` (lihat status). |
| 9 | Model `OrderItem` jadi kode mati (nunjuk tabel yang sudah di-drop) | 🟡 Belum dibersihkan | Tidak berbahaya (gak dipanggil di mana pun), tapi sebaiknya dihapus biar gak membingungkan developer berikutnya. |
| 10 | Kolom order/notes cuma bisa 1 baris, gak bisa multi-item | ✅ Sudah fix | *(Lalu dihapus lagi dari form publik — order sekarang 100% diisi CS saat konsultasi, lihat §Tahap 1 & 2 di `ALUR-ANTRIAN-STUDIO.md`.)* |
| 11 | Fotografer harus nunggu pelanggan #1 total selesai baru bisa manggil #2 → ada jeda kosong pas ganti baju/bersiap | ✅ Sudah fix | Status baru `preparing` ("Siap-siap") + `Ticket::callToPrepare()` — fotografer bisa manggil #2 lebih awal selagi #1 masih `serving`. Auto-promote `preparing → calling` begitu #1 selesai (`advanceStage()`). Lihat `ALUR-ANTRIAN-STUDIO.md` §5. |
| 12 | Tiket `preparing` jadi "tidak kelihatan" di dashboard kalau tiket `serving` di loket yang sama di-*uncall* | 🔴 Belum | Edge case jarang terjadi — panel "Siap-siap" di dashboard cuma muncul kalau ada `currentTicket` berstatus `serving`. Perlu ditambah tampilan panel itu juga di kondisi "loket kosong" kalau ternyata sering kejadian di lapangan. |
| 13 | Fitur "Sedang Diedit" (satu tiket) & "Siap Diedit / batch edit" (numpuk beberapa tiket) — ditemukan setengah jadi & rusak (syntax error fatal, migration duplikat yang bakal crash `migrate`, route nunjuk ke method yang gak ada, form pakai method HTTP salah, progress bar & status box pelanggan bolong buat status baru) | ✅ Sudah fix (audit 27 Jul 2026) | Lihat detail lengkap di bagian bawah dokumen ini ("Audit 27 Juli 2026"). |

## Legenda
- ✅ Sudah fix / sudah aman
- 🟢 Spec final sudah disepakati, tinggal dikerjakan (lihat `ALUR-ANTRIAN-STUDIO.md` §7 untuk urutan)
- 🟡 Perlu diverifikasi atau dibersihkan, tapi bukan bug aktif
- 🔴 Belum dikerjakan, butuh keputusan/implementasi

## Cara pakai dokumen ini
Setiap kali ada perubahan terkait salah satu poin di atas, update kolom
Status + Keterangan-nya di sini juga, supaya siapa pun (manusia atau AI)
yang buka project ini bisa langsung tahu kondisi terkininya tanpa harus
baca ulang seluruh riwayat chat.

## Audit 27 Juli 2026 — fitur "Sedang Diedit" & "Siap Diedit" (batch edit)

Ditemukan implementasi fitur baru yang belum pernah didiskusikan sebelumnya
di histori chat ini — kemungkinan hasil kerja sesi/proses lain yang
kepotong di tengah jalan. Setelah ditelusuri, fiturnya sendiri **bagus dan
berguna** (persis kebutuhan "numpuk edit foto"), jadi diselesaikan alih-alih
dihapus. Rincian bug yang ditemukan & diperbaiki:

1. **Fatal syntax error** di `Ticket.php` — `match()` punya key `'serving'`
   dobel + 2 arm `default`. App gak akan jalan sama sekali sebelum ini
   diperbaiki.
2. **Fatal syntax error** di `OperatorController.php` — method
   `uncallTicket()` gak ketutup kurung kurawal, nyambung ke method lain.
3. **Migration duplikat** (`...add_last_skipped_at_to_tickets_table.php`)
   nambah kolom yang udah ada dari migration lain — kalau dijalankan,
   `php artisan migrate` bakal crash total dengan SQL error. **Dihapus.**
4. **Bug logika** di `advanceStage()` — baris `'ready_for_edit_at' => now()`
   nyelip di update tiket yang gak berhubungan (promosi tiket "Siap-siap").
5. **Migration yang kurang** — status `ready_for_edit` dipakai di banyak
   tempat tapi gak pernah dimigrasikan ke kolom `status` (ENUM). Migration
   baru dibuat: `2026_07_27_000003_add_ready_for_edit_status_to_tickets_table.php`.
6. **Route nunjuk ke method yang gak ada** — `operator.start-editing` ada
   di routes tapi `OperatorController::startEditing()` gak pernah dibuat.
   Klik tombolnya = error 500. Method ditambahkan.
7. **Tombol yang hilang** — jalur "antri dulu, edit belakangan"
   (`finishPhotoSession`) udah lengkap di backend tapi gak ada tombolnya
   di dashboard. Ditambahkan (jadi 2 pilihan: "Edit Sekarang" vs "Antri
   Dulu").
8. **Form batch-edit pakai `@method('PUT')`** padahal route-nya `POST` —
   submit bakal gagal. Dihapus.
9. **Event realtime gak lengkap** — `finishPhotoSession()` cuma broadcast
   `QueueUpdated` (buat dashboard operator), lupa `TicketStatusChanged`
   (buat halaman status pelanggan itu sendiri) — pelanggan gak lihat update
   real-time. Ditambahkan.
10. **Progress bar pelanggan salah mundur** — status `ready_for_edit` gak
    ke-mapping, jadi progress bar-nya balik ke node "Siap-siap" padahal
    fotonya udah kelar diambil. Diperbaiki + ditambah visual khusus (🗂️).
11. **Kotak status pelanggan kosong melompong** — gak ada branch buat
    status `ready_for_edit` di halaman status tiket, jadi box-nya render
    kosong. Ditambahkan.

**Cara verifikasi:** semua file PHP sudah di-lint (`php -l`) bersih, semua
route yang dipanggil di view sudah terdaftar, semua route yang terdaftar
nunjuk ke method yang beneran ada, dan semua tag Blade (`@if`/`@endif`,
dst.) sudah seimbang.

## Audit susulan (hari sama) — dari laporan screenshot user

1. **Bug paling krusial**: panel aksi fotografer di stage `photo_session`
   gak punya branch buat status `calling` — jadi tombolnya langsung
   "Selesaikan, Lanjut ke Pembayaran" tanpa lewat "Mulai Foto" sama sekali.
   Efeknya: alur foto/edit ke-skip total, sama kayak sebelum fitur ini
   ada. **Diperbaiki** — tambah branch `@elseif(stage===photo_session &&
   status===calling)` dengan tombol "📸 Mulai Foto".
2. **Script batch-edit checkbox render sebagai teks halaman** — tag
   `</script>` ketutup kepagian (sebelum kode JS-nya kelar), jadi sisa
   kodenya nongol sebagai teks biasa di footer. **Diperbaiki** — script
   digabung jadi 1 blok utuh.
3. **Fitur otomatis "Siap-siap" (hybrid) ke-revert diam-diam** — logika
   `autoCallNextToPrepare()` di `startServing()` sempat hilang, diganti
   comment "auto-call dinonaktifkan", padahal ini keputusan yang sudah
   disepakati sebelumnya. **Dikembalikan.**
4. Dokumentasi/comment berantakan — ada 2 docblock nempel jadi 1
   (punya `finishPhotoSession()` nyasar ke atas `startEditing()`).
   **Dirapikan**, masing-masing balik ke method-nya.

**Pelajaran:** audit sebelumnya cuma cek syntax/route/broadcast, belum cek
kelengkapan LOGIKA tiap kombinasi stage+status. Sekarang sudah dipetakan
manual: kombinasi (stage, status) yang mungkin muncul di panel aksi cuma
`registration+calling`, `registration+serving`, `photo_session+calling`,
`photo_session+serving`, `photo_session+editing`, `payment+calling`,
`payment+serving` — semua sudah dicek ada tombolnya masing-masing.
