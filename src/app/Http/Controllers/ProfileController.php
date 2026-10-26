<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Profil staf (Admin, CS & Kasir, Fotografer): nama, foto, dan ganti password. */
class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profile.show', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Nama tidak boleh kosong.',
            'name.max' => 'Nama maksimal 100 karakter.',
        ]);

        auth()->user()->update(['name' => trim($validated['name'])]);

        return back()->with('success', 'Profil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $key = 'profile-password:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'current_password' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.',
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Isi password saat ini.',
            'password.required' => 'Isi password baru.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['current_password' => 'Password saat ini salah.']);
        }

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Password baru harus berbeda dari password saat ini.']);
        }

        RateLimiter::clear($key);
        $user->update(['password' => $validated['password']]); // di-hash otomatis (cast 'hashed')

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function photo(Request $request, AvatarService $avatars): RedirectResponse
    {
        $request->validate([
            // 5 MB cukup untuk foto HP; hasilnya dikecilkan jadi puluhan KB.
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Pilih foto dulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
            'photo.uploaded' => 'Foto gagal diunggah. Ukuran terlalu besar atau koneksi terputus.',
        ]);

        try {
            $avatars->store(auth()->user(), (string) file_get_contents($request->file('photo')->getRealPath()));
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['photo' => $e->getMessage()]);
        }

        return back()->with('success', 'Foto profil diperbarui.');
    }

    public function removePhoto(AvatarService $avatars): RedirectResponse
    {
        $avatars->delete(auth()->user());

        return back()->with('success', 'Foto profil dihapus.');
    }

    /** Sajikan foto (disk privat), hanya untuk yang sudah login. Versi di URL (?v=) mengatur cache. */
    public function avatar(User $user): BinaryFileResponse
    {
        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return response()->file(Storage::disk('local')->path($user->avatar_path), [
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
