<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupController extends Controller
{
    private const TZ_MEXICO       = 'America/Mexico_City';
    private const FORMAT_DATETIME = 'Y-m-d H:i:s';
    private const BACKUP_DISK     = 'local';
    private const BACKUP_DIR      = 'backups';

    public function database()
    {
        if (Auth::id() !== 2) {
            return redirect()->route('admin.dashboard')->with('error', 'No tienes permisos para acceder a esta área.');
        }
        return view('admin.database', ['backups' => $this->listarBackups(3)]);
    }

    public function databaseHistory(Request $request)
    {
        if (Auth::id() !== 2) {
            return redirect()->route('admin.dashboard')->with('error', 'Denegado.');
        }
        
        $fechaFiltro = $request->input('fecha');
        $horaFiltro  = $request->input('hora');
        
        // --- 1. LECTURA DE RESPALDOS (.sql) ---
        $backups = [];
        foreach (Storage::disk(self::BACKUP_DISK)->files(self::BACKUP_DIR) as $file) {
            $carbonDate  = Carbon::createFromTimestamp(Storage::disk(self::BACKUP_DISK)->lastModified($file))->setTimezone(self::TZ_MEXICO);
            $dateString  = $carbonDate->format('Y-m-d');
            $horaArchivo = $carbonDate->format('H');

            if ($fechaFiltro && $dateString !== $fechaFiltro) {
                continue;
            }
            if ($horaFiltro && substr($horaFiltro, 0, 2) !== $horaArchivo) {
                continue;
            }
            
            $backups[] = [
                'name'   => basename($file),
                'path'   => $file,
                'size'   => round(Storage::disk(self::BACKUP_DISK)->size($file) / 1024, 2) . ' KB',
                'date'   => $carbonDate->format(self::FORMAT_DATETIME),
                'carbon' => $carbonDate,
            ];
        }
        usort($backups, fn($a, $b) => $b['date'] <=> $a['date']);

        // --- 2. LECTURA DE REPORTES (.json) ---
        $reports = [];
        if (Storage::disk(self::BACKUP_DISK)->exists('reports')) {
            $files = Storage::disk(self::BACKUP_DISK)->files('reports');
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $carbonDate = Carbon::createFromTimestamp(Storage::disk(self::BACKUP_DISK)->lastModified($file))->setTimezone(self::TZ_MEXICO);
                    
                    // Aplicar los mismos filtros de fecha/hora para los reportes
                    $dateString  = $carbonDate->format('Y-m-d');
                    $horaArchivo = $carbonDate->format('H');

                    if ($fechaFiltro && $dateString !== $fechaFiltro) {
                        continue;
                    }
                    if ($horaFiltro && substr($horaFiltro, 0, 2) !== $horaArchivo) {
                        continue;
                    }

                    $reports[] = [
                        'name'   => basename($file),
                        'path'   => $file,
                        'size'   => $this->formatSizeUnits(Storage::disk(self::BACKUP_DISK)->size($file)),
                        'carbon' => $carbonDate,
                    ];
                }
            }
            
            usort($reports, function ($a, $b) {
                return $b['carbon']->timestamp - $a['carbon']->timestamp;
            });
        }

        // Retornamos la vista (Asegúrate de que el blade se llame 'database-history.blade.php' o ajusta el nombre aquí)
        return view('admin.database-history', compact('backups', 'reports', 'fechaFiltro', 'horaFiltro'));
    }

    public function createBackup()
    {
        if (Auth::id() !== 2) {
            return redirect()->route('admin.dashboard')->with('error', 'Denegado.');
        }
        
        $ahora    = now(self::TZ_MEXICO);
        $fileName = 'manual_backup_' . $ahora->format('Y-m-d_H-i-s') . '.sql';
        $path     = self::BACKUP_DIR . '/' . $fileName;
        
        Storage::disk(self::BACKUP_DISK)->put($path, $this->generarSQLCompleto());
        
        return back()->with([
            'success'       => '¡Respaldo generado y guardado con éxito!',
            'download_path' => $path,
            'file_name'     => $fileName,
        ]);
    }

    public function downloadBackup(Request $request)
    {
        if (Auth::id() !== 2) {
            return back()->with('error', 'Denegado.');
        }
        $path = $request->file_path;
        
        if (!Storage::disk(self::BACKUP_DISK)->exists($path)) {
            return back()->with('error', 'El archivo ya no existe en el servidor.');
        }
        return Storage::disk(self::BACKUP_DISK)->download($path);
    }

    public function restore(Request $request)
    {
        $response = back();
        $userId = Auth::id();
        
        if ($userId !== 2) {
            $response->with('error', 'Denegado.');
        } elseif (!Storage::disk(self::BACKUP_DISK)->exists($request->file_path)) {
            $response->with('error', 'El archivo de respaldo no fue encontrado.');
        } else {
            $response = $this->ejecutarRestauracion(
                Storage::disk(self::BACKUP_DISK)->get($request->file_path),
                $userId,
                '¡Base de datos restaurada con éxito desde el historial!'
            );
        }
        
        return $response;
    }

    public function restoreUpload(Request $request)
    {
        $response = back();
        $userId = Auth::id();
        $file = $request->file('sql_file');

        if ($userId !== 2) {
            $response->with('error', 'Denegado.');
        } elseif (!$request->hasFile('sql_file')) {
            $response->with('error', 'Por favor selecciona un archivo .sql');
        } elseif (strtolower($file->getClientOriginalExtension()) !== 'sql') {
            $response->with('error', 'El archivo debe ser un formato .sql válido.');
        } else {
            $response = $this->ejecutarRestauracion(
                file_get_contents($file->getRealPath()),
                $userId,
                '¡Base de datos restaurada exitosamente con tu archivo manual!'
            );
        }

        return $response;
    }

    public function saveAuto(Request $request)
    {
        if (Auth::id() !== 2) {
            return redirect()->route('admin.dashboard')->with('error', 'Denegado.');
        }
        
        $enabled = $request->input('backup_enabled', '0');
        \App\Models\Setting::updateOrCreate(['key' => 'backup_enabled'], ['value' => $enabled]);
        
        if ($enabled == '1') {
            \App\Models\Setting::updateOrCreate(['key' => 'backup_frecuencia'], ['value' => $request->frecuencia]);
            \App\Models\Setting::updateOrCreate(['key' => 'backup_hora'],       ['value' => $request->hora ?? '03:00']);
            \App\Models\Setting::updateOrCreate(['key' => 'backup_intervalo'],  ['value' => $request->intervalo ?? '60']);
            \App\Models\Setting::updateOrCreate(['key' => 'backup_delete_old'], ['value' => '1']);
        }
        return back()->with('success', 'Configuración de automatización actualizada.');
    }

    public function runAutoBackup()
    {
        $isEnabled = \App\Models\Setting::where('key', 'backup_enabled')->orderBy('id', 'desc')->value('value');
        if ($isEnabled !== '1') {
            return response('Apagado.', 200)->header('Cache-Control', 'no-store, no-cache');
        }

        $frecuencia  = \App\Models\Setting::where('key', 'backup_frecuencia')->orderBy('id', 'desc')->value('value') ?? 'diario';
        $intervalo   = \App\Models\Setting::where('key', 'backup_intervalo')->orderBy('id', 'desc')->value('value') ?? '60';
        $horaDeseada = \App\Models\Setting::where('key', 'backup_hora')->orderBy('id', 'desc')->value('value') ?? '03:00';
        $lastRun     = \App\Models\Setting::where('key', 'backup_last_run')->orderBy('id', 'desc')->value('value');
        $ahora       = now(self::TZ_MEXICO);

        [$debeEjecutarse, $mensajeDiagnostico] = $this->shouldRunBackup($frecuencia, $intervalo, $horaDeseada, $lastRun, $ahora);

        if (!$debeEjecutarse) {
            return response($mensajeDiagnostico, 200)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        \App\Models\Setting::where('key', 'backup_last_run')->delete();
        \App\Models\Setting::create(['key' => 'backup_last_run', 'value' => $ahora->format(self::FORMAT_DATETIME)]);

        $fileName = 'autobackup_' . $ahora->format('Y-m-d_H-i-s') . '.sql';
        Storage::disk(self::BACKUP_DISK)->put(self::BACKUP_DIR . '/' . $fileName, $this->generarSQLCompleto());
        $this->eliminarBackupsAntiguos($ahora);

        return response("BACKUP GENERADO ({$frecuencia}).", 200)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    private function ejecutarRestauracion(string $sql, int $userId, string $successMsg)
    {
        try {
            $sql = str_replace(['DELIMITER $$', 'DELIMITER ;', '$$'], '', $sql);
            DB::unprepared($sql);
            Auth::loginUsingId($userId);
            return back()->with('success', $successMsg);
        } catch (\Exception $e) {
            return back()->with('error', 'Error crítico al restaurar: ' . $e->getMessage());
        }
    }

    private function generarSQLCompleto(): string
    {
        $ahora = now(self::TZ_MEXICO)->format(self::FORMAT_DATETIME);

        $sql  = "-- ========================================================\n";
        $sql .= "-- RESPALDO COMPLETO DE BASE DE DATOS: La 501 Sports\n";
        $sql .= "-- Fecha de generación: {$ahora}\n";
        $sql .= "-- ========================================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        foreach (DB::select('SHOW FULL TABLES') as $table) {
            $tableArray = array_values((array) $table);
            $sql .= "DROP TABLE IF EXISTS `{$tableArray[0]}`;\n";
            $sql .= "DROP VIEW IF EXISTS `{$tableArray[0]}`;\n";
            $sql .= $this->buildTableStructure($tableArray[0], $tableArray[1]);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    private function buildTableStructure(string $tableName, string $tableType): string
    {
        $sql = '';
        try {
            $createTable = $tableType === 'VIEW'
                ? DB::select("SHOW CREATE VIEW `{$tableName}`")
                : DB::select("SHOW CREATE TABLE `{$tableName}`");
                
            $createSql = array_values((array) $createTable[0]);
            $estructuraLimpia = str_replace('utf8mb4_uca1400_ai_ci', 'utf8mb4_general_ci', $createSql[1]);
            $sql .= $estructuraLimpia . ";\n\n";
            
            if ($tableType === 'BASE TABLE') {
                $sql .= $this->buildTableRows($tableName);
            }
        } catch (\Exception $e) {
            $sql .= "-- ERROR EN `{$tableName}`: " . $e->getMessage() . "\n\n";
        }
        return $sql;
    }

    private function buildTableRows(string $tableName): string
    {
        $sql  = '';
        $rows = DB::select("SELECT * FROM `{$tableName}`");
        
        if (count($rows) === 0) {
            return $sql;
        }
        
        foreach ($rows as $row) {
            $values = array_map(function ($value) {
                if (is_null($value)) {
                    return 'NULL';
                }
                return "'" . str_replace("\n", "\\n", addslashes($value)) . "'";
            }, (array) $row);
            $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(", ", $values) . ");\n";
        }
        return $sql . "\n\n";
    }

    private function shouldRunBackup(string $frecuencia, string $intervalo, string $horaDeseada, ?string $lastRun, Carbon $ahora): array
    {
        if (!$lastRun) {
            return [true, ''];
        }
        
        if ($frecuencia === 'intervalo') {
            return $this->checkIntervalo($intervalo, $lastRun, $ahora);
        }
        
        return $this->checkPeriodico($frecuencia, $horaDeseada, $lastRun, $ahora);
    }

    private function checkIntervalo(string $intervalo, string $lastRun, Carbon $ahora): array
    {
        $segundosPasados    = strtotime($ahora->format(self::FORMAT_DATETIME)) - strtotime($lastRun);
        $segundosRequeridos = ((int) $intervalo * 60) - 10;
        
        if ($segundosPasados >= $segundosRequeridos) {
            return [true, ''];
        }
        
        $faltan = $segundosRequeridos - $segundosPasados;
        return [false, "⏳ DIAGNÓSTICO (Intervalo {$intervalo}m): Han pasado {$segundosPasados}s. Faltan {$faltan}s."];
    }

    private function checkPeriodico(string $frecuencia, string $horaDeseada, string $lastRun, Carbon $ahora): array
    {
        $ultimaVezCarbon = Carbon::parse($lastRun, self::TZ_MEXICO);
        $horaActualStr   = $ahora->format('H:i');
        
        $resultado = [false, "⏳ Frecuencia desconocida: {$frecuencia}"];

        if ($frecuencia === 'diario') {
            $resultado = $this->checkDiario($ultimaVezCarbon, $horaActualStr, $horaDeseada, $ahora);
        } elseif ($frecuencia === 'semanal') {
            $resultado = $this->checkSemanal($ultimaVezCarbon, $horaActualStr, $horaDeseada, $ahora);
        } elseif ($frecuencia === 'mensual') {
            $resultado = $this->checkMensual($ultimaVezCarbon, $horaActualStr, $horaDeseada, $ahora);
        }
        
        return $resultado;
    }

    private function checkDiario(Carbon $ultima, string $horaActual, string $horaDeseada, Carbon $ahora): array
    {
        if (!$ahora->isSameDay($ultima) && $horaActual >= $horaDeseada) {
            return [true, ''];
        }
        return [false, "⏳ DIAGNÓSTICO (Diario a las {$horaDeseada}): Aún no se cumple el día y la hora."];
    }

    private function checkSemanal(Carbon $ultima, string $horaActual, string $horaDeseada, Carbon $ahora): array
    {
        if ($ahora->diffInDays($ultima) >= 7 && $horaActual >= $horaDeseada) {
            return [true, ''];
        }
        $dias = $ahora->diffInDays($ultima);
        return [false, "⏳ DIAGNÓSTICO (Semanal a las {$horaDeseada}): Han pasado {$dias} días."];
    }

    private function checkMensual(Carbon $ultima, string $horaActual, string $horaDeseada, Carbon $ahora): array
    {
        if ($ahora->diffInMonths($ultima) >= 1 && $horaActual >= $horaDeseada) {
            return [true, ''];
        }
        return [false, "⏳ DIAGNÓSTICO (Mensual a las {$horaDeseada}): Aún no ha pasado un mes completo."];
    }

    private function eliminarBackupsAntiguos(Carbon $ahora): void
    {
        $shouldDelete = \App\Models\Setting::where('key', 'backup_delete_old')
            ->orderBy('id', 'desc')->value('value');
            
        if ($shouldDelete !== '1') {
            return;
        }

        foreach (Storage::disk(self::BACKUP_DISK)->files(self::BACKUP_DIR) as $file) {
            $fechaArchivo = Carbon::createFromTimestamp(
                Storage::disk(self::BACKUP_DISK)->lastModified($file)
            )->timezone(self::TZ_MEXICO);

            if ($fechaArchivo->diffInDays($ahora) >= 3) {
                Storage::disk(self::BACKUP_DISK)->delete($file);
            }
        }
    }

    private function listarBackups(int $limit = 0): array
    {
        $backups = [];
        foreach (Storage::disk(self::BACKUP_DISK)->files(self::BACKUP_DIR) as $file) {
            $carbonDate = Carbon::createFromTimestamp(Storage::disk(self::BACKUP_DISK)->lastModified($file))->setTimezone(self::TZ_MEXICO);
            $backups[]  = [
                'name' => basename($file),
                'path' => $file,
                'size' => round(Storage::disk(self::BACKUP_DISK)->size($file) / 1024, 2) . ' KB',
                'date' => $carbonDate->format(self::FORMAT_DATETIME),
            ];
        }
        usort($backups, fn($a, $b) => $b['date'] <=> $a['date']);
        return $limit > 0 ? array_slice($backups, 0, $limit) : $backups;
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
}
