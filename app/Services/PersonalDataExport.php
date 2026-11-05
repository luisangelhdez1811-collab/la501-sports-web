<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PromoSubscriber;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Derecho de Acceso (LFPDPPP): la copia COMPLETA de los datos que La 501 Sports tiene de una persona. La
 * web (descarga de un .json) y la app entregan exactamente esta misma estructura, toda en español y con las
 * fechas en hora de México (nunca con «Z», que es UTC y se lee con 6 horas de diferencia).
 *
 * Una copia incompleta o con datos falsos («0 logros») sería incumplir el derecho, por eso aquí van
 * TODOS los pedidos, reservaciones y mensajes, no solo los recientes.
 */
class PersonalDataExport
{
    public const TIMEZONE = 'America/Mexico_City';

    private const ROLES = [
        'cliente' => 'Cliente',
        'admin' => 'Administrador',
        'empleado' => 'Empleado (mesero)',
        'cocinero' => 'Cocina',
        'limpieza' => 'Limpieza',
    ];

    private const PAGOS = [
        'efectivo' => 'Efectivo al recibir',
        'tarjeta' => 'Tarjeta de crédito o débito (Mercado Pago)',
    ];

    public static function for(User $user): array
    {
        return (new self())->build($user);
    }

    public function build(User $user): array
    {
        return [
            'documento' => 'Copia de mis datos personales — La 501 Sports',
            'generado' => $this->fecha(now()),
            'responsable' => [
                'nombre_comercial' => 'La 501 Sports Restaurant',
                'titular' => 'Andrés Rivera Jiménez',
                'domicilio' => 'Plaza de la Revolución Mexicana #16, Col. Centro, Huejutla de Reyes, Hidalgo, C.P. 43000, México',
                'correo_de_privacidad' => 'Equipolapapa501@gmail.com',
            ],
            'titular' => $this->titular($user),
            'preferencias' => $this->preferencias($user),
            'lealtad' => $this->lealtad($user),
            'pedidos' => $this->pedidos($user),
            'reservaciones' => $this->reservaciones($user),
            'mensajes_de_contacto' => $this->mensajes($user),
            'lo_que_no_guardamos' => [
                'Nunca guardamos el número, la fecha de vencimiento ni el código de seguridad de tu tarjeta: el pago lo procesa Mercado Pago y a nosotros solo nos llega el número de operación.',
                'Tu contraseña no se guarda en texto legible: se guarda cifrada y nadie, ni nosotros, puede leerla.',
            ],
            'otros_derechos' => [
                'Rectificación' => 'Puedes corregir tu nombre y correo en tu perfil, y tu teléfono con un código de verificación, desde el sitio web o la app.',
                'Cancelación' => 'Puedes eliminar tu cuenta en Configuración (sitio web y app). Cómo funciona y qué se conserva: ' . LegalLinks::deleteAccount(),
                'Oposición' => 'En Configuración puedes apagar las promociones por correo y las recomendaciones según tus pedidos.',
                'Más información' => 'Para ejercer cualquier derecho por correo o ver cómo tratamos tus datos: Equipolapapa501@gmail.com · ' . LegalLinks::privacy(),
            ],
        ];
    }

    private function titular(User $user): array
    {
        return [
            'nombre' => $user->name,
            'correo' => $user->email,
            'telefono' => $user->telefono,
            'foto_de_perfil' => $user->avatarUrl(),
            'direccion_favorita' => $user->direccion_favorita ?? null,
            'tipo_de_cuenta' => self::ROLES[$user->role] ?? ucfirst((string) $user->role),
            // Nunca null: si la cuenta no trae fecha (registros muy viejos) se usa la del primer pedido o hoy
            'miembro_desde' => $this->fecha($user->created_at ?? $user->orders()->min('created_at') ?? now()),
            'aviso_de_privacidad_aceptado' => $this->fecha($user->aviso_privacidad_aceptado_at),
        ];
    }

    private function preferencias(User $user): array
    {
        $suscrito = $user->role === 'cliente'
            && PromoSubscriber::where('user_id', $user->id)->orWhere('email', $user->email)->exists();

        return [
            'promociones_por_correo' => $suscrito,
            'recomendaciones_segun_mis_pedidos' => (bool) ($user->recomendaciones_personalizadas ?? true),
            'ultima_actualizacion' => $this->fecha($user->preferencias_privacidad_at),
        ];
    }

