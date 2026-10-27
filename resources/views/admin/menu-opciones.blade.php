@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto" x-data="{ nueva: false }">

    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <a href="{{ route('admin.menu') }}" class="text-xs font-bold uppercase tracking-wider text-zinc-500 hover:text-orange-500">← Volver al menú</a>
            <h1 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-wider mt-1">Salsas y extras</h1>
            <p class="text-zinc-500 text-sm">Listas de opciones que se eligen al pedir un platillo. Se asignan a cada platillo desde su formulario.</p>
        </div>
        <button type="button" @click="nueva = !nueva"
                class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-bold transition">
            ➕ Nueva lista
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm font-semibold">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- NUEVA LISTA --}}
    <div x-show="nueva" x-cloak class="mb-6">
        @include('admin.partials.grupo-opciones-form', ['grupo' => null])
    </div>

    <div class="space-y-4">
        @forelse($grupos as $grupo)
            <details class="group bg-white dark:bg-[#111111] border border-zinc-200 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                <summary class="cursor-pointer list-none p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-black text-zinc-900 dark:text-white">{{ $grupo->name }}</p>
                        <p class="text-xs text-zinc-500">
                            {{ $grupo->label }} · {{ $grupo->options->count() }} opciones · en {{ $grupo->products_count }} platillo(s)
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1.5 max-w-xl justify-end">
                        @foreach($grupo->options->take(8) as $opcion)
                            <span class="text-[11px] px-2 py-0.5 rounded-full border {{ $opcion->available ? 'border-zinc-200 dark:border-white/10 text-zinc-600 dark:text-zinc-300' : 'border-red-300 text-red-500 line-through' }}">
                                {{ $opcion->name }}@if($opcion->price > 0) +${{ number_format($opcion->price, 0) }}@endif
                            </span>
                        @endforeach
                        @if($grupo->options->count() > 8)
                            <span class="text-[11px] text-zinc-400">+{{ $grupo->options->count() - 8 }}</span>
                        @endif
                    </div>
                </summary>
                <div class="border-t border-zinc-200 dark:border-white/10 p-5">
                    @include('admin.partials.grupo-opciones-form', ['grupo' => $grupo])

                    <form action="{{ route('admin.menu.options.destroy', $grupo) }}" method="POST" class="mt-3 text-right"
                          onsubmit="return confirm('¿Eliminar la lista «{{ $grupo->name }}»? Se quitará de los {{ $grupo->products_count }} platillo(s) que la usan.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs font-bold uppercase tracking-wider text-red-500 hover:underline">Eliminar lista</button>
                    </form>
                </div>
            </details>
        @empty
            <div class="text-center py-16 text-zinc-500">
                <p class="text-4xl mb-2">🌶️</p>
                <p class="font-bold">Aún no hay listas de opciones.</p>
                <p class="text-sm">Crea una o importa el Excel del menú desde «Importar Menú».</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
