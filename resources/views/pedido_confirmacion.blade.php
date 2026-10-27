@extends('layouts.app')

@section('content')
<style>
    .conf-page {
        --or: #F97316;
        --or2: #EA580C;
        --gn: #16A34A;
        --gn2: #22C55E;
        --bg: #0f0d0a;
        --bg-card: #161310;
        --txt: #FFFFFF;
        --txt-sub: #A8A29E;
        --bdr: rgba(255,255,255,0.07);
    }
    :root:not(.dark) .conf-page {
        --bg: #F5F3EF;
        --bg-card: #FFFFFF;
        --txt: #1A1208;
        --txt-sub: #6B6560;
        --bdr: rgba(0,0,0,0.09);
    }

    .conf-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .ticket-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        color: var(--txt);
        position: relative;
        overflow: hidden;
    }

    /* Franja decorativa superior */
    .ticket-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 6px;
        background: linear-gradient(90deg, var(--or), var(--gn));
    }

    .ticket-header {
        text-align: center;
        margin-bottom: 28px;
    }
    .ticket-logo {
        font-size: 32px;
        margin-bottom: 8px;
        display: inline-block;
    }
    .ticket-header h2 {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 36px;
        letter-spacing: 2px;
        margin: 0;
        color: var(--txt);
    }
    .ticket-header p {
        font-size: 14px;
        color: var(--txt-sub);
        margin: 4px 0 0;
    }

    /* TIMELINE DEL ESTADO */
    .status-timeline {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin: 32px 0;
        padding: 0 10px;
    }
    .status-timeline::before {
        content: '';
        position: absolute;
        top: 15px; left: 24px; right: 24px;
        height: 3px;
        background: var(--bdr);
        z-index: 1;
    }
    .timeline-progress {
        position: absolute;
        top: 15px; left: 24px;
        height: 3px;
        background: var(--or);
        z-index: 1;
        transition: width 0.5s ease;
    }
    .status-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        width: 60px;
    }
    .step-dot {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--bg-card);
        border: 3px solid var(--bdr);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px;
        transition: all 0.3s ease;
        color: var(--txt-sub);
    }
    .status-step.completed .step-dot {
        border-color: var(--gn2);
        background: var(--gn2);
        color: white;
    }
    .status-step.active .step-dot {
        border-color: var(--or);
        box-shadow: 0 0 12px var(--or);
        color: var(--or);
        font-weight: bold;
        transform: scale(1.1);
    }
    .step-lbl {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 8px;
        text-align: center;
        color: var(--txt-sub);
    }
    .status-step.completed .step-lbl { color: var(--gn2); }
    .status-step.active .step-lbl { color: var(--txt); }

    /* DETALLE DEL PEDIDO */
    .ticket-details {
        border-top: 1px dashed var(--bdr);
        border-bottom: 1px dashed var(--bdr);
        padding: 20px 0;
        margin-bottom: 24px;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        margin-bottom: 8px;
    }
    .detail-label { color: var(--txt-sub); }
    .detail-val { font-weight: 600; color: var(--txt); }

    /* LISTA PLATILLOS */
    .ticket-items {
        margin-bottom: 24px;
    }
    .ticket-item-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    .ticket-item-name {
        font-weight: 600;
        font-size: 14px;
    }
    .ticket-item-qty {
        color: var(--or);
        font-weight: 800;
        margin-right: 6px;
    }
    .ticket-item-excl {
        font-size: 11px;
        color: #EF4444;
        margin-top: 2px;
        font-weight: 500;
    }
    .ticket-item-price {
        font-weight: 700;
        font-size: 14px;
    }

    /* TOTAL */
    .ticket-total-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 12px;
        border-top: 1px solid var(--bdr);
    }
    .total-lbl {
        font-family: 'Oswald', sans-serif;
        font-size: 18px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .total-val {
        font-size: 24px;
        font-weight: 800;
        color: var(--or);
    }

    /* BOTONES */
    .actions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 28px;
    }
    .action-btn {
        padding: 12px 18px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;
        border: none;
        text-decoration: none !important;
    }
    .btn-print {
        background: rgba(255,255,255,0.05);
        color: var(--txt);
        border: 1px solid var(--bdr);
    }
    .btn-print:hover {
        background: rgba(255,255,255,0.1);
    }
    .btn-home {
        background: var(--or);
        color: white;
        box-shadow: 0 4px 12px rgba(234,88,12,0.25);
    }
    .btn-home:hover {
        background: var(--or2);
        transform: translateY(-1px);
    }

    /* ESTILOS DE IMPRESIÓN POS (TÉRMICO 80mm) */
    @media print {
        body, html {
            background: #FFFFFF !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        nav, footer, header, .no-print, .actions-grid, .status-timeline-section, .co-back {
            display: none !important;
        }
        .conf-container {
            margin: 0 !important;
            padding: 0 !important;
            width: 80mm !important;
            max-width: 80mm !important;
        }
        .ticket-card {
            border: none !important;
            box-shadow: none !important;
            background: #FFFFFF !important;
            color: #000000 !important;
            padding: 10px !important;
            margin: 0 !important;
            width: 80mm !important;
        }
        .ticket-card::before {
            display: none !important;
        }
        .ticket-header h2 {
            font-size: 24px !important;
            color: #000000 !important;
        }
        .ticket-header p, .detail-row, .ticket-item-row, .ticket-total-section {
            color: #000000 !important;
        }
        .detail-val, .ticket-item-price, .total-val {
            color: #000000 !important;
        }
        .ticket-item-excl {
            color: #000000 !important;
            font-weight: bold !important;
        }
        .ticket-details {
            border-top: 1px dashed #000000 !important;
            border-bottom: 1px dashed #000000 !important;
        }
        .ticket-total-section {
            border-top: 1px dashed #000000 !important;
        }
        .total-val {
            font-size: 20px !important;
        }
    }
