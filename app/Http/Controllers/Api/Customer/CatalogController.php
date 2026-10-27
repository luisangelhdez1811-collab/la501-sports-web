<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\MenuCatalog;
use App\Services\MlEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
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

    public function show(Product $product, MenuCatalog $catalog): JsonResponse
    {
        $menu = $catalog->productPayload($product);

        return response()->json([
            'success' => true,
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
                'ingredients' => $product->ingredientes->pluck('nombre')->values(),
                'section' => $menu['section'],
                'station' => $menu['station'],
                'presentation' => $product->presentation,
                'variants' => $menu['variants'],
                'option_groups' => $menu['option_groups'],
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $cartItemIds = collect(explode(',', (string) $request->query('cart_ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        // Oposición (ARCO): si el cliente apagó "Recomendaciones según mis pedidos", su
        // historial no se usa y recibe las mismas sugerencias que un visitante.
        $user = auth('sanctum')->user();
        if ($user && !($user->recomendaciones_personalizadas ?? true)) {
            $user = null;
        }

        $mlService = app(MlEngineService::class);
        $result = $mlService->getRecommendationsForCustomer($user, $cartItemIds);

        $result['products'] = collect($result['products'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image' => $this->resolveImageUrl($product->image),
                'price' => (float) $product->price,
                'currency' => 'MXN',
                'category' => $product->category,
                'subcategory' => $product->subcategory,
                'available' => (bool) $product->available,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
