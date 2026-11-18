<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * B-31 (LFPDPPP, proporcionalidad): borra los datos personales que ya cumplieron el plazo que dice el
 * Aviso de Privacidad. Los plazos salen de config/privacy.php. Registra cuántos registros tocó, nunca
 * los datos.
 */
class PurgePersonalData extends Command
{
    protected $signature = 'privacy:purge';

    protected $description = 'Borra los datos personales que ya cumplieron su plazo de conservación';

    /** Pedidos que ya terminaron. «paid» es Confirmado: puede seguir en camino y necesita la ubicación. */
    private const ESTADOS_CERRADOS = ['delivered', 'cancelled', 'failed'];

    public function handle(): int
    {
        $plazos = config('privacy.retention_days');
        $resultado = [];

        $resultado['ubicaciones_gps'] = Order::query()
            ->whereIn('status', self::ESTADOS_CERRADOS)
            ->where('updated_at', '<', now()->subDays($plazos['order_gps']))
            ->where(fn ($q) => $q->whereNotNull('delivery_lat')->orWhereNotNull('delivery_lng'))
            ->update(['delivery_lat' => null, 'delivery_lng' => null]);

        $resultado['notas_de_pedido'] = DB::table('order_items')
            ->whereNotNull('notes')
            ->where('created_at', '<', now()->subDays($plazos['order_notes']))
            ->update(['notes' => null]);

        $resultado['reservaciones'] = Reservation::query()
            ->whereDate('fecha_reservacion', '<', now()->subDays($plazos['reservations']))
            ->delete();

        $resultado['mensajes_de_contacto'] = ContactMessage::query()
            ->where('created_at', '<', now()->subDays($plazos['contact_messages']))
            ->delete();

        $resultado['codigos_de_recuperacion'] = DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subHours($plazos['otp_hours']))
            ->delete();

        $resultado['respaldos'] = $this->borrarRespaldos($plazos['backups']);
        $resultado['bitacoras'] = $this->borrarBitacoras($plazos['server_logs']);

        Log::info('privacy:purge', $resultado);
        foreach ($resultado as $que => $cuantos) {
            $this->line(str_pad($que, 26) . $cuantos);
        }

        return self::SUCCESS;
    }

    private function borrarRespaldos(int $dias): int
    {
        $disco = Storage::disk('local');
        $limite = now()->subDays($dias)->getTimestamp();
        $borrados = 0;

        foreach ($disco->files('backups') as $archivo) {
            if ($disco->lastModified($archivo) < $limite) {
                $disco->delete($archivo);
                $borrados++;
            }
        }

        return $borrados;
    }

    private function borrarBitacoras(int $dias): int
    {
        $limite = now()->subDays($dias)->getTimestamp();
        $borrados = 0;

        foreach (File::glob(storage_path('logs/*.log')) as $archivo) {
            if (File::lastModified($archivo) < $limite) {
                File::delete($archivo);
                $borrados++;
            }
        }

        return $borrados;
    }
}
