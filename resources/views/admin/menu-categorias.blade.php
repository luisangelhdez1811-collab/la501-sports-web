@extends('layouts.admin')

@section('content')
@php
    $secciones = ['comidas' => 'Comidas', 'bebidas' => 'Bebidas'];
    $campo = 'w-full bg-zinc-50 dark:bg-black border border-zinc-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm text-zinc-900 dark:text-white';
@endphp
<div class="max-w-4xl mx-auto" x-data="{ nueva: false }">

    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <a href="{{ route('admin.menu') }}" class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-orange-500">← Volver al menú</a>
            <h1 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-wider mt-1">Categorías</h1>
            <p class="text-zinc-500 text-sm">Las secciones de la carta, en el orden en que las ve el cliente. Agrega una antes de capturar sus platillos.</p>
        </div>
        <button type="button" @click="nueva = !nueva"
                class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-bold transition">
            + Nueva categoría
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm font-semibold">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- NUEVA CATEGORÍA --}}
    <form x-show="nueva" x-cloak action="{{ route('admin.menu.categories.store') }}" method="POST"
          class="mb-6 bg-white dark:bg-[#111111] border border-zinc-200 dark:border-white/10 rounded-2xl p-5 grid sm:grid-cols-[1fr_180px_auto] gap-3 items-end">
        @csrf
        <div>
            <label for="cat-nueva" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Nombre</label>
            <input id="cat-nueva" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Ej: Ensaladas" class="{{ $campo }}">
        </div>
        <div>
            <label for="cat-nueva-sec" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Sección</label>
            <select id="cat-nueva-sec" name="section" class="{{ $campo }}">
                @foreach($secciones as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('section') === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-bold">Agregar</button>
    </form>

    @foreach($secciones as $seccion => $titulo)
        @php $lista = $categorias->where('section', $seccion)->values(); @endphp
        <h2 class="text-xs font-black uppercase tracking-[0.2em] text-zinc-500 mt-8 mb-3">{{ $titulo }}</h2>

        <div class="bg-white dark:bg-[#111111] border border-zinc-200 dark:border-white/10 rounded-2xl divide-y divide-zinc-200 dark:divide-white/10 overflow-hidden">
            @forelse($lista as $i => $categoria)
                <div x-data="{ editar: false, quitar: false }" class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <form action="{{ route('admin.menu.categories.move', $categoria) }}" method="POST">
                                @csrf <input type="hidden" name="direction" value="up">
                                <button type="submit" @disabled($i === 0) class="w-7 h-6 text-zinc-400 hover:text-zinc-900 dark:hover:text-white disabled:opacity-25" aria-label="Subir {{ $categoria->name }}">▲</button>
                            </form>
                            <form action="{{ route('admin.menu.categories.move', $categoria) }}" method="POST">
                                @csrf <input type="hidden" name="direction" value="down">
                                <button type="submit" @disabled($i === $lista->count() - 1) class="w-7 h-6 text-zinc-400 hover:text-zinc-900 dark:hover:text-white disabled:opacity-25" aria-label="Bajar {{ $categoria->name }}">▼</button>
                            </form>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-zinc-900 dark:text-white">{{ $categoria->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $categoria->products_count }} platillo(s)</p>
                        </div>
                        <button type="button" @click="editar = !editar; quitar = false" class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-orange-500">Editar</button>
                        <button type="button" @click="quitar = !quitar; editar = false" class="text-xs font-bold uppercase tracking-wider text-red-500 hover:underline">Quitar</button>
                    </div>

                    <form x-show="editar" x-cloak action="{{ route('admin.menu.categories.update', $categoria) }}" method="POST"
                          class="mt-3 grid sm:grid-cols-[1fr_180px_auto] gap-3 items-end">
                        @csrf @method('PUT')
                        <div>
                            <label for="cat-{{ $categoria->id }}" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Nombre</label>
                            <input id="cat-{{ $categoria->id }}" name="name" value="{{ $categoria->name }}" required maxlength="100" class="{{ $campo }}">
                        </div>
                        <div>
                            <label for="cat-sec-{{ $categoria->id }}" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Sección</label>
                            <select id="cat-sec-{{ $categoria->id }}" name="section" class="{{ $campo }}">
                                @foreach($secciones as $valor => $texto)
                                    <option value="{{ $valor }}" @selected($categoria->section === $valor)>{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-zinc-900 dark:bg-white text-white dark:text-black rounded-lg text-sm font-bold">Guardar</button>
                        <p class="sm:col-span-3 text-xs text-zinc-500">Al renombrarla, sus {{ $categoria->products_count }} platillo(s) cambian de categoría con ella.</p>
                    </form>

                    <form x-show="quitar" x-cloak action="{{ route('admin.menu.categories.destroy', $categoria) }}" method="POST"
                          class="mt-3 flex flex-wrap items-end gap-3">
                        @csrf @method('DELETE')
                        @if($categoria->products_count > 0)
                            <div class="flex-1 min-w-[200px]">
                                <label for="cat-mover-{{ $categoria->id }}" class="block text-xs font-bold uppercase tracking-wider text-zinc-500 mb-1">Pasar sus {{ $categoria->products_count }} platillo(s) a</label>
                                <select id="cat-mover-{{ $categoria->id }}" name="move_to" required class="{{ $campo }}">
                                    <option value="">Elige una categoría…</option>
                                    @foreach($categorias->where('id', '!=', $categoria->id) as $otra)
                                        <option value="{{ $otra->id }}">{{ $otra->name }} ({{ $secciones[$otra->section] ?? $otra->section }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <p class="flex-1 text-sm text-zinc-500">No tiene platillos; se puede quitar sin mover nada.</p>
                        @endif
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold">Quitar categoría</button>
                    </form>
                </div>
            @empty
                <p class="p-6 text-sm text-zinc-500 text-center">No hay categorías de {{ strtolower($titulo) }}.</p>
            @endforelse
        </div>
    @endforeach
</div>
@endsection
