<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Services\MenuCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicDataController extends Controller
{

    private const PUBLIC_SETTING_KEYS = [
        'business_name',
        'about_us',
        'address_line1',
        'address_line2',
        'address_line3',
        'map_url',
        'phone',
        'telefono',
        'whatsapp',
        'facebook',
        'instagram',
        'schedule_lunes',
        'schedule_martes',
        'schedule_miercoles',
        'schedule_jueves',
        'schedule_viernes',
        'schedule_sabado',
        'schedule_domingo',
    ];

    private const DAYS = [
        'schedule_lunes' => 'lunes',
        'schedule_martes' => 'martes',
        'schedule_miercoles' => 'miércoles',
        'schedule_jueves' => 'jueves',
        'schedule_viernes' => 'viernes',
        'schedule_sabado' => 'sábado',
        'schedule_domingo' => 'domingo',
    ];

    /**
     * Convierte lo que haya guardado en la columna `image` (puede ser
     * una URL absoluta de Cloudinary/Unsplash o una ruta relativa del
     * disco "public", p. ej. "news/archivo.jpg") en una URL absoluta
     * lista para usarse en la skill de Alexa o en el frontend.
     */
    private function resolveImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function info(): JsonResponse
    {
        $settings = Setting::query()
            ->whereIn('key', self::PUBLIC_SETTING_KEYS)
            ->pluck('value', 'key');

        $horarios = collect(self::DAYS)
            ->mapWithKeys(function (string $day, string $key) use ($settings) {
                return [$day => $settings->get($key)];
            })
            ->filter(fn ($value) => filled($value))
            ->all();

        $direccion = collect([
            $settings->get('address_line1'),
            $settings->get('address_line2'),
            $settings->get('address_line3'),
        ])
            ->filter(fn ($value) => filled($value))
            ->implode(', ');

        $horariosUnicos = array_values(
            array_unique(
                array_filter(array_values($horarios))
            )
        );

        $resumenHorario = null;

        if (count($horarios) === 7 && count($horariosUnicos) === 1) {
            $resumenHorario = 'Abrimos todos los días de '
                . $horariosUnicos[0]
                . '.';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $settings->get(
                    'business_name',
                    'La 501 Sports'
                ),

                'about_us' => $settings->get(
                    'about_us',
                    'Restaurante y sports bar donde la pasión por el deporte y la buena comida se unen.'
                ),

                'address' => $direccion,

                'map_url' => $settings->get('map_url'),

                'contact' => [
                    'phone' => $settings->get('phone')
                        ?? $settings->get('telefono'),

                    'whatsapp' => $settings->get('whatsapp'),
                    'facebook' => $settings->get('facebook'),
                    'instagram' => $settings->get('instagram'),
                ],

                'schedule' => $horarios,

                'schedule_summary' => $resumenHorario,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function categories(): JsonResponse
    {
        $categories = Product::query()
            ->where('available', true)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json([
            'success' => true,
            'total' => $categories->count(),
            'data' => $categories,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function products(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:100'],
            'available' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:300'],
        ]);

        // Los platillos del menú anterior se archivan: no se ofrecen ni como "agotados"
        $query = Product::query()
            ->whereNull('archived_at')
            ->with(['variants', 'optionGroups.options'])
            ->select([
                'id',
                'name',
                'description',
                'image',
                'price',
                'category',
                'subcategory',
                'section',
                'station',
                'presentation',
                'sort_order',
                'available',
            ]);

        if (!empty($validated['q'])) {
            $search = trim($validated['q']);

            $query->where(function ($productQuery) use ($search) {
                $productQuery
                    ->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (array_key_exists('available', $validated)) {
            $query->where(
                'available',
                filter_var(
                    $validated['available'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        // El menú completo pasa de 100 productos; 200 alcanza para traerlo en una sola llamada
        $limit = $validated['limit'] ?? 200;
        $catalog = app(MenuCatalog::class);

        $products = $query
            ->menuOrder()
            ->limit($limit)
            ->get()
            ->map(function (Product $product) use ($catalog) {
                $menu = $catalog->productPayload($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => $this->resolveImageUrl($product->image),
                    'price' => (float) $product->price,
                    'currency' => 'MXN',
                    'category' => $product->category,
                    'subcategory' => $product->subcategory,
                    'available' => (bool) $product->available,
                    // comidas | bebidas
                    'section' => $menu['section'],
                    // cocina | barra | ninguna (se entrega directo)
                    'station' => $menu['station'],
                    'presentation' => $product->presentation,
                    // Presentaciones con precio propio (vaso / litro, copeo / botella)
                    'variants' => $menu['variants'],
                    // Salsas, papas, etc. con min/max por platillo; mandar en el pedido como
                    // items[].options = [{id, quantity}] y items[].variant_id
                    'option_groups' => $menu['option_groups'],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'total' => $products->count(),
            'data' => $products,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function promotions(): JsonResponse
    {
        $today = now()->toDateString();

        $promotions = Promotion::query()
            ->with('products')
            ->where('active', 1)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->select([
                'id',
                'title',
                'description',
                'image',
                'price_text',
                'tag',
                'active',
                'end_date',
                'sellable_online',
            ])
            ->orderBy('title')
            ->get()
            ->map(function (Promotion $promotion) {
                return [
                    'id' => $promotion->id,
                    'title' => $promotion->title,
                    'description' => $promotion->description,
                    'image' => $this->resolveImageUrl($promotion->image),
                    // Texto de precio tal cual lo captura el admin (ej. "2x$150"),
                    // nunca lo inventamos en el cliente.
                    'price_label' => $promotion->price_text,
                    // Etiqueta libre (ej. "Solo jueves"), null si no se puso.
                    'badge' => $promotion->tag,
                    'active' => (bool) $promotion->active,
                    'end_date' => $promotion->end_date
                        ? (string) $promotion->end_date
                        : null,
                    // true = solo se puede aprovechar en el local, no es pedible en línea/app.
                    'store_only' => !$promotion->sellable_online,
                    // Lo que incluye. En los renglones con choice = true se manda
                    // choices[slot] = [product_id, ...] con "quantity" platillos al pedirla.
                    'includes' => $promotion->includesLabel(),
                    'slots' => collect($promotion->slotsPayload())->map(fn ($renglon) => [
                        ...$renglon,
                        'products' => array_map(fn ($p) => ['image' => $this->resolveImageUrl($p['image'])] + $p, $renglon['products']),
                    ])->all(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'total' => $promotions->count(),
            'data' => $promotions,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function news(): JsonResponse
    {
        $today = now()->toDateString();

        $news = News::query()
            ->where('active', 1)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->select([
                'title',
                'content',
                'image',
            ])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (News $newsItem) {
                return [
                    'title' => $newsItem->title,
                    'content' => $newsItem->content,
                    'image' => $this->resolveImageUrl($newsItem->image),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'total' => $news->count(),
            'data' => $news,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $search = trim($validated['q']);
        $today = now()->toDateString();

        $product = Product::query()
            ->select([
                'id',
                'name',
                'description',
                'image',
                'price',
                'category',
                'subcategory',
                'available',
            ])
            ->where(function ($query) use ($search) {
                $query
                    ->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%");
            })
            ->first();

        if ($product) {
            $availabilityMessage = $product->available
                ? 'Actualmente está disponible.'
                : 'Actualmente no está disponible.';

            return response()->json([
                'success' => true,
                'found' => true,
                'type' => 'product',

                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => $this->resolveImageUrl($product->image),
                    'price' => (float) $product->price,
                    'currency' => 'MXN',
                    'category' => $product->category,
                    'subcategory' => $product->subcategory,
                    'available' => (bool) $product->available,
                ],

                'speech' => sprintf(
                    '%s. %s Tiene un precio de %s pesos. %s',
                    $product->name,
                    $product->description ?: 'Es parte de nuestro menú.',
                    number_format((float) $product->price, 0),
                    $availabilityMessage
                ),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $promotion = Promotion::query()
            ->where('active', 1)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->where(function ($query) use ($search) {
                $query
                    ->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->select([
                'id',
                'title',
                'description',
                'image',
                'price_text',
                'tag',
                'active',
                'end_date',
                'sellable_online',
            ])
            ->first();

        if ($promotion) {
            return response()->json([
                'success' => true,
                'found' => true,
                'type' => 'promotion',

                'data' => [
                    'id' => $promotion->id,
                    'title' => $promotion->title,
                    'description' => $promotion->description,
                    'image' => $this->resolveImageUrl($promotion->image),
                    'price_label' => $promotion->price_text,
                    'badge' => $promotion->tag,
                    'active' => true,
                    'end_date' => $promotion->end_date
                        ? (string) $promotion->end_date
                        : null,
                    'store_only' => !$promotion->sellable_online,
                ],

                'speech' => $promotion->title
                    . '. '
                    . $promotion->description,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $newsItem = News::query()
            ->where('active', 1)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->where(function ($query) use ($search) {
                $query
                    ->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%");
            })
            ->select([
                'title',
                'content',
                'image',
            ])
            ->first();

        if ($newsItem) {
            return response()->json([
                'success' => true,
                'found' => true,
                'type' => 'news',

                'data' => [
                    'title' => $newsItem->title,
                    'content' => $newsItem->content,
                    'image' => $this->resolveImageUrl($newsItem->image),
                ],

                'speech' => $newsItem->title
                    . '. '
                    . $newsItem->content,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'success' => true,
            'found' => false,
            'type' => null,
            'data' => null,
            'speech' => 'No encontré información pública relacionada con '
                . $search
                . '.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}