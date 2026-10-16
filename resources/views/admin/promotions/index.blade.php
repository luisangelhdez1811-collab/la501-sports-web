@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.js"></script>

<style>
    /* ── TOKENS ── */
    .ap {
        --accent:    #2563EB;
        --accent2:   #1D4ED8;
        --danger:    #EF4444;
        --success:   #16A34A;

        --bg:        #F8F8F8;
        --bg-card:   #FFFFFF;
        --bg-input:  #F4F4F5;
        --txt:       #18181B;
        --txt-sub:   #71717A;
        --bdr:       #E4E4E7;
    }
    .dark .ap {
        --bg:        #0A0A0A;
        --bg-card:   #111111;
        --bg-input:  #1C1C1C;
        --txt:       #FAFAFA;
        --txt-sub:   #71717A;
        --bdr:       rgba(255,255,255,0.08);
    }

    /* ── PAGE ── */
    .ap { background: var(--bg); min-height: 100%; }

    /* ── PAGE HEADER ── */
    .ap-header {
        display: flex; align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        gap: 16px; flex-wrap: wrap;
    }
    .ap-header-left h1 {
        font-size: 20px; font-weight: 700;
        color: var(--txt); margin: 0 0 2px;
    }
    .ap-header-left p {
        font-size: 13px; color: var(--txt-sub); margin: 0;
    }

    /* primary button */
    .ap-btn-primary {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--accent); color: #fff;
        font-size: 13px; font-weight: 600;
        padding: 8px 16px; border-radius: 7px; border: none;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
        transition: background .15s, transform .1s;
    }
    .ap-btn-primary:hover { background: var(--accent2); transform: translateY(-1px); }

    /* ── GRID ── */
    .ap-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }

    /* ── CARD ── */
    .ap-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 10px;
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: box-shadow .2s, border-color .2s;
    }
    .ap-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.08);
        border-color: #D4D4D8;
    }
    .dark .ap-card:hover { border-color: rgba(255,255,255,.15); }

    /* card image strip */
    .ap-card-img {
        height: 160px;
        position: relative;
        overflow: hidden;
        background: var(--bg-input);
        display: flex; align-items: center; justify-content: center;
    }
    .ap-card-img img {
        position: absolute; inset: 0;
        width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .ap-card-emoji { font-size: 52px; }
    .ap-card-tag {
        position: absolute; top: 10px; right: 10px;
        background: rgba(0,0,0,.6);
        backdrop-filter: blur(6px);
        color: #fff; font-size: 11px; font-weight: 600;
        padding: 3px 10px; border-radius: 100px;
        letter-spacing: .3px;
    }

    /* card actions overlay */
    .ap-card-actions {
        position: absolute; top: 10px; left: 10px;
        display: flex; gap: 6px;
    }
    .ap-card-act-btn {
        width: 30px; height: 30px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.9);
        backdrop-filter: blur(6px);
        border: none; cursor: pointer;
        transition: background .15s, transform .15s;
    }
    .dark .ap-card-act-btn { background: rgba(0,0,0,.7); }
    .ap-card-act-btn:hover { transform: scale(1.08); }
    .ap-card-act-btn svg { width: 14px; height: 14px; }
    .ap-card-act-btn.edit  svg { color: var(--accent); }
    .ap-card-act-btn.del   svg { color: var(--danger); }

    /* card body */
    .ap-card-body { padding: 14px 16px 16px; flex-grow: 1; display: flex; flex-direction: column; }
    .ap-card-body h3 {
        font-size: 15px; font-weight: 700;
        color: var(--txt); margin: 0 0 4px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .ap-card-body p {
        font-size: 12px; color: var(--txt-sub);
        line-height: 1.55; flex-grow: 1; margin: 0 0 12px;
        display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden;
    }
    .ap-card-foot {
        display: flex; align-items: center;
        justify-content: space-between;
        border-top: 1px solid var(--bdr);
        padding-top: 10px; margin-top: auto;
    }
    .ap-price {
        font-size: 18px; font-weight: 800;
        color: var(--success);
    }
    .ap-expiry {
        font-size: 11px; color: var(--txt-sub);
        display: flex; align-items: center; gap: 4px;
    }
    .ap-expiry svg { width: 12px; height: 12px; opacity: .6; }

    /* ── EMPTY STATE ── */
    .ap-empty {
        grid-column: 1/-1;
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 10px;
        padding: 60px 24px;
        text-align: center;
    }
    .ap-empty svg { width: 40px; height: 40px; color: var(--txt-sub); margin: 0 auto 12px; }
    .ap-empty h3 { font-size: 16px; font-weight: 700; color: var(--txt); margin: 0 0 4px; }
    .ap-empty p { font-size: 13px; color: var(--txt-sub); margin: 0; }

    /* ── MODAL ── */
    .ap-overlay {
        position: fixed; inset: 0; z-index: 50;
        background: rgba(0,0,0,.5);
        backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .ap-modal {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 12px;
        width: 100%; max-width: 600px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }
    .ap-modal-head {
        display: flex; align-items: center;
        justify-content: space-between;
        padding: 18px 20px;
        border-bottom: 1px solid var(--bdr);
        background: var(--bg-card);
    }
    .ap-modal-head h3 {
        font-size: 16px; font-weight: 700;
        color: var(--txt); margin: 0;
    }
    .ap-modal-close {
        width: 28px; height: 28px; border-radius: 6px;
        background: var(--bg-input); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--txt-sub); transition: background .15s;
    }
    .ap-modal-close:hover { background: var(--bdr); }
    .ap-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }
    .ap-modal-foot {
        display: flex; gap: 10px;
        padding: 16px 20px;
        border-top: 1px solid var(--bdr);
    }

    /* form elements */
    .ap-label {
        display: block; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--txt-sub); margin-bottom: 5px;
    }
    .ap-input {
        width: 100%; padding: 8px 12px;
        background: var(--bg-input);
        border: 1px solid var(--bdr);
        border-radius: 7px;
        font-size: 13px; color: var(--txt);
        outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .ap-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .ap-row { display: grid; gap: 12px; }
    .ap-row-2 { grid-template-columns: 1fr 1fr; }
    .ap-row-3 { grid-template-columns: 1fr 1fr 1fr; }

    /* image upload zone (16:9, coincide con la tarjeta pública) */
    .ap-upload-zone {
        border: 1px dashed var(--bdr);
        border-radius: 7px;
        aspect-ratio: 16 / 9; width: 100%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; overflow: hidden; position: relative;
        background: var(--bg-input);
        transition: border-color .15s;
    }
    .ap-upload-zone:hover { border-color: var(--accent); }
    .ap-upload-placeholder {
        display: flex; flex-direction: column; align-items: center; gap: 6px;
        position: relative; z-index: 1;
    }
    .ap-upload-placeholder svg { width: 24px; height: 24px; color: var(--txt-sub); }
    .ap-upload-placeholder span {
        font-size: 12px; color: var(--txt-sub); font-weight: 500;
    }
    .ap-upload-img {
        position: absolute; inset: 0;
        width: 100%; height: 100%; object-fit: cover;
    }
    .ap-upload-overlay {
        position: absolute; inset: 0;
        background: rgba(0,0,0,.45);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity .2s; z-index: 2;
    }
    .ap-upload-zone:hover .ap-upload-overlay { opacity: 1; }
    .ap-upload-overlay span { font-size: 12px; color: #fff; font-weight: 600; }
    .ap-imghelp { font-size: 11px; color: var(--txt-sub); margin: 6px 2px 0; line-height: 1.5; }
    .ap-imghelp strong { color: var(--txt); }

    /* ── PLATILLOS INCLUIDOS (buscador) ── */
    .ap-prod-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .ap-prod-chip {
        display: flex; align-items: center; gap: 8px;
        background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.18);
        border-radius: 20px; padding: 5px 6px 5px 12px;
        font-size: 12px; font-weight: 600; color: var(--accent);
    }
    .ap-prod-qty { display: flex; align-items: center; gap: 4px; background: var(--bg-card); border-radius: 20px; padding: 2px 4px; }
    .ap-prod-qty button {
        width: 18px; height: 18px; border-radius: 50%; border: none; cursor: pointer;
        background: rgba(37,99,235,.12); color: var(--accent); font-weight: 700; font-size: 12px;
        display: flex; align-items: center; justify-content: center; line-height: 1; padding: 0;
    }
    .ap-prod-qty span { font-size: 12px; min-width: 14px; text-align: center; color: var(--txt); }
    .ap-prod-remove {
        width: 18px; height: 18px; border-radius: 50%; border: none; cursor: pointer;
        background: rgba(37,99,235,.15); color: var(--accent);
        display: flex; align-items: center; justify-content: center; line-height: 1; padding: 0;
        transition: background .15s, color .15s;
    }
    .ap-prod-remove:hover { background: rgba(239,68,68,.2); color: var(--danger); }
    .ap-prod-search { position: relative; }
    .ap-prod-dropdown {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12); z-index: 60; overflow-y: auto; max-height: 220px;
    }
    .ap-prod-dd-item {
        padding: 8px 12px; font-size: 12px; font-weight: 500; color: var(--txt); cursor: pointer;
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        border-bottom: 1px solid var(--bdr); transition: background .1s;
    }
    .ap-prod-dd-item:last-child { border-bottom: none; }
    .ap-prod-dd-item:hover { background: rgba(37,99,235,.06); color: var(--accent); }
    .ap-prod-dd-item small { color: var(--txt-sub); font-weight: 600; text-transform: uppercase; font-size: 10px; flex-shrink: 0; }
    .ap-prod-dd-empty {
        padding: 14px 12px; font-size: 12px; color: var(--txt-sub); text-align: center; font-style: italic;
        border: 1px solid var(--bdr); border-radius: 8px; margin-top: 4px;
    }

    /* ── DÍAS ACTIVOS ── */
    .ap-days-grid { display: flex; gap: 6px; flex-wrap: wrap; }
    .ap-day-btn {
        padding: 7px 12px; border-radius: 7px; font-size: 12px; font-weight: 600;
        border: 1px solid var(--bdr); background: var(--bg-input); color: var(--txt-sub);
        cursor: pointer; transition: background .15s, color .15s, border-color .15s;
    }
    .ap-day-btn:hover { color: var(--txt); border-color: #C4C4C8; }
    .ap-day-btn.on { background: var(--accent); color: #fff; border-color: var(--accent); }

    /* buttons */
    .ap-btn-cancel {
        flex: 1; padding: 9px; border-radius: 7px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        font-size: 13px; font-weight: 600; color: var(--txt-sub);
        cursor: pointer; transition: background .15s;
    }
    .ap-btn-cancel:hover { background: var(--bdr); }
    .ap-btn-save {
        flex: 2; padding: 9px; border-radius: 7px;
        background: var(--accent); color: #fff;
        border: none; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
    }
    .ap-btn-save:hover { background: var(--accent2); }
    .ap-btn-save:disabled { opacity: .6; cursor: not-allowed; }

    /* ── RECORTE DE IMAGEN (Cropper.js) ── */
    .ap-crop-overlay {
        position: fixed; inset: 0; z-index: 70;
        background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .ap-crop-modal {
        background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 12px;
        width: 100%; max-width: 560px; box-shadow: 0 24px 64px rgba(0,0,0,.35);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .ap-crop-hd { padding: 16px 18px; border-bottom: 1px solid var(--bdr); }
    .ap-crop-hd h3 { font-size: 15px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .ap-crop-hd p  { font-size: 12px; color: var(--txt-sub); margin: 0; }
    .ap-crop-body { background: #000; height: 320px; }
    .ap-crop-body img { display: block; max-width: 100%; }
    .ap-crop-foot { padding: 14px 18px; border-top: 1px solid var(--bdr); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .ap-crop-hint { font-size: 11px; color: var(--txt-sub); }
    .ap-crop-actions { display: flex; gap: 8px; }
    @media (max-width: 480px) { .ap-crop-body { height: 260px; } .ap-crop-foot { flex-direction: column; align-items: stretch; } }

    @media (max-width: 540px) {
        .ap-row-2, .ap-row-3 { grid-template-columns: 1fr; }
    }
</style>

<div class="ap" x-data="promoManager()">

    {{-- PAGE HEADER --}}
    <div class="ap-header">
        <div class="ap-header-left">
            <h1>Promociones</h1>
            <p>{{ $promotions->count() }} promoción{{ $promotions->count() !== 1 ? 'es' : '' }} activa{{ $promotions->count() !== 1 ? 's' : '' }}</p>
        </div>
        <button @click="openCreate()" class="ap-btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Promoción
        </button>
    </div>

    {{-- GRID --}}
    <div class="ap-grid">
        @forelse($promotions as $item)
        <div class="ap-card">

            {{-- Image strip --}}
            <div class="ap-card-img {{ !$item->image ? 'bg-gradient-to-br ' . $item->color_gradient : '' }}">
                @if($item->image)
                    <img src="{{ (str_starts_with($item->image, 'http://') || str_starts_with($item->image, 'https://')) ? $item->image : asset('storage/' . $item->image) }}" alt="{{ $item->title }}">
                @else
                    <span class="ap-card-emoji">{{ $item->icon }}</span>
                @endif

                @if($item->tag)
                <div class="ap-card-tag">{{ $item->tag }}</div>
                @endif
                @if(!$item->sellable_online)
                <div class="ap-card-tag" style="right:auto;left:10px;top:auto;bottom:10px;background:rgba(0,0,0,.65);">Solo local</div>
                @endif

                {{-- Actions --}}
                <div class="ap-card-actions">
                    <button @click="openEdit({{ json_encode($item) }})" class="ap-card-act-btn edit" title="Editar">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <form action="{{ route('admin.promotions.destroy', $item->id) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar «{{ $item->title }}»?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="ap-card-act-btn del" title="Eliminar">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Body --}}
            <div class="ap-card-body">
                <h3>{{ $item->title }}</h3>
                <p>{{ $item->description }}</p>
                <div class="ap-card-foot">
                    <span class="ap-price">{{ $item->price_text }}</span>
                    @if($item->end_date)
                    <span class="ap-expiry">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Hasta {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="ap-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <h3>Sin promociones</h3>
            <p>Crea tu primera promoción con el botón de arriba.</p>
        </div>
        @endforelse
    </div>

    {{-- MODAL --}}
    <div x-show="openModal" x-cloak class="ap-overlay" @click.self="openModal = false">
        <div class="ap-modal" @click.stop>

            <div class="ap-modal-head">
                {{-- FIX L367: aria-label como fallback para lectores de pantalla --}}
                <h3 x-text="isEdit ? 'Editar Promoción' : 'Nueva Promoción'">Formulario de Promoción</h3>
                <button @click="openModal = false" class="ap-modal-close" aria-label="Cerrar modal">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="actionUrl" method="POST" enctype="multipart/form-data"
                  @submit="if(isSubmitting){$event.preventDefault()}else{isSubmitting=true}">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!isEdit">

                <div class="ap-modal-body">

                    {{-- Imagen --}}
                    <div>
                        {{-- FIX L384: label apunta al input con for="promo-image" --}}
                        <label class="ap-label" for="promo-image">Imagen (opcional — reemplaza ícono y color)</label>
                        <label class="ap-upload-zone" for="promo-image">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" class="ap-upload-img" alt="Vista previa de la promoción seleccionada">
                            </template>
                            <template x-if="imagePreview">
                                <div class="ap-upload-overlay"><span>Cambiar imagen</span></div>
                            </template>
                            <div x-show="!imagePreview" class="ap-upload-placeholder">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Subir imagen</span>
                            </div>
                            <input type="file" id="promo-image" name="image" class="hidden" accept="image/*"
                                   @change="previewImage($event)">
                        </label>
                        <p class="ap-imghelp">Recomendado: imagen <strong>panorámica (16:9)</strong>, mínimo 800×450px.<br>Aceptamos JPG, PNG, WEBP.</p>
                    </div>

                    {{-- Título + Etiqueta --}}
                    <div class="ap-row ap-row-2">
                        <div>
                            {{-- FIX L406: label for="promo-title" + id en el input --}}
                            <label class="ap-label" for="promo-title">Título *</label>
                            <input type="text" id="promo-title" name="title" x-model="promoData.title"
                                   required placeholder="Ej: Jueves de Alitas"
                                   class="ap-input">
                        </div>
                        <div>
                            {{-- FIX L412: label for="promo-tag" + id en el input --}}
                            <label class="ap-label" for="promo-tag">Etiqueta</label>
                            <input type="text" id="promo-tag" name="tag" x-model="promoData.tag"
                                   placeholder="Ej: ¡Solo Jueves!"
                                   class="ap-input">
                        </div>
                    </div>

                    {{-- Ícono + Texto de precio + Caducidad --}}
                    <div class="ap-row ap-row-3">
                        <div>
                            {{-- FIX L422: label for="promo-icon" + id en el input --}}
                            <label class="ap-label" for="promo-icon">Ícono</label>
                            <input type="text" id="promo-icon" name="icon" x-model="promoData.icon"
                                   placeholder="🔥" class="ap-input text-center text-xl">
                        </div>
                        <div>
                            {{-- FIX L427: label for="promo-price" + id en el input --}}
                            <label class="ap-label" for="promo-price">Texto de precio (lo que ve el cliente) *</label>
                            <input type="text" id="promo-price" name="price_text" x-model="promoData.price_text"
                                   required placeholder="Ej: 2x$150"
                                   class="ap-input">
                        </div>
                        <div>
                            {{-- FIX L433: label for="promo-end-date" + id en el input --}}
                            <label class="ap-label" for="promo-end-date">Caduca</label>
                            <input type="date" id="promo-end-date" name="end_date" x-model="promoData.end_date"
                                   class="ap-input">
                        </div>
                    </div>

                    {{-- Precio real (numérico, lo que se cobra en el carrito) --}}
                    <div>
                        <label class="ap-label" for="promo-price-real">Precio real a cobrar (numérico) *</label>
                        <input type="number" id="promo-price-real" name="price" x-model="promoData.price"
                               required min="0" step="0.01" placeholder="Ej: 150.00"
                               class="ap-input">
                        <p class="ap-imghelp">Esto es lo que se cobra de verdad en el carrito. El texto de arriba ("2x$150") es solo lo que ve el cliente en la tarjeta — aquí va el número real, ej. <strong>150.00</strong>.</p>
                    </div>

                    {{-- Se puede vender en línea --}}
                    <div>
                        <label class="ap-label">¿Se puede pedir en línea?</label>
                        <div class="ap-days-grid">
                            <button type="button" class="ap-day-btn"
                                    :class="promoData.sellable_online ? 'on' : ''"
                                    @click="promoData.sellable_online = true">Sí, en línea</button>
                            <button type="button" class="ap-day-btn"
                                    :class="!promoData.sellable_online ? 'on' : ''"
                                    @click="promoData.sellable_online = false">Solo en el local</button>
                        </div>
                        <input type="hidden" name="sellable_online" :value="promoData.sellable_online ? 1 : 0">
                        <p class="ap-imghelp">Elige <strong>"Solo en el local"</strong> para promociones que no se pueden vender por internet — ej. el postre de cumpleaños, que requiere mostrar una identificación y solo aplica en el restaurante. El botón "Aprovechar" no aparecerá para el cliente en línea; solo se podrá dar en el local.</p>
                    </div>

                    {{-- Días activos --}}
                    <div>
                        <label class="ap-label">Días activos (opcional)</label>
                        <div class="ap-days-grid">
                            <template x-for="d in dayOptions" :key="d.key">
                                <button type="button" class="ap-day-btn"
                                        :class="promoData.days_active.includes(d.key) ? 'on' : ''"
                                        @click="toggleDay(d.key)" x-text="d.label"></button>
                            </template>
                        </div>
                        <template x-for="d in promoData.days_active" :key="'day-'+d">
                            <input type="hidden" name="days_active[]" :value="d">
                        </template>
                        <p class="ap-imghelp">Si no eliges ningún día, la promoción está disponible <strong>todos los días</strong> (hasta que caduque, si tiene fecha). Si eliges días específicos — ej. solo Jueves — el botón "Aprovechar" solo funcionará esos días.</p>
                    </div>

                    {{-- Platillos incluidos --}}
                    <div>
                        <label class="ap-label">Platillos incluidos en esta promoción</label>

                        <div class="ap-prod-chips" x-show="selectedProducts.length > 0">
                            <template x-for="(sp, idx) in selectedProducts" :key="sp.id">
                                <div class="ap-prod-chip">
                                    <span x-text="sp.name"></span>
                                    <div class="ap-prod-qty">
                                        <button type="button" @click="decrementProductQty(idx)">−</button>
                                        <span x-text="sp.quantity"></span>
                                        <button type="button" @click="incrementProductQty(idx)">+</button>
                                    </div>
                                    <button type="button" class="ap-prod-remove" @click="removeProduct(idx)" title="Quitar">
                                        <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <p x-show="selectedProducts.length === 0" class="ap-imghelp" style="margin-top:0;">
                            Ningún platillo agregado aún — opcional, pero recomendado para que "Aprovechar" ponga los platillos correctos en el carrito.
                        </p>

                        <div class="ap-prod-search">
                            <input type="text" class="ap-input" placeholder="Buscar platillo... ej: alitas, hamburguesa, bebidas"
                                   x-model="productQuery" @focus="productOpen=true" @input="productOpen=true"
                                   @keydown.escape="productOpen=false" autocomplete="off">

                            <div class="ap-prod-dropdown" x-show="productOpen && productsFiltered().length > 0"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                <template x-for="p in productsFiltered()" :key="p.id">
                                    <div class="ap-prod-dd-item" @mousedown.prevent="selectProduct(p)">
                                        <span x-text="p.name"></span>
                                        <small x-text="p.category"></small>
                                    </div>
                                </template>
                            </div>
                            <div x-show="productOpen && productQuery.trim() && productsFiltered().length === 0" class="ap-prod-dd-empty">
                                Sin coincidencias en el menú
                            </div>
                        </div>

                        <template x-for="(sp, idx) in selectedProducts" :key="'hidden-'+sp.id">
                            <span>
                                <input type="hidden" :name="'products['+idx+'][id]'" :value="sp.id">
                                <input type="hidden" :name="'products['+idx+'][quantity]'" :value="sp.quantity">
                            </span>
                        </template>
                    </div>

                    {{-- Color --}}
                    <div>
                        {{-- FIX L441: label for="promo-color" + id en el select --}}
                        <label class="ap-label" for="promo-color">Color de fondo (sin imagen)</label>
                        <select id="promo-color" name="color_gradient" x-model="promoData.color_gradient" class="ap-input">
                            <option value="from-green-600 to-green-900">Verde</option>
                            <option value="from-orange-500 to-red-700">Naranja / Rojo</option>
                            <option value="from-purple-600 to-indigo-900">Morado / Indigo</option>
                            <option value="from-blue-500 to-blue-800">Azul</option>
                        </select>
                    </div>

                    {{-- Descripción --}}
                    <div>
                        {{-- FIX L452: label for="promo-desc" + id en el textarea --}}
                        <label class="ap-label" for="promo-desc">Descripción *</label>
                        <textarea id="promo-desc" name="description" x-model="promoData.description"
                                  rows="3" required
                                  class="ap-input resize-none"></textarea>
                    </div>

                </div>

                <div class="ap-modal-foot">
                    <button type="button" @click="openModal = false" class="ap-btn-cancel">Cancelar</button>
                    <button type="submit" :disabled="isSubmitting" class="ap-btn-save"
                            x-text="isSubmitting ? 'Guardando…' : (isEdit ? 'Guardar Cambios' : 'Crear Promoción')">
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DE RECORTE DE IMAGEN (Cropper.js) --}}
    <div x-show="cropperOpen" x-cloak class="ap-crop-overlay" @click.self="cancelCrop()">
        <div class="ap-crop-modal" @click.stop>
            <div class="ap-crop-hd">
                <h3>Ajustar imagen</h3>
                <p>Mueve y haz zoom para elegir qué parte se mostrará (formato 16:9)</p>
            </div>
            <div class="ap-crop-body">
                <template x-if="cropperSrc">
                    <img x-ref="promoCropperImg" :src="cropperSrc">
                </template>
            </div>
            <div class="ap-crop-foot">
                <span class="ap-crop-hint">Se guardará como imagen 800×450px</span>
                <div class="ap-crop-actions">
                    <button type="button" @click="cancelCrop()" class="ap-btn-cancel" style="width:auto;flex:none;padding:8px 16px;">Cancelar</button>
                    <button type="button" @click="confirmCrop()" class="ap-btn-save" style="width:auto;flex:none;padding:8px 16px;">Recortar y usar</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function promoManager() {
        return {
            openModal: false,
            isEdit: false,
            isSubmitting: false,
            actionUrl: '',
            imagePreview: null,

            cropperOpen: false,
            cropperSrc: null,
            cropperInstance: null,
            cropperInputEl: null,

            promoData: {
                title: '', description: '', tag: '',
                price_text: '', price: '', icon: '🔥',
                color_gradient: 'from-green-600 to-green-900',
                end_date: '', days_active: [], sellable_online: true
            },

            dayOptions: [
                { key: 'domingo',   label: 'Dom' },
                { key: 'lunes',     label: 'Lun' },
                { key: 'martes',    label: 'Mar' },
                { key: 'miercoles', label: 'Mié' },
                { key: 'jueves',    label: 'Jue' },
                { key: 'viernes',   label: 'Vie' },
                { key: 'sabado',    label: 'Sáb' },
            ],

            toggleDay(key) {
                const idx = this.promoData.days_active.indexOf(key);
                if (idx === -1) this.promoData.days_active.push(key);
                else this.promoData.days_active.splice(idx, 1);
            },

            allProducts: @json($products ?? []),
            selectedProducts: [],
            productQuery: '',
            productOpen: false,

            openCreate() {
                this.isEdit = false;
                this.actionUrl = '{{ route("admin.promotions.store") }}';
                this.imagePreview = null;
                this.promoData = { title:'', description:'', tag:'', price_text:'', price:'', icon:'🔥', color_gradient:'from-green-600 to-green-900', end_date:'', days_active:[], sellable_online:true };
                this.selectedProducts = [];
                this.productQuery = '';
                this.productOpen = false;
                this.isSubmitting = false;
                this.openModal = true;
            },

            openEdit(item) {
                this.isEdit = true;
                this.actionUrl = '{{ route("admin.promotions.store") }}' + '/' + item.id;
                this.imagePreview = item.image ? (item.image.startsWith('http') ? item.image : `/storage/${item.image}`) : null;
                this.promoData = {
                    title: item.title,
                    description: item.description,
                    tag: item.tag || '',
                    price_text: item.price_text,
                    price: item.price ?? '',
                    icon: item.icon || '🔥',
                    color_gradient: item.color_gradient || 'from-green-600 to-green-900',
                    end_date: item.end_date || '',
                    days_active: item.days_active ? item.days_active.split(',').map(d => d.trim()).filter(Boolean) : [],
                    sellable_online: item.sellable_online === undefined ? true : !!item.sellable_online,
                };
                this.selectedProducts = (item.products || []).map(p => ({
                    id: p.id,
                    name: p.name,
                    quantity: (p.pivot && p.pivot.quantity) ? p.pivot.quantity : 1,
                }));
                this.productQuery = '';
                this.productOpen = false;
                this.isSubmitting = false;
                this.openModal = true;
            },

            productsFiltered() {
                const q = this.productQuery.trim().toLowerCase();
                let list = this.allProducts;
                if (q) {
                    list = list.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        (p.category && p.category.toLowerCase().includes(q)) ||
                        (p.subcategory && p.subcategory.toLowerCase().includes(q))
                    );
                }
                const selectedIds = this.selectedProducts.map(sp => sp.id);
                return list.filter(p => !selectedIds.includes(p.id)).slice(0, 30);
            },

            selectProduct(p) {
                this.selectedProducts.push({ id: p.id, name: p.name, quantity: 1 });
                this.productQuery = '';
                this.productOpen = false;
            },

            removeProduct(idx) {
                this.selectedProducts.splice(idx, 1);
            },

            incrementProductQty(idx) {
                this.selectedProducts[idx].quantity++;
            },

            decrementProductQty(idx) {
                if (this.selectedProducts[idx].quantity > 1) this.selectedProducts[idx].quantity--;
            },

            previewImage(e) {
                const file = e.target.files[0];
                if (!file) return;
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona un archivo de imagen válido (JPG, PNG, WEBP, etc.)');
                    e.target.value = '';
                    return;
                }
                this.cropperInputEl = e.target;
                const reader = new FileReader();
                reader.onload = ev => {
                    this.cropperSrc  = ev.target.result;
                    this.cropperOpen = true;
                    this.$nextTick(() => this.initCropper());
                };
                reader.readAsDataURL(file);
            },

            initCropper() {
                if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
                this.cropperInstance = new Cropper(this.$refs.promoCropperImg, {
                    aspectRatio: 16 / 9,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    responsive: true,
                });
            },

            confirmCrop() {
                if (!this.cropperInstance) return;
                const canvas = this.cropperInstance.getCroppedCanvas({
                    width: 800, height: 450, imageSmoothingQuality: 'high',
                });
                canvas.toBlob((blob) => {
                    const file = new File([blob], 'promo-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.cropperInputEl.files = dt.files;
                    this.imagePreview = canvas.toDataURL('image/jpeg', 0.9);
                    this.cropperOpen = false;
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                    this.cropperSrc = null;
                }, 'image/jpeg', 0.9);
            },

            cancelCrop() {
                this.cropperOpen = false;
                if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
                this.cropperSrc = null;
                if (this.cropperInputEl) this.cropperInputEl.value = '';
            }
        }
    }
</script>

@endsection