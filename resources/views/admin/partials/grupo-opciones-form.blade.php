{{-- Formulario de una lista de opciones (nueva si $grupo es null) --}}
@php
    $opciones = $grupo
        ? $grupo->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'detail' => $o->detail, 'price' => $o->price, 'available' => $o->available])->values()
        : collect([['id' => null, 'name' => '', 'detail' => '', 'price' => 0, 'available' => true]]);
    $prefijo = $grupo ? 'g' . $grupo->id : 'nuevo';
    $campo = 'w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/40';
@endphp

<form action="{{ $grupo ? route('admin.menu.options.update', $grupo) : route('admin.menu.options.store') }}" method="POST"
      x-data="{ opciones: @js($opciones) }"
      class="{{ $grupo ? '' : 'bg-white dark:bg-[#111111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 shadow-sm' }} space-y-4">
    @csrf
    @if($grupo) @method('PUT') @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="{{ $prefijo }}-name" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Nombre corto (lo ve cocina)</label>
            <input id="{{ $prefijo }}-name" name="name" required maxlength="100" value="{{ $grupo->name ?? '' }}" placeholder="Ej: Salsa" class="{{ $campo }}">
        </div>
        <div>
            <label for="{{ $prefijo }}-label" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Texto para el cliente</label>
            <input id="{{ $prefijo }}-label" name="label" required maxlength="150" value="{{ $grupo->label ?? '' }}" placeholder="Ej: Elige tu salsa" class="{{ $campo }}">
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div>
            <label for="{{ $prefijo }}-min" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Mínimo</label>
            <input id="{{ $prefijo }}-min" type="number" name="min_select" min="0" max="20" required value="{{ $grupo->min_select ?? 0 }}" class="{{ $campo }}">
        </div>
        <div>
            <label for="{{ $prefijo }}-max" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Máximo</label>
            <input id="{{ $prefijo }}-max" type="number" name="max_select" min="1" max="20" required value="{{ $grupo->max_select ?? 1 }}" class="{{ $campo }}">
        </div>
        <div>
            <label for="{{ $prefijo }}-rep" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Repetir la misma</label>
            <input id="{{ $prefijo }}-rep" type="number" name="max_per_option" min="1" max="10" required value="{{ $grupo->max_per_option ?? 1 }}" class="{{ $campo }}">
        </div>
        <div>
            <label for="{{ $prefijo }}-sort" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Orden</label>
            <input id="{{ $prefijo }}-sort" type="number" name="sort_order" min="0" value="{{ $grupo->sort_order ?? 0 }}" class="{{ $campo }}">
        </div>
    </div>
    <p class="text-[11px] text-zinc-500">Mínimo y máximo son los que se proponen al asignar la lista a un platillo; cada platillo puede cambiarlos (alitas de 350 g: 1 salsa, de 700 g: 2).</p>

    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">Opciones</p>
        <div class="space-y-2">
            <template x-for="(op, idx) in opciones" :key="idx">
                <div class="flex flex-wrap sm:flex-nowrap gap-2 items-center">
                    <input type="hidden" :name="'options[' + idx + '][id]'" :value="op.id">
                    <input :name="'options[' + idx + '][name]'" x-model="op.name" placeholder="Nombre (Ej: Buffalo)" aria-label="Nombre de la opción" class="{{ $campo }} sm:flex-[2]">
                    <input :name="'options[' + idx + '][detail]'" x-model="op.detail" placeholder="Detalle (Ej: Picor 2 de 5)" aria-label="Detalle de la opción" class="{{ $campo }} sm:flex-[2]">
                    <input type="number" step="0.01" min="0" :name="'options[' + idx + '][price]'" x-model="op.price" placeholder="+$" aria-label="Precio extra" class="{{ $campo }} sm:w-24 sm:flex-none">
                    <label class="flex items-center gap-1.5 text-xs font-bold text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                        <input type="checkbox" :name="'options[' + idx + '][available]'" value="1" x-model="op.available" class="accent-green-600">
                        Disponible
                    </label>
                    <button type="button" @click="opciones.splice(idx, 1)" class="text-zinc-400 hover:text-red-500 text-xl px-1" aria-label="Quitar opción">&times;</button>
                </div>
            </template>
        </div>
        <button type="button" @click="opciones.push({ id: null, name: '', detail: '', price: 0, available: true })"
                class="mt-2 text-xs font-bold uppercase tracking-wider text-green-600 hover:underline">+ Agregar opción</button>
    </div>

    <div class="text-right">
        <button type="submit" class="px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-sm font-bold transition">
            {{ $grupo ? 'Guardar cambios' : 'Crear lista' }}
        </button>
    </div>
</form>
