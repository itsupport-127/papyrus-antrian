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
        'preferred_counter_id',
        'permissions',
        'avatar_path',
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

    /** URL foto profil (null kalau belum ada). ?v= berubah tiap data user berubah -> cache browser ikut segar. */
    public function avatarUrl(): ?string
    {
        if (!$this->avatar_path) {
            return null;
        }

        return route('profile.avatar', $this) . '?v=' . ($this->updated_at?->timestamp ?? 1);
    }

    /** Inisial (maks. 2 huruf) buat avatar kalau belum ada foto. */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice(array_filter($words), 0, 2));

        return implode('', $letters) ?: '?';
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
            'fotografer' => ['photo', 'walkin', 'customers', 'booking'],
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
        // PENTING: JANGAN tambah `->where('location_id', $this->location_id)`
        // di sini lagi (sebelumnya ada, sengaja dibuang). `current_operator_id`
        // di tabel counters SUDAH cukup buat nentuin loket siapa punya siapa
        // (cuma 1 counter yang bisa punya current_operator_id = user ini di
        // saat bersamaan, dijaga di OperatorController::setCounter()) --
        // nambah filter location_id di sini malah bikin relasi ini SELALU
        // balik null buat user yang location_id-nya kosong (staf "gantian"
        // yang locket tetapnya sengaja dikosongin di /admin/users, lihat
        // OperatorController::resolveWorkLocation()), PADAHAL dia
        // sebenarnya lagi pegang satu loket yang sah abis milih lewat
        // halaman "Pilih Studio". Efeknya: semua tombol aksi (Panggil,
        // Lewati, Selesai, dst) gagal terus dengan pesan "Loket Anda belum
        // siap. Muat ulang halaman ini." padahal loketnya udah dipilih.
        return $this->hasOne(Counter::class, 'current_operator_id');
    }

    /**
     * Loket/studio TETAP yang di-set Admin buat user ini (mis. Fotografer1
     * selalu Studio Bawah). Diprioritaskan di
     * `OperatorController::resolveCounterForUser()` dibanding "loket kosong
     * pertama yang ketemu" — supaya pengelompokan fotografer/studio
     * konsisten tiap hari, gak tergantung urutan login.
     */
    public function preferredCounter(): BelongsTo
    {
        return $this->belongsTo(Counter::class, 'preferred_counter_id');
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
    private ?array $manageableLocationIdsMemo = null;

    public function manageableLocationIds(): array
    {
        // Di-memo per instance user (per request): dipanggil per BARIS di tabel
        // Data Pelanggan (lihat Ticket::canUnlockNotesBy), tanpa memo ini jadi
        // 1 query Location::pluck per baris.
        return $this->manageableLocationIdsMemo ??= $this->computeManageableLocationIds();
    }

    private function computeManageableLocationIds(): array
    {
        // Admin, CS & Kasir, DAN Fotografer -- semuanya boleh lihat/kelola
        // lintas lokasi (PP maupun BK), bukan cuma CS & Kasir. Ini penting
        // khususnya buat Fotografer: tiket hasil check-in reservasi (lokasi
        // BK, service_type=booking) tetap harus kelihatan & bisa dikelola
        // begitu masuk tahap Sesi Foto, walau lokasi "rumah" Fotografer itu
        // sendiri di-set ke PP. Cuma role 'viewer' yang tetap dibatasi ke 1
        // lokasi (lihat isCounterStaff()).
        if ($this->isAdmin() || $this->isCounterStaff()) {
            return Location::pluck('id')->toArray();
        }

        return $this->location_id ? [$this->location_id] : [];
    }
}