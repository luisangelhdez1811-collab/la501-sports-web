<?php

namespace App\Http\Controllers\Admin;

use App\Events\MenuUpdated;
use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Categorías de la carta: agregar una nueva antes de capturar sus platillos, renombrarla,
 * cambiar su orden o quitarla (pasando sus platillos a otra).
 */
class MenuCategoryController extends Controller
{
    public function index()
    {
        $categorias = MenuCategory::withCount('products')->ordered()->get();

        return view('admin.menu-categorias', compact('categorias'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('menu_categories', 'name')],
            'section' => ['required', Rule::in(Product::SECTIONS)],
        ]);

        $categoria = MenuCategory::create([
            'name' => trim($datos['name']),
            'section' => $datos['section'],
            'sort_order' => (int) MenuCategory::where('section', $datos['section'])->max('sort_order') + 1,
        ]);

        return back()->with('success', "Categoría «{$categoria->name}» agregada. Ya puedes elegirla al crear un platillo.");
    }

    /** Renombrar o cambiar de sección también actualiza sus platillos. */
    public function update(Request $request, MenuCategory $categoria)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('menu_categories', 'name')->ignore($categoria->id)],
            'section' => ['required', Rule::in(Product::SECTIONS)],
        ]);

        DB::transaction(function () use ($categoria, $datos) {
            Product::where('category', $categoria->name)->update([
                'category' => trim($datos['name']),
                'section' => $datos['section'],
            ]);
            $categoria->update(['name' => trim($datos['name']), 'section' => $datos['section']]);
        });

        event(new MenuUpdated());

        return back()->with('success', "Categoría «{$categoria->name}» guardada.");
    }

    /** Sube o baja la categoría un lugar dentro de su sección. */
    public function move(Request $request, MenuCategory $categoria)
    {
        $direccion = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        $lista = MenuCategory::where('section', $categoria->section)->ordered()->get()->values();
        $actual = $lista->search(fn (MenuCategory $c) => $c->id === $categoria->id);
        $vecino = $lista->get($direccion === 'up' ? $actual - 1 : $actual + 1);

        if ($vecino) {
            // Se renumera la sección completa para que no haya empates en el orden
            $lista->splice($actual, 1);
            $lista->splice($direccion === 'up' ? $actual - 1 : $actual + 1, 0, [$categoria]);
            DB::transaction(fn () => $lista->each(fn (MenuCategory $c, int $i) => $c->update(['sort_order' => $i + 1])));
            event(new MenuUpdated());
        }

        return back();
    }

    /** Si tiene platillos, se pasan a la categoría elegida antes de quitarla. */
    public function destroy(Request $request, MenuCategory $categoria)
    {
        $platillos = Product::where('category', $categoria->name)->whereNull('archived_at')->count();
        $destino = null;

        if ($platillos > 0) {
            $destinoId = $request->validate([
                'move_to' => ['required', 'integer', Rule::exists('menu_categories', 'id'), Rule::notIn([$categoria->id])],
            ], [
                'move_to.required' => "«{$categoria->name}» tiene {$platillos} platillo(s): elige a qué categoría pasarlos.",
            ])['move_to'];
            $destino = MenuCategory::findOrFail($destinoId);
        }

        DB::transaction(function () use ($categoria, $destino) {
            if ($destino) {
                Product::where('category', $categoria->name)->whereNull('archived_at')
                    ->update(['category' => $destino->name, 'section' => $destino->section]);
            }
            $categoria->delete();
        });

        event(new MenuUpdated());

        return back()->with('success', $destino
            ? "Categoría «{$categoria->name}» eliminada; sus platillos pasaron a «{$destino->name}»."
            : "Categoría «{$categoria->name}» eliminada.");
    }
}
