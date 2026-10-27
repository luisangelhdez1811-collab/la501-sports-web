<?php

namespace App\Console\Commands;

use App\Events\MenuUpdated;
use App\Services\MenuExcelImporter;
use Illuminate\Console\Command;

class ImportMenu extends Command
{
    protected $signature = 'menu:importar
                            {archivo : Ruta del Excel (hojas Comidas, Bebidas, Extras y Salsas)}
                            {--simular : Muestra qué cambiaría sin guardar nada}';

    protected $description = 'Carga el menú desde el Excel de captura: productos, presentaciones, salsas y extras';

    public function handle(MenuExcelImporter $importer): int
    {
        $archivo = $this->argument('archivo');
        if (!is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");
            return self::FAILURE;
        }

        $simular = (bool) $this->option('simular');
        $reporte = $importer->import($archivo, $simular);

        $this->info(($simular ? '[SIMULACIÓN] ' : '') . 'Menú importado:');
        $this->line('  Nuevos: ' . count($reporte['created']));
        $this->line('  Actualizados (conservan foto e historial): ' . count($reporte['updated']));
        $this->line('  Archivados (ya no están en el menú): ' . count($reporte['archived']));
        $this->line('  Grupos de opciones: ' . collect($reporte['groups'])->map(fn ($n, $g) => "{$g} ({$n})")->implode(', '));

        if ($this->output->isVerbose()) {
            $this->newLine();
            $this->comment('Actualizados:');
            foreach ($reporte['updated'] as $linea) {
                $this->line("  · {$linea}");
            }
            $this->comment('Archivados:');
            foreach ($reporte['archived'] as $linea) {
                $this->line("  · {$linea}");
            }
        }

        if (!$simular) {
            event(new MenuUpdated());
        }

        return self::SUCCESS;
    }
}
