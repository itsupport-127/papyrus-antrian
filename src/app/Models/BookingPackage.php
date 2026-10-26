<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pilihan paket untuk reservasi (dropdown "Pilihan Paket"). Dikelola Admin di
 * menu "Paket Reservasi". Reservasi menyimpan NAMA paketnya (bookings.package_name),
 * bukan id, jadi paket yang dihapus/dinonaktifkan tidak mengubah reservasi lama.
 */
class BookingPackage extends Model
{
    protected $fillable = ['name', 'is_active', 'sort_order', 'edit_days'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Urutan tampil: sort_order lalu nama. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Rapikan nama paket: spasi berlebih dibuang & huruf besar semua (sama
     * seperti penulisan di spreadsheet CS, dan mencegah "Premium A" vs
     * "PREMIUM A" jadi dua paket berbeda).
     */
    public static function normalizeName(string $name): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $name)));
    }

    /**
     * Nama paket AKTIF sesuai urutan, untuk dropdown di form reservasi.
     * Di-memo per request (form tambah & edit bisa tampil bersamaan), dan
     * aman kalau tabelnya belum ada (mis. file sudah dicopy tapi migrasi
     * belum dijalankan): dropdown kosong, halaman lain tetap jalan.
     *
     * @return list<string>
     */
    public static function activeNames(): array
    {
        static $memo = null;

        if ($memo === null) {
            try {
                $memo = static::where('is_active', true)->ordered()->pluck('name')->all();
            } catch (\Throwable $e) {
                $memo = [];
            }
        }

        return $memo;
    }
}
