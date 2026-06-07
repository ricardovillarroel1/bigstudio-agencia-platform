<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $metaPixelId = Setting::get('meta_pixel_id', '');
        return view('admin.settings', compact('metaPixelId'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'meta_pixel_id' => 'nullable|string|max:50',
        ]);

        $pixelId = $request->input('meta_pixel_id');

        // Clean the pixel ID - only allow numbers
        if ($pixelId) {
            $pixelId = preg_replace('/[^0-9]/', '', $pixelId);
        }

        Setting::set('meta_pixel_id', $pixelId ?: null);

        return redirect()->route('admin.settings')->with('success', 'Configuración guardada exitosamente.');
    }
}
