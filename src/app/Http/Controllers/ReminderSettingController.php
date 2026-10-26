<?php

namespace App\Http\Controllers;

use App\Models\ReminderSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin > Pengingat: atur kapan & seberapa sering dashboard operator
 * mengingatkan (suara + pesan) untuk 3 hal yang sering terlupa. Lihat
 * ReminderSetting::TYPES.
 */
class ReminderSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.reminders.index', [
            'types' => ReminderSetting::TYPES,
            'settings' => ReminderSetting::resolved(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        $messages = [];
        foreach (ReminderSetting::TYPES as $type => $meta) {
            $label = $meta['label'];
            $rules["settings.$type.enabled"] = ['nullable', 'boolean'];
            $rules["settings.$type.start_after_minutes"] = ['required', 'integer', 'min:0', 'max:240'];
            $rules["settings.$type.interval_minutes"] = ['required', 'integer', 'min:1', 'max:240'];
            $messages["settings.$type.start_after_minutes.required"] = "\"$label\": isi berapa menit sebelum mulai mengingatkan.";
            $messages["settings.$type.start_after_minutes.integer"] = "\"$label\": menit mulai harus berupa angka.";
            $messages["settings.$type.start_after_minutes.min"] = "\"$label\": menit mulai tidak boleh negatif.";
            $messages["settings.$type.start_after_minutes.max"] = "\"$label\": menit mulai maksimal 240.";
            $messages["settings.$type.interval_minutes.required"] = "\"$label\": isi berapa menit sekali diingatkan.";
            $messages["settings.$type.interval_minutes.integer"] = "\"$label\": interval harus berupa angka.";
            $messages["settings.$type.interval_minutes.min"] = "\"$label\": interval minimal 1 menit.";
            $messages["settings.$type.interval_minutes.max"] = "\"$label\": interval maksimal 240 menit.";
        }

        $validated = $request->validate($rules, $messages);

        foreach (array_keys(ReminderSetting::TYPES) as $type) {
            $row = $validated['settings'][$type];
            ReminderSetting::updateOrCreate(
                ['type' => $type],
                [
                    'enabled' => (bool) ($row['enabled'] ?? false),
                    'start_after_minutes' => (int) $row['start_after_minutes'],
                    'interval_minutes' => (int) $row['interval_minutes'],
                ]
            );
        }

        return back()->with('success', 'Pengaturan pengingat disimpan. Dashboard operator memakainya dalam beberapa detik, tanpa perlu refresh.');
    }
}
