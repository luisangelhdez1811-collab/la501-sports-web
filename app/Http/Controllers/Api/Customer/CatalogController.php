<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

    public function show(Product $product): JsonResponse
    {
        $product->load('ingredientes');

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

        $mlService = app(MlEngineService::class);
        $result = $mlService->getRecommendationsForCustomer(auth('sanctum')->user(), $cartItemIds);

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
