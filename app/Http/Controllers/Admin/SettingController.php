<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::current();
        $schedule = Schedule::orderBy('weekday')->get()->keyBy('weekday');

        return view('admin.settings.edit', [
            'settings' => $settings,
            'schedule' => $schedule,
            'weekdays' => Schedule::WEEKDAYS,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'cutoff_minutes_before_close' => ['required', 'integer', 'min:0', 'max:240'],
            'default_delivery_fee' => ['required', 'numeric', 'min:0', 'max:1000'],
            'delivery_fee_cap' => ['required', 'numeric', 'min:0', 'max:1000'],
            'dorms' => ['nullable', 'string'],
        ]);

        // A cap below the per-item fee would make every order pay the cap. Allow
        // 0 (cap disabled); otherwise require the cap to be at least one item's fee.
        if ($data['delivery_fee_cap'] > 0 && $data['delivery_fee_cap'] < $data['default_delivery_fee']) {
            return back()->withInput()->withErrors([
                'delivery_fee_cap' => 'The delivery fee cap must be at least the per-item fee, or 0 to disable it.',
            ]);
        }

        $dorms = collect(preg_split('/\r\n|\r|\n/', (string) ($data['dorms'] ?? '')))
            ->map(fn ($d) => trim($d))
            ->filter()
            ->unique()
            ->values()
            ->all();

        Setting::current()->update([
            'restaurant_name' => $data['restaurant_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'cutoff_minutes_before_close' => (int) $data['cutoff_minutes_before_close'],
            'default_delivery_fee' => (int) round($data['default_delivery_fee'] * 100),
            'delivery_fee_cap' => (int) round($data['delivery_fee_cap'] * 100),
            'dorms' => $dorms,
        ]);

        return back()->with('success', 'Settings saved.');
    }

    public function updatePreorder(Request $request)
    {
        $data = $request->validate([
            'preorder_enabled' => ['nullable', 'boolean'],
            'preorder_open_day' => ['required', 'integer', 'between:0,6'],
            'preorder_open_time' => ['required', 'date_format:H:i'],
            'preorder_cutoff_day' => ['required', 'integer', 'between:0,6'],
            'preorder_cutoff_time' => ['required', 'date_format:H:i'],
            'preorder_fulfillment_day' => ['required', 'integer', 'between:0,6'],
            'preorder_window_start' => ['required', 'date_format:H:i'],
            'preorder_window_end' => ['required', 'date_format:H:i'],
            'preorder_delivery_fee' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        if ($data['preorder_window_end'] <= $data['preorder_window_start']) {
            return back()->with('error', 'Preorder: the delivery window end time must be after the start time.');
        }

        Setting::current()->update([
            'preorder_enabled' => (bool) ($data['preorder_enabled'] ?? false),
            'preorder_open_day' => (int) $data['preorder_open_day'],
            'preorder_open_time' => $data['preorder_open_time'],
            'preorder_cutoff_day' => (int) $data['preorder_cutoff_day'],
            'preorder_cutoff_time' => $data['preorder_cutoff_time'],
            'preorder_fulfillment_day' => (int) $data['preorder_fulfillment_day'],
            'preorder_window_start' => $data['preorder_window_start'],
            'preorder_window_end' => $data['preorder_window_end'],
            'preorder_delivery_fee' => (int) round($data['preorder_delivery_fee'] * 100),
        ]);

        return back()->with('success', 'Preorder settings saved.');
    }

    public function uploadQr(Request $request)
    {
        $request->validate([
            'qr_image' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:' . config('ordering.menu_image_max_kb')],
        ]);

        $file = $request->file('qr_image');

        // Derive the extension from the DETECTED content type, never the client
        // filename, so nothing executable can be written to a web-served folder.
        $ext = match ($file->getMimeType()) {
            'image/png' => 'png',
            default => 'jpg', // image/jpeg and anything unexpected
        };
        $filename = 'qr.' . $ext;

        $dir = public_path('uploads');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Drop a prior QR saved under a different extension so no stale copy lingers.
        $current = Setting::current()->qr_image_path;
        if ($current && $current !== 'uploads/' . $filename && is_file(public_path($current))) {
            @unlink(public_path($current));
        }

        $file->move($dir, $filename);

        Setting::current()->update(['qr_image_path' => 'uploads/' . $filename]);

        return back()->with('success', 'QR image updated.');
    }

    public function updateSchedule(Request $request)
    {
        $data = $request->validate([
            'days' => ['required', 'array'],
            'days.*.is_open' => ['nullable', 'boolean'],
            'days.*.open_time' => ['required', 'date_format:H:i'],
            'days.*.close_time' => ['required', 'date_format:H:i'],
        ]);

        foreach ($data['days'] as $weekday => $row) {
            if ($row['close_time'] <= $row['open_time']) {
                return back()
                    ->withInput()
                    ->with('error', (Schedule::WEEKDAYS[(int) $weekday] ?? 'A day') . ': close time must be after open time.');
            }
        }

        foreach ($data['days'] as $weekday => $row) {
            Schedule::updateOrCreate(
                ['weekday' => (int) $weekday],
                [
                    'is_open' => (bool) ($row['is_open'] ?? false),
                    'open_time' => $row['open_time'],
                    'close_time' => $row['close_time'],
                ]
            );
        }

        return back()->with('success', 'Schedule saved.');
    }
}
