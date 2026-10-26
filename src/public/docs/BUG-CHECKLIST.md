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
