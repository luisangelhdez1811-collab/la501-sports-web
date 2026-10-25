<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\MenuCategory;
use App\Models\MenuOption;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Carga el menú desde el Excel de captura (hojas «Comidas», «Bebidas» y «Extras y Salsas»).
 *
 * - Un producto que ya existía con el mismo nombre (o casi: "Burrito Canival" = "Burrito
 *   Caníbal") se actualiza en su lugar y conserva su foto, sus promociones y su historial.
 * - Lo que ya no está en el Excel se archiva (deja de mostrarse, pero no se borra).
 * - Los extras, salsas y opciones de «Arma tus Papas» se vuelven grupos de opciones. Son
 *   voluntarios (mínimo 0): al tomar el pedido solo se recuerda lo que no se eligió. Solo el
 *   sabor de la malteada es obligatorio.
 *
 * Se puede correr varias veces: la segunda vez actualiza en vez de duplicar.
 */
class MenuExcelImporter
{
    private const GROUP_PAPA_TYPE = 'Tipo de papa';
    private const GROUP_SEASONING = 'Sazonador';
    private const GROUP_SPECIALTIES = 'Especialidades';
    private const GROUP_SAUCE = 'Salsa';
    private const GROUP_EXTRA_SAUCE = 'Salsa extra';
    private const GROUP_FLAVOR = 'Sabor';

    // Orden en que se muestran los grupos dentro de un platillo
    private const GROUP_ORDER = [
        self::GROUP_PAPA_TYPE => 1, self::GROUP_SEASONING => 2, self::GROUP_SPECIALTIES => 3,
        self::GROUP_SAUCE => 4, self::GROUP_FLAVOR => 5, 'Papas' => 6, 'Extra' => 7, self::GROUP_EXTRA_SAUCE => 8,
    ];

    // Bebidas que se entregan tal cual (no pasan por barra)
    private const NO_PREP_SUBCATEGORIES = ['cervezas', 'cubetas', 'embotelladas', 'mezcladores'];
    // Lo de «Algo Dulce» que prepara la barra
    private const BAR_DESSERT_SUBCATEGORIES = ['malteadas', 'bebidas calientes'];

    private const BUILD_YOUR_FRIES = 'Arma tus Papas';

    private array $report = ['created' => [], 'updated' => [], 'archived' => [], 'groups' => [], 'warnings' => []];
    private array $usedProductIds = [];
    private Collection $existing;

    public function import(string $path, bool $dryRun = false): array
    {
        $this->report = ['created' => [], 'updated' => [], 'archived' => [], 'groups' => [], 'warnings' => []];
        $this->usedProductIds = [];
        $sheets = $this->readWorkbook($path);

        DB::beginTransaction();

        try {
            $this->existing = Product::with('ingredientes')->orderBy('id')->get();

            $products = array_merge(
                $this->importFood($sheets['comidas']),
                $this->importDrinks($sheets['bebidas'])
            );

            $this->importOptionGroups($sheets['extras'], $products);
            $this->archiveMissing();

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->report;
    }

    // -------------------------------------------------------------------------
    // Lectura del archivo
    // -------------------------------------------------------------------------

    private function readWorkbook(string $path): array
    {
        $book = IOFactory::load($path);
        $sheets = [];

        foreach ($book->getWorksheetIterator() as $sheet) {
            $title = $this->normalize($sheet->getTitle());
            $key = match (true) {
                str_contains($title, 'comida') => 'comidas',
                str_contains($title, 'bebida') => 'bebidas',
                str_contains($title, 'extra') || str_contains($title, 'salsa') => 'extras',
                default => null,
            };

            if ($key) {
                $sheets[$key] = $this->rows($sheet->toArray(null, true, false, false));
            }
        }

        foreach (['comidas', 'bebidas', 'extras'] as $requerida) {
            if (!isset($sheets[$requerida])) {
                throw new RuntimeException("Al Excel le falta la hoja «{$requerida}». Usa el formato de captura del menú (Comidas, Bebidas, Extras y Salsas).");
            }
        }

        return $sheets;
    }

    /** Convierte las filas en arreglos con llave = encabezado normalizado ("precio litro"). */
    private function rows(array $raw): array
    {
        $headerIndex = null;
        foreach ($raw as $i => $row) {
            $normalizados = array_map(fn ($v) => $this->normalize((string) $v), $row);
            if (in_array('nombre', $normalizados, true)) {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $headers = array_map(fn ($v) => $this->normalize((string) $v), $raw[$headerIndex]);
        $rows = [];

        foreach (array_slice($raw, $headerIndex + 1) as $row) {
            $assoc = [];
            foreach ($headers as $col => $header) {
                if ($header !== '') {
                    $assoc[$header] = is_string($row[$col] ?? null) ? trim($row[$col]) : ($row[$col] ?? null);
                }
            }
            if (trim((string) ($assoc['nombre'] ?? '')) !== '') {
                $rows[] = $assoc;
            }
        }

        return $rows;
    }

    private function column(array $row, string $prefix): mixed
    {
        foreach ($row as $header => $value) {
            if (str_starts_with($header, $prefix)) {
                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $limpio = preg_replace('/[^0-9.]/', '', (string) $value);

        return $limpio === '' ? null : (float) $limpio;
    }

    // -------------------------------------------------------------------------
    // Comidas y bebidas
    // -------------------------------------------------------------------------

    private function importFood(array $rows): array
    {
        $products = [];

        foreach ($rows as $orden => $row) {
            $category = (string) $this->column($row, 'categoria');
            $subcategory = (string) $this->column($row, 'subcategoria');
            $shortName = (string) $row['nombre'];
            $description = $this->column($row, 'descripcion');
            $isFries = $this->normalize($category) === $this->normalize(self::BUILD_YOUR_FRIES);

            $station = in_array($this->normalize($subcategory), self::BAR_DESSERT_SUBCATEGORIES, true)
                ? Product::STATION_BAR
                : Product::STATION_KITCHEN;

            $product = $this->upsertProduct([
                'name' => $isFries ? self::BUILD_YOUR_FRIES : $this->foodName($category, $shortName),
                'category' => $category,
                // "Paso 1 · Base" es parte del armado, no una subcategoría de la carta
                'subcategory' => str_starts_with($this->normalize($subcategory), 'paso') ? null : ($subcategory ?: null),
                'description' => $description,
                'presentation' => $this->column($row, 'presentacion'),
                'price' => $this->money($this->column($row, 'precio')) ?? 0,
                'section' => Product::SECTION_FOOD,
                'station' => $station,
                'sort_order' => $orden + 1,
            ], []);

            $this->syncIngredients($product, $description);
            $products[] = ['product' => $product, 'row' => $row, 'short_name' => $shortName];
        }

        return $products;
    }

    private function importDrinks(array $rows): array
    {
        $products = [];

        foreach ($rows as $orden => $row) {
            $category = (string) $this->column($row, 'categoria');
            $subcategory = (string) $this->column($row, 'subcategoria');
            $name = (string) $row['nombre'];
            $presentation = $this->column($row, 'presentacion');
            $unitPrice = $this->money($this->column($row, 'precio vaso'));
            $literPrice = $this->money($this->column($row, 'precio litro'));
            $bottlePrice = $this->money($this->column($row, 'precio botella'));

            $variants = [];
            if ($bottlePrice) {
                $variants = [
                    ['name' => 'Copeo 1.5 oz', 'price' => $unitPrice],
                    ['name' => trim('Botella ' . ($presentation ?? '')), 'price' => $bottlePrice],
                ];
                $presentation = null;
            } elseif ($literPrice) {
                $variants = [
                    ['name' => 'Vaso', 'price' => $unitPrice],
                    ['name' => 'Litro', 'price' => $literPrice],
                ];
            }

            $noPrep = in_array($this->normalize($subcategory), self::NO_PREP_SUBCATEGORIES, true);

            $product = $this->upsertProduct([
                'name' => $this->normalize($category) === '6 shots' ? '6 Shots ' . $name : $name,
                'category' => $category,
                'subcategory' => $subcategory ?: null,
                'description' => $this->column($row, 'descripcion'),
                'presentation' => $presentation,
                'price' => $unitPrice ?? 0,
                'section' => Product::SECTION_DRINKS,
                'station' => $noPrep ? Product::STATION_NONE : Product::STATION_BAR,
                'sort_order' => $orden + 1,
            ], array_filter($variants, fn ($v) => $v['price'] !== null));

            $products[] = ['product' => $product, 'row' => $row, 'short_name' => $name];
        }

        return $products;
    }

    /** En la carta dice "Clásica" bajo «Hamburguesas»; en cocina y tickets debe decir "Hamburguesa Clásica". */
    private function foodName(string $category, string $name): string
    {
        return match ($this->normalize($category)) {
            'burritos' => 'Burrito ' . $name,
            'hamburguesas' => 'Hamburguesa ' . $name,
            'jochos' => 'Jocho ' . $name,
            'tacos' => $this->normalize($name) === 'mixto' ? 'Tacos Mixtos' : 'Tacos de ' . $name,
            default => $name,
        };
    }

    private function upsertProduct(array $data, array $variants): Product
    {
        $product = $this->findExisting($data['name'], $data['category']) ?? new Product();
        $isNew = !$product->exists;
        $before = $product->name;

        $product->fill($data + ['available' => 1, 'archived_at' => null]);
        $product->save();
        MenuCategory::ensure($product->category, $product->section);

        $this->usedProductIds[] = $product->id;

        $product->variants()->delete();
        foreach (array_values($variants) as $i => $variant) {
            $product->variants()->create($variant + ['sort_order' => $i + 1]);
        }

        if ($isNew) {
            $this->report['created'][] = $product->name;
        } else {
            $this->report['updated'][] = $before === $product->name ? $product->name : "{$before} → {$product->name}";
        }

        return $product;
    }

    /**
     * Busca el producto que ya existía: primero el mismo nombre y categoría (una importación
     * anterior), luego el nombre sin acentos ni palabras de relleno, y al final uno casi igual
     * (una letra de diferencia) para errores de dedo del menú anterior.
     */
    private function findExisting(string $name, string $category): ?Product
    {
        $disponibles = $this->existing->reject(fn (Product $p) => in_array($p->id, $this->usedProductIds, true));

        $exacto = $disponibles->first(fn (Product $p) => $p->name === $name && $p->category === $category);
        if ($exacto) {
            return $exacto;
        }

        $clave = $this->matchKey($name);
        $porClave = $disponibles->first(fn (Product $p) => $this->matchKey($p->name) === $clave);
        if ($porClave || mb_strlen($clave) < 6) {
            return $porClave;
        }

        $parecido = $disponibles
            ->map(fn (Product $p) => ['product' => $p, 'distance' => levenshtein($this->matchKey($p->name), $clave)])
            ->filter(fn ($c) => $c['distance'] <= 1)
            ->sortBy('distance')
            ->first()['product'] ?? null;

        // "Aros de Cebolla" = "Aros Cebolla 12 piezas"; "César con Pollo a la Plancha" = "César con Pollo"
        return $parecido ?? $disponibles->first(function (Product $p) use ($clave) {
            $otra = $this->matchKey($p->name);
            return mb_strlen($otra) >= 6 && (str_starts_with($otra, $clave . ' ') || str_starts_with($clave, $otra . ' '));
        });
    }

    private function matchKey(string $name): string
    {
        $key = $this->normalize($name);
        $key = preg_replace('/\b(\d+)\s*(gr|grs|g)\b/', '$1g', $key);
        $key = preg_replace('/\b(\d+(\.\d+)?)\s*(oz|ml|l)\b/', '', $key);
        $key = preg_replace('/\b(de|del|la|el|los|las|y|con|copeo|botella)\b/', ' ', $key);
        $key = preg_replace('/\+.*$/', '', $key);

        return trim(preg_replace('/\s+/', ' ', $key));
    }

    /**
     * La descripción del menú nuevo es la lista de ingredientes ("Pan brioche, carne de res,
     * tocino..."): con ella el cliente puede pedir "sin cebolla". Se conserva el enlace a
     * inventario de los ingredientes que ya existían con el mismo nombre.
     */
    private function syncIngredients(Product $product, ?string $description): void
    {
        $nombres = $this->ingredientsFrom($description);
        if (count($nombres) < 3) {
            return;
        }

        $inventarioPorNombre = $product->ingredientes
            ->filter(fn ($i) => $i->inventory_id)
            ->keyBy(fn ($i) => $this->normalize($i->nombre));

        $product->ingredientes()->delete();

        foreach ($nombres as $nombre) {
            $anterior = $inventarioPorNombre->get($this->normalize($nombre));
            Ingrediente::create([
                'product_id' => $product->id,
                'nombre' => $nombre,
                'inventory_id' => $anterior?->inventory_id,
                'cantidad_usada' => $anterior?->cantidad_usada ?? 1,
            ]);
        }
    }

    private function ingredientsFrom(?string $description): array
    {
        // "Sabores: moka, vainilla..." no es una lista de ingredientes: se elige como sabor
        if (!$description || substr_count($description, ',') < 2 || preg_match('/^\s*sabores?:/iu', $description)) {
            return [];
        }

        $texto = rtrim(trim($description), '.');
        $texto = preg_replace('/\s+y\s+(?=[^,]+$)/u', ', ', $texto);

        return collect(explode(',', $texto))
            ->map(fn ($parte) => trim($parte))
            ->reject(fn ($parte) => $parte === '' || preg_match('/envuelt|tortilla de harina|tu salsa|salsa favorita|un toque de/iu', $parte))
            ->map(fn ($parte) => preg_replace('/^\d+\s*g\s+de\s+/u', '', $parte))
            ->map(fn ($parte) => Str::ucfirst($parte))
            ->unique()
            ->values()
            ->all();
    }

    private function archiveMissing(): void
    {
        $this->existing
            ->reject(fn (Product $p) => in_array($p->id, $this->usedProductIds, true) || $p->archived_at)
            ->each(function (Product $p) {
                $p->forceFill(['available' => 0, 'archived_at' => now()])->save();
                $this->report['archived'][] = $p->name;
            });
    }

    // -------------------------------------------------------------------------
    // Extras, salsas y "Arma tus Papas"
    // -------------------------------------------------------------------------

    private function importOptionGroups(array $rows, array $products): void
    {
        $porTipo = collect($rows)->groupBy(fn ($r) => $this->normalize((string) $this->column($r, 'tipo')));
        $productos = collect($products);
        $asignaciones = [];

        $salsas = $porTipo->get('salsa', collect());
        $salsa = $this->upsertGroup(self::GROUP_SAUCE, 'Elige tu salsa', 0, 1, 1,
            $salsas->map(fn ($r) => ['name' => $r['nombre'], 'detail' => $this->column($r, 'detalle'), 'price' => 0])->all());

        // Complementos: se agrupan por a qué platillos aplican ("Con papas" y "Con papas sazonadas" van juntos)
        foreach ($porTipo->get('complemento', collect())->groupBy(fn ($r) => (string) $this->column($r, 'aplica')) as $aplicaA => $filas) {
            $esSalsaExtra = $filas->contains(fn ($r) => str_contains($this->normalize($r['nombre']), 'salsa extra'));

            if ($esSalsaExtra) {
                $precio = $this->money($this->column($filas->first(), 'precio')) ?? 0;
                $grupo = $this->upsertGroup(self::GROUP_EXTRA_SAUCE, 'Salsa extra (+$' . number_format($precio, 0) . ' c/u)', 0, 3, 1,
                    $salsas->map(fn ($r) => ['name' => $r['nombre'], 'detail' => $this->column($r, 'detalle'), 'price' => $precio])->all());
            } else {
                $esPapas = $filas->every(fn ($r) => str_contains($this->normalize($r['nombre']), 'papas'));
                $nombre = $esPapas ? 'Papas' : 'Extra';
                $grupo = $this->upsertGroup($nombre, $esPapas ? '¿Lo quieres con papas?' : 'Extras', 0, 1, 1,
                    $filas->map(fn ($r) => ['name' => $r['nombre'], 'detail' => $this->column($r, 'detalle'), 'price' => $this->money($this->column($r, 'precio')) ?? 0])->all());
            }

            foreach ($this->productsInCategories($productos, $aplicaA) as $item) {
                $asignaciones[$item['product']->id][$grupo->id] = [$grupo->min_select, $grupo->max_select];
            }
        }

        // Salsas incluidas: alitas/boneless/especialidades según su presentación ("2 salsas"),
        // y los platillos que dicen "en tu salsa preferida"
        foreach ($productos as $item) {
            $product = $item['product'];
            $presentacion = $this->normalize((string) $product->presentation);
            $descripcion = $this->normalize((string) $product->description);

            if (preg_match('/(\d+)\s*salsas?/', $presentacion, $m)) {
                $asignaciones[$product->id][$salsa->id] = [0, (int) $m[1]];
                $extra = OptionGroup::where('name', self::GROUP_EXTRA_SAUCE)->first();
                if ($extra && !isset($asignaciones[$product->id][$extra->id])) {
                    $asignaciones[$product->id][$extra->id] = [0, $extra->max_select];
                }
            } elseif (preg_match('/salsa (preferida|favorita)/', $descripcion)) {
                $asignaciones[$product->id][$salsa->id] = [0, 1];
            } elseif (preg_match('/agrega una salsa/', $descripcion)) {
                $asignaciones[$product->id][$salsa->id] = [0, 1];
            }
        }

        $this->importBuildYourFries($porTipo, $productos, $salsa, $asignaciones);
        $this->importFlavors($productos, $asignaciones);

        foreach ($productos as $item) {
            $product = $item['product'];
            $sync = [];
            foreach ($asignaciones[$product->id] ?? [] as $groupId => [$min, $max]) {
                $nombre = OptionGroup::find($groupId)->name;
                $sync[$groupId] = ['min_select' => $min, 'max_select' => $max, 'sort_order' => self::GROUP_ORDER[$nombre] ?? 9];
            }
            $product->optionGroups()->sync($sync);
        }
    }

    private function importBuildYourFries(Collection $porTipo, Collection $productos, OptionGroup $salsa, array &$asignaciones): void
    {
        $papas = $productos->first(fn ($i) => $i['product']->name === self::BUILD_YOUR_FRIES);
        if (!$papas) {
            return;
        }

        // Tipo de papa: "Papas francesas o crisscut" → Francesas / Crisscut
        $base = preg_replace('/^papas\s+/iu', '', $papas['short_name']);
        $tipos = array_map(fn ($t) => ['name' => Str::ucfirst(trim($t)), 'price' => 0], preg_split('/\s+o\s+|,/u', $base));
        // La papa es la base del platillo: sin ella no se puede preparar, así que es obligatoria
        $tipo = $this->upsertGroup(self::GROUP_PAPA_TYPE, 'Elige tu papa', 1, 1, 1, $tipos);

        $sazonadores = $porTipo->first(fn ($rows, $tipo) => str_starts_with($tipo, 'sazonador'), collect());
        $sazonador = $this->upsertGroup(self::GROUP_SEASONING, 'Elige tu sazonador', 0, 1, 1,
            $sazonadores->map(fn ($r) => ['name' => $r['nombre'], 'price' => 0])->all());

        $especialidades = $porTipo->get('especialidad', collect());
        $nota = $this->normalize((string) $this->column($especialidades->first() ?? [], 'nota'));
        $max = preg_match('/maximo (\d+)/', $nota, $m) ? (int) $m[1] : 5;
        $repetir = preg_match('/hasta (\d+) veces/', $nota, $m) ? (int) $m[1] : 1;
        $esp = $this->upsertGroup(self::GROUP_SPECIALTIES, "Agrega especialidades (máx. {$max})", 0, $max, $repetir,
            $especialidades->map(fn ($r) => ['name' => $r['nombre'], 'detail' => $this->column($r, 'detalle'), 'price' => $this->money($this->column($r, 'precio')) ?? 0])->all());

        $id = $papas['product']->id;
        $asignaciones[$id][$tipo->id] = [1, 1];
        $asignaciones[$id][$sazonador->id] = [0, 1];
        $asignaciones[$id][$esp->id] = [0, $max];
        $asignaciones[$id][$salsa->id] = [0, 1];
    }

    /** "Sabores: moka, vainilla, chocolate o fresa." → grupo para elegir el sabor */
    private function importFlavors(Collection $productos, array &$asignaciones): void
    {
        foreach ($productos as $item) {
            $product = $item['product'];
            if (!preg_match('/^sabores?:\s*(.+)$/iu', trim((string) $product->description), $m)) {
                continue;
            }

            $sabores = preg_split('/\s*,\s*|\s+o\s+|\s+y\s+/u', rtrim($m[1], '.'));
            $grupo = $this->upsertGroup(self::GROUP_FLAVOR . ' ' . $product->name, 'Elige el sabor', 1, 1, 1,
                array_map(fn ($s) => ['name' => Str::ucfirst(trim($s)), 'price' => 0], $sabores));
            // El sabor es lo único obligatorio: una malteada no sale sin sabor
            $asignaciones[$product->id][$grupo->id] = [1, 1];
            // El sabor sustituye a los ingredientes (no hay nada que quitar)
            $product->ingredientes()->delete();
        }
    }

    private function productsInCategories(Collection $productos, string $aplicaA): Collection
    {
        $palabras = collect(preg_split('/[\s,·]+/u', $this->normalize($aplicaA)))
            ->reject(fn ($p) => in_array($p, ['', 'y', 'e', 'de', 'paso'], true) || is_numeric($p));

        return $productos->filter(function ($item) use ($palabras) {
            $categoria = collect(preg_split('/[\s,]+/u', $this->normalize((string) $item['product']->category)));
            return $palabras->intersect($categoria)->isNotEmpty();
        });
    }

    private function upsertGroup(string $name, string $label, int $min, int $max, int $maxPerOption, array $options): OptionGroup
    {
        $group = OptionGroup::updateOrCreate(['name' => $name], [
            'label' => $label,
            'min_select' => $min,
            'max_select' => $max,
            'max_per_option' => $maxPerOption,
            'sort_order' => self::GROUP_ORDER[$name] ?? 9,
        ]);

        $vigentes = [];
        foreach (array_values($options) as $i => $option) {
            $vigentes[] = MenuOption::updateOrCreate(
                ['option_group_id' => $group->id, 'name' => $option['name']],
                ['detail' => $option['detail'] ?? null, 'price' => $option['price'] ?? 0, 'available' => true, 'sort_order' => $i + 1]
            )->id;
        }
        $group->options()->whereNotIn('id', $vigentes)->delete();

        $this->report['groups'][$name] = count($vigentes);

        return $group;
    }

    private function normalize(string $text): string
    {
        return trim(mb_strtolower(Str::ascii($text)));
    }
}
