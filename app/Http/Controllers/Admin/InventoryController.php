<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Traits\CsvExporter;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use CsvExporter;

    // =========================================================================
    // VISUALIZACIÓN PRINCIPAL
    // =========================================================================

    public function index()
    {
        $items = Inventory::orderBy('name', 'asc')->get();
        $totalItems = $items->count();
        $lowStockCount = $items->filter(fn($item) => $item->current_stock <= $item->min_stock)->count();

        return view('admin.inventory.index', compact('items', 'totalItems', 'lowStockCount'));
    }

    // =========================================================================
    // CRUD ORIGINAL RESTAURADO
    // =========================================================================

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'category'      => 'required|string|max:255',
            'current_stock' => 'required|numeric|min:0',
            'min_stock'     => 'required|numeric|min:0',
            'unit'          => 'required|string|max:50',
        ]);

        Inventory::create([
            'name'          => trim($request->name),
            'category'      => trim($request->category),
            'current_stock' => $request->current_stock,
            'min_stock'     => $request->min_stock,
            'unit'          => trim($request->unit),
        ]);

        return back()->with('success', 'Artículo agregado al inventario exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $item = Inventory::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'category'      => 'required|string|max:255',
            'current_stock' => 'required|numeric|min:0',
            'min_stock'     => 'required|numeric|min:0',
            'unit'          => 'required|string|max:50',
        ]);

        $item->update([
            'name'          => trim($request->name),
            'category'      => trim($request->category),
            'current_stock' => $request->current_stock,
            'min_stock'     => $request->min_stock,
            'unit'          => trim($request->unit),
        ]);

        return back()->with('success', 'Artículo actualizado correctamente.');
    }

    public function adjust(Request $request, $id)
    {
        $item = Inventory::findOrFail($id);

        // Validamos que se envíe la cantidad y qué tipo de acción es (sumar o restar)
        $request->validate([
            'adjustment' => 'required|numeric|min:0.01',
            'action'     => 'required|in:add,subtract'
        ]);

        if ($request->action === 'add') {
            $item->current_stock += $request->adjustment;
        } else {
            $item->current_stock -= $request->adjustment;
            
            // Evitamos que el stock quede en números negativos
            if ($item->current_stock < 0) {
                $item->current_stock = 0;
            }
        }

        $item->save();

        return back()->with('success', 'Stock actualizado correctamente.');
    }

    public function destroy($id)
    {
        $item = Inventory::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Artículo eliminado del inventario.');
    }

    // =========================================================================
    // IMPORTACIÓN / EXPORTACIÓN CSV (Lógica de tu compañero)
    // =========================================================================

    public function exportCSV()
    {
        $fileName = 'Inventario_La501_' . date('Y-m-d') . '.csv';
        $headers = $this->getCsvHeaders($fileName);

        $callback = function () {
            $file = fopen('php://output', 'w');
            $this->insertBom($file);
            fputcsv($file, ['Nombre', 'Categoria', 'Stock Actual', 'Stock Minimo', 'Unidad'], ',');

            Inventory::orderBy('name', 'asc')->each(function ($item) use ($file) {
                fputcsv($file, [
                    $item->name,
                    $item->category,
                    $item->current_stock,
                    $item->min_stock,
                    $item->unit,
                ], ',');
            });
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $fileName = 'Plantilla_Inventario_La501.csv';
        $headers = $this->getCsvHeaders($fileName);

        $callback = function () {
            $file = fopen('php://output', 'w');
            $this->insertBom($file);
            fputcsv($file, ['Nombre', 'Categoria', 'StockActual', 'StockMinimo', 'Unidad'], ',');
            fputcsv($file, ['Carne de Res Premium', 'Carnes', '25.5', '5.0', 'kg'], ',');
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCSV(Request $request)
    {
        $request->validate(['csv_file' => 'required|mimes:csv,txt']);
        $file = fopen($request->file('csv_file')->getRealPath(), 'r');
        $isFirstRow = true;

        while (($data = fgetcsv($file, 2000, ",")) !== false) {
            if ($isFirstRow || empty($data[0])) {
                $isFirstRow = false;
                continue;
            }

            Inventory::updateOrCreate(
                ['name' => trim($data[0])],
                [
                    'category'      => trim($data[1] ?? 'Otros'),
                    'current_stock' => (float) ($data[2] ?? 0),
                    'min_stock'     => (float) ($data[3] ?? 0),
                    'unit'          => trim($data[4] ?? 'pz'),
                ]
            );
        }
        fclose($file);
        return back()->with('success', '¡Inventario actualizado correctamente!');
    }
}
