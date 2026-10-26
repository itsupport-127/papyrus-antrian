<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pilihan dropdown "Tambahan" di form reservasi (mis. Kostum, Background, Pose).
 * Dikelola Admin di menu "Tambahan Reservasi". Reservasi menyimpan NAMA pilihannya
 * (bookings.extras, JSON), bukan id, jadi pilihan yang dihapus/dinonaktifkan tidak
 * mengubah reservasi lama.
 */
class BookingExtra extends Model
{
    protected $fillable = ['name', 'is_active', 'sort_order'];

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

    /** Rapikan nama: spasi berlebih dibuang, huruf pertama dibuat kapital. */
    public static function normalizeName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    /**
     * Nama pilihan AKTIF sesuai urutan, untuk dropdown "Tambahan" di form
     * reservasi. Di-memo per request, dan aman kalau tabelnya belum ada
     * (migrasi belum dijalankan): dropdown kosong, halaman lain tetap jalan.
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
