# Alur Antrian — Papyrus Photo Jogja

Dokumen ini menjelaskan alur bisnis lengkap sistem antrian, biar developer
atau AI mana pun yang lanjutin coding-nya gak perlu nebak-nebak lagi.

Sumber diskusi: masukan dari Bu Angel (alur awal) + masukan kasir (proses
konsultasi sering berubah-ubah sebelum fix) + review AI lain (potensi bug).

**Status: FINAL — siap diimplementasikan.** Semua keputusan desain di
dokumen ini sudah disepakati (23 Juli 2026). Lihat bagian 7 untuk daftar
keputusan final beserta alasannya.

---

## 1. Prinsip utama

1. **Nomor antrian ≠ order final.** Begitu pelanggan ambil nomor, itu cuma
   giliran ngobrol sama CS — bukan pesanan yang sudah fix.
2. **Gak ada order "sampah".** Kalau pelanggan batal pas konsultasi, gak ada
   jejak order yang harus dibersihin — cukup `cancel()` tiketnya.
3. **1 tiket, 1 QR, dari awal sampai selesai.** Tidak ada nomor/QR kedua yang
   muncul di tengah jalan — lihat bagian 5 kenapa ini dipilih dibanding bikin
   entity `Order` terpisah.
4. **Semua orang (operator & pelanggan) bisa lihat progress real-time**, tapi
   pelanggan cuma lihat status yang sudah "dihaluskan" jadi bahasa awam,
   bukan field-field internal (`stage`, `status`, dsb).
5. **Pengambilan tiket wajib di lokasi (GPS)**, tapi memantau status setelah
   itu bebas dari mana saja. (Sudah diimplementasikan — lihat `Location`
   model: `hasGpsRestriction()`, `isWithinGpsRadius()`.)
6. **Kesalahan staff tidak boleh menghukum pelanggan.** Kalau fotografer
   salah panggil/gak jadi motret, itu di-*undo* tanpa nambah `skip_count` —
   `skip_count` cuma buat pelanggan yang beneran gak ada di tempat.
7. **Sekali sesi foto mulai, pesanan terkunci.** Cuma Admin yang bisa buka
   kunci itu kalau darurat — bukan sembarang CS/Kasir, biar kuncinya
   beneran berarti.

---

## 2. Entity & pemetaan field

Semua tahap di bawah masih 1 baris di tabel `tickets`. Kombinasi
`stage` + `status` (+ field tambahan) menentukan tahap sebenarnya.

| Field                     | Ada sekarang? | Keterangan |
|---------------------------|:---:|---|
| `stage`                   | ✅ | `registration` \| `photo_session` \| `payment` |
| `status`                  | ✅ | `waiting` \| `calling` \| `serving` \| `completed` \| `cancelled` |
| `notes`                   | ✅ | Catatan pesanan (multi-baris). Diisi awal oleh pelanggan, **ditimpa/dilengkapi CS** saat konsultasi. |
| `counter_id`, `served_by` | ✅ | Loket & staff yang lagi pegang tiket ini |
| `skip_count`, `last_skipped_at` | ✅ | Untuk kasus "dipanggil tapi pelanggan gak ada di tempat" |
| `order_code` (accessor)   | ✅ | `ORD-000123`, permanen dari primary key. **Belum** disembunyikan sebelum konfirmasi — lihat bagian 5. |
| `confirmed_at`            | ❌ **belum ada — perlu migration** | Diisi saat CS klik "Konfirmasi Pesanan". Sebelum ini terisi, tiket TIDAK boleh lanjut ke `photo_session`. |
| `notes_locked_at`         | ❌ **belum ada — perlu migration** | Diisi otomatis saat `startServing()` di stage `photo_session` (sesi foto mulai). Selama terisi, cuma Admin yang boleh edit `notes`. |
| `photographer/counter yang pegang stage photo_session` | ✅ (lewat `counter_id`) | Sudah otomatis lewat mekanisme "panggil berikutnya" per counter (`Counter::stages`). |
| `gps_radius_meters` (di tabel `locations`) | ✅ ada, default lama 150 | **Diturunkan jadi default 50** — cukup ketat buat "harus di lokasi" tapi masih toleran ke akurasi GPS HP yang gak sempurna. Bisa disesuaikan lagi per lokasi di Admin → Lokasi. |

