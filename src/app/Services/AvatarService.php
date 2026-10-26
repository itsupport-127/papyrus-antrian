<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Foto profil yang ringan buat server:
 *  - unggahan asli (maks. 5 MB) TIDAK disimpan; gambar dipotong persegi dari tengah, diperkecil ke
 *    256x256 px, lalu dikompres (WebP kalau didukung, kalau tidak JPEG) -> biasanya 10-40 KB;
 *  - satu file per user (ditimpa kalau diganti, file lama dihapus) -> penyimpanan tidak menumpuk;
 *  - disimpan di disk privat (bukan folder publik), disajikan lewat route yang butuh login.
 */
class AvatarService
{
    /** Sisi gambar hasil (px). */
    public const SIZE = 256;

    /** Batas ukuran file hasil kompres; kualitas diturunkan bertahap sampai muat. */
    public const MAX_BYTES = 100 * 1024;

    /** Batas resolusi unggahan, supaya decode gambar tidak menghabiskan memori server. */
    public const MAX_PIXELS = 25_000_000;

    /**
     * Murni pengolahan gambar (tanpa Laravel) -- mudah diuji.
     *
     * @return array{data: string, ext: string}
     *
     * @throws \RuntimeException pesan siap tampil ke pengguna
     */
    public static function compress(string $binary, int $size = self::SIZE): array
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('Server belum mendukung kompres gambar (ekstensi PHP GD belum aktif). Hubungi admin.');
        }

        $info = @getimagesizefromstring($binary);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new \RuntimeException('Format gambar tidak didukung. Pakai JPG, PNG, atau WebP.');
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new \RuntimeException('Resolusi gambar terlalu besar. Perkecil dulu fotonya (maksimal sekitar 25 megapiksel).');
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            throw new \RuntimeException('Gambar tidak bisa dibaca. Coba foto lain.');
        }

        // Foto HP sering "miring" lewat tanda EXIF -- putar sesuai tanda itu.
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($binary));
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($angle !== 0) {
                $rotated = imagerotate($src, $angle, 0);
                if ($rotated) {
                    imagedestroy($src);
                    $src = $rotated;
                }
            }
        }

        // Potong persegi dari tengah, lalu perkecil.
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $sx = intdiv($w - $side, 2);
        $sy = intdiv($h - $side, 2);

        $dst = imagecreatetruecolor($size, $size);
        $useWebp = function_exists('imagewebp');
        if ($useWebp) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        } else {
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        }
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $size, $size, $side, $side);

        $data = '';
        foreach ([82, 68, 52, 38] as $quality) {
            ob_start();
            $useWebp ? imagewebp($dst, null, $quality) : imagejpeg($dst, null, $quality);
            $data = (string) ob_get_clean();

            if (strlen($data) <= self::MAX_BYTES) {
                break;
            }
        }

        imagedestroy($src);
        imagedestroy($dst);

        return ['data' => $data, 'ext' => $useWebp ? 'webp' : 'jpg'];
    }

    public function store(User $user, string $binary): string
    {
        $result = self::compress($binary);
        $disk = Storage::disk('local');
        $path = "avatars/{$user->id}.{$result['ext']}";

        foreach (['webp', 'jpg'] as $ext) {
            if ($ext !== $result['ext']) {
                $disk->delete("avatars/{$user->id}.{$ext}");
            }
        }

        $disk->put($path, $result['data']);
        $user->update(['avatar_path' => $path]);

        return $path;
    }

    public function delete(User $user): void
    {
        foreach (['webp', 'jpg'] as $ext) {
            Storage::disk('local')->delete("avatars/{$user->id}.{$ext}");
        }

        $user->update(['avatar_path' => null]);
    }
}
