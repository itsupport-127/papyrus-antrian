<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'location_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions(), true);
    }

    public function getDefaultPermissions(): array
    {
        return match ($this->role) {
            'admin' => ['walkin', 'booking', 'photo', 'payment', 'customers', 'all_locations'],
            'operator' => ['walkin', 'booking', 'payment', 'customers'],
            'fotografer' => ['photo', 'walkin', 'customers'],
            'viewer' => ['customers'],
            default => [],
        };
    }

    public function getPermissions(): array
    {
        return array_values(array_unique(array_merge($this->getDefaultPermissions(), $this->permissions ?? [])));
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function operatingCounter(): HasOne
    {
        return $this->hasOne(Counter::class, 'current_operator_id')
            ->where('location_id', $this->location_id);
    }

    public function servedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'served_by');
    }

    /**
     * Tahap tiket yang dilayani tiap role, dipakai untuk mencocokkan
     * user ke loket secara otomatis (tanpa harus memilih loket manual).
     * 'operator' = CS & Kasir (registrasi/order + pembayaran & cetak digabung).
     */
    public const ROLE_STAGES = [
        'operator' => ['registration', 'payment'],
        'fotografer' => ['photo_session'],
        // Admin bisa "masuk" ke Panel CS (pilih lokasi dulu) dan dapat akses
        // yang sama seperti CS & Kasir — termasuk tambah reservasi manual.
        'admin' => ['registration', 'payment'],
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isFotografer(): bool
    {
        return $this->role === 'fotografer';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    /**
     * Apakah user ini staf loket (CS & Kasir atau fotografer) yang boleh
     * masuk ke panel operator dan memanggil antrian.
     */
    public function isCounterStaff(): bool
    {
        return $this->isOperator() || $this->isFotografer();
    }

    /**
     * Tahap-tahap tiket yang menjadi tanggung jawab role user ini.
     *
     * @return array<int, string>
     */
    public function stagesForRole(): array
    {
        return self::ROLE_STAGES[$this->role] ?? [];
    }

    public function canManageLocation(Location $location): bool
    {
        return $this->isAdmin() || ($this->isCounterStaff() && $this->location_id === $location->id);
    }

    /**
     * ID lokasi yang boleh dikelola (edit/hapus tiket, dst) oleh user ini.
     * Sengaja disamakan dengan scope yang dipakai untuk MENAMPILKAN data di
     * tab Data Pelanggan (Admin & CS/Kasir lihat + kelola lintas lokasi,
     * role lain cuma lokasi tempat mereka ditugaskan) — supaya tombol
     * Edit/Hapus yang muncul di tabel selalu benar-benar bisa dipakai,
     * tidak ada tombol yang keliatan tapi ujung-ujungnya 403.
     *
     * @return array<int, int>
     */
    public function manageableLocationIds(): array
    {
        if ($this->isAdmin() || $this->isOperator()) {
            return Location::pluck('id')->toArray();
        }

        return $this->location_id ? [$this->location_id] : [];
    }
}