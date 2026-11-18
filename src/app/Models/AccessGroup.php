<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grup hak akses: kumpulan permission yang bisa dipasang ke banyak user
 * sekaligus. Permission efektif user = default role + grup + centang per-user.
 */
class AccessGroup extends Model
{
    protected $fillable = ['name', 'description', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    /** Daftar semua hak akses yang bisa diatur: key => [label, kategori]. */
    public static function catalog(): array
    {
        return [
            'walkin' => ['Walk-in', 'Antrian'],
            'photo' => ['Panggil Foto', 'Antrian'],
            'payment' => ['Pembayaran', 'Antrian'],
            'booking' => ['Reservasi Hari Ini', 'Reservasi'],
            'list_booking' => ['List Booking (lihat, tambah, ubah, hapus, import/export)', 'Reservasi'],
            'customers' => ['Data Pelanggan', 'Data'],
            'all_locations' => ['Semua Lokasi', 'Data'],
        ];
    }

    public static function catalogByCategory(): array
    {
        $out = [];
        foreach (self::catalog() as $key => [$label, $cat]) {
            $out[$cat][$key] = $label;
        }

        return $out;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