---

## 3. Alur tahap demi tahap

### Tahap 1 — Ambil nomor antrian (di lokasi, GPS)
- Pelanggan isi **Nama + No. Telepon** (Email opsional) — **tidak ada kolom
  order di form ini.** Sengaja dibikin sesingkat mungkin supaya gak bikin
  antrian baru di depan kiosk gara-gara pelanggan mikir mau pesan apa.
- **Sudah diimplementasikan.** GPS wajib aktif & dalam radius lokasi.
- Tombol: **"Ambil Nomor Antrian"** (bukan "Order Sekarang") — mengelola
  ekspektasi pelanggan bahwa ini baru giliran, belum pesanan.
- `stage = registration`, `status = waiting`, `notes` kosong.
- Status ke pelanggan: **"Menunggu Dipanggil"**.

### Tahap 2 — Dipanggil CS/Kasir (konsultasi)
- CS klik "Panggil Selanjutnya" → `status = calling` → pelanggan datang →
  CS klik mulai layani → `status = serving`.
- CS isi & ubah-ubah `notes` dari nol berkali-kali selama ngobrol (bukan
  "mengedit tulisan pelanggan" — dari awal memang kosong, jadi CS-lah satu-
  satunya sumber data pesanan, lebih rapi & gak perlu diluruskan lagi).
- **Belum ada order final di tahap ini** — ini murni diskusi.
- Status ke pelanggan: **"Sedang Konsultasi"**.

### Tahap 3 — Konfirmasi pesanan *(perlu diimplementasikan — spec final)*
- Setelah pelanggan setuju, CS klik **"✅ Konfirmasi Pesanan"**.
- Sistem set `confirmed_at = now()`. **Tidak** membuat baris/tabel baru,
  tidak generate QR baru — cukup field ini yang terisi.
- Begitu `confirmed_at` terisi, kode `ORD-xxxxxx` (sudah ada di sistem)
  baru ditampilkan ke pelanggan sebagai "kode order" resminya.
- Kalau pelanggan batal di tahap ini: CS klik **Batalkan** (fungsi
  `cancel()` sudah ada) — tidak pernah masuk hitungan order.
- Guard: tombol "Lanjut ke Sesi Foto" (`completeRegistration()`) harus
  **disabled** kalau `confirmed_at` masih kosong.
- Status ke pelanggan: tetap **"Sedang Konsultasi"** sampai konfirmasi,
  lalu **"Menunggu Sesi Foto"** begitu lanjut ke tahap 4.

### Tahap 4 — Menunggu sesi foto
- `stage = photo_session`, `status = waiting`.
- Fotografer/studio manapun yang "siap" (klik tombol di akun mereka) akan
  otomatis dapat tiket berikutnya lewat mekanisme counter (`Counter::stages`
  berisi `photo_session`) — **sudah ada**, sudah race-condition-safe.
- Status ke pelanggan: **"Menunggu Sesi Foto"**.

### Tahap 5 — Dipanggil fotografer (dengan pipelining "Siap-siap")

**Koreksi penting:** "Siap-siap" **bukan** soal fotografer klaim ruangan
(itu kesalahpahaman di draf awal dokumen ini). Yang dimaksud adalah:
selagi fotografer masih memotret pelanggan #1 (status `serving`), dia bisa
klik **"👋 Panggil Siap-siap Berikutnya"** buat pelanggan #2 — supaya #2
bisa langsung ganti baju/bersiap dan gak ada jeda kosong pas gilirannya
tiba. Ini penting justru karena ada 2+ fotografer/ruangan yang jalan
paralel dan gak pasti siapa selesai duluan.

Status baru: **`preparing`** ("Siap-siap") — beda dari `calling`/`serving`.

Aturannya:
- Cuma boleh dipanggil kalau tiket yang sekarang aktif di loket itu sudah
  **`serving`** (beneran mulai difoto), bukan baru `calling`.
