<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan pengingat di dashboard operator. Lihat migrasi
 * 2026_10_08_000001_create_reminder_settings_table untuk arti tiap kolom.
 */
class ReminderSetting extends Model
{
    protected $fillable = ['type', 'enabled', 'start_after_minutes', 'interval_minutes'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'start_after_minutes' => 'integer',
            'interval_minutes' => 'integer',
        ];
    }

    /** Jenis pengingat + teks penjelasan untuk halaman admin. */
    public const TYPES = [
        'ready' => [
            'label' => 'Klik Ready di Siap-siap',
            'who' => 'CS & Kasir',
            'desc' => 'Ada pelanggan di Siap-siap yang belum di-Ready. Tanpa Ready, fotografer belum bisa memanggilnya ke studio.',
        ],
        'payment' => [
            'label' => 'Selesaikan pembayaran',
            'who' => 'CS & Kasir',
            'desc' => 'Tiket masih berstatus sedang dilayani di Pembayaran. Mengingatkan kalau pembayaran sebenarnya sudah selesai tapi belum ditekan "Selesai" di app.',
        ],
        'edit' => [
            'label' => 'Selesaikan edit foto',
            'who' => 'Fotografer',
            'desc' => 'Ada foto yang belum ditandai selesai diedit (siap diedit, sedang diedit, atau "edit menyusul" setelah bayar duluan).',
        ],
    ];

    /** Nilai awal kalau barisnya belum ada / tabel belum dimigrasi. */
    public const DEFAULTS = [
        'ready' => ['enabled' => true, 'start_after_minutes' => 1, 'interval_minutes' => 2],
        'payment' => ['enabled' => true, 'start_after_minutes' => 3, 'interval_minutes' => 3],
        'edit' => ['enabled' => true, 'start_after_minutes' => 10, 'interval_minutes' => 5],
    ];

    /**
     * Pengaturan lengkap per jenis, selalu berisi ketiga jenis. Aman kalau
     * tabelnya belum ada (file sudah dicopy tapi migrasi belum jalan): pakai
     * nilai awal, dashboard tidak error.
     *
     * @return array<string, array{enabled: bool, start_after_minutes: int, interval_minutes: int}>
     */
    public static function resolved(): array
    {
        $result = self::DEFAULTS;

        try {
            foreach (static::all() as $row) {
                if (isset($result[$row->type])) {
                    $result[$row->type] = [
                        'enabled' => (bool) $row->enabled,
                        'start_after_minutes' => (int) $row->start_after_minutes,
                        'interval_minutes' => max(1, (int) $row->interval_minutes),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // tabel belum ada -> pakai DEFAULTS
        }

        return $result;
    }

    /** Bentuk ringkas untuk dashboard (dibaca JavaScript). */
    public static function forDashboard(): array
    {
        $out = [];
        foreach (self::resolved() as $type => $s) {
            $out[$type] = ['on' => $s['enabled'], 'start' => $s['start_after_minutes'], 'every' => $s['interval_minutes']];
        }

        return $out;
    }
}
