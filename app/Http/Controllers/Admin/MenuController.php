<?php

namespace App\Http\Controllers\Admin;

use App\Events\MenuUpdated;
use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\Ingrediente;
use App\Services\MenuExcelImporter;
use App\Traits\CsvExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    use CsvExporter;

    // =========================================================================
    // VISUALIZACIÓN
    // =========================================================================

    public function index()
    {
        // Solo el menú vigente: lo archivado (menú anterior) se conserva para el historial de ventas
        $products = Product::with(['ingredientes', 'variants', 'optionGroups'])
            ->whereNull('archived_at')
            ->menuOrder()
            ->get();
        // Traemos el inventario para la nueva lógica de tu compañero
        $inventario = \App\Models\Inventory::select('id', 'name')->get();
        $grupos = OptionGroup::withCount('options')->orderBy('sort_order')->orderBy('name')->get();
        $categorias = MenuCategory::ordered()->get(['id', 'name', 'section']);

        return view('admin.menu', compact('products', 'inventario', 'grupos', 'categorias'));
    }

    // =========================================================================
    // CRUD ORIGINAL (Tus métodos restaurados)
    // =========================================================================

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            // Las categorías se administran en «Categorías»; la sección sale de la categoría
            'category' => ['required', 'string', Rule::exists('menu_categories', 'name')],
            'subcategory' => 'nullable|string|max:255',
            'station' => ['required', Rule::in(Product::STATIONS)],
            'presentation' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'variants' => 'nullable|array',
            'variants.*.name' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'groups' => 'nullable|array',
            'groups.*.on' => 'nullable|boolean',
            'groups.*.min' => 'nullable|integer|min:0|max:20',
            'groups.*.max' => 'nullable|integer|min:0|max:20',
        ]);
    }

    private function productAttributes(Request $request, array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'category' => $validated['category'],
            'subcategory' => filled($validated['subcategory'] ?? null) ? trim($validated['subcategory']) : null,
            'section' => MenuCategory::where('name', $validated['category'])->value('section') ?? Product::SECTION_FOOD,
            'station' => $validated['station'],
            'presentation' => $validated['presentation'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'available' => $request->has('available') ? 1 : 0,
        ];
    }

    /** Presentaciones (vaso / litro) y grupos de opciones (salsas, papas...) del platillo. */
    private function syncMenuOptions(Product $product, array $validated): void
    {
        $variantes = collect($validated['variants'] ?? [])
            ->filter(fn ($v) => filled($v['name'] ?? null) && is_numeric($v['price'] ?? null))
            ->values();

        $product->variants()->delete();
        foreach ($variantes as $i => $variante) {
            $product->variants()->create([
                'name' => trim($variante['name']),
                'price' => $variante['price'],
                'sort_order' => $i + 1,
            ]);
        }

        // Con presentaciones, el precio de la carta es el de la más barata ("Desde $80")
        if ($variantes->isNotEmpty()) {
            $product->update(['price' => $variantes->min('price')]);
        }

        $sync = [];
        $orden = 1;
        foreach ($validated['groups'] ?? [] as $groupId => $config) {
            if (empty($config['on'])) {
                continue;
            }
            $min = (int) ($config['min'] ?? 0);
            $sync[(int) $groupId] = [
                'min_select' => $min,
                'max_select' => max($min, (int) ($config['max'] ?? 1)),
                'sort_order' => $orden++,
            ];
        }
        $product->optionGroups()->sync($sync);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $imagePath = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }

        $product = Product::create($this->productAttributes($request, $validated) + ['image' => $imagePath]);
        $this->syncMenuOptions($product, $validated);

        if ($request->ingredients && is_array($request->ingredients)) {
            foreach ($request->ingredients as $item) {
                if (!empty($item)) {
                    Ingrediente::create([
                        'product_id' => $product->id,
                        'nombre'     => $item
                    ]);
                }
            }
        }

        // Opcional: Notificar a los clientes que el menú cambió (Lógica de tu compañero)
        event(new MenuUpdated());

        return back()->with('success', 'Platillo guardado correctamente');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $this->validateProduct($request);

        if ($request->hasFile('image')) {
            try {
                if ($product->image && !str_starts_with($product->image, 'http') && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->image = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }

        $product->update($this->productAttributes($request, $validated));
        $this->syncMenuOptions($product, $validated);

        if ($request->ingredients) {
            $product->ingredientes()->delete();
            foreach ($request->ingredients as $item) {
                if (!empty($item)) {
                    Ingrediente::create([
                        'product_id' => $product->id,
                        'nombre' => $item
                    ]);
                }
            }
        }

        event(new MenuUpdated());

        return back()->with('success', 'Platillo actualizado correctamente');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        if ($product->image && !str_starts_with($product->image, 'http') && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        
        event(new MenuUpdated());

        return back()->with('success', 'Platillo eliminado correctamente');
    }

    public function apiProducts()
    {
        $products = Product::where('available', 1)->get();
        return response()->json($products);
    }

    // =========================================================================
    // IMPORTACIÓN / EXPORTACIÓN CSV (Métodos de tu compañero)
    // =========================================================================

    public function exportCSV()
    {
        $fileName = 'Menu_La501_' . date('Y-m-d') . '.csv';
        $headers = $this->getCsvHeaders($fileName);

        $callback = function () {
            $file = fopen('php://output', 'w');
            $this->insertBom($file);

            fputcsv($file, ['Nombre', 'Precio', 'Categoria', 'Subcategoria', 'Descripcion', 'Disponible'], ',');

            Product::all()->each(function ($product) use ($file) {
                fputcsv($file, [
                    $product->name,
                    $product->price,
                    $product->category,
                    $product->subcategory ?? '', // Añadido ?? '' por si subcategory no existe en la BD
                    $product->description,
                    $product->available ? 'Si' : 'No',
                ], ',');
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $fileName = 'Plantilla_Menu_La501.csv';
        $headers = $this->getCsvHeaders($fileName);

        $callback = function () {
            $file = fopen('php://output', 'w');
            $this->insertBom($file);
            fputcsv($file, ['Nombre', 'Precio', 'Categoria', 'Subcategoria', 'Descripcion', 'Disponible'], ',');
            fputcsv($file, ['Hamburguesa Clasica', '120.50', 'Hamburguesas', '', 'Carne de res, queso, lechuga y tomate', 'Si'], ',');
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCSV(Request $request, MenuExcelImporter $importer)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt,xlsx|max:5120']);

        // El Excel de captura (Comidas, Bebidas, Extras y Salsas) carga el menú completo
        if (strtolower($request->file('csv_file')->getClientOriginalExtension()) === 'xlsx') {
            try {
                $reporte = $importer->import($request->file('csv_file')->getRealPath());
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            event(new MenuUpdated());

            return back()->with('success', sprintf(
                '¡Menú importado! %d nuevos, %d actualizados y %d del menú anterior archivados.',
                count($reporte['created']), count($reporte['updated']), count($reporte['archived'])
            ));
        }

        $file = fopen($request->file('csv_file')->getRealPath(), 'r');
        $isFirstRow = true;

        while (($data = fgetcsv($file, 2000, ",")) !== false) {
            if ($isFirstRow || !isset($data[0]) || trim($data[0]) === '') {
                $isFirstRow = false;
                continue;
            }

            $disponible = (isset($data[5]) && strtolower(trim($data[5])) === 'no') ? 0 : 1;

            $product = Product::updateOrCreate(
                ['name' => trim($data[0])],
                [
                    'price'       => (float) ($data[1] ?? 0),
                    'category'    => trim($data[2] ?? 'General'),
                    'subcategory' => trim($data[3] ?? ''),
                    'description' => trim($data[4] ?? ''),
                    'available'   => $disponible,
                ]
            );
            MenuCategory::ensure($product->category, $product->section ?: Product::SECTION_FOOD);
        }
        fclose($file);
        
        event(new MenuUpdated());
        
        return back()->with('success', '¡Menú actualizado con éxito!');
    }
}