- Cuma **1 tiket `preparing`** per loket dalam satu waktu — gak boleh
  manggil #3 sebelum #2 naik status jadi `calling`.
- Begitu #1 `advanceStage()` (selesai, lanjut ke pembayaran), sistem
  **otomatis** naikkan #2 dari `preparing` → `calling` di loket yang sama
  (`Ticket::advanceStage()`) — fotografer tinggal klik "Mulai Foto",
  **tidak perlu panggil ulang dari nol**.
- Kalau ternyata gak jadi/salah panggil, ada tombol "Batalkan Siap-siap"
  (pakai `uncall()` yang sama, tidak kena `skip_count`).

Status ke pelanggan saat `preparing`: **"Silakan Bersiap-siap di [Studio] —
Sebentar Lagi Giliran Anda"**.

- Fotografer klik "Panggil Selanjutnya" (`callNext`) buat tiket pertama →
  tiket otomatis ke-assign ke `counter_id` fotografer/studio itu (sudah
  race-condition-safe).
- Status ke pelanggan (status `calling`, belum `preparing`):
  **"Dipanggil ke [nama studio/fotografer]"**.

**Catatan keterbatasan (belum ditangani):** kalau tiket yang sedang
`serving` di-*uncall* sementara ada tiket `preparing` di loket yang sama,
tiket `preparing` itu jadi "tidak kelihatan" sebentar di panel dashboard
(karena panel Siap-siap cuma muncul kalau ada `currentTicket` berstatus
`serving`). Kasus ini jarang terjadi tapi perlu diperbaiki kalau ternyata
sering kejadian di lapangan.

### Tahap 6 — Sesi foto berlangsung
- Fotografer klik "Mulai Foto" (`startServing()`, `status = serving`).
- Bersamaan dengan itu, set `notes_locked_at = now()`. Selama field ini
  terisi, `notes` **cuma bisa diedit Admin** (guard di controller —
  request update dari CS/Kasir yang bukan Admin ditolak kalau
  `notes_locked_at` sudah terisi).
- Status ke pelanggan: **"Sedang Foto"**.

### Tahap 7 — Selesai foto → lanjut kasir
- Fotografer klik "Selesai" (`advanceStage()`) → `stage = payment`,
  `status = waiting`.
- Karena bisa lebih dari 1 fotografer/studio, tidak pasti siapa yang
  selesai duluan — makanya dipakai sistem antrian lagi di tahap payment
  (**sudah begini by design**, bukan bug).
- Status ke pelanggan: **"Foto Selesai, Menunggu Pembayaran"**.

### Tahap 8 — Pembayaran & ambil hasil
- Kasir manapun panggil dari antrian `payment`.
- Selesai bayar & serah hasil → `advanceStage()` lagi → `status = completed`.
- Status ke pelanggan: **"Selesai"**.

---

## 4. Status yang ditampilkan ke pelanggan

Field `stage`/`status` internal **jangan** ditampilkan mentah-mentah ke
pelanggan. Perlu fungsi mapping (belum ada, disarankan bikin
`Ticket::customerStatusLabel()`):

| stage | status | confirmed_at | Label ke pelanggan |
|---|---|---|---|
| registration | waiting | - | Menunggu Dipanggil |
| registration | calling/serving | kosong | Sedang Konsultasi |
| registration | calling/serving | terisi | Sedang Konsultasi (sudah dikonfirmasi, siap lanjut) |
| photo_session | waiting | - | Menunggu Sesi Foto |
| photo_session | calling | - | Dipanggil ke Studio {counter->name} |
| photo_session | preparing | - | Silakan Bersiap-siap di Studio {counter->name} — Sebentar Lagi Giliran Anda |
| photo_session | serving | - | Sedang Foto |
| payment | waiting | - | Foto Selesai, Menunggu Pembayaran |
| payment | calling/serving | - | Dipanggil ke Kasir |
| - | completed | - | Selesai |
| - | cancelled | - | Dibatalkan |

---

