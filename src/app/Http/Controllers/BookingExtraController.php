<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingExtra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin > Tambahan Reservasi: kelola daftar pilihan dropdown "Tambahan" di form
 * reservasi (CS/operator). Reservasi menyimpan nama pilihannya sebagai teks,
 * jadi menghapus/menonaktifkan pilihan TIDAK mengubah reservasi lama.
 */
class BookingExtraController extends Controller
{
    public function index(): View
    {
        $extras = BookingExtra::ordered()->get();

        // Berapa reservasi yang memakai tiap pilihan (info sebelum hapus/ganti nama).
        $usage = [];
        Booking::query()->whereNotNull('extras')->pluck('extras')->each(function ($list) use (&$usage) {
            foreach ((array) $list as $name) {
                $usage[$name] = ($usage[$name] ?? 0) + 1;
            }
        });

        return view('admin.booking-extras.index', compact('extras', 'usage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => BookingExtra::normalizeName((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('booking_extras', 'name')],
        ], [
            'name.required' => 'Nama pilihan wajib diisi.',
            'name.max' => 'Nama pilihan maksimal 100 karakter.',
            'name.unique' => 'Pilihan dengan nama itu sudah ada.',
        ]);

        BookingExtra::create([
            'name' => $validated['name'],
            'is_active' => true,
            'sort_order' => ((int) BookingExtra::max('sort_order')) + 1, // taruh di paling bawah
        ]);

        return back()->with('success', "Pilihan {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Ganti nama pilihan. Nama di reservasi yang sudah ada ikut diganti, supaya
     * perbaikan salah ketik tidak meninggalkan dua nama berbeda.
     */
    public function update(Request $request, BookingExtra $extra): RedirectResponse
    {
        $request->merge(['name' => BookingExtra::normalizeName((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('booking_extras', 'name')->ignore($extra->id)],
        ], [
            'name.required' => 'Nama pilihan wajib diisi.',
            'name.max' => 'Nama pilihan maksimal 100 karakter.',
            'name.unique' => 'Pilihan dengan nama itu sudah ada.',
        ]);

        $old = $extra->name;
        if ($validated['name'] === $old) {
            return back();
        }

        $renamed = DB::transaction(function () use ($extra, $old, $validated) {
            $extra->update(['name' => $validated['name']]);

            $count = 0;
            Booking::whereNotNull('extras')->get()->each(function (Booking $booking) use ($old, $validated, &$count) {
                $list = (array) $booking->extras;
                if (!in_array($old, $list, true)) {
                    return;
                }
                $booking->update(['extras' => array_values(array_map(
                    fn ($name) => $name === $old ? $validated['name'] : $name,
                    $list
                ))]);
                $count++;
            });

            return $count;
        });

        $msg = "Pilihan {$old} diganti menjadi {$validated['name']}.";
        if ($renamed > 0) {
            $msg .= " {$renamed} reservasi ikut diperbarui.";
        }

        return back()->with('success', $msg);
    }

    public function toggle(Request $request, BookingExtra $extra): RedirectResponse
    {
        $extra->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('success', "Pilihan {$extra->name} " . ($extra->is_active ? 'diaktifkan.' : 'dinonaktifkan (tidak muncul di form reservasi).'));
    }

    /** Geser urutan di dropdown: direction = up | down. */
    public function move(Request $request, BookingExtra $extra): RedirectResponse
    {
        $direction = $request->input('direction') === 'up' ? -1 : 1;

        DB::transaction(function () use ($extra, $direction) {
            $ids = BookingExtra::ordered()->pluck('id')->all();
            $pos = array_search($extra->id, $ids, true);
            $target = $pos === false ? false : $pos + $direction;

            if ($pos === false || $target < 0 || $target >= count($ids)) {
                return;
            }

            [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];

            // Nomori ulang 1..n supaya urutan selalu rapi (tidak ada nilai kembar).
            foreach ($ids as $i => $id) {
                BookingExtra::where('id', $id)->update(['sort_order' => $i + 1]);
            }
        });

        return back();
    }

    public function destroy(BookingExtra $extra): RedirectResponse
    {
        $name = $extra->name;
        $extra->delete();

        return back()->with('success', "Pilihan {$name} dihapus dari daftar. Reservasi yang sudah memakai pilihan ini tidak berubah.");
    }
}
