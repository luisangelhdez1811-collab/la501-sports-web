<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'delivery_lat',
        'delivery_lng',
        'customer_email',
        'tracking_token',
        'table_number',
        'total',
        'status',
        'delivery_started_at',
        'delivered_confirmed_at',
        'last_reminder_sent_at',
        'payment_method',
        'payment_id',
        'waiter_id',
        'charged_by',
        'forced_checkout',
        'forced_checkout_reason',
        'client_ref',
        'diner_label',
        'table_charge_id',
    ];

    protected $casts = [
        'forced_checkout' => 'boolean',
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
        'delivery_started_at' => 'datetime',
        'delivered_confirmed_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

    /**
     * El pedido es "a domicilio" cuando no tiene mesa asignada.
     */
    public function esADomicilio(): bool
    {
        return empty($this->table_number);
    }

    /**
     * El cliente compartió su ubicación GPS al hacer el pedido.
     */
    public function hasLocation(): bool
    {
        return $this->delivery_lat !== null && $this->delivery_lng !== null;
    }

    /**
     * Enlace de Google Maps con la ubicación de entrega (null si no hay GPS).
     */
    public function mapsUrl(): ?string
    {
        if (!$this->hasLocation()) {
            return null;
        }

        return 'https://www.google.com/maps?q=' . $this->delivery_lat . ',' . $this->delivery_lng;
    }

    /**
     * QR (SVG en línea) que abre la ubicación de entrega en Maps. Se genera en el
     * servidor para que las coordenadas no salgan a ningún servicio externo.
     */
    public function mapsQrSvg(int $size = 180): ?string
    {
        $url = $this->mapsUrl();
        if ($url === null || !class_exists(\BaconQrCode\Writer::class)) {
            return null;
        }

        // Módulos de tamaño entero + zona de silencio de 4 módulos (estándar QR):
        // con un tamaño fraccionario los módulos se ven irregulares al imprimir y
        // algunos lectores no logran escanearlos.
        // ISO-8859-1 (la URL es ASCII): con UTF-8 se agrega un segmento ECI que algunos
        // lectores muestran mal o rechazan.
        $nivel = \BaconQrCode\Common\ErrorCorrectionLevel::M();
        $modulos = \BaconQrCode\Encoder\Encoder::encode($url, $nivel, 'ISO-8859-1')->getMatrix()->getWidth();
        $silencio = 4;
        $porModulo = max(3, intdiv($size, $modulos + 2 * $silencio));
        $lado = ($modulos + 2 * $silencio) * $porModulo;

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($lado, $silencio),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );

        $svg = (new \BaconQrCode\Writer($renderer))->writeString($url, 'ISO-8859-1', $nivel);

        // Quita el prólogo XML para incrustar el SVG en HTML y pide bordes nítidos
        $svg = trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));

        return preg_replace('/^<svg /', '<svg shape-rendering="crispEdges" ', $svg, 1);
    }

    /**
     * Correo al que se le puede notificar sobre este pedido (registrado o invitado).
     */
    public function correoNotificacion(): ?string
    {
        if ($this->user_id && $this->user && $this->user->email) {
            return $this->user->email;
        }

        return $this->customer_email ?: null;
    }

    /**
     * Genera el token si el pedido es de antes de que existiera esta función.
     */
    public function trackingToken(): string
    {
        if (empty($this->tracking_token)) {
            $this->tracking_token = Str::random(48);
            $this->save();
        }

        return $this->tracking_token;
    }

    /**
     * Pedidos que cuentan como venta cobrada (para reportes/estadísticas).
     */
    public function scopeVendidas($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'paid')
              ->orWhere(function ($sq) {
                  $sq->whereNull('table_number')
                     ->whereIn('status', ['ready', 'delivering', 'delivered']);
              });
        });
    }

    /**
     * Estados en los que el pedido todavía espera a cocina o barra. Un domicilio llega
     * a cocina ya pagado ('paid'); uno de mesa se cobra al final ('pending').
     */
    public function preparationStatuses(): array
    {
        return $this->esADomicilio() ? ['paid', 'preparing'] : ['pending', 'preparing'];
    }

    public function hasPendingPreparation(): bool
    {
        return $this->items()->whereNull('prepared_at')->exists();
    }

    /**
     * Pasa el pedido a "listo" cuando cocina y barra terminaron todo lo suyo (o cuando no
     * había nada que preparar, como un pedido de puros refrescos). Devuelve true si cambió.
     */
    public function syncPreparationStatus(): bool
    {
        if (!in_array($this->status, $this->preparationStatuses(), true) || $this->hasPendingPreparation()) {
            return false;
        }

        $this->status = 'ready';
        $this->save();

        return true;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function chargedBy()
    {
        return $this->belongsTo(User::class, 'charged_by');
    }

    public function tableCharge()
    {
        return $this->belongsTo(TableCharge::class);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->tracking_token)) {
                $order->tracking_token = Str::random(48);
            }
        });

        static::updated(function ($order) {
            if ($order->isDirty('status') && $order->status === 'paid') {
                $order->discountInventoryStock();

                // Un domicilio de puras bebidas que no se preparan no pasa por cocina:
                // al pagarse queda listo para el mandadito.
                if ($order->esADomicilio()) {
                    $order->syncPreparationStatus();
                }
            }

            // Si alguien marca el pedido completo como listo (app de cocina, botón general),
            // todas sus líneas quedan listas para que el mesero las vea por entregar.
            if ($order->isDirty('status') && $order->status === 'ready') {
                $order->items()->whereNull('prepared_at')->update(['prepared_at' => now()]);
            }
        });

        static::created(function ($order) {
            if ($order->status === 'paid') {
                $order->discountInventoryStock();
            }
        });
    }

    public function discountInventoryStock()
    {
        $this->load('items.product.ingredientes');

        foreach ($this->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $excluded = is_array($item->excluded_ingredients)
                ? $item->excluded_ingredients
                : json_decode($item->excluded_ingredients, true) ?? [];

            foreach ($product->ingredientes as $ingrediente) {
                if ($ingrediente->inventory_id) {
                    $isExcluded = collect($excluded)->contains(function ($value) use ($ingrediente) {
                        return strtolower(trim($value)) === strtolower(trim($ingrediente->nombre));
                    });

                    if (!$isExcluded) {
                        $qtyToDiscount = $item->quantity * $ingrediente->cantidad_usada;
                        
                        $inventory = \App\Models\Inventory::find($ingrediente->inventory_id);
                        if ($inventory) {
                            $inventory->current_stock = max(0, $inventory->current_stock - $qtyToDiscount);
                            $inventory->save();
                        }
                    }
                }
            }
        }
    }
}
