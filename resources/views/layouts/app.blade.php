<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La 501 Sports</title>
    <meta name="description" content="Restaurant La 501 Sports: donde el deporte y la familia se unen. Consulta el menú, pide a domicilio, reserva tu mesa y conoce nuestras promociones.">

    <link rel="icon" type="image/png" href="{{ asset('images/logo_501_transparente.png') }}?v=2">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f0d0a">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="La 501">

    {{-- Tema antes de pintar --}}
    <script>
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>

    {{-- Tailwind compilado (npm run build:css) --}}
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}?v={{ filemtime(public_path('css/tailwind.css')) }}">

    {{-- Alpine --}}
    <script src="https://unpkg.com/alpinejs@3.17.4/dist/cdn.min.js" defer></script>

    {{-- Fuentes de marca --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Carga no bloqueante: el texto se pinta con la fuente del sistema y cambia al llegar la de marca --}}
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>

    <style>
        /* ── TOKENS ── */
        :root {
            --or:   #F97316;
            --or2:  #EA580C;
            --gn:   #16A34A;
            --gn2:  #22C55E;
        }

        /* ── TRANSICIÓN SUAVE DE TEMA ── */
        .theme-ready,
        .theme-ready *,
        .theme-ready *::before,
        .theme-ready *::after {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 280ms;
        }

        /* ── LOGO ── */
        .nav-logo-img {
            transition: transform 0.3s ease, filter 0.3s ease;
        }
        .nav-logo-img:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 0 8px rgba(249,115,22,0.5));
        }

        /* ── NAV LINK UNDERLINE DESLIZANTE ── */
        .nav-link {
            position: relative;
            display: inline-block;
            font-family: 'Oswald', sans-serif;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-decoration: none;
            padding: 0 12px 4px 12px;
            transition: color 0.2s;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 12px;
            right: 12px;
            height: 2px;
            background: var(--or);
            transform: scaleX(0);
            transition: transform 0.25s ease;
        }
        .nav-link:hover::after,
        .nav-link.active::after {
            transform: scaleX(1);
        }

        /* ── BOTÓN CTA NAV ── */
        .nav-cta-btn {
            font-family: 'Oswald', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            color: #fff;
            /* naranja más oscuro (#C2410C) para contraste AA con texto blanco de 12px */
            background: #C2410C;
            padding: 8px 20px;
            clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
            box-shadow: 0 3px 18px rgba(249,115,22,0.4);
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .nav-cta-btn:hover {
            background: #9A3412;
            box-shadow: 0 5px 26px rgba(249,115,22,0.55);
            transform: translateY(-1px);
        }

        /* ── THEME TOGGLE ── */
        .theme-toggle-btn {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: none; outline: none;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .theme-toggle-btn:hover {
            box-shadow: 0 0 0 2px var(--or);
            transform: scale(1.05);
        }

        /* ── CARRITO ── */
        .cart-link {
            position: relative;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            width: 38px; height: 38px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .cart-link:hover {
            box-shadow: 0 0 0 2px var(--or);
            transform: scale(1.05);
        }

        /* ── DROPDOWN USUARIO ── */
        .user-dropdown {
            border-top: 2px solid var(--or) !important;
        }
        .user-avatar {
            background: linear-gradient(135deg, var(--or), var(--or2)) !important;
        }

        /* ── MOBILE LINK ── */
        .mobile-nav-link {
            font-family: 'Oswald', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            display: block;
            padding: 11px 16px;
            border-radius: 10px;
            border-left: 3px solid transparent;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
        }
        .mobile-nav-link:hover {
            border-left-color: var(--or);
        }

        /* ── FOOTER ── */
        .footer-brand-name {
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .footer-or-bar {
            display: inline-block;
            width: 32px; height: 2px;
            background: linear-gradient(to right, var(--or), var(--gn));
            border-radius: 2px;
            margin-bottom: 8px;
        }
        .footer-link {
            font-family: 'Oswald', sans-serif;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link:hover { color: var(--or) !important; }

        /* ── BORDE INFERIOR NAV ── */
        .nav-bottom-border {
            border-bottom: 1px solid rgba(249,115,22,0.15);
        }
        .dark .nav-bottom-border {
            border-bottom: 1px solid rgba(249,115,22,0.12);
        }

        /* ── BOTÓN FLOTANTE DEL CARRITO ── */
        .btn-floating-cart {
            box-shadow: 0 10px 30px -5px rgba(249, 115, 22, 0.5);
            animation: floating-cart-wiggle 4s ease-in-out infinite;
        }
        .btn-floating-cart:hover {
            animation-play-state: paused;
        }
        @keyframes floating-cart-wiggle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
    </style>
</head>

<body class="theme-ready bg-white text-zinc-900 dark:bg-[#0f0d0a] dark:text-white min-h-screen flex flex-col">

    {{-- ══════════════════════════════════════
         NAVBAR
    ══════════════════════════════════════ --}}
    @if(Auth::check() && in_array(trim(strtolower(Auth::user()->role)), ['admin', 'cocinero', 'empleado']) && (request()->routeIs('perfil') || request()->is('perfil*')))
        <header class="bg-[#111111] dark:bg-[#080808] text-white py-4 px-6 border-b border-white/5 flex items-center justify-between no-print sticky top-0 z-50">
            <div class="flex items-center gap-3">
                <span class="text-xl">👤</span>
                <span class="font-bold tracking-wider font-['Oswald'] text-sm uppercase">Perfil de {{ Auth::user()->name }} ({{ ucfirst(Auth::user()->role) }})</span>
            </div>
            @php
                $backUrl = route('admin.dashboard');
                if (Auth::user()->role === 'cocinero') {
                    $backUrl = route('kitchen.index');
                } elseif (Auth::user()->role === 'empleado') {
                    $backUrl = route('mesero.mesas');
                }
            @endphp
            <a href="{{ $backUrl }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition-all decoration-none">
                🔙 Volver al Panel
            </a>
        </header>
    @else
        <nav x-data="{ mobileMenuOpen: false }"
             class="sticky top-0 z-50 bg-white/90 dark:bg-black/85 backdrop-blur-md nav-bottom-border">

        <div class="relative flex items-center justify-between px-6 py-4 w-full">

            {{-- Logo --}}
            <a href="/" class="flex-shrink-0 z-10">
                @php
                    $logoSetting = \App\Models\Setting::where('key', 'logo')->first();
                    $logoSrc = asset('images/logo_501.png');
                    if ($logoSetting) {
                        if (str_starts_with($logoSetting->value, 'http://') || str_starts_with($logoSetting->value, 'https://')) {
                            $logoSrc = $logoSetting->value;
                        } elseif (str_starts_with($logoSetting->value, 'logos/')) {
                            $logoSrc = asset('storage/' . $logoSetting->value);
                        }
                    }
                    // Cloudinary: pedir el logo al tamaño en que se muestra (56px, x2 para pantallas retina) y en formato moderno
                    if (str_contains($logoSrc, 'res.cloudinary.com') && str_contains($logoSrc, '/image/upload/')) {
                        $logoSrc = str_replace('/image/upload/', '/image/upload/f_auto,q_auto,h_112/', $logoSrc);
                    }
                @endphp
                <img src="{{ $logoSrc }}" alt="Logo La 501" width="100" height="56" fetchpriority="high" class="h-14 w-auto nav-logo-img">
            </a>

            {{-- Links Escritorio --}}
            <ul class="hidden lg:flex gap-1 items-center flex-1 justify-center mx-4">
                <li><a href="{{ route('nosotros') }}" class="nav-link {{ request()->routeIs('nosotros') ? 'active text-orange-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-orange-500' }}">Quienes Somos</a></li>
                <li><a href="{{ route('contacto') }}" class="nav-link {{ request()->routeIs('contacto') ? 'active text-orange-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-orange-500' }}">Contacto</a></li>
                <li><a href="{{ route('reservaciones') }}" class="nav-link {{ request()->routeIs('reservaciones') ? 'active text-orange-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-orange-500' }}">Reservaciones</a></li>
                <li><a href="{{ route('promociones') }}" class="nav-link {{ request()->routeIs('promociones') ? 'active text-orange-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-orange-500' }}">Promociones</a></li>
                <li><a href="{{ route('novedades') }}" class="nav-link {{ request()->routeIs('novedades') ? 'active text-orange-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-orange-500' }}">Novedades</a></li>
                <li><a href="{{ route('ubicacion') }}" class="nav-link {{ request()->routeIs('ubicacion') ? 'active text-orange-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-orange-500' }}">Ubicación</a></li>
            </ul>

            {{-- Acciones --}}
            <div class="flex gap-3 items-center flex-shrink-0 z-10">

                <a href="{{ route('pedido') }}" class="hidden lg:inline-flex nav-cta-btn">
                    Pedir Ahora
                </a>

                {{-- Theme toggle --}}
                <button id="theme-toggle" type="button" aria-label="Cambiar tema claro/oscuro" class="theme-toggle-btn bg-zinc-100 dark:bg-zinc-800">
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5 text-zinc-500" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                </button>

                {{-- Usuario autenticado --}}
                @auth
                    <div class="relative inline-block text-left" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                                class="flex items-center gap-2 p-1 pr-3 rounded-full bg-zinc-100 dark:bg-zinc-800 hover:ring-2 hover:ring-orange-500 transition-all">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset(Auth::user()->avatar) }}"
                                    class="h-8 w-8 rounded-full object-cover border-2 border-orange-500 shadow"
                                    alt="{{ Auth::user()->name }}">
                            @else
                                <div class="user-avatar h-8 w-8 rounded-full flex items-center justify-center text-white font-bold uppercase text-xs shadow">
                                    {{ substr(Auth::user()->name, 0, 2) }}
                                </div>
                            @endif
                            <div class="hidden sm:block text-left">
                                <p class="text-xs font-bold leading-none text-zinc-800 dark:text-white">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] text-zinc-500 uppercase">{{ Auth::user()->role === 'empleado' ? 'mesero' : Auth::user()->role }}</p>
                            </div>
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="user-dropdown absolute right-0 mt-2 w-56 origin-top-right bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/10 rounded-2xl shadow-2xl z-50 overflow-hidden"
                             style="display:none;">

                            <div class="px-4 py-3 border-b border-zinc-100 dark:border-white/5">
                                <p class="text-xs text-zinc-500 italic">Puntos La 501</p>
                                <p class="text-lg font-bold text-orange-500">🏆 {{ Auth::user()->points ?? 0 }} pts</p>
                            </div>
                            
                            <div class="py-1">
                                {{-- 🌟 ACCESO INTELIGENTE A PANEL ADMINISTRATIVO --}}
                                @if(in_array(auth()->user()->role, ['admin', 'mesero', 'empleado']))
    @php
        // Si es admin va a su dashboard, TODOS los demás (mesero/empleado) van a mesas
        $panelRoute = auth()->user()->role === 'admin' ? route('admin.dashboard') : route('mesero.mesas');
    @endphp
    <a href="{{ $panelRoute }}" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-orange-600 dark:text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-500/10 transition">
        💼 Panel de Empleado
    </a>
@endif

                                @if(auth()->user()->role === 'cliente')
                                <a href="{{ route('perfil') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/5 transition">
                                    👤 Mi Perfil
                                </a>
                                @endif
                                <a href="{{ route('configuracion') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-white/5 transition">
                                    ⚙️ Configuración
                                </a>
                            </div>
                            
                            <div class="border-t border-zinc-100 dark:border-white/5">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition font-bold">
                                        🚪 Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('order.lookup') }}" class="hidden sm:flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-orange-500 transition font-medium">
                        <span>Mis Pedidos</span>
                    </a>
                    <a href="{{ route('login') }}" class="hidden sm:flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 hover:text-orange-500 transition font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Iniciar Sesión</span>
                    </a>
                @endauth

                <a href="{{ route('cart.index') }}" id="global-cart-btn" class="cart-link bg-zinc-100 dark:bg-zinc-800">
                    <span class="text-xl">🛒</span>
                    <span id="global-cart-badge" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center border-2 border-white dark:border-[#0f0d0a] animate-bounce" style="{{ count((array) session('cart')) > 0 ? '' : 'display: none;' }}">
                        {{ count((array) session('cart')) }}
                    </span>
                </a>

                {{-- Hamburguesa --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        type="button"
                        aria-label="Abrir menú de navegación"
                        :aria-expanded="mobileMenuOpen.toString()"
                        class="lg:hidden w-9 h-9 flex items-center justify-center rounded-10 bg-zinc-100 dark:bg-zinc-800 border border-transparent hover:border-orange-500 transition-all">
                    <svg x-show="!mobileMenuOpen" class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileMenuOpen" class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

            </div>
        </div>

        {{-- Menú móvil --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="lg:hidden px-5 pb-5 border-t border-orange-500/10 dark:border-orange-500/10"
             style="display:none;">
            <div class="flex flex-col gap-2 pt-4">
                
                {{-- 🌟 ACCESO INTELIGENTE MÓVIL --}}
                {{-- 🌟 ACCESO INTELIGENTE MÓVIL --}}
                @auth
                    @if(in_array(auth()->user()->role, ['admin', 'mesero', 'empleado']))
                        @php
                            // Simplificado: Admin va a su dashboard, el resto a mesas
                            $panelRoute = auth()->user()->role === 'admin' ? route('admin.dashboard') : route('mesero.mesas');
                        @endphp
                        <a href="{{ $panelRoute }}" class="mobile-nav-link text-white bg-orange-600 hover:bg-orange-700 shadow-md mb-2">
                            💼 Ir a Panel de Empleado
                        </a>
                    @endif
                @endauth

                <a href="{{ route('pedido') }}"       class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">A Domicilio</a>
                <a href="{{ route('order.lookup') }}" class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Mis Pedidos</a>
                <a href="{{ route('menu') }}"         class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Menú</a>
                <a href="{{ route('nosotros') }}"     class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Quienes Somos</a>
                <a href="{{ route('contacto') }}"     class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Contacto</a>
                <a href="{{ route('reservaciones') }}" class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Reservaciones</a>
                <a href="{{ route('promociones') }}"  class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Promociones</a>
                <a href="{{ route('novedades') }}"    class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Novedades</a>
                <a href="{{ route('ubicacion') }}"    class="mobile-nav-link text-zinc-600 dark:text-zinc-300 hover:text-orange-500 dark:hover:text-orange-400 bg-zinc-50 dark:bg-white/5">Ubicación</a>

                @guest
                    <a href="{{ route('login') }}" class="mt-2 nav-cta-btn text-center py-3">
                        Iniciar Sesión
                    </a>
                @endguest
            </div>
        </div>

    </nav>
    @endif

    {{-- ══════════════════════════════════════
         CONTENIDO DE CADA VISTA
    ══════════════════════════════════════ --}}
    <main class="flex-grow">
        @yield('content')
    </main>

    {{-- ══════════════════════════════════════
         FOOTER
    ══════════════════════════════════════ --}}
    <footer class="border-t border-orange-500/15 dark:border-orange-500/10 bg-white dark:bg-[#0f0d0a]">
        <div class="max-w-screen-xl mx-auto px-8 py-10 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
            <div>
                <div class="footer-or-bar"></div>
                <p class="footer-brand-name text-zinc-900 dark:text-white">Restaurant La 501 Sports</p>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 tracking-wide">Donde el deporte y la familia se unen</p>
            </div>
            <div class="flex gap-6">
                <a href="{{ route('aviso-privacidad') }}" class="footer-link text-zinc-600 dark:text-zinc-400">Aviso de Privacidad</a>
                <a href="{{ route('terminos-condiciones') }}" class="footer-link text-zinc-600 dark:text-zinc-400">Términos y Condiciones</a>
                <a href="{{ route('nosotros') }}" class="footer-link text-zinc-600 dark:text-zinc-400">Quienes Somos</a>
                <a href="{{ route('contacto') }}" class="footer-link text-zinc-600 dark:text-zinc-400">Contacto</a>
            </div>
            <p class="text-xs text-zinc-600 dark:text-zinc-400 tracking-wide">
                © {{ date('Y') }} La 501 Sports.<br class="md:hidden"> Todos los derechos reservados.
            </p>
        </div>
    </footer>

    {{-- ══════════════════════════════════════
         SCRIPTS
    ══════════════════════════════════════ --}}
    <script>
        document.body.classList.remove('theme-ready');
        setTimeout(() => document.body.classList.add('theme-ready'), 120);

        const themeBtn  = document.getElementById('theme-toggle');
        const darkIcon  = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        const html      = document.documentElement;

        if (localStorage.getItem('theme') === 'light') {
            html.classList.remove('dark');
        } else {
            html.classList.add('dark');
        }

        function syncIcons() {
            if (html.classList.contains('dark')) {
                lightIcon.classList.remove('hidden');
                darkIcon.classList.add('hidden');
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        }
        syncIcons();

        themeBtn.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
            syncIcons();
        });

        // PWA: registrar el Service Worker (requiere HTTPS o localhost)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch((error) => console.error('Error al registrar el Service Worker:', error));
            });
        }
    </script>

    <!-- Botón Flotante del Carrito (Fácilmente visible) -->
    <a href="{{ route('cart.index') }}" 
       id="floating-cart-btn" 
       class="btn-floating-cart fixed bottom-6 right-6 z-50 flex items-center justify-center w-16 h-16 bg-gradient-to-tr from-orange-500 to-orange-600 text-white rounded-full transition-all transform hover:-translate-y-2 active:scale-95 no-print"
       style="display: {{ count((array) session('cart')) > 0 ? 'flex' : 'none' }};">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-7 h-7">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span id="floating-cart-badge" class="absolute -top-1 -right-1 bg-green-600 text-white text-xs font-black w-6 h-6 rounded-full flex items-center justify-center border-2 border-white shadow-md animate-pulse">
            {{ count((array) session('cart')) }}
        </span>
    </a>
</body>
</html>
