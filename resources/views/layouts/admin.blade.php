<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Empleados · La 501</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo_501_transparente.png') }}?v=2">

    <script>
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; transition: background-color 0.2s, color 0.2s; }

        :root {
            --nav-bg:    #FFFFFF;
            --nav-bdr:   #E4E4E7;
            --nav-txt:   #71717A;
            --nav-act:   #18181B;
            --nav-hover: #3F3F46;
            --pill-bg:   #F4F4F5;
            --pill-act:  #FFFFFF;
            --usr-bg:    #F4F4F5;
            --usr-bdr:   #E4E4E7;
            --accent:    #DD3A1D;
            --aside-w:   240px;
        }
        .dark {
            --nav-bg:    #0F0F0F;
            --nav-bdr:   rgba(255,255,255,0.07);
            --nav-txt:   #71717A;
            --nav-act:   #FAFAFA;
            --nav-hover: #D4D4D8;
            --pill-bg:   #1A1A1A;
            --pill-act:  #262626;
            --usr-bg:    #1A1A1A;
            --usr-bdr:   rgba(255,255,255,0.07);
        }

        /* ── ASIDE (solo escritorio) ── */
        .adm-aside {
            display: none;
        }
        @media (min-width: 1024px) {
            .adm-aside {
                display: flex;
                flex-direction: column;
                position: fixed;
                top: 0; left: 0;
                width: var(--aside-w);
                height: 100vh;
                background: var(--nav-bg);
                border-right: 1px solid var(--nav-bdr);
                z-index: 50;
                overflow-y: auto;
                scrollbar-width: none;
            }
            .adm-aside::-webkit-scrollbar { display: none; }

            .adm-main-wrap {
                margin-left: var(--aside-w);
            }
        }

        /* ── ASIDE INTERIOR ── */
        .aside-logo-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 16px 16px;
            border-bottom: 1px solid var(--nav-bdr);
            text-decoration: none;
            flex-shrink: 0;
        }
        .aside-logo-wrap img { height: 55px; width: auto; }
        .aside-badge {
            font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--accent); background: rgba(221,58,29,0.08);
            border: 1px solid rgba(221,58,29,0.2);
            padding: 2px 8px; border-radius: 4px;
        }

        .aside-nav {
            flex: 1;
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .aside-section-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--nav-txt);
            padding: 10px 8px 4px;
            margin-top: 6px;
        }

        .aside-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
            color: var(--nav-txt);
            text-decoration: none;
            transition: color 0.15s, background 0.15s;
            white-space: nowrap;
        }
        .aside-link:hover {
            background: var(--pill-bg);
            color: var(--nav-hover);
        }
        .dark .aside-link:hover { background: rgba(255,255,255,0.05); }
        .aside-link.active {
            background: rgba(221,58,29,0.08);
            color: var(--accent);
        }
        .aside-link.active .aside-icon { opacity: 1; }
        .aside-icon { opacity: 0.45; flex-shrink: 0; }
        .aside-link.active svg { color: var(--accent); }

        .aside-notif-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #EAB308; display: inline-block; flex-shrink: 0;
            animation: adm-pulse 2s ease-in-out infinite;
            margin-left: auto;
        }
        @keyframes adm-pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.6; transform:scale(0.85); } }

        /* ── ASIDE FOOTER (usuario) ── */
        .aside-footer {
            padding: 12px 10px;
            border-top: 1px solid var(--nav-bdr);
            flex-shrink: 0;
        }
        .aside-user-btn {
            display: flex; align-items: center; gap: 9px;
            padding: 8px 10px; border-radius: 8px;
            background: var(--usr-bg); border: 1px solid var(--usr-bdr);
            cursor: pointer; width: 100%;
            transition: border-color 0.15s;
        }
        .aside-user-btn:hover { border-color: var(--accent); }
        .aside-avatar {
            width: 30px; height: 30px; border-radius: 6px;
            background: var(--accent); display: flex; align-items: center;
            justify-content: center; font-size: 11px; font-weight: 700;
            color: #fff; letter-spacing: 0.5px; flex-shrink: 0;
        }
        .aside-user-name  { font-size: 12px; font-weight: 600; color: var(--nav-act); line-height: 1; }
        .aside-user-role  { font-size: 10px; color: var(--nav-txt); text-transform: uppercase; letter-spacing: 0.5px; line-height: 1; margin-top: 2px; }
        .aside-chevron    { color: var(--nav-txt); margin-left: auto; transition: transform 0.2s; }
        .aside-dropdown {
            position: absolute; bottom: calc(100% + 6px); left: 10px; right: 10px;
            background: var(--nav-bg); border: 1px solid var(--nav-bdr);
            border-radius: 8px; box-shadow: 0 -4px 24px rgba(0,0,0,0.12);
            overflow: hidden; z-index: 100;
        }
        .dark .aside-dropdown { box-shadow: 0 -4px 24px rgba(0,0,0,0.5); }
        .aside-dd-item {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 14px; font-size: 12px; font-weight: 500;
            color: var(--nav-hover); text-decoration: none;
            transition: background 0.12s; border: none; background: none;
            width: 100%; text-align: left; cursor: pointer;
        }
        .aside-dd-item:hover { background: var(--pill-bg); }
        .aside-dd-sep { height: 1px; background: var(--nav-bdr); }
        .aside-dd-item.danger { color: #EF4444; }
        .aside-dd-item.danger:hover { background: rgba(239,68,68,0.06); }

        /* ── THEME TOGGLE (en aside) ── */
        .aside-theme-btn {
            display: flex; align-items: center; gap: 9px;
            padding: 8px 10px; border-radius: 7px;
            font-size: 13px; font-weight: 500;
            color: var(--nav-txt); cursor: pointer;
            width: 100%; background: none; border: none;
            transition: background 0.15s, color 0.15s;
        }
        .aside-theme-btn:hover { background: var(--pill-bg); color: var(--nav-hover); }

        /* ── HEADER MÓVIL (solo <1024px) ── */
        .adm-nav {
            position: sticky; top: 0; z-index: 50;
            background: var(--nav-bg); border-bottom: 1px solid var(--nav-bdr);
            height: 56px; display: flex; align-items: center;
        }
        @media (min-width: 1024px) {
            .adm-nav { display: none; }
        }
        .adm-nav-inner {
            width: 100%; padding: 0 16px;
            display: flex; align-items: center; gap: 12px;
        }
        .adm-logo-wrap {
            display: flex; align-items: center; gap: 10px;
            flex-shrink: 0; text-decoration: none;
        }
        .adm-logo-wrap img { height: 30px; width: auto; }
        .adm-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; flex-shrink: 0; }
        .adm-icon-btn {
            width: 32px; height: 32px; border-radius: 6px;
            background: transparent; border: 1px solid var(--nav-bdr);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--nav-txt);
            transition: background 0.15s, color 0.15s;
        }
        .adm-icon-btn:hover { background: var(--pill-bg); color: var(--nav-act); }
        .adm-user-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 4px; border-radius: 6px;
            background: var(--usr-bg); border: 1px solid var(--usr-bdr);
            cursor: pointer; transition: border-color 0.15s;
        }
        .adm-user-btn:hover { border-color: var(--accent); }
        .adm-avatar {
            width: 28px; height: 28px; border-radius: 5px;
            background: var(--accent); display: flex; align-items: center;
            justify-content: center; font-size: 11px; font-weight: 700;
            color: #fff; letter-spacing: 0.5px; flex-shrink: 0;
        }
        .adm-user-name { font-size: 12px; font-weight: 600; color: var(--nav-act); line-height: 1; }
        .adm-user-role { font-size: 10px; color: var(--nav-txt); text-transform: uppercase; letter-spacing: 0.5px; line-height: 1; margin-top: 2px; }
        .adm-chevron { color: var(--nav-txt); transition: transform 0.2s; }
        .adm-dropdown {
            position: absolute; right: 0; top: calc(100% + 6px); width: 200px;
            background: var(--nav-bg); border: 1px solid var(--nav-bdr);
            border-radius: 8px; box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden; z-index: 100;
        }
        .dark .adm-dropdown { box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .adm-dd-item {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 14px; font-size: 12px; font-weight: 500;
            color: var(--nav-hover); text-decoration: none;
            transition: background 0.12s; border: none; background: none;
            width: 100%; text-align: left; cursor: pointer;
        }
        .adm-dd-item:hover { background: var(--pill-bg); }
        .adm-dd-sep { height: 1px; background: var(--nav-bdr); }
        .adm-dd-item.danger { color: #EF4444; }
        .adm-dd-item.danger:hover { background: rgba(239,68,68,0.06); }

        /* ── MENÚ MÓVIL ── */
        .adm-mobile-menu {
            display: none; flex-direction: column; gap: 2px;
            padding: 8px 12px 12px; border-top: 1px solid var(--nav-bdr);
            background: var(--nav-bg);
            position: absolute; top: 56px; left: 0; width: 100%;
        }
        .adm-mobile-menu.open { display: flex; }
        .adm-mobile-link {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 12px; border-radius: 6px;
            font-size: 13px; font-weight: 500;
            color: var(--nav-txt); text-decoration: none;
            transition: background 0.12s, color 0.12s;
        }
        .adm-mobile-link:hover { background: var(--pill-bg); color: var(--nav-act); }
        .adm-mobile-link.active { background: rgba(221,58,29,0.08); color: var(--accent); }
        .adm-hamburger { display: flex; }

        /* ── MAIN ── */
        .adm-main { flex-grow: 1; background: #F8F8F8; min-height: 100vh; }
        .dark .adm-main { background: #0A0A0A; }
        .adm-main-inner { max-width: 1400px; margin: 0 auto; padding: 28px 28px; }
        @media (max-width: 1023px) {
            .adm-main { min-height: calc(100vh - 56px); }
        }
    </style>
</head>

<body class="bg-white dark:bg-[#0A0A0A] text-zinc-900 dark:text-white min-h-screen flex flex-col">

    {{-- ══════════════════════════════════════════
         ASIDE — Solo escritorio (≥1024px)
    ══════════════════════════════════════════ --}}
    @php
        $logoRoute = '/';
        if (auth()->user()->role === 'admin') {
            $logoRoute = route('admin.dashboard');
        } elseif (in_array(auth()->user()->role, ['mesero', 'empleado'])) {
            $logoRoute = route('mesero.mesas');
        }
        $logoSetting = \App\Models\Setting::where('key', 'logo')->first();
        $logoSrc = asset('images/logo_501.png');
        if ($logoSetting) {
            if (str_starts_with($logoSetting->value, 'http://') || str_starts_with($logoSetting->value, 'https://')) {
                $logoSrc = $logoSetting->value;
            } elseif (str_starts_with($logoSetting->value, 'logos/')) {
                $logoSrc = asset('storage/' . $logoSetting->value);
            }
        }
    @endphp

    <aside class="adm-aside">

        {{-- Logo --}}
        <a href="{{ $logoRoute }}" class="aside-logo-wrap">
            <img src="{{ $logoSrc }}" alt="La 501">
            <span class="aside-badge">{{ ucfirst(auth()->user()->role) }}</span>
        </a>

        {{-- Navegación --}}
        <nav class="aside-nav" aria-label="Navegación principal">

            @if(auth()->user()->role === 'admin')
                <span class="aside-section-label">Principal</span>

                <a href="{{ route('admin.dashboard') }}" class="aside-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Ventas
                </a>

                <a href="{{ route('admin.menu') }}" class="aside-link {{ request()->routeIs('admin.menu') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Menú
                </a>

                <a href="{{ route('admin.inventory.index') }}" class="aside-link {{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                    Inventario
                </a>

                <a href="{{ route('admin.promotions.index') }}" class="aside-link {{ request()->routeIs('admin.promotions.index') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    Promociones
                </a>

                <a href="{{ route('admin.news') }}" class="aside-link {{ request()->routeIs('admin.news') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    Novedades
                </a>

                <a href="{{ route('admin.reservations.index') }}" class="aside-link {{ request()->routeIs('admin.reservations.index') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Reservaciones
                </a>

                <a href="{{ route('admin.mensajes') }}" class="aside-link {{ request()->routeIs('admin.mensajes') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Mensajes
                </a>

                <a href="{{ route('admin.waiters.performance') }}" class="aside-link {{ request()->routeIs('admin.waiters.performance') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Meseros
                </a>

                <a href="{{ route('admin.ml.index') }}" class="aside-link {{ request()->routeIs('admin.ml.*') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Predicciones
                </a>

                <a href="{{ route('admin.settings.index') }}" class="aside-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Información
                </a>
            @endif

            @if(in_array(auth()->user()->role, ['mesero', 'empleado']))
                @if(auth()->user()->role === 'admin')
                    <span class="aside-section-label">Servicio</span>
                @endif
                <a href="{{ route('mesero.mesas') }}" class="aside-link {{ request()->routeIs('mesero.mesas') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M14 3v18"/></svg>
                    Mesas / Pedidos
                </a>
                <a href="{{ route('mesero.domicilios') }}" class="aside-link {{ request()->routeIs('mesero.domicilios*') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h1m6 0h1v-5l-3-4h-4v9m-6 0a2 2 0 11-4 0m10 0a2 2 0 11-4 0"/></svg>
                    Domicilios
                </a>
            @endif

            @if(Auth::id() === 2)
                <span class="aside-section-label">Sistema</span>

                {{-- Gestionar Personal --}}
                <a href="{{ route('admin.users.index') }}" class="aside-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Gestionar Personal
                </a>

                {{-- Base de Datos --}}
                <a href="{{ route('admin.database') }}" class="aside-link {{ request()->routeIs('admin.database') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    Base de Datos
                </a>

                {{-- Monitor (ARREGLADO) --}}
                <a href="{{ route('admin.database.monitor') }}" class="aside-link {{ request()->routeIs('admin.database.monitor') ? 'active' : '' }}">
                    <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Monitor
                </a>
            @endif

        </nav>

        {{-- Footer: tema + usuario --}}
        <div class="aside-footer">

            {{-- Toggle de tema --}}
            <button id="aside-theme-toggle" class="aside-theme-btn">
                <svg id="aside-icon-dark" class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                <svg id="aside-icon-light" class="w-4 h-4 opacity-50 hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                <span id="aside-theme-label" style="font-size:13px; color: var(--nav-txt);">Tema oscuro</span>
            </button>

            {{-- Usuario --}}
            <div class="relative mt-2" x-data="{ userMenu: false }">
                <button @click="userMenu = !userMenu" @click.away="userMenu = false" class="aside-user-btn">
                    <div class="aside-avatar">{{ substr(Auth::user()->name, 0, 2) }}</div>
                    <div class="text-left overflow-hidden">
                        <div class="aside-user-name truncate">{{ Auth::user()->name }}</div>
                        <div class="aside-user-role">{{ ucfirst(Auth::user()->role) }}</div>
                    </div>
                    <svg class="aside-chevron w-3.5 h-3.5 flex-shrink-0" :class="userMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="userMenu" class="aside-dropdown" style="display:none;">
                    <a href="{{ route('perfil') }}" class="aside-dd-item">
                        <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Mi Cuenta
                    </a>
                    <div class="aside-dd-sep"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="aside-dd-item danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    {{-- ══════════════════════════════════════════
         HEADER MÓVIL — Solo <1024px
    ══════════════════════════════════════════ --}}
    <nav class="adm-nav" aria-label="Navegación móvil" x-data="{ mobileOpen: false, userOpen: false }">
        <div class="adm-nav-inner">

            <a href="{{ $logoRoute }}" class="adm-logo-wrap">
                <img src="{{ $logoSrc }}" alt="La 501">
            </a>

            <div class="adm-actions">
                <button id="theme-toggle" class="adm-icon-btn" title="Cambiar tema">
                    <svg id="theme-toggle-light-icon" class="hidden w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                    <svg id="theme-toggle-dark-icon" class="hidden w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                </button>

                <div class="relative" x-data="{ userMenu: false }">
                    <button @click="userMenu = !userMenu" @click.away="userMenu = false" class="adm-user-btn">
                        <div class="adm-avatar">{{ substr(Auth::user()->name, 0, 2) }}</div>
                        <div class="hidden sm:block text-left">
                            <div class="adm-user-name">{{ Auth::user()->name }}</div>
                            <div class="adm-user-role">{{ ucfirst(Auth::user()->role) }}</div>
                        </div>
                        <svg class="adm-chevron w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="userMenu" class="adm-dropdown" style="display:none;">
                        <a href="{{ route('perfil') }}" class="adm-dd-item">
                            <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Mi Cuenta
                        </a>
                        @if(Auth::id() === 2)
                        <a href="{{ route('admin.users.index') }}" class="adm-dd-item">
                            <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Gestionar Personal
                        </a>
                        <a href="{{ route('admin.database') }}" class="adm-dd-item">
                            <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                            Sistema
                        </a>
                        <a href="{{ route('admin.database.monitor') }}" class="aside-link {{ request()->routeIs('admin.database.monitor') ? 'active' : '' }}">
                            <svg class="aside-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Monitor
                        </a>
                        @endif
                        <div class="adm-dd-sep"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="adm-dd-item danger">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>

                <button @click="mobileOpen = !mobileOpen" class="adm-icon-btn adm-hamburger">
                    <svg x-show="!mobileOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Menú móvil desplegable --}}
        <div :class="mobileOpen ? 'open' : ''" class="adm-mobile-menu">
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="adm-mobile-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Ventas</a>
                <a href="{{ route('admin.menu') }}" class="adm-mobile-link {{ request()->routeIs('admin.menu') ? 'active' : '' }}">Menú</a>
                <a href="{{ route('admin.inventory.index') }}" class="adm-mobile-link {{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">Inventario</a>
                <a href="{{ route('admin.promotions.index') }}" class="adm-mobile-link {{ request()->routeIs('admin.promotions.index') ? 'active' : '' }}">Promociones</a>
                <a href="{{ route('admin.news') }}" class="adm-mobile-link {{ request()->routeIs('admin.news') ? 'active' : '' }}">Novedades</a>
                <a href="{{ route('admin.reservations.index') }}" class="adm-mobile-link {{ request()->routeIs('admin.reservations.index') ? 'active' : '' }}">Reservaciones</a>
                <a href="{{ route('admin.mensajes') }}" class="adm-mobile-link {{ request()->routeIs('admin.mensajes') ? 'active' : '' }}">Mensajes</a>
                <a href="{{ route('admin.waiters.performance') }}" class="adm-mobile-link {{ request()->routeIs('admin.waiters.performance') ? 'active' : '' }}">Meseros</a>
                <a href="{{ route('admin.ml.index') }}" class="adm-mobile-link {{ request()->routeIs('admin.ml.*') ? 'active' : '' }}">Predicciones</a>
                <a href="{{ route('admin.settings.index') }}" class="adm-mobile-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Información</a>
            @endif
            @if(in_array(auth()->user()->role, ['mesero', 'empleado']))
                <a href="{{ route('mesero.mesas') }}" class="adm-mobile-link {{ request()->routeIs('mesero.mesas') ? 'active' : '' }}">Mesas / Pedidos</a>
                <a href="{{ route('mesero.domicilios') }}" class="adm-mobile-link {{ request()->routeIs('mesero.domicilios*') ? 'active' : '' }}">Domicilios</a>
            @endif
        </div>
    </nav>

    {{-- ══════════════════════════════════════════
         CONTENIDO PRINCIPAL
    ══════════════════════════════════════════ --}}
    <div class="adm-main-wrap flex flex-col flex-1">
        <main class="adm-main">
            <div class="adm-main-inner">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        const html = document.documentElement;

        function syncAllThemeIcons() {
            const isDark = html.classList.contains('dark');

            // Header móvil
            const mLight = document.getElementById('theme-toggle-light-icon');
            const mDark  = document.getElementById('theme-toggle-dark-icon');
            if (mLight) mLight.classList.toggle('hidden', !isDark);
            if (mDark)  mDark.classList.toggle('hidden', isDark);

            // Aside escritorio
            const aLight = document.getElementById('aside-icon-light');
            const aDark  = document.getElementById('aside-icon-dark');
            const aLabel = document.getElementById('aside-theme-label');
            if (aLight) aLight.classList.toggle('hidden', !isDark);
            if (aDark)  aDark.classList.toggle('hidden', isDark);
            if (aLabel) aLabel.textContent = isDark ? 'Tema claro' : 'Tema oscuro';
        }

        syncAllThemeIcons();

        // Botón aside
        const asideBtn = document.getElementById('aside-theme-toggle');
        if (asideBtn) {
            asideBtn.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
                syncAllThemeIcons();
            });
        }

        // Botón header móvil
        const mobileBtn = document.getElementById('theme-toggle');
        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
                syncAllThemeIcons();
            });
        }

        // Dentro de tu <script> en el layout
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Echo) {
                window.Echo.private('admin-notifications')
                    .listen('.NotificationProcessed', (e) => {
                        console.log("Evento recibido:", e);
                        // Tu código de Swal.fire...
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: e.type === 'reservacion' ? 'success' : 'info', // Dinámico
                            title: 'Notificación de La 501',
                            text: e.message,
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true
                        });

                        if(window.location.pathname.includes('reservaciones')) {
                            // Asegúrate de que esta función exista o recarga la página
                            if (typeof actualizarTablaReservaciones === "function") {
                                actualizarTablaReservaciones();
                            } else {
                                location.reload();
                            }
                        }
                    });
            }
        });
    </script>
</body>
</html>