    private function lealtad(User $user): array
    {
        $logros = $user->achievements()->orderBy('user_achievements.unlocked_at')->get();

        return [
            'puntos' => (int) ($user->points ?? 0),
            'logros' => $logros->map(fn ($logro) => [
                'nombre' => $logro->nombre,
                'descripcion' => $logro->descripcion,
                'fecha' => $this->fecha($logro->pivot->unlocked_at ?? $logro->pivot->created_at),
            ])->values()->all(),
        ];
    }

    private function pedidos(User $user): array
    {
        return Order::with('items')
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Order $pedido) => $this->pedido($pedido))
            ->values()
            ->all();
    }

    private function pedido(Order $pedido): array
    {
        $datos = [
            'folio' => $pedido->id,
            'fecha' => $this->fecha($pedido->created_at),
            'estado' => $pedido->statusLabel(),
            'tipo' => $pedido->esADomicilio() ? 'A domicilio' : 'En mesa ' . $pedido->table_number,
            'forma_de_pago' => self::PAGOS[$pedido->payment_method] ?? $pedido->payment_method,
            'numero_de_operacion' => $pedido->payment_id,
            'total' => (float) $pedido->total,
            'datos_de_entrega' => [
                'nombre' => $pedido->customer_name,
                'telefono' => $pedido->customer_phone,
                'correo' => $pedido->customer_email,
                'direccion' => $pedido->customer_address,
            ],
            'productos' => $pedido->items->map(fn (OrderItem $linea) => [
                'producto' => $linea->product_name,
                'cantidad' => (int) $linea->quantity,
                'precio' => (float) $linea->price,
                'subtotal' => (float) $linea->subtotal,
                'opciones' => $linea->optionsSummary() !== '' ? $linea->optionsSummary() : null,
                'ingredientes_excluidos' => array_values($linea->excluded_ingredients ?? []),
                'notas' => $linea->notes,
            ])->values()->all(),
            'confirmaciones' => [
                'envio_no_incluido_aceptado' => $this->fecha($pedido->delivery_terms_accepted_at),
                'mayoria_de_edad_confirmada' => $this->fecha($pedido->adult_confirmed_at),
                'autorizacion_de_datos_de_salud' => $this->fecha($pedido->health_data_consent_at),
            ],
        ];

        if ($pedido->hasLocation()) {
            $datos['datos_de_entrega']['ubicacion_gps'] = [
                'latitud' => $pedido->delivery_lat,
                'longitud' => $pedido->delivery_lng,
            ];
        }

        if ($pedido->status === 'cancelled' || $pedido->cancelled_at) {
            $datos['cancelacion'] = [
                'fecha' => $this->fecha($pedido->cancelled_at),
                'motivo' => $pedido->cancel_reason,
                'devolucion' => $this->devolucion($pedido->refund_status),
                'numero_de_devolucion' => $pedido->refund_id,
            ];
        }

        return $datos;
    }

    private function devolucion(?string $estado): ?string
    {
        return match ($estado) {
            'refunded' => 'Devuelto',
            'manual' => 'Se devuelve a mano en máximo 5 días hábiles',
            null, '' => null,
            default => $estado,
        };
    }

    private function reservaciones(User $user): array
    {
        return Reservation::where('correo_electronico', $user->email)
            ->orderBy('fecha_reservacion')
            ->orderBy('id')
            ->get()
            ->map(fn (Reservation $r) => [
                'nombre' => $r->nombre_completo,
                'telefono' => $r->telefono,
                'correo' => $r->correo_electronico,
                'fecha' => optional($r->fecha_reservacion)->toDateString(),
                'hora' => $r->hora_reservacion,
                'personas' => (int) $r->cantidad_personas,
                'zona' => $r->zona,
                'estado' => $r->status,
                'registrada' => $this->fecha($r->created_at),
            ])
            ->values()
            ->all();
    }

    private function mensajes(User $user): array
    {
        return ContactMessage::where('email', $user->email)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactMessage $m) => [
                'fecha' => $this->fecha($m->created_at),
                'nombre' => $m->name,
                'correo' => $m->email,
                'tipo' => $m->type,
                'asunto' => $m->subject,
                'mensaje' => $m->message,
                'estado' => $m->status,
            ])
            ->values()
            ->all();
    }

    /** Fecha ISO con la zona de México, p. ej. 2026-08-24T14:11:35-06:00. */
    private function fecha(mixed $fecha): ?string
    {
        if (!$fecha instanceof CarbonInterface) {
            return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->setTimezone(self::TIMEZONE)->toIso8601String() : null;
        }

        return $fecha->copy()->setTimezone(self::TIMEZONE)->toIso8601String();
    }
}
