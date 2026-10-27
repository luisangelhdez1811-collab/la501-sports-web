<?php

namespace App\Http\Controllers\Admin;

use App\Events\MenuUpdated;
use App\Http\Controllers\Controller;
use App\Models\OptionGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Listas de opciones del menú: salsas, papas, sabores, especialidades de "Arma tus Papas".
 * Cada lista se asigna a los platillos desde el formulario del platillo.
 */
class OptionGroupController extends Controller
{
    public function index()
    {
        $grupos = OptionGroup::with('options')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.menu-opciones', compact('grupos'));
    }

    public function store(Request $request)
    {
        $datos = $this->validateGroup($request);
        $grupo = OptionGroup::create($datos);
        $this->syncOptions($grupo, $request);

        event(new MenuUpdated());

        return back()->with('success', "Lista «{$grupo->name}» creada.");
    }

    public function update(Request $request, OptionGroup $grupo)
    {
        $grupo->update($this->validateGroup($request, $grupo));
        $this->syncOptions($grupo, $request);

        event(new MenuUpdated());

        return back()->with('success', "Lista «{$grupo->name}» guardada.");
    }

    public function destroy(OptionGroup $grupo)
    {
        $nombre = $grupo->name;
        $grupo->delete();

        event(new MenuUpdated());

        return back()->with('success', "Lista «{$nombre}» eliminada.");
    }

    private function validateGroup(Request $request, ?OptionGroup $grupo = null): array
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('option_groups', 'name')->ignore($grupo?->id)],
            'label' => 'required|string|max:150',
            'min_select' => 'required|integer|min:0|max:20',
            'max_select' => 'required|integer|min:1|max:20',
            'max_per_option' => 'required|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer',
            'options.*.name' => 'nullable|string|max:100',
            'options.*.detail' => 'nullable|string|max:100',
            'options.*.price' => 'nullable|numeric|min:0',
            'options.*.available' => 'nullable|boolean',
        ]);

        $datos['max_select'] = max($datos['max_select'], $datos['min_select']);
        $datos['sort_order'] ??= 0;

        return collect($datos)->except('options')->all();
    }

    /** Actualiza las opciones de la lista: las que quedan, las nuevas y las que se quitaron. */
    private function syncOptions(OptionGroup $grupo, Request $request): void
    {
        $vigentes = [];

        foreach (array_values($request->input('options', [])) as $i => $fila) {
            if (!filled($fila['name'] ?? null)) {
                continue;
            }

            $datos = [
                'name' => trim($fila['name']),
                'detail' => filled($fila['detail'] ?? null) ? trim($fila['detail']) : null,
                'price' => (float) ($fila['price'] ?? 0),
                'available' => !empty($fila['available']),
                'sort_order' => $i + 1,
            ];

            $opcion = !empty($fila['id']) ? $grupo->options()->find($fila['id']) : null;
            if ($opcion) {
                $opcion->update($datos);
            } else {
                $opcion = $grupo->options()->create($datos);
            }
            $vigentes[] = $opcion->id;
        }

        $grupo->options()->whereNotIn('id', $vigentes)->delete();
    }
}
