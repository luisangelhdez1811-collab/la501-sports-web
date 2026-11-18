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
use App\Services\AlcoholPolicy;
class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::with('products')->latest()->get();
        $products = Product::whereNull('archived_at')->orderBy('name')->get(['id', 'name', 'category', 'subcategory', 'price']);
        return view('admin.promotions.index', compact('promotions', 'products'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'tag' => 'nullable|string|max:50',
            'price_text' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'icon' => 'nullable|string|max:20',
            'color_gradient' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9 :\/\[\]#._-]*$/i'],
            // B-27: toda promoción dice hasta cuándo vale y sus condiciones (LFPC art. 46 a 48)
            'end_date' => 'required|date|after_or_equal:today',
            'conditions' => 'required|string|max:500',
            'alcoholic' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_source' => 'nullable|required_with:image|string|max:150',
            'products' => 'nullable|array',
            'products.*.id' => 'required_with:products|integer|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.slot' => 'nullable|integer|min:0',
            'days_active' => 'nullable|array',
            'days_active.*' => 'in:domingo,lunes,martes,miercoles,jueves,viernes,sabado',
            'sellable_online' => 'nullable|boolean',
        ]);
        if ($error = $this->publicidadDeAlcoholInvalida($request)) {
            return back()->withInput()->withErrors(['description' => $error]);
        }
        $data = $request->except(['image', 'products', 'days_active']);
        $data['active'] = 1;
        $data['alcoholic'] = $request->boolean('alcoholic');
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
            'description' => 'required|string|max:2000',
            'tag' => 'nullable|string|max:50',
            'price_text' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'icon' => 'nullable|string|max:20',
            'color_gradient' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9 :\/\[\]#._-]*$/i'],
            // B-27: toda promoción dice hasta cuándo vale y sus condiciones (LFPC art. 46 a 48)
            'end_date' => 'required|date|after_or_equal:today',
            'conditions' => 'required|string|max:500',
            'alcoholic' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_source' => 'nullable|required_with:image|string|max:150',
            'products' => 'nullable|array',
            'products.*.id' => 'required_with:products|integer|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.slot' => 'nullable|integer|min:0',
            'days_active' => 'nullable|array',
            'days_active.*' => 'in:domingo,lunes,martes,miercoles,jueves,viernes,sabado',
            'sellable_online' => 'nullable|boolean',
        ]);
        if ($error = $this->publicidadDeAlcoholInvalida($request)) {
            return back()->withInput()->withErrors(['description' => $error]);
        }
        $data = $request->except(['image', 'products', 'days_active']);
        if (!$request->filled('image_source')) {
            unset($data['image_source']); // sin imagen nueva se conserva el origen que tenía
        }
        $data['alcoholic'] = $request->boolean('alcoholic');
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

    /**
     * B-25 (LGS art. 308): una promoción con alcohol solo informa. No puede invitar a consumir con
     * imperativos ni asociar la bebida con deportes. Revisa los textos que ve el cliente.
     */
    private function publicidadDeAlcoholInvalida(Request $request): ?string
    {
        $ids = collect($request->input('products', []))->pluck('id')->filter();
        $conAlcohol = $request->boolean('alcoholic')
            || ($ids->isNotEmpty() && Product::whereIn('id', $ids)->where('alcoholic', true)->exists());

        if (!$conAlcohol) {
            return null;
        }

        $texto = implode(' ', array_filter([
            $request->input('title'), $request->input('description'), $request->input('tag'),
            $request->input('price_text'), $request->input('conditions'),
        ]));
        $frases = AlcoholPolicy::forbiddenPhrases($texto);

        return $frases === [] ? null : 'Esta promoción lleva alcohol y la ley (Ley General de Salud, art. 308) no permite invitar a '
            . 'consumirlo ni relacionarlo con deportes. Cambia: «' . implode('», «', $frases) . '».';
    }

    /**
     * Cada renglón (slot) es un platillo fijo o, si tiene varios, los que el cliente puede
     * elegir; la cantidad es la del renglón. Sin slot (formularios anteriores), cada
     * platillo es su propio renglón.
     */
    private function syncProducts(Promotion $promotion, array $products): void
    {
        $syncData = [];
        foreach (array_values($products) as $i => $p) {
            if (!empty($p['id'])) {
                $syncData[(int) $p['id']] = [
                    'quantity' => max(1, (int) ($p['quantity'] ?? 1)),
                    'slot' => isset($p['slot']) ? (int) $p['slot'] : $i,
                ];
            }
        }
        $promotion->products()->sync($syncData);
    }

    private function notifySubscribers(Promotion $promotion, bool $esNueva): void
    {
        // Solo quien confirmó su suscripción (doble opt-in)
        $subscribers = PromoSubscriber::confirmed()->get();

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