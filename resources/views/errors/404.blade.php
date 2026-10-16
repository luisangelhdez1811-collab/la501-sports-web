@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center px-6 py-20 text-center">
    
    {{-- El número gigante de fondo --}}
    <h1 class="text-[150px] md:text-[200px] font-black leading-none text-zinc-100 dark:text-[#141414] select-none">
        404
    </h1>
    
    {{-- El contenedor del mensaje que sube un poco para empalmarse con el número --}}
    <div class="-mt-16 md:-mt-24 relative z-10">
        <div class="w-20 h-20 mx-auto bg-orange-500/10 text-orange-500 rounded-full flex items-center justify-center mb-6 border border-orange-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
            ¡Ups! Página no encontrada
        </h2>
        
        <p class="text-zinc-500 dark:text-zinc-400 max-w-md mx-auto mb-8 text-lg">
            Lo sentimos, la página que buscas no existe, cambió de nombre o fue movida temporalmente.
        </p>

        {{-- Botón para regresar al inicio --}}
        <a href="{{ url('/') }}"
           class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-all transform active:scale-95 shadow-lg shadow-orange-500/25">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
            </svg>
            Regresar al Inicio
        </a>
    </div>

</div>
@endsection
