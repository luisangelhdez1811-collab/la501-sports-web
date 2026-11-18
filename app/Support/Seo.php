<?php

namespace App\Support;

use App\Models\Setting;
use App\Services\ProviderInfo;

/**
 * Lo que leen Google y los asistentes de IA: datos del negocio (JSON-LD), robots.txt, sitemap.xml y llms.txt.
 *
 * Todo sale de la tabla settings (dirección y horarios que el restaurante edita en el panel) y de
 * ProviderInfo, para que coincida siempre con lo que dice la página y con el Perfil de Empresa de Google.
 */
class Seo
{
    /** Páginas públicas que se pueden indexar: nombre de ruta => [prioridad, frecuencia de cambio]. */
    public const PAGINAS = [
        'nosotros' => ['1.0', 'weekly'],
        'menu' => ['0.9', 'weekly'],
        'pedido' => ['0.9', 'weekly'],
        'promociones' => ['0.8', 'weekly'],
        'novedades' => ['0.7', 'weekly'],
        'reservaciones' => ['0.7', 'monthly'],
        'ubicacion' => ['0.7', 'monthly'],
        'contacto' => ['0.6', 'monthly'],
        'aviso-privacidad' => ['0.3', 'yearly'],
        'terminos-condiciones' => ['0.3', 'yearly'],
    ];

    /** Zonas privadas o sin valor para buscadores. */
    public const NO_RASTREAR = [
        '/admin', '/cocina', '/mesero', '/api/', '/perfil', '/configuracion', '/mis-pedidos', '/pedido/',
        '/checkout', '/carrito', '/login', '/registro', '/recuperar', '/restablecer', '/verificar',
        '/confirmar-contrasena', '/aceptar-avisos', '/newsletter/', '/clear-laravel-cache', '/run-auto-backup',
    ];

    private const DIAS = [
        'lunes' => 'Monday', 'martes' => 'Tuesday', 'miercoles' => 'Wednesday', 'jueves' => 'Thursday',
        'viernes' => 'Friday', 'sabado' => 'Saturday', 'domingo' => 'Sunday',
    ];

    public static function robots(): string
    {
        // Fuera de producción (pruebas, copias de respaldo) no se indexa nada
        if (!app()->environment('production')) {
            return "User-agent: *\nDisallow: /\n";
        }

        $lineas = ['User-agent: *', 'Allow: /'];
        foreach (self::NO_RASTREAR as $ruta) {
            $lineas[] = 'Disallow: ' . $ruta;
        }
        $lineas[] = '';
        $lineas[] = 'Sitemap: ' . url('sitemap.xml');

        return implode("\n", $lineas) . "\n";
    }

