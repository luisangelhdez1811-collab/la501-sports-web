<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\CheckoutCompliance;
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

        // Texto del envío que ve el cliente al pedir (B-11); si no hay uno guardado, el que se usa por defecto
        $deliveryFeeNote = CheckoutCompliance::deliveryNote();

        return view('admin.settings.index', compact('logo', 'map_url', 'address_line1', 'address_line2', 'address_line3', 'schedule', 'carouselSlides', 'deliveryFeeNote'));
    }

    /** B-11: «Costo del envío a domicilio». Texto obligatorio, máximo 500 caracteres. */
    public function updateDelivery(Request $request)
    {
        $validated = $request->validate([
            'delivery_fee_note' => ['required', 'string', 'max:500'],
        ], [
            'delivery_fee_note.required' => 'Escribe cuánto cuesta el envío y a quién se le paga. Es obligatorio para el cliente.',
            'delivery_fee_note.max' => 'El texto del envío puede tener máximo 500 caracteres.',
        ]);

        Setting::updateOrCreate(['key' => 'delivery_fee_note'], ['value' => trim($validated['delivery_fee_note'])]);

        return back()->with('success', 'Costo del envío actualizado. Ya lo ven los clientes en la web y en la app.');
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
