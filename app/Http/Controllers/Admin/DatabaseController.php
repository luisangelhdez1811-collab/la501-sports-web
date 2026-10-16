<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseController extends Controller
{
    /**
     * Mantenimiento de Índices y Tablas (Optimización)
     */
    public function optimize(Request $request)
    {
        $action = $request->input('action');
        
        try {
            // Obtenemos todas las tablas de la base de datos
            $tables = array_map('current', DB::select('SHOW TABLES'));
            
            if ($action === 'indices') {
                // Analizar tablas (Actualiza la distribución de claves para los índices)
                foreach ($tables as $table) {
                    DB::statement("ANALYZE TABLE `{$table}`");
                }
                $message = 'Mantenimiento de índices ejecutado: Estadísticas actualizadas correctamente.';
                
            } elseif ($action === 'tablas') {
                // Optimizar tablas (Reorganiza el almacenamiento físico, útil tras muchos deletes/updates)
                foreach ($tables as $table) {
                    DB::statement("OPTIMIZE TABLE `{$table}`");
                }
                $message = 'Optimización de tablas completada: Espacio defragmentado.';
                
            } else {
                return back()->with('error', 'Acción de optimización no válida.');
            }

            Log::info("Base de datos optimizada: {$action} por el usuario ID " . auth()->id());
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error en optimización de BD: ' . $e->getMessage());
            return back()->with('error', 'Error al optimizar: ' . $e->getMessage());
        }
    }

    /**
     * Reorganización y Reindexado
     * Nota: En MySQL, OPTIMIZE TABLE ya hace el reindexado
     */
    public function reindex()
    {
        try {
            $tables = array_map('current', DB::select('SHOW TABLES'));

            foreach ($tables as $table) {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            }

            Log::info("Reindexado completo de la base de datos ejecutado.");
            return back()->with('success', 'Reorganización y reindexado completado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error al reindexar BD: ' . $e->getMessage());
            return back()->with('error', 'Error durante el reindexado: ' . $e->getMessage());
        }
    }

    /**
     * Limpieza de Datos (Cleanup)
     */
    public function cleanup()
    {
        try {
            $deletedCount = 0;

            // 1. Limpiar notificaciones antiguas (más de 30 días)
            if (Schema::hasTable('notifications')) {
                $deletedCount += DB::table('notifications')
                    ->where('created_at', '<', Carbon::now()->subDays(30))
                    ->delete();
            }

            // 2. Limpiar tokens de reseteo de contraseña expirados
            if (Schema::hasTable('password_reset_tokens')) {
                // Asumiendo que expiran rápido, borramos los de hace más de 1 día
                $deletedCount += DB::table('password_reset_tokens')
                    ->where('created_at', '<', Carbon::now()->subDays(1))
                    ->delete();
            }
            
            // 3. Limpiar Jobs fallidos (si usas queues)
            if (Schema::hasTable('failed_jobs')) {
                $deletedCount += DB::table('failed_jobs')
                    ->where('failed_at', '<', Carbon::now()->subDays(15))
                    ->delete();
            }

            // 4. Limpiar caché expirada de la base de datos (si usas driver de cache en BD)
            if (Schema::hasTable('cache')) {
                $deletedCount += DB::table('cache')
                    ->where('expiration', '<', Carbon::now()->getTimestamp())
                    ->delete();
            }

            Log::info("Limpieza de BD ejecutada. Registros eliminados: {$deletedCount}");
            
            return back()->with('success', "Limpieza finalizada. Se purgaron {$deletedCount} registros obsoletos.");

        } catch (\Exception $e) {
            Log::error('Error en limpieza de BD: ' . $e->getMessage());
            return back()->with('error', 'Error durante la limpieza: ' . $e->getMessage());
        }
    }

    /**
     * Reportes de Información
     */
    public function reports()
    {
        try {
            $dbName = env('DB_DATABASE');
            
            // Obtener el tamaño total de la base de datos en MB
            $sizeQuery = DB::select("
                SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$dbName]);
            
            $totalSizeMb = round($sizeQuery[0]->size_mb ?? 0, 2);

            // Obtener estadísticas por tabla
            $tablesStatus = DB::select("SHOW TABLE STATUS");
            
            $reportData = [
                'Fecha de Reporte' => Carbon::now()->format('Y-m-d H:i:s'),
                'Base de Datos' => $dbName,
                'Tamaño Total (MB)' => $totalSizeMb,
                'Motor Predeterminado' => 'InnoDB',
                'Tablas' => []
            ];

            foreach ($tablesStatus as $table) {
                $reportData['Tablas'][] = [
                    'Nombre' => $table->Name,
                    'Filas' => $table->Rows,
                    'Tamaño Datos (KB)' => round($table->Data_length / 1024, 2),
                    'Tamaño Índices (KB)' => round($table->Index_length / 1024, 2),
                    'Motor' => $table->Engine,
                ];
            }

            // Crear un archivo JSON temporal con el reporte
            $fileName = 'db_report_' . Carbon::now()->format('Y_m_d_His') . '.json';
            $content = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // Guardar en storage local y retornar como descarga
            Storage::disk('local')->put('reports/' . $fileName, $content);
            $filePath = storage_path('app/reports/' . $fileName);

            Log::info("Reporte de base de datos generado: {$fileName}");

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error al generar reporte de BD: ' . $e->getMessage());
            return back()->with('error', 'No se pudo generar el reporte: ' . $e->getMessage());
        }
    }
    public function downloadReport(Request $request)
    {
        $filePath = $request->input('file_path');

        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->download($filePath);
        }

        return back()->with('error', 'El archivo de reporte ya no existe en el servidor.');
    }
}
