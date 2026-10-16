@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto">
    
    <div class="mb-8">
        <a href="{{ route('admin.database') }}" class="inline-flex items-center gap-1 text-sm font-bold text-zinc-500 hover:text-zinc-800 dark:hover:text-white mb-4 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver a Sistema
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Historial de Respaldos
        </h1>
        <p class="text-sm text-zinc-500 mt-1">Explora, filtra y restaura cualquier punto en el tiempo de la base de datos de La 501 Sports.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 text-green-600 dark:text-green-400 rounded-xl text-sm font-bold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 rounded-xl text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTROS --}}
    <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-4 shadow-sm mb-6">
        <form action="{{ route('admin.database.history') }}" method="GET" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="w-full sm:w-1/3">
                <label for="fecha" class="block text-xs font-bold text-zinc-500 uppercase mb-1">Filtrar por Día</label>
                <input type="date" id="fecha" name="fecha" value="{{ $fechaFiltro ?? '' }}" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-white outline-none focus:border-orange-500">
            </div>

            <div class="w-full sm:w-1/3">
                <label for="hora" class="block text-xs font-bold text-zinc-500 uppercase mb-1">Filtrar por Hora aprox.</label>
                <input type="time" id="hora" name="hora" value="{{ $horaFiltro ?? '' }}" class="w-full bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-white outline-none focus:border-orange-500">
            </div>

            <div class="w-full sm:w-auto flex gap-2">
                <button type="submit" class="px-6 py-2.5 bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-200 dark:text-black text-white text-sm font-bold rounded-lg transition shadow-lg">
                    Buscar
                </button>
                @if(($fechaFiltro ?? false) || ($horaFiltro ?? false))
                    <a href="{{ route('admin.database.history') }}" class="px-4 py-2.5 bg-red-50 dark:bg-red-500/10 text-red-600 hover:bg-red-100 rounded-lg text-sm font-bold transition flex items-center justify-center" title="Quitar filtros">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- SECCIÓN DE RESPALDOS --}}
    <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-6 shadow-sm mb-10">
        @if(isset($backups) && count($backups) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($backups as $backup)
                    <div class="flex flex-col p-4 bg-zinc-50 dark:bg-[#1a1612] border border-zinc-200 dark:border-white/5 rounded-xl hover:border-orange-500/50 transition duration-300">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-10 h-10 bg-orange-500/10 text-orange-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded">
                                {{ $backup['carbon']->diffForHumans() }}
                            </span>
                        </div>

                        <div class="mb-4 flex-grow">
                            <h3 class="font-bold text-zinc-900 dark:text-white text-sm mb-1">{{ $backup['carbon']->isoFormat('dddd D [de] MMMM') }}</h3>
                            <p class="text-xs text-zinc-500 font-mono">Hora: {{ $backup['carbon']->format('H:i:s') }}</p>
                            <p class="text-xs text-zinc-500 font-mono mt-0.5">Peso: {{ $backup['size'] }}</p>
                        </div>

                        <div class="flex items-center gap-2 mt-auto">
                            <form action="{{ route('admin.database.restore') }}" method="POST" class="w-1/2" onsubmit="return confirm('⚠️ ¿ESTÁS SEGURO?\n\nLa base de datos se reemplazará completamente por esta versión.');">
                                @csrf
                                <input type="hidden" name="file_path" value="{{ $backup['path'] }}">
                                <button type="submit" class="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold rounded-lg transition shadow-md flex items-center justify-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Restaurar
                                </button>
                            </form>

                            <form action="{{ route('admin.database.download') }}" method="POST" class="w-1/2">
                                @csrf
                                <input type="hidden" name="file_path" value="{{ $backup['path'] }}">
                                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition shadow-md flex items-center justify-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Descargar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-sm text-zinc-500">
                <svg class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                No se encontraron respaldos de SQL disponibles.
            </div>
        @endif
    </div>

    {{-- SECCIÓN DE REPORTES (NUEVA) --}}
    <div class="mb-4 mt-8">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Historial de Reportes
        </h2>
        <p class="text-sm text-zinc-500 mt-1">Auditorías, estadísticas de rendimiento y métricas exportadas.</p>
    </div>

    <div class="bg-white dark:bg-[#111] border border-zinc-200 dark:border-white/10 rounded-2xl p-6 shadow-sm mb-6">
        @if(isset($reports) && count($reports) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($reports as $report)
                    <div class="flex flex-col p-4 bg-zinc-50 dark:bg-[#15121a] border border-zinc-200 dark:border-white/5 rounded-xl hover:border-purple-500/50 transition duration-300">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-10 h-10 bg-purple-500/10 text-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded">
                                {{ $report['carbon']->diffForHumans() }}
                            </span>
                        </div>

                        <div class="mb-4 flex-grow">
                            <h3 class="font-bold text-zinc-900 dark:text-white text-sm mb-1 truncate" title="{{ $report['name'] }}">{{ $report['name'] }}</h3>
                            <p class="text-xs text-zinc-500 font-mono mt-0.5">Peso: {{ $report['size'] }}</p>
                        </div>

                        <div class="flex items-center mt-auto">
                            {{-- Asegúrate de crear una ruta para descargar los reportes json --}}
                            <form action="{{ route('admin.database.report.download') ?? '#' }}" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="file_path" value="{{ $report['path'] }}">
                                <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition shadow-md flex items-center justify-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Descargar JSON
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-sm text-zinc-500">
                <svg class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                No se han generado reportes de auditoría todavía.
            </div>
        @endif
    </div>
</div>
@endsection
