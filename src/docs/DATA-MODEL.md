# Data Model — bagian yang relevan dengan alur antrian

Ringkasan teknis field-field yang dipakai di alur (lihat `ALUR-ANTRIAN-STUDIO.md`
untuk konteks bisnisnya). Ditulis biar AI/dev lain gak perlu baca ulang semua
migration satu-satu.

## Tabel `tickets` (model: `App\Models\Ticket`)

Field yang sudah ada & relevan:

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | int, PK | Dipakai untuk generate `order_code` (`ORD-000123`) |
| `location_id` | FK → locations | |
| `service_category_id` | FK → service_categories, nullable | |
| `counter_id` | FK → counters, nullable | Loket/studio yang lagi pegang tiket ini |
| `served_by` | FK → users, nullable | Staff yang lagi pegang |
| `ticket_number` | string | Nomor harian, reset tiap hari (mis. `PP-001`) — **bukan** id unik permanen |
| `customer_name`, `customer_phone`, `customer_email` | string | |
| `status` | enum-ish string | `waiting` \| `calling` \| `preparing` \| `serving` \| `completed` \| `cancelled` \| `skipped`. `preparing` ("Siap-siap") ditambah untuk fitur pipelining fotografer. |
| `stage` | enum-ish string | `registration` \| `photo_session` \| `payment` (lihat `Ticket::STAGE_ORDER`) |
| `notes` | text, nullable | Catatan order, multi-baris. Diisi pelanggan saat ambil tiket, **ditimpa/dilengkapi CS** saat konsultasi |
| `called_at`, `served_at`, `completed_at` | timestamp | |
| `registration_completed_at`, `photo_completed_at`, `payment_completed_at` | timestamp | Ditulis di `completeRegistration()` / `advanceStage()` |
| `service_time` | int (detik) | Dihitung otomatis saat selesai bayar |
| `skip_count`, `last_skipped_at` | int, timestamp | Untuk `skip()` — dipanggil tapi pelanggan gak ada |
| `latitude`, `longitude` | *(field ini ada di `locations`, bukan `tickets`)* | dikirim dari form, dipakai untuk validasi GPS, **tidak disimpan** ke tiket (cuma dipakai sesaat di `TicketController::store`) |

Field yang **sudah final, siap dibuatkan migration**:

| Field baru | Tipe | Kapan diisi |
|---|---|---|
| `confirmed_at` | timestamp, nullable | Saat CS klik "Konfirmasi Pesanan" (tahap 3 di alur). Guard: `completeRegistration()` cuma boleh jalan kalau field ini terisi. |
| `notes_locked_at` | timestamp, nullable | Otomatis diisi di `startServing()` saat `stage = photo_session` (sesi foto mulai). Selama terisi, `notes` cuma bisa diedit Admin. |

Accessor yang sudah ada:
- `getOrderCodeAttribute()` → `ORD-000123`, permanen dari `id`. **Rencana:**
  baru ditampilkan ke pelanggan kalau `confirmed_at` terisi.
- `getPositionAttribute()` → posisi dalam antrian di stage saat ini.
- `getEstimatedWaitTimeAttribute()` → estimasi waktu tunggu.

Method penting (`App\Models\Ticket`):
- `call(Counter, User)` — dipanggil operator, `status → calling`.
- `startServing()` — `status → serving`. Perlu ditambah: kalau
  `stage = photo_session`, set `notes_locked_at = now()` juga.
- `completeRegistration()` — pindah `stage: registration → photo_session`.
  **Perlu ditambah guard**: hanya boleh jalan kalau `confirmed_at` sudah terisi.
- `advanceStage()` — pindah `photo_session → payment`, atau `payment → completed`.
- `skip()` — dipanggil tapi pelanggan gak ada; auto-cancel setelah `MAX_SKIPS` (3x).
- `cancel()` — batalkan tiket kapan saja sebelum selesai.
- `confirmOrder()` — set `confirmed_at = now()`.
- `uncall()` — kembalikan `status → waiting`, kosongkan
  `counter_id`/`served_by`, **tanpa** nambah `skip_count`. Beda dari `skip()`
  yang memang buat menghukum ketidakhadiran pelanggan — ini buat kesalahan
  staff (salah panggil / gak jadi motret).
- `callToPrepare(Counter, User)` — "Panggil Siap-siap": panggil tiket
  BERIKUTNYA selagi tiket yang sekarang `serving`, status jadi `preparing`.
  Guard-nya di `OperatorController::callNextToPrepare()`.
- `createTicket(...)` — static factory dipanggil dari `TicketController::store`.

Method penting (`App\Models\Counter`):
- `currentTicket()` — tiket yang lagi aktif (`calling`/`serving`) di loket
  ini. **Tidak** termasuk `preparing`.
- `preparingTicket()` — tiket yang lagi "Siap-siap" di loket ini (kalau ada).

## Tabel `locations` (model: `App\Models\Location`)

Field GPS (sudah diimplementasikan):

| Field | Tipe | Keterangan |
|---|---|---|
| `latitude`, `longitude` | decimal(10,7), nullable | Kosong = lokasi ini tidak dibatasi GPS |
| `gps_radius_meters` | int, default **50** *(final, turun dari 150)* | Radius yang diizinkan — 50m dipilih karena cukup ketat tapi masih toleran ke akurasi GPS HP di dalam ruangan. Bisa disesuaikan per lokasi di Admin → Lokasi. |

Method: `hasGpsRestriction()`, `distanceInMetersFrom($lat, $lng)` (Haversine),
`isWithinGpsRadius($lat, $lng)`.

## Tabel `counters` (model: `App\Models\Counter`)

- `stages` (array/json) — daftar `stage` yang bisa dilayani loket ini, mis.
  `['photo_session']` untuk loket fotografer, `['registration']` untuk CS,
  `['payment']` untuk kasir. Ini mekanisme yang bikin multi-fotografer/
  multi-studio jalan tanpa rebutan — **sudah diimplementasikan dan
  race-condition-safe** (`OperatorController::callNext` pakai
  `DB::transaction()` + `lockForUpdate()`).

## Yang sudah dihapus (jangan dipakai lagi)

- Tabel `order_items` dan kolom `total_price`/`acc_pemesanan` di `tickets` —
  sudah di-drop lewat migration `2026_07_18_120000_drop_order_pricing_from_tickets.php`.
- Model `App\Models\OrderItem` **masih ada di codebase tapi jadi kode mati**
  (nunjuk ke tabel yang sudah gak ada). Aman dihapus filenya, atau biarkan
  kalau memang mau dipakai lagi nanti untuk rincian harga per item.
