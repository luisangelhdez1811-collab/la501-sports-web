<?php

namespace App\Http\Controllers\Admin;

use App\Events\MenuUpdated;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Ingrediente;
use App\Traits\CsvExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    use CsvExporter;

    // =========================================================================
    // VISUALIZACIÓN
    // =========================================================================

    public function index()
    {
        // Traemos los productos con sus ingredientes para tu lógica de visualización/edición
        $products = Product::with('ingredientes')->get();
        // Traemos el inventario para la nueva lógica de tu compañero
        $inventario = \App\Models\Inventory::select('id', 'name')->get();
        
        return view('admin.menu', compact('products', 'inventario'));
    }

    // =========================================================================
    // CRUD ORIGINAL (Tus métodos restaurados)
    // =========================================================================

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $imagePath = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'image' => $imagePath,
            'available' => $request->has('available') ? 1 : 0,
        ]);

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

        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

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

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'available' => $request->has('available') ? 1 : 0,
        ]);

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

    public function importCSV(Request $request)
    {
        $request->validate(['csv_file' => 'required|mimes:csv,txt|max:2048']);
        $file = fopen($request->file('csv_file')->getRealPath(), 'r');
        $isFirstRow = true;

        while (($data = fgetcsv($file, 2000, ",")) !== false) {
            if ($isFirstRow || !isset($data[0]) || trim($data[0]) === '') {
                $isFirstRow = false;
                continue;
            }

            $disponible = (isset($data[5]) && strtolower(trim($data[5])) === 'no') ? 0 : 1;

            Product::updateOrCreate(
                ['name' => trim($data[0])],
                [
                    'price'       => (float) ($data[1] ?? 0),
                    'category'    => trim($data[2] ?? 'General'),
                    'subcategory' => trim($data[3] ?? ''),
                    'description' => trim($data[4] ?? ''),
                    'available'   => $disponible,
                ]
            );
        }
        fclose($file);
        
        event(new MenuUpdated());
        
        return back()->with('success', '¡Menú actualizado con éxito!');
    }
}