## 5. Kenapa tetap 1 tiket/1 QR (bukan entity Order terpisah)

Alternatif yang sempat diusulkan: bikin tabel `orders` terpisah, baru dibuat
(dengan nomor & QR sendiri) begitu CS klik konfirmasi.

Diputuskan **tidak** dipakai, dengan alasan:
- `Ticket` sudah jadi pusat dari hampir semua fitur (broadcast realtime,
  push notification, QR check-in, dashboard operator, laporan). Pecah jadi
  2 entity = ubah besar-besaran di seluruh sistem.
- Pelanggan sudah pegang 1 QR dari awal (buat lihat status "sedang
  konsultasi") — kalau nanti muncul QR/nomor kedua pas order fix, malah
  bikin bingung "QR yang mana yang bener".
- Sistem sudah punya `order_code` (`ORD-xxxxxx`, dari primary key tiket,
  permanen) — tinggal disembunyikan sampai `confirmed_at` terisi, efeknya
  sama (order code baru "ada artinya" setelah fix) tanpa bikin tabel baru.

**Kalau ke depannya butuh riwayat/edit history per revisi pesanan** (misal
laporan "berapa kali order direvisi sebelum fix"), baru pertimbangkan tabel
log terpisah (append-only), bukan entity Order penuh.

---

## 6. Keputusan final

| # | Pertanyaan | Keputusan |
|---|---|---|
| 1 | Entity Order terpisah, atau 1 tiket + `confirmed_at`? | **1 tiket + `confirmed_at`.** Paling murah dikerjakan, paling gak berisiko bikin bug baru, gak perlu bongkar arsitektur. |
| 2 | Siapa yang boleh override kunci notes pas "Sedang Foto"? | **Admin saja.** Kalau semua staff bisa override, gunanya kunci ini hilang. Kasus darurat harus lewat Admin secara sadar. |
| 3 | "Batalkan Pemanggilan" fotografer kena `skip_count`? | **Tidak.** Method baru `uncall()`, terpisah dari `skip()`. Kesalahan staff tidak boleh menghukum pelanggan. |
| 4 | Radius GPS default? | **50 meter** (turun dari 150m). Cukup ketat tapi masih toleran ke akurasi GPS HP di dalam ruangan. Tetap bisa disesuaikan per lokasi. |

## 7. Rencana implementasi (urutan pengerjaan) — ✅ SUDAH DIKERJAKAN

1. **Migration**: `confirmed_at`, `notes_locked_at` di `tickets`; status enum
   ditambah `preparing`. ✅
2. **`Ticket` model**: `confirmOrder()`, guard `completeRegistration()`,
   `uncall()`, `callToPrepare()`, auto-promote `preparing → calling` di
   `advanceStage()`, `customerStatusLabel()`, kunci `notes` di
   `startServing()`. ✅
3. **Controller (`OperatorController`)**: `confirmOrder()`, `uncallTicket()`,
   `callNextToPrepare()`, guard `updateNotes()` (Admin-only saat terkunci),
   guard `completeRegistration()`. ✅
4. **View (dashboard operator)**: tombol Konfirmasi Pesanan, Batalkan
   Pemanggilan, Panggil Siap-siap Berikutnya + panel status Siap-siap,
   notes read-only kalau terkunci & bukan Admin. ✅
5. **View (halaman status pelanggan)**: `customerStatusLabel()` dipakai di
   semua status termasuk `preparing`; `order_code` cuma muncul kalau
   `confirmed_at` terisi. ✅
6. **Admin → Lokasi**: default radius GPS lokasi baru jadi 50m (lokasi lama
   ikut dibackfill kalau masih pakai default 150m lama). ✅

Radius GPS default sekarang: **50 meter** (lihat migration
`2026_07_23_000001_add_confirmation_and_lock_to_tickets_table.php` — juga
membackfill lokasi lama yang masih pakai 150m).

Lihat `docs/BUG-CHECKLIST.md` untuk daftar lengkap potensi bug + status
pengerjaannya, dan `docs/DATA-MODEL.md` untuk detail teknis field/tabel.
