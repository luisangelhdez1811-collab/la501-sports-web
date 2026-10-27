<?php

namespace Tests\Feature;

use App\Models\OptionGroup;
use App\Models\Product;
use App\Services\MenuExcelImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MenuExcelImporterTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->archivo = tempnam(sys_get_temp_dir(), 'menu') . '.xlsx';
        $this->crearExcel();
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        parent::tearDown();
    }

    /** Mismo formato que el Excel de captura del menú, con unas cuantas filas. */
    private function crearExcel(): void
    {
        $libro = new Spreadsheet();
        $hojas = [
            'Comidas' => [
                ['Categoría', 'Subcategoría', 'Nombre', 'Descripción', 'Presentación / porción', 'Precio', 'Extras / opciones'],
                ['Burritos', '', 'Caníbal', 'Arrachera texana, chistorra, tocino, papa frita, envuelto en tortilla de harina caliente.', '', 165, 'Con papas +$22'],
                ['Hamburguesas', '', 'Boneless', 'Pan brioche, boneless bañados en tu salsa preferida, tocino, queso fundido y jitomate.', '', 115, ''],
                ['Alitas, Costillas y Especialidades', 'Alitas', 'Alitas 700 g', '', '700 g · 2 salsas', 270, ''],
                ['Arma tus Papas', 'Paso 1 · Base', 'Papas francesas o crisscut', 'Incluye queso amarillo y ranch.', '', 95, ''],
                ['Algo Dulce', 'Malteadas', 'Malteada', 'Sabores: moka, vainilla, chocolate o fresa.', '', 72, ''],
            ],
            'Bebidas' => [
                ['Categoría', 'Subcategoría', 'Nombre', 'Descripción', 'Presentación', 'Precio vaso / copeo / unidad', 'Precio litro', 'Precio botella'],
                ['Cervecería', 'Cervezas', 'Cerveza Corona', '', '', 35, '', ''],
                ['Destilados', 'Tequila', 'José Cuervo Especial Blanco', '', '1 L', 75, '', 770],
                ['Coctelería', 'Tequila', 'Margarita', 'Tequila, licor de naranja, miel de agave y limón.', '', 80, 160, ''],
            ],
            'Extras y Salsas' => [
                ['Tipo', 'Aplica a', 'Nombre', 'Detalle', 'Precio', 'Verificar', 'Nota'],
                ['Complemento', 'Burritos, hamburguesas y jochos', 'Con papas', '', 22, '', ''],
                ['Complemento', 'Alitas, boneless y especialidades', 'Salsa extra', '', 30, '', ''],
                ['Sazonador (incluido)', 'Arma tus Papas · Paso 2', 'Flamin Hot', '', '', '', ''],
                ['Especialidad', 'Arma tus Papas · Paso 3', 'Porción de Boneless', '4 piezas', 38, '', 'Máximo 5; se puede repetir hasta 3 veces la misma'],
                ['Salsa', 'Alitas, boneless, especialidades y papas · Paso 4', 'Buffalo', 'Picor: 2 de 5', '', '', ''],
                ['Salsa', 'Alitas, boneless, especialidades y papas · Paso 4', 'Macha', 'Picor: 5 de 5', '', '', ''],
            ],
        ];

        $libro->removeSheetByIndex(0);
        foreach ($hojas as $titulo => $filas) {
            $libro->createSheet()->setTitle($titulo)->fromArray($filas);
        }

        (new Xlsx($libro))->save($this->archivo);
    }

    public function test_importa_productos_presentaciones_y_estaciones(): void
    {
        app(MenuExcelImporter::class)->import($this->archivo);

        $burrito = Product::where('name', 'Burrito Caníbal')->firstOrFail();
        $this->assertSame('comidas', $burrito->section);
        $this->assertSame('cocina', $burrito->station);
        // La descripción se vuelve la lista de ingredientes (sin "envuelto en tortilla...")
        $this->assertSame(['Arrachera texana', 'Chistorra', 'Tocino', 'Papa frita'], $burrito->ingredientes->pluck('nombre')->all());

        $this->assertSame('ninguna', Product::where('name', 'Cerveza Corona')->value('station'));
        $this->assertSame('barra', Product::where('name', 'Malteada')->value('station'));

        $tequila = Product::where('name', 'José Cuervo Especial Blanco')->firstOrFail();
        $this->assertSame(['Copeo 1.5 oz' => 75.0, 'Botella 1 L' => 770.0], $tequila->variants->pluck('price', 'name')->all());

        $margarita = Product::where('name', 'Margarita')->firstOrFail();
        $this->assertSame(['Vaso', 'Litro'], $margarita->variants->pluck('name')->all());
    }

    public function test_crea_los_grupos_y_los_asigna_con_sus_limites(): void
    {
        app(MenuExcelImporter::class)->import($this->archivo);

        $limites = fn (string $producto) => Product::where('name', $producto)->firstOrFail()
            ->optionGroups->mapWithKeys(fn ($g) => [$g->name => [$g->pivot->min_select, $g->pivot->max_select]])->all();

        // Casi todo es voluntario (solo se recuerda al tomar el pedido); lo obligatorio es lo que
        // define el platillo: el sabor de la malteada y el tipo de papa
        $this->assertSame(['Salsa' => [0, 2], 'Salsa extra' => [0, 3]], $limites('Alitas 700 g'));
        $this->assertSame(['Salsa' => [0, 1], 'Papas' => [0, 1]], $limites('Hamburguesa Boneless'));
        $this->assertSame(['Papas' => [0, 1]], $limites('Burrito Caníbal'));
        $this->assertSame(['Tipo de papa' => [1, 1], 'Sazonador' => [0, 1], 'Especialidades' => [0, 5], 'Salsa' => [0, 1]], $limites('Arma tus Papas'));
        $this->assertSame(['Sabor Malteada' => [1, 1]], $limites('Malteada'));
        $this->assertSame(['Moka', 'Vainilla', 'Chocolate', 'Fresa'], OptionGroup::where('name', 'Sabor Malteada')->firstOrFail()->options->pluck('name')->all());

        // Las etiquetas no numeran pasos: no hay un orden para elegir
        $this->assertSame(0, OptionGroup::where('label', 'like', 'Paso%')->count());
        // "Sabores: moka, vainilla..." no se vuelve lista de ingredientes
        $this->assertSame(0, Product::where('name', 'Malteada')->firstOrFail()->ingredientes()->count());

        $especialidades = OptionGroup::where('name', 'Especialidades')->firstOrFail();
        $this->assertSame(3, $especialidades->max_per_option);
        $this->assertEquals(30, OptionGroup::where('name', 'Salsa extra')->firstOrFail()->options->first()->price);
    }

    public function test_conserva_los_productos_parecidos_y_archiva_lo_que_ya_no_esta(): void
    {
        $anterior = Product::create(['name' => 'Burrito Canival', 'price' => 150, 'category' => 'Burritos', 'image' => 'burrito.jpg', 'available' => 1]);
        $strombo = Product::create(['name' => 'Strombo Original', 'price' => 89, 'category' => 'Strombolis', 'available' => 1]);

        $reporte = app(MenuExcelImporter::class)->import($this->archivo);

        $anterior->refresh();
        $this->assertSame('Burrito Caníbal', $anterior->name);
        $this->assertEquals(165, $anterior->price);
        $this->assertSame('burrito.jpg', $anterior->image);

        $this->assertNotNull($strombo->fresh()->archived_at);
        $this->assertSame(0, (int) $strombo->fresh()->available);
        $this->assertContains('Strombo Original', $reporte['archived']);
    }

    public function test_importar_dos_veces_no_duplica(): void
    {
        $importer = app(MenuExcelImporter::class);
        $importer->import($this->archivo);
        $total = Product::count();

        $reporte = $importer->import($this->archivo);

        $this->assertSame($total, Product::count());
        $this->assertSame([], $reporte['created']);
        $this->assertSame([], $reporte['archived']);
    }

    public function test_la_simulacion_no_guarda_nada(): void
    {
        app(MenuExcelImporter::class)->import($this->archivo, true);

        $this->assertSame(0, Product::count());
        $this->assertSame(0, OptionGroup::count());
    }
}
