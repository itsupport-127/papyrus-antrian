<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin > Paket Reservasi: kelola daftar "Pilihan Paket" yang muncul di form
 * reservasi (CS/operator). Reservasi menyimpan nama paketnya sebagai teks,
 * jadi menghapus/menonaktifkan paket TIDAK mengubah reservasi lama.
 */
class BookingPackageController extends Controller
{
    public function index(): View
    {
        $packages = BookingPackage::ordered()->get();

        // Berapa reservasi yang memakai tiap paket (buat info sebelum hapus/ganti nama).
        $usage = Booking::query()
            ->whereNotNull('package_name')
            ->selectRaw('package_name, COUNT(*) as c')
            ->groupBy('package_name')
            ->pluck('c', 'package_name');

        return view('admin.booking-packages.index', compact('packages', 'usage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => BookingPackage::normalizeName((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('booking_packages', 'name')],
        ], [
            'name.required' => 'Nama paket wajib diisi.',
            'name.max' => 'Nama paket maksimal 100 karakter.',
            'name.unique' => 'Paket dengan nama itu sudah ada.',
        ]);

        BookingPackage::create([
            'name' => $validated['name'],
            'is_active' => true,
            'sort_order' => ((int) BookingPackage::max('sort_order')) + 1, // taruh di paling bawah
        ]);

        return back()->with('success', "Paket {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Ganti nama paket. Nama di reservasi yang sudah ada ikut diganti, supaya
     * perbaikan salah ketik tidak meninggalkan dua nama berbeda untuk paket yang sama.
     */
    public function update(Request $request, BookingPackage $package): RedirectResponse
    {
        $request->merge(['name' => BookingPackage::normalizeName((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('booking_packages', 'name')->ignore($package->id)],
            // Lama edit (hari) -> estimasi selesai edit di tiket reservasi. 0 = edit hari ini, kosong = belum diatur.
            'edit_days' => ['nullable', 'integer', 'min:0', 'max:60'],
        ], [
            'name.required' => 'Nama paket wajib diisi.',
            'name.max' => 'Nama paket maksimal 100 karakter.',
            'name.unique' => 'Paket dengan nama itu sudah ada.',
            'edit_days.integer' => 'Lama edit harus berupa angka (hari).',
            'edit_days.min' => 'Lama edit minimal 0 hari.',
            'edit_days.max' => 'Lama edit maksimal 60 hari.',
        ]);

        $editDays = isset($validated['edit_days']) && $validated['edit_days'] !== '' ? (int) $validated['edit_days'] : null;

        $old = $package->name;
        if ($validated['name'] === $old) {
            $package->update(['edit_days' => $editDays]);

            return back()->with('success', "Lama edit paket {$old} disimpan.");
        }

        $renamed = DB::transaction(function () use ($package, $old, $validated) {
            $package->update(['name' => $validated['name'], 'edit_days' => $editDays]);

            return Booking::where('package_name', $old)->update(['package_name' => $validated['name']]);
        });

        $msg = "Paket {$old} diganti menjadi {$validated['name']}.";
        if ($renamed > 0) {
            $msg .= " {$renamed} reservasi ikut diperbarui.";
        }

        return back()->with('success', $msg);
    }

    public function toggle(Request $request, BookingPackage $package): RedirectResponse
    {
        $package->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('success', "Paket {$package->name} " . ($package->is_active ? 'diaktifkan.' : 'dinonaktifkan (tidak muncul di form reservasi).'));
    }

    /** Geser urutan di dropdown: direction = up | down. */
    public function move(Request $request, BookingPackage $package): RedirectResponse
    {
        $direction = $request->input('direction') === 'up' ? -1 : 1;

        DB::transaction(function () use ($package, $direction) {
            $ids = BookingPackage::ordered()->pluck('id')->all();
            $pos = array_search($package->id, $ids, true);
            $target = $pos === false ? false : $pos + $direction;

            if ($pos === false || $target < 0 || $target >= count($ids)) {
                return;
            }

            [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];

            // Nomori ulang 1..n supaya urutan selalu rapi (tidak ada nilai kembar).
            foreach ($ids as $i => $id) {
                BookingPackage::where('id', $id)->update(['sort_order' => $i + 1]);
            }
        });

        return back();
    }

    public function destroy(BookingPackage $package): RedirectResponse
    {
        $name = $package->name;
        $package->delete();

        return back()->with('success', "Paket {$name} dihapus dari daftar. Reservasi yang sudah memakai paket ini tidak berubah.");
    }
}