</style>

<div class="conf-page min-h-screen bg-[#0f0d0a] py-8"
     x-data="tracker('{{ $order->status }}', {{ $order->esADomicilio() ? 'true' : 'false' }}, '{{ route('order.track.status', $order->trackingToken()) }}')">
    <div class="conf-container">

        {{-- Alerta flotante interna --}}
        <div x-show="alerta" x-cloak x-transition
             class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-green-600 text-white font-bold px-6 py-3 rounded-full shadow-2xl flex items-center gap-2 text-sm border border-green-500/20">
            <span>🍳</span> <span x-text="alertaMsg"></span>
        </div>

        <div class="ticket-card">

            {{-- LOGO Y CABECERA --}}
            <div class="ticket-header">
                <span class="ticket-logo">🍔</span>
                <h2>Ticket de Pedido</h2>
                <p>¡Gracias por elegir La 501 Sports Bar!</p>
            </div>

            {{-- TIMELINE DE ESTADO (Oculto en impresión) --}}
            <div class="status-timeline-section no-print">
                <div class="status-timeline">
                    <div class="timeline-progress" :style="'width: ' + getProgressPercent() + '%'"></div>
                    
                    <div class="status-step" :class="isStepCompleted('received') ? 'completed' : (isStepActive('received') ? 'active' : '')">
                        <div class="step-dot">
                            <span x-show="isStepCompleted('received')">✓</span>
                            <span x-show="!isStepCompleted('received')">1</span>
                        </div>
                        <span class="step-lbl">Recibido</span>
                    </div>

                    <div class="status-step" :class="isStepCompleted('preparing') ? 'completed' : (isStepActive('preparing') ? 'active' : '')">
                        <div class="step-dot">
                            <span x-show="isStepCompleted('preparing')">✓</span>
                            <span x-show="!isStepCompleted('preparing')">2</span>
                        </div>
                        <span class="step-lbl">Cocinado</span>
                    </div>

                    @if($order->esADomicilio())
                    <div class="status-step" :class="isStepCompleted('delivering') ? 'completed' : (isStepActive('delivering') ? 'active' : '')">
                        <div class="step-dot">
                            <span x-show="isStepCompleted('delivering')">✓</span>
                            <span x-show="!isStepCompleted('delivering')">3</span>
                        </div>
                        <span class="step-lbl">Entregado al repartidor</span>
                    </div>
                    @else
                    <div class="status-step" :class="isStepCompleted('ready') ? 'completed' : (isStepActive('ready') ? 'active' : '')">
                        <div class="step-dot">
                            <span x-show="isStepCompleted('ready')">✓</span>
                            <span x-show="!isStepCompleted('ready')">3</span>
                        </div>
                        <span class="step-lbl">Listo</span>
                    </div>
                    @endif
                </div>
            </div>


            {{-- DETALLES GENERALES --}}
            <div class="ticket-details">
                <div class="detail-row">
                    <span class="detail-label">Folio de Pedido:</span>
                    <span class="detail-val">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cliente:</span>
                    <span class="detail-val">{{ $order->customer_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Teléfono:</span>
                    <span class="detail-val">{{ $order->customer_phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dirección:</span>
                    <span class="detail-val">{{ $order->customer_address }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-val">{{ $order->created_at->format('d/m/Y h:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Método Pago:</span>
                    <span class="detail-val">{{ ucfirst(strtolower($order->payment_method)) }}</span>
                </div>
            </div>

            {{-- DESGLOSE DE PLATILLOS --}}
            <div class="ticket-items">
                @foreach($order->items as $item)
                    <div class="ticket-item-row">
                        <div>
                            <div class="ticket-item-name">
                                <span class="ticket-item-qty">{{ $item->quantity }}x</span>
                                <span>{{ $item->product_name }}</span>
                            </div>
                            @if($item->optionsSummary() !== '')
                                <div class="ticket-item-excl">{{ $item->optionsSummary() }}</div>
                            @endif
                            @if(!empty($item->excluded_ingredients))
                                <div class="ticket-item-excl">
                                    Sin: {{ implode(', ', $item->excluded_ingredients) }}
                                </div>
                            @endif
                            @if(!empty($item->notes))
                                <div class="ticket-item-excl">
                                    📝 {{ $item->notes }}
                                </div>
                            @endif
                        </div>
                        <div class="ticket-item-price">
                            ${{ number_format($item->subtotal, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- TOTAL --}}
            <div class="ticket-total-section">
                <span class="total-lbl">Total del Pedido</span>
                <span class="total-val">${{ number_format($order->total, 2) }}</span>
            </div>

            {{-- QR DE UBICACIÓN (solo a domicilio con GPS) --}}
            @if ($order->esADomicilio() && $order->hasLocation() && ($qrSvg = $order->mapsQrSvg(200)))
                <div style="text-align:center; padding:14px 0 6px; margin-top:12px; border-top:1px dashed currentColor;">
                    <div style="display:inline-block; background:#fff; padding:6px; line-height:0;">{!! $qrSvg !!}</div>
                    <p style="margin:6px 0 0; font-size:12px;">Escanea para abrir la ubicación en Maps</p>
                </div>
            @endif

            @if($order->esADomicilio())
            <p class="no-print" style="font-size:12px; color:var(--txt-sub); text-align:center; margin:10px 0 0;">
                Este total es solo del platillo. El envío lo cobra el repartidor por separado al entregarte tu pedido.
            </p>
            @endif

            {{-- ACCIONES --}}
            <div class="actions-grid no-print">
                <button @click="window.print()" class="action-btn btn-print">
                    🖨️ Imprimir Ticket
                </button>
                <a href="{{ route('pedido') }}" class="action-btn btn-home">
                    🍔 Volver al Menú
                </a>
            </div>

            <div class="no-print" style="text-align:center; margin-top:16px;">
                <a href="{{ route('order.lookup') }}?telefono={{ urlencode($order->customer_phone) }}"
                   style="color: var(--txt-sub); font-size: 12px; text-decoration: underline;">
                    📜 Ver mis pedidos anteriores
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    function tracker(initialStatus, esADomicilio, statusUrl) {
        return {
            status: initialStatus,
            esADomicilio: esADomicilio,
            alerta: false,
            alertaMsg: '',
            permissionRequested: false,

            init() {
                // Solicitar permisos de notificación navegador
                if ('Notification' in window) {
                    Notification.requestPermission();
                }

                // No hace falta seguir preguntando una vez que ya se confirmó la entrega
                if (this.status === 'delivered') return;

                // Iniciar consulta de estado cada 8 segundos
                setInterval(() => {
                    this.checkStatus();
                }, 8000);
            },

            async checkStatus() {
                try {
                    const response = await fetch(statusUrl);
                    if (response.ok) {
                        const data = await response.json();
                        if (data.status && data.status !== this.status) {
                            this.playNotificationSound();
                            this.notifyUser(data.status);
                            this.status = data.status;
                        }
                    }
                } catch(e) {
                    console.log('Error de red al consultar estado:', e);
                }
            },

            getProgressPercent() {
                if (this.esADomicilio) {
                    if (this.status === 'delivering' || this.status === 'delivered') return 100;
                    if (this.status === 'preparing' || this.status === 'ready') return 50;
                    return 0; // paid / pending
                }
                if (this.status === 'ready') return 100;
                if (this.status === 'preparing') return 50;
                return 0;
            },

            // Recibido: se cumple en cuanto el cliente hace el pedido.
            // Cocinado: cocina lo marcó listo.
            // Entregado al repartidor: el empleado lo entregó al mandadito.
            isStepCompleted(step) {
                if (step === 'received') {
                    return true;
                }
                if (step === 'preparing') {
                    return ['ready', 'delivering', 'delivered'].includes(this.status);
                }
                if (step === 'ready') {
                    return false; // último paso para mesa, no hay nada después
                }
                if (step === 'delivering') {
                    return ['delivering', 'delivered'].includes(this.status);
                }
                return false;
            },

            isStepActive(step) {
                if (step === 'received') {
                    return false;
                }
                if (step === 'preparing') {
                    return ['paid', 'pending', 'preparing'].includes(this.status);
                }
                if (step === 'ready') {
                    return this.status === 'ready';
                }
                if (step === 'delivering') {
                    return this.status === 'ready';
                }
                return false;
            },

            playNotificationSound() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Campana alegre D5 -> A5
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc.frequency.setValueAtTime(880.00, ctx.currentTime + 0.15); // A5
                    
                    gain.gain.setValueAtTime(0.12, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    
                    osc.start();
                    osc.stop(ctx.currentTime + 0.5);
                } catch(e) {
                    console.log('Fallo de Web Audio API:', e);
                }
            },

            notifyUser(newStatus) {
                let msg = '';
                if (newStatus === 'preparing') {
                    msg = '🍳 ¡Tu pedido ha comenzado a prepararse en cocina!';
                } else if (newStatus === 'ready') {
                    msg = '🎉 ¡Tu pedido está listo!';
                } else if (newStatus === 'delivering') {
                    msg = '🛵 ¡Tu pedido fue entregado al repartidor y va en camino!';
                }

                if (msg) {
                    this.alertaMsg = msg;
                    this.alerta = true;
                    setTimeout(() => { this.alerta = false; }, 6000);

                    // Notificación push navegador
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('La 501 Sports Bar', {
                            body: msg,
                            icon: 'https://cdn-icons-png.flaticon.com/512/1046/1046747.png'
                        });
                    }
                }
            }
        }
    }
</script>
@endsection
