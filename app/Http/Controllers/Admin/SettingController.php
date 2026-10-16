<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $logo = Setting::where('key', 'logo')->first();
        $map_url = Setting::where('key', 'map_url')->first();
        $address_line1 = Setting::where('key', 'address_line1')->first();
        $address_line2 = Setting::where('key', 'address_line2')->first();
        $address_line3 = Setting::where('key', 'address_line3')->first();
        
        $schedule = [
            'domingo' => Setting::where('key', 'schedule_domingo')->value('value'),
            'lunes' => Setting::where('key', 'schedule_lunes')->value('value'),
            'martes' => Setting::where('key', 'schedule_martes')->value('value'),
            'miercoles' => Setting::where('key', 'schedule_miercoles')->value('value'),
            'jueves' => Setting::where('key', 'schedule_jueves')->value('value'),
            'viernes' => Setting::where('key', 'schedule_viernes')->value('value'),
            'sabado' => Setting::where('key', 'schedule_sabado')->value('value'),
        ];
        
        $carouselSlides = \App\Models\CarouselSlide::orderBy('order')->get();
        
        return view('admin.settings.index', compact('logo', 'map_url', 'address_line1', 'address_line2', 'address_line3', 'schedule', 'carouselSlides'));
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $setting = Setting::firstOrCreate(['key' => 'logo']);

        if ($request->hasFile('logo')) {
            try {
                if ($setting->value && str_starts_with($setting->value, 'logos/') && Storage::disk('public')->exists($setting->value)) {
                    Storage::disk('public')->delete($setting->value);
                }

                $url = \App\Services\CloudinaryService::upload($request->file('logo'));
                $setting->update(['value' => $url]);
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir el logo a Cloudinary: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Logo actualizado con éxito.');
    }

    public function updateMap(Request $request)
    {
        $request->validate([
            'map_url' => 'required|string|url'
        ]);

        Setting::updateOrCreate(
            ['key' => 'map_url'],
            ['value' => $request->map_url]
        );

        return back()->with('success', 'Mapa actualizado con éxito.');
    }

    public function updateAddress(Request $request)
    {
        $request->validate([
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'address_line3' => 'nullable|string|max:255',
        ]);

        Setting::updateOrCreate(['key' => 'address_line1'], ['value' => $request->address_line1]);
        Setting::updateOrCreate(['key' => 'address_line2'], ['value' => $request->address_line2]);
        Setting::updateOrCreate(['key' => 'address_line3'], ['value' => $request->address_line3]);

        return back()->with('success', 'Dirección actualizada con éxito.');
    }

    public function updateSchedule(Request $request)
    {
        $days = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        
        foreach ($days as $day) {
            Setting::updateOrCreate(
                ['key' => 'schedule_' . $day],
                ['value' => $request->input('schedule_' . $day)]
            );
        }

        return back()->with('success', 'Horarios actualizados con éxito.');
    }
}