    public static function sitemap(): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach (self::PAGINAS as $ruta => [$prioridad, $frecuencia]) {
            $xml[] = '  <url><loc>' . htmlspecialchars(route($ruta), ENT_XML1) . '</loc>'
                . "<changefreq>{$frecuencia}</changefreq><priority>{$prioridad}</priority></url>";
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    /** llms.txt (llmstxt.org): resumen en Markdown para que los asistentes de IA respondan bien sobre el lugar. */
    public static function llms(): string
    {
        $p = ProviderInfo::get();
        $horario = self::horarioLegible();

        $md = [
            '# ' . $p['name'],
            '',
            '> Restaurant-bar deportivo familiar en Huejutla de Reyes, Hidalgo, México. Comida y bebidas; pedidos a domicilio con pago en efectivo o tarjeta, reservaciones y promociones.',
            '',
            '- Dirección: ' . self::direccion(),
            '- Teléfono: ' . $p['phone'],
            '- Correo: ' . $p['email'],
        ];
        if ($horario) {
            $md[] = '- Horario: ' . $horario;
        }
        $md[] = '- Precios en pesos mexicanos (MXN), con IVA incluido.';
        $md[] = '- Las bebidas con alcohol solo se venden a mayores de 18 años.';
        $md[] = '';
        $md[] = '## Páginas';
        $md[] = '';
        $md[] = '- [Menú](' . route('menu') . '): platillos, bebidas y precios vigentes.';
        $md[] = '- [Pedir a domicilio](' . route('pedido') . '): pedido en línea.';
        $md[] = '- [Promociones](' . route('promociones') . '): promociones activas.';
        $md[] = '- [Novedades](' . route('novedades') . '): noticias y eventos.';
        $md[] = '- [Reservaciones](' . route('reservaciones') . '): reservar mesa.';
        $md[] = '- [Ubicación](' . route('ubicacion') . '): cómo llegar.';
        $md[] = '- [Contacto](' . route('contacto') . ')';
        $md[] = '';
        $md[] = '## Datos';
        $md[] = '';
        $md[] = '- [Menú en JSON](' . url('api/v1/public/products') . '): productos disponibles con nombre, descripción, categoría y precio.';
        $md[] = '';
        $md[] = '## Optional';
        $md[] = '';
        $md[] = '- [Aviso de Privacidad](' . route('aviso-privacidad') . ')';
        $md[] = '- [Términos y Condiciones](' . route('terminos-condiciones') . ')';

        return implode("\n", $md) . "\n";
    }

    /** Datos estructurados schema.org/Restaurant para las páginas de inicio, ubicación y contacto. */
    public static function restaurantSchema(): array
    {
        $p = ProviderInfo::get();
        $datos = Setting::whereIn('key', ['address_line1', 'address_line2', 'address_line3'])->pluck('value', 'key');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => $p['name'],
            'url' => url('/'),
            'logo' => asset('images/logo_501_transparente.png'),
            'image' => asset('images/logo_501_transparente.png'),
            'telephone' => '+52 ' . $p['phone'],
            'email' => $p['email'],
            'servesCuisine' => 'Mexicana',
            'priceRange' => '$$',
            'acceptsReservations' => true,
            'hasMenu' => route('menu'),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => trim(($datos['address_line1'] ?? '') . ', ' . ($datos['address_line2'] ?? ''), ' ,') ?: null,
                'addressLocality' => 'Huejutla de Reyes',
                'addressRegion' => 'Hidalgo',
                'postalCode' => '43000',
                'addressCountry' => 'MX',
            ]),
        ];

        if ($horas = self::horarios()) {
            $schema['openingHoursSpecification'] = $horas;
        }

        return $schema;
    }

    private static function direccion(): string
    {
        $datos = Setting::whereIn('key', ['address_line1', 'address_line2', 'address_line3'])->pluck('value', 'key');
        $texto = trim(implode(', ', array_filter([$datos['address_line1'] ?? null, $datos['address_line2'] ?? null, $datos['address_line3'] ?? null])));

        return $texto !== '' ? $texto : ProviderInfo::get()['address'];
    }

    /** Horarios del panel («1:00 PM – 10:30 PM») como OpeningHoursSpecification; los días que no se entienden se omiten. */
    private static function horarios(): array
    {
        $guardados = Setting::whereIn('key', array_map(fn ($d) => "schedule_{$d}", array_keys(self::DIAS)))->pluck('value', 'key');
        $porHorario = [];

        foreach (self::DIAS as $dia => $enIngles) {
            $rango = self::rango((string) ($guardados["schedule_{$dia}"] ?? ''));
            if ($rango) {
                $porHorario[implode('-', $rango)][] = $enIngles;
            }
        }

        $especificacion = [];
        foreach ($porHorario as $rango => $dias) {
            [$abre, $cierra] = explode('-', $rango);
            $especificacion[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $dias, 'opens' => $abre, 'closes' => $cierra];
        }

        return $especificacion;
    }

    private static function horarioLegible(): ?string
    {
        $guardados = Setting::whereIn('key', array_map(fn ($d) => "schedule_{$d}", array_keys(self::DIAS)))->pluck('value', 'key')->filter();
        if ($guardados->isEmpty()) {
            return null;
        }

        $valores = $guardados->unique();
        if ($valores->count() === 1 && $guardados->count() === 7) {
            return 'todos los días, ' . $valores->first();
        }

        return collect(self::DIAS)->keys()
            ->filter(fn ($d) => filled($guardados["schedule_{$d}"] ?? null))
            ->map(fn ($d) => ucfirst($d) . ': ' . $guardados["schedule_{$d}"])
            ->implode('; ');
    }

    /** @return array{0: string, 1: string}|null horas de apertura y cierre en formato 24 h. */
    private static function rango(string $texto): ?array
    {
        if (!preg_match('/(\d{1,2}):(\d{2})\s*([AP])\.?\s*M\.?\D+(\d{1,2}):(\d{2})\s*([AP])\.?\s*M/i', $texto, $m)) {
            return null;
        }

        $a24 = function (int $h, int $min, string $ap): string {
            $h = $h % 12 + (strtoupper($ap) === 'P' ? 12 : 0);

            return sprintf('%02d:%02d', $h, $min);
        };

        return [$a24((int) $m[1], (int) $m[2], $m[3]), $a24((int) $m[4], (int) $m[5], $m[6])];
    }
}
