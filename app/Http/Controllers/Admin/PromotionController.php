<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\Promotion;
use App\Models\PromoSubscriber;
use App\Models\Product;
class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::with('products')->latest()->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'category', 'subcategory', 'price']);
        return view('admin.promotions.index', compact('promotions', 'products'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tag' => 'nullable|string|max:50',
            'price_text' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'icon' => 'nullable|string|max:20',
            'color_gradient' => 'nullable|string',
            'end_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'products' => 'nullable|array',
            'products.*.id' => 'required_with:products|integer|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
            'days_active' => 'nullable|array',
            'days_active.*' => 'in:domingo,lunes,martes,miercoles,jueves,viernes,sabado',
            'sellable_online' => 'nullable|boolean',
        ]);
        $data = $request->except(['image', 'products', 'days_active']);
        $data['active'] = 1;
        $data['sellable_online'] = $request->boolean('sellable_online');
        $data['days_active'] = !empty($request->days_active) ? implode(',', $request->days_active) : null;
        if ($request->hasFile('image')) {
            try {
                $data['image'] = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }
        $promotion = Promotion::create($data);
        $this->syncProducts($promotion, $request->input('products', []));
        $this->notifySubscribers($promotion, esNueva: true);
        return back()->with('success', '¡Promoción creada con éxito!');
    }
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tag' => 'nullable|string|max:50',
            'price_text' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'icon' => 'nullable|string|max:20',
            'color_gradient' => 'nullable|string',
            'end_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'products' => 'nullable|array',
            'products.*.id' => 'required_with:products|integer|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
            'days_active' => 'nullable|array',
            'days_active.*' => 'in:domingo,lunes,martes,miercoles,jueves,viernes,sabado',
            'sellable_online' => 'nullable|boolean',
        ]);
        $data = $request->except(['image', 'products', 'days_active']);
        $data['sellable_online'] = $request->boolean('sellable_online');
        $data['days_active'] = !empty($request->days_active) ? implode(',', $request->days_active) : null;
        if ($request->hasFile('image')) {
            try {
                if ($promotion->image && !str_starts_with($promotion->image, 'http') && Storage::disk('public')->exists($promotion->image)) {
                    Storage::disk('public')->delete($promotion->image);
                }
                $data['image'] = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }
        $promotion->update($data);
        $this->syncProducts($promotion, $request->input('products', []));
        $this->notifySubscribers($promotion, esNueva: false);
        return back()->with('success', '¡Promoción actualizada con éxito!');
    }
    public function destroy($id)
    {
        Promotion::destroy($id);
        return back()->with('success', 'Promoción eliminada');
    }

    private function syncProducts(Promotion $promotion, array $products): void
    {
        $syncData = [];
        foreach ($products as $p) {
            if (!empty($p['id'])) {
                $syncData[(int) $p['id']] = ['quantity' => max(1, (int) ($p['quantity'] ?? 1))];
            }
        }
        $promotion->products()->sync($syncData);
    }

    private function notifySubscribers(Promotion $promotion, bool $esNueva): void
    {
        $subscribers = PromoSubscriber::all();

        foreach ($subscribers as $subscriber) {
            try {
                Mail::send('emails.promotion_notification', [
                    'promotion' => $promotion,
                    'esNueva' => $esNueva,
                    'unsubscribeToken' => $subscriber->unsubscribe_token,
                ], function ($message) use ($subscriber, $promotion, $esNueva) {
                    $message->to($subscriber->email)->subject(
                        ($esNueva ? 'Nueva promoción: ' : 'Promoción actualizada: ') . $promotion->title
                    );
                });
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar el correo de promoción a ' . $subscriber->email . ': ' . $e->getMessage());
            }
        }
    }
}