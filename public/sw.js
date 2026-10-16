/*
 * Service Worker – La 501 Sports
 *
 * Estrategia:
 *  - Páginas públicas (inicio, menú, contacto, ubicación, reservaciones):
 *    red primero; cada respuesta correcta se guarda en Cache Storage. Sin
 *    conexión se sirve la última copia guardada de esa misma página, y si aún
 *    no se había visitado, la copia del inicio.
 *  - Estáticos propios (css, íconos, imágenes, storage) y de terceros
 *    (Alpine, Google Fonts): caché con actualización en segundo plano
 *    (stale-while-revalidate).
 *  - Rutas con sesión (admin, cocina, mesero, login, perfil, carrito, checkout,
 *    pedidos, api) nunca se guardan en caché. Si se abren sin conexión se
 *    muestra un aviso con un botón "Regresar al inicio".
 *
 * Para publicar cambios, incrementa CACHE_VERSION.
 */
const CACHE_VERSION = 'la501-v3';
const SHELL_CACHE = `${CACHE_VERSION}-shell`;
const PAGES_CACHE = `${CACHE_VERSION}-pages`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;

const HOME_URL = '/';

// Se descargan al instalar para que el sitio funcione offline desde la primera visita.
const PRECACHE = [
    HOME_URL,
    '/menu',
    '/contacto',
    '/ubicacion',
    '/reservaciones',
    '/css/tailwind.css',
    '/manifest.webmanifest',
    '/images/logo_501_transparente.png',
    '/images/logo_501.png',
    '/images/NH.jpg',
    '/images/carrusel_burgers_beer.png',
    '/images/carrusel_chicken_wings.png',
    '/images/carrusel_sports_bar.png',
    '/images/patterns/logo-501-pattern-dark.svg',
    '/images/patterns/logo-501-pattern-light.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
];

const CACHEABLE_PAGES = new Set(['/', '/menu', '/contacto', '/ubicacion', '/reservaciones']);
const STATIC_PATH = /^\/(css|icons|images|storage)\//;
const THIRD_PARTY_HOSTS = new Set([
    'unpkg.com',
    'fonts.googleapis.com',
    'fonts.gstatic.com',
]);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then((cache) => Promise.allSettled(
                // allSettled: un recurso que falle no impide instalar el resto
                PRECACHE.map((url) => cache.add(new Request(url, { cache: 'reload' })))
            ))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => !key.startsWith(CACHE_VERSION)).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        if (THIRD_PARTY_HOSTS.has(url.hostname)) {
            event.respondWith(staleWhileRevalidate(request));
        }
        return;
    }

    if (request.mode === 'navigate') {
        const path = normalizePath(url.pathname);
        event.respondWith(
            CACHEABLE_PAGES.has(path)
                ? networkFirstPage(request, path)
                : networkOrOfflineNotice(request)
        );
        return;
    }

    if (STATIC_PATH.test(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

function normalizePath(pathname) {
    return pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname;
}

async function networkFirstPage(request, path) {
    const cache = await caches.open(PAGES_CACHE);
    try {
        const response = await fetch(request);
        // Solo se guarda la respuesta directa (sin redirecciones a login, etc.)
        if (response.ok && !response.redirected) {
            cache.put(path, response.clone());
        }
        return response;
    } catch (error) {
        return (await cache.match(path))
            || (await caches.match(path))
            || (await cache.match(HOME_URL))
            || (await caches.match(HOME_URL))
            || new Response('Sin conexión', {
                status: 503,
                headers: { 'Content-Type': 'text/plain; charset=utf-8' },
            });
    }
}

// Páginas que no se guardan: si no hay red, aviso con botón para volver al inicio.
async function networkOrOfflineNotice(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return new Response(OFFLINE_NOTICE, {
            status: 503,
            headers: { 'Content-Type': 'text/html; charset=utf-8' },
        });
    }
}

const OFFLINE_NOTICE = `<!doctype html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0f0d0a">
<title>Sin conexión - La 501 Sports</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f0d0a;color:#fff;font-family:system-ui,sans-serif;text-align:center;padding:24px;box-sizing:border-box}
img{width:120px;height:auto;margin-bottom:24px}
h1{font-size:1.5rem;margin:0 0 8px}
p{color:#a1a1aa;margin:0 0 28px;max-width:320px}
a{display:inline-block;background:#C2410C;color:#fff;text-decoration:none;font-weight:700;padding:14px 28px;border-radius:8px}
</style></head><body><main>
<img src="/images/logo_501_transparente.png" alt="La 501 Sports">
<h1>Sin conexión</h1>
<p>Esta sección necesita internet. Revisa tu conexión o regresa al inicio.</p>
<a href="/">Regresar al inicio</a>
</main></body></html>`;

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await caches.match(request, { ignoreSearch: false });
    const network = fetch(request)
        .then((response) => {
            // Las respuestas opacas (status 0) de terceros también se pueden reutilizar
            if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
            return response;
        })
        .catch(() => cached || Response.error());
    return cached || network;
}
