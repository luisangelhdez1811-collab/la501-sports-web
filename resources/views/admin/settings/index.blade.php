@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.js"></script>

<style>
    .as {
        --accent:   #2563EB;
        --accent2:  #1D4ED8;

        --bg:       #F8F8F8;
        --bg-card:  #FFFFFF;
        --bg-input: #F4F4F5;
        --txt:      #18181B;
        --txt-sub:  #71717A;
        --bdr:      #E4E4E7;
    }
    .dark .as {
        --bg:       #0A0A0A;
        --bg-card:  #111111;
        --bg-input: #1C1C1C;
        --txt:      #FAFAFA;
        --txt-sub:  #71717A;
        --bdr:      rgba(255,255,255,0.08);
    }

    .as { background: var(--bg); min-height: 100%; }

    /* page header */
    .as-header { margin-bottom: 24px; }
    .as-header h1 { font-size: 20px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .as-header p  { font-size: 13px; color: var(--txt-sub); margin: 0; }

    /* grid */
    .as-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    /* card */
    .as-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 10px;
        overflow: hidden;
    }
    .as-card-head {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--bdr);
    }
    .as-card-head-icon {
        width: 30px; height: 30px; border-radius: 7px;
        background: rgba(37,99,235,.08);
        border: 1px solid rgba(37,99,235,.15);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .as-card-head-icon svg { width: 15px; height: 15px; color: var(--accent); }
    .as-card-head h2 { font-size: 14px; font-weight: 700; color: var(--txt); margin: 0; }
    .as-card-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }

    /* labels & inputs */
    .as-label {
        display: block; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--txt-sub); margin-bottom: 4px;
    }
    .as-input {
        width: 100%; padding: 8px 10px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        border-radius: 7px; font-size: 13px; color: var(--txt);
        outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .as-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }

    /* info box */
    .as-info {
        background: var(--bg-input);
        border: 1px solid var(--bdr);
        border-radius: 7px;
        padding: 10px 12px;
        font-size: 12px; color: var(--txt-sub); line-height: 1.6;
    }
    .as-info strong { color: var(--txt); font-weight: 600; }

    /* logo preview */
    .as-logo-preview {
        width: 100%; height: 130px;
        background: var(--bg-input);
        border: 1px solid var(--bdr);
        border-radius: 7px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; padding: 16px;
    }
    .as-logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }

    /* file picker */
    .as-file-label {
        display: inline-flex; align-items: center; gap: 7px;
        width: 100%;
        padding: 8px 12px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        border-radius: 7px; cursor: pointer;
        font-size: 13px; color: var(--txt-sub); font-weight: 500;
        transition: border-color .15s, color .15s;
    }
    .as-file-label:hover { border-color: var(--accent); color: var(--accent); }
    .as-file-label svg { width: 14px; height: 14px; flex-shrink: 0; }

    /* map preview */
    .as-map-preview {
        width: 100%; height: 160px;
        background: var(--bg-input);
        border: 1px solid var(--bdr);
        border-radius: 7px; overflow: hidden;
        position: relative;
    }
    .as-map-preview iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .as-map-empty {
        width: 100%; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px;
    }
    .as-map-empty svg { width: 24px; height: 24px; color: var(--txt-sub); opacity: .4; }
    .as-map-empty span { font-size: 12px; color: var(--txt-sub); }

    /* schedule rows */
    .as-schedule { display: flex; flex-direction: column; gap: 6px; }
    .as-sched-row {
        display: flex; align-items: center; gap: 10px;
    }
    .as-sched-day {
        width: 80px; flex-shrink: 0;
        font-size: 12px; font-weight: 600;
        color: var(--txt-sub);
    }
    .as-sched-row .as-input { flex: 1; }
    .as-tip {
        font-size: 11px; color: var(--txt-sub);
        background: var(--bg-input);
        border: 1px solid var(--bdr);
        border-radius: 6px; padding: 6px 10px;
    }

    /* submit button */
    .as-btn {
        width: 100%; padding: 9px;
        background: var(--accent); color: #fff;
        border: none; border-radius: 7px;
        font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
        display: flex; align-items: center; justify-content: center; gap: 6px;
        text-decoration: none;
    }
    .as-btn:hover { background: var(--accent2); }
    .as-btn:disabled { opacity: .6; cursor: not-allowed; }
    .as-btn svg { width: 14px; height: 14px; animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── CARRUSEL ─────────────────────────────── */
    .as-car-head { justify-content: space-between; }
    .as-car-head-left { display: flex; align-items: center; gap: 10px; }
    .as-car-addbtn {
        width: auto; padding: 7px 16px;
        background: #16A34A; box-shadow: 0 1px 3px rgba(22,163,74,.3);
    }
    .as-car-addbtn:hover { background: #15803D; }

    .as-alert { border-radius: 7px; padding: 10px 12px; font-size: 12px; font-weight: 600; border: 1px solid; }
    .as-alert.ok  { background: rgba(22,163,74,.06); border-color: rgba(22,163,74,.25); color: #16A34A; }
    .as-alert.err { background: rgba(220,38,38,.06); border-color: rgba(220,38,38,.25); color: #EF4444; }

    .as-car-form {
        background: var(--bg-input); border: 1px solid var(--bdr);
        border-radius: 8px; padding: 18px;
        display: flex; flex-direction: column; gap: 12px;
    }
    .as-car-form h3 { font-size: 13px; font-weight: 700; color: var(--txt); margin: 0; }
    .as-car-form-grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .as-car-form-foot { display: flex; justify-content: flex-end; }
    .as-car-form-foot .as-btn { width: auto; padding: 8px 20px; }

    /* upload box (banner 21:9) */
    .as-car-upload {
        display: flex; position: relative;
        aspect-ratio: 21 / 9; width: 100%; border-radius: 7px;
        border: 1px dashed var(--bdr); background: var(--bg-card);
        cursor: pointer; overflow: hidden;
        align-items: center; justify-content: center;
        transition: border-color .15s;
    }
    .as-car-upload:hover { border-color: var(--accent); }
    .as-car-upload img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .as-car-upload-ph { display: flex; flex-direction: column; align-items: center; gap: 5px; position: relative; z-index: 1; }
    .as-car-upload-ph svg { width: 20px; height: 20px; color: var(--txt-sub); }
    .as-car-upload-ph span { font-size: 12px; color: var(--txt-sub); font-weight: 500; }
    .as-car-upload-ov {
        position: absolute; inset: 0; background: rgba(0,0,0,.45);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity .2s; z-index: 2;
    }
    .as-car-upload:hover .as-car-upload-ov { opacity: 1; }
    .as-car-upload-ov span { font-size: 12px; color: #fff; font-weight: 600; }
    .as-imghelp { font-size: 11px; color: var(--txt-sub); margin: 6px 2px 0; line-height: 1.5; }
    .as-imghelp strong { color: var(--txt); }

    .as-car-empty { text-align: center; padding: 32px 0; color: var(--txt-sub); }
    .as-car-empty .ico { font-size: 26px; margin-bottom: 6px; display: block; }
    .as-car-empty .ttl { font-size: 13px; font-weight: 600; }
    .as-car-empty .sub { font-size: 11px; margin-top: 2px; }

    .as-car-list { display: flex; flex-direction: column; gap: 10px; }
    .as-car-item {
        background: var(--bg-input); border: 1px solid var(--bdr);
        border-radius: 8px; padding: 12px; display: flex; align-items: center;
        gap: 16px; flex-wrap: wrap;
    }
    .as-car-thumb {
        width: 130px; aspect-ratio: 21/9; border-radius: 6px; overflow: hidden;
        flex-shrink: 0; background: #000; border: 1px solid var(--bdr);
    }
    .as-car-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .as-car-info { flex: 1; min-width: 200px; }
    .as-car-badges { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .as-car-badge {
        font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 4px;
        background: #F97316; color: #fff;
    }
    .as-car-badge.off { background: #EF4444; }
    .as-car-title { font-size: 13px; font-weight: 700; color: var(--txt); margin: 0; }
    .as-car-title small { font-weight: 500; color: var(--txt-sub); }
    .as-car-desc { font-size: 11px; color: var(--txt-sub); margin: 2px 0 0; }
    .as-car-item-actions { display: flex; gap: 8px; }
    .as-car-item-actions .as-btn { font-size: 11px; padding: 6px 12px; }
    .as-car-item-actions .as-btn.edit { background: #2563EB; }
    .as-car-item-actions .as-btn.del  { background: #EF4444; }

    .as-car-edit {
        background: var(--bg-card); border: 1px solid var(--bdr);
        border-radius: 8px; padding: 16px; margin-top: -4px;
        display: flex; flex-direction: column; gap: 10px;
    }
    .as-car-edit h4 { font-size: 12px; font-weight: 700; color: var(--txt); margin: 0; }
    .as-car-active-row { display: flex; align-items: center; gap: 8px; }
    .as-car-active-row label { font-size: 11px; font-weight: 600; color: var(--txt-sub); text-transform: uppercase; }
    .as-car-edit-foot { display: flex; justify-content: flex-end; gap: 8px; }
    .as-car-edit-foot .as-btn { width: auto; padding: 6px 16px; }
    .as-car-edit-foot .as-btn.cancel { background: #6B7280; }

    /* ── RECORTE DE IMAGEN (Cropper.js) ── */
    .as-crop-overlay {
        position: fixed; inset: 0; z-index: 70;
        background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .as-crop-modal {
        background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 12px;
        width: 100%; max-width: 640px; box-shadow: 0 24px 64px rgba(0,0,0,.35);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .as-crop-hd { padding: 16px 18px; border-bottom: 1px solid var(--bdr); }
    .as-crop-hd h3 { font-size: 15px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .as-crop-hd p  { font-size: 12px; color: var(--txt-sub); margin: 0; }
    .as-crop-body { background: #000; height: 320px; }
    .as-crop-body img { display: block; max-width: 100%; }
    .as-crop-foot { padding: 14px 18px; border-top: 1px solid var(--bdr); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .as-crop-hint { font-size: 11px; color: var(--txt-sub); }
    .as-crop-actions { display: flex; gap: 8px; }
    @media (max-width: 480px) { .as-crop-body { height: 240px; } .as-crop-foot { flex-direction: column; align-items: stretch; } }

    @media (max-width: 768px) {
        .as-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .as-car-form-grid3 { grid-template-columns: 1fr; }
    }
</style>

<div class="as">

    <div class="as-header">
        <h1>Configuración</h1>
        <p>Administra la información general del sitio web.</p>
    </div>

    <div class="as-grid">

        {{-- ── LOGO ── --}}
        <div class="as-card" x-data="{ imagePreview: null, isSubmitting: false }">
            <div class="as-card-head">
                <div class="as-card-head-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2>Logo Principal</h2>
            </div>
            <div class="as-card-body">
                <form action="{{ route('admin.settings.logo') }}" method="POST" enctype="multipart/form-data"
                      @submit="if(isSubmitting){$event.preventDefault()}else{isSubmitting=true}">
                    @csrf

                    {{-- Preview --}}
                    <div class="as-logo-preview">
                        <template x-if="imagePreview">
                            <img :src="imagePreview" alt="Vista previa del nuevo logo">
                        </template>
                        <template x-if="!imagePreview">
                            @php
                                $logoPath = asset('images/logo_501.png');
                                if ($logo) {
                                    if (str_starts_with($logo->value, 'http://') || str_starts_with($logo->value, 'https://')) {
                                        $logoPath = $logo->value;
                                    } elseif (str_starts_with($logo->value, 'logos/')) {
                                        $logoPath = asset('storage/' . $logo->value);
                                    }
                                }
                            @endphp
                            <img src="{{ $logoPath }}" alt="Logo actual">
                        </template>
                    </div>

                    {{-- File picker --}}
                    <label class="as-file-label" for="logo-upload">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>Seleccionar imagen…</span>
                        <input type="file" id="logo-upload" name="logo" class="hidden" accept="image/*" required
                               @change="const f=$event.target.files[0];if(f){const r=new FileReader();r.onload=e=>imagePreview=e.target.result;r.readAsDataURL(f)}">
                    </label>

                    <button type="submit" :disabled="isSubmitting" class="as-btn">
                        <template x-if="isSubmitting">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-text="isSubmitting ? 'Guardando…' : 'Guardar Logo'"></span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ── MAPA ── --}}
        <div class="as-card"
             x-data="{
                rawMapInput: '{{ $map_url->value ?? '' }}',
                isSubmitting: false,
                get mapPreview() {
                    if (!this.rawMapInput) return '';
                    let val = this.rawMapInput;
                    if (val.includes('output=embed') || val.includes('google.com/maps/embed')) return val;
                    let query = val;
                    if (val.includes('/place/')) {
                        let m = val.match(/\/place\/([^\/]+)/);
                        if (m) { try { query = decodeURIComponent(m[1].replace(/\+/g,' ')); } catch(e) { query = m[1]; } }
                    } else if (val.includes('/@')) {
                        let m = val.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                        if (m) query = m[1]+','+m[2];
                    }
                    return 'https://maps.google.com/maps?q='+encodeURIComponent(query)+'&output=embed';
                }
             }">
            <div class="as-card-head">
                <div class="as-card-head-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h2>Ubicación en el Mapa</h2>
            </div>
            <div class="as-card-body">
                <form action="{{ route('admin.settings.map') }}" method="POST"
                      @submit="if(isSubmitting){$event.preventDefault()}else{isSubmitting=true}">
                    @csrf
                    <input type="hidden" name="map_url" :value="mapPreview">

                    <div class="as-info">
                        <strong>¿Cómo actualizar?</strong> Pega la URL de Google Maps o escribe el nombre del lugar.
                        Ej: <em>La 501 Centro, Huejutla</em>
                    </div>

                    <div>
                        {{-- FIX L272: label for="map-input" + id en el input --}}
                        <label class="as-label" for="map-input">Dirección o enlace</label>
                        <input type="text" id="map-input" x-model="rawMapInput" required
                               placeholder="Ej: La 501 Centro, Huejutla"
                               class="as-input">
                    </div>

                    {{-- Preview mapa --}}
                    <div class="as-map-preview">
                        <template x-if="mapPreview">
                            <iframe :src="mapPreview" allowfullscreen="" loading="lazy"
                                    title="Vista previa del mapa"></iframe>
                        </template>
                        <template x-if="!mapPreview">
                            <div class="as-map-empty">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 13l4.553 2.276A1 1 0 0021 21.382V10.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 4"/>
                                </svg>
                                <span>Escribe algo para ver el mapa</span>
                            </div>
                        </template>
                    </div>

                    <button type="submit" :disabled="isSubmitting" class="as-btn">
                        <template x-if="isSubmitting">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-text="isSubmitting ? 'Guardando…' : 'Guardar Mapa'"></span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ── DIRECCIÓN ── --}}
        <div class="as-card" x-data="{ isSubmitting: false }">
            <div class="as-card-head">
                <div class="as-card-head-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <h2>Dirección del Local</h2>
            </div>
            <div class="as-card-body">
                <form action="{{ route('admin.settings.address') }}" method="POST"
                      @submit="if(isSubmitting){$event.preventDefault()}else{isSubmitting=true}">
                    @csrf

                    <div>
                        {{-- FIX L322: label for="addr-line1" + id en el input --}}
                        <label class="as-label" for="addr-line1">Calle y número</label>
                        <input type="text" id="addr-line1" name="address_line1"
                               value="{{ $address_line1->value ?? '' }}"
                               required placeholder="Av. Deportiva #501"
                               class="as-input">
                    </div>
                    <div>
                        {{-- FIX L329: label for="addr-line2" + id en el input --}}
                        <label class="as-label" for="addr-line2">Colonia o sector</label>
                        <input type="text" id="addr-line2" name="address_line2"
                               value="{{ $address_line2->value ?? '' }}"
                               placeholder="Col. Centro"
                               class="as-input">
                    </div>
                    <div>
                        {{-- FIX L336: label for="addr-line3" + id en el input --}}
                        <label class="as-label" for="addr-line3">Ciudad, estado y CP</label>
                        <input type="text" id="addr-line3" name="address_line3"
                               value="{{ $address_line3->value ?? '' }}"
                               placeholder="CP 43000, Huejutla de Reyes, Hgo."
                               class="as-input">
                    </div>

                    <button type="submit" :disabled="isSubmitting" class="as-btn">
                        <template x-if="isSubmitting">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-text="isSubmitting ? 'Guardando…' : 'Guardar Dirección'"></span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ── HORARIOS ── --}}
        <div class="as-card" x-data="{ isSubmitting: false }">
            <div class="as-card-head">
                <div class="as-card-head-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2>Horario de Atención</h2>
            </div>
            <div class="as-card-body">
                <form action="{{ route('admin.settings.schedule') }}" method="POST"
                      @submit="if(isSubmitting){$event.preventDefault()}else{isSubmitting=true}">
                    @csrf

                    <p class="as-tip">Escribe "Cerrado" en los días que no abren.</p>

                    @php
                        $days = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
                    @endphp

                    <div class="as-schedule">
                        @foreach($days as $day)
                        @php $dayKey = strtolower($day); @endphp
                        <div class="as-sched-row">
                            {{-- FIX: label for apuntando al id único del input de cada día --}}
                            <label class="as-sched-day" for="sched-{{ $dayKey }}">{{ $day }}</label>
                            <input type="text" id="sched-{{ $dayKey }}" name="schedule_{{ $dayKey }}"
                                   value="{{ $schedule[$dayKey] ?? '12:30 PM – 10:30 PM' }}"
                                   class="as-input">
                        </div>
                        @endforeach
                    </div>

                    <button type="submit" :disabled="isSubmitting" class="as-btn">
                        <template x-if="isSubmitting">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-text="isSubmitting ? 'Guardando…' : 'Guardar Horarios'"></span>
                    </button>
                </form>
            </div>
        </div>

    </div>

    {{-- ── GESTIÓN DEL CARRUSEL DE IMÁGENES ── --}}
    <div class="as-card" style="margin-top: 24px;" x-data="carouselManager()">
        <div class="as-card-head as-car-head">
            <div class="as-car-head-left">
                <div class="as-card-head-icon" style="background: rgba(249,115,22,.08); border-color: rgba(249,115,22,.15);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="color: #F97316;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2>Imágenes del Carrusel de Inicio</h2>
            </div>
            <button type="button" @click="showAddForm = !showAddForm; if(!showAddForm) newImagePreview = null" class="as-btn as-car-addbtn">
                <span x-text="showAddForm ? 'Cancelar' : '➕ Agregar Diapositiva'"></span>
            </button>
        </div>

        <div class="as-card-body">

            {{-- Mensajes de estado --}}
            @if(session('status'))
                <div class="as-alert ok">{{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="as-alert err">{{ session('error') }}</div>
            @endif

            {{-- Formulario para Agregar --}}
            <div x-show="showAddForm" x-cloak class="as-car-form">
                <h3>Nueva Diapositiva (Subida Directa a Cloudinary)</h3>
                <form action="{{ route('admin.carousel.store') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 12px;">
                    @csrf

                    <div>
                        <label class="as-label">Imagen del Banner</label>
                        <label class="as-car-upload">
                            <template x-if="newImagePreview">
                                <img :src="newImagePreview" alt="Vista previa del banner">
                            </template>
                            <template x-if="newImagePreview">
                                <div class="as-car-upload-ov"><span>Cambiar imagen</span></div>
                            </template>
                            <div x-show="!newImagePreview" class="as-car-upload-ph">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Subir imagen</span>
                            </div>
                            <input type="file" name="image" accept="image/*" class="hidden" @change="previewSlideImage($event, 'new')">
                        </label>
                        <p class="as-imghelp">Recomendado: banner panorámico <strong>21:9</strong>, mínimo 1600×686px.<br>Aceptamos JPG, PNG, WEBP.</p>
                    </div>

                    <div>
                        <label class="as-label">O Enlace URL Externo existente</label>
                        <input type="url" name="image_url" placeholder="https://res.cloudinary.com/..." class="as-input">
                    </div>

                    <div class="as-car-form-grid3">
                        <div>
                            <label class="as-label">Subtítulo</label>
                            <input type="text" name="subtitle" placeholder="Ej: Gourmet & Grill" class="as-input">
                        </div>
                        <div>
                            <label class="as-label">Título Principal</label>
                            <input type="text" name="title" placeholder="Ej: SABOR INIGUALABLE" class="as-input">
                        </div>
                        <div>
                            <label class="as-label">Orden</label>
                            <input type="number" name="order" value="1" required class="as-input">
                        </div>
                    </div>
                    <div>
                        <label class="as-label">Descripción</label>
                        <textarea name="description" rows="2" placeholder="Descripción breve..." class="as-input" style="resize: none;"></textarea>
                    </div>
                    <div class="as-car-form-foot">
                        <button type="submit" class="as-btn">Subir y Guardar</button>
                    </div>
                </form>
            </div>

            {{-- Listado de Diapositivas --}}
            @if($carouselSlides->isEmpty())
                <div class="as-car-empty">
                    <span class="ico">🎠</span>
                    <p class="ttl">No hay diapositivas configuradas</p>
                    <p class="sub">Usa el botón de arriba para agregar tu primera diapositiva al carrusel.</p>
                </div>
            @else
                <div class="as-car-list">
                    @foreach($carouselSlides as $slide)
                        <div class="as-car-item">

                            {{-- Vista previa --}}
                            <div class="as-car-thumb">
                                <img src="{{ $slide->image_path }}" alt="Slide">
                            </div>

                            {{-- Info --}}
                            <div class="as-car-info">
                                <div class="as-car-badges">
                                    <span class="as-car-badge">Orden #{{ $slide->order }}</span>
                                    @if(!$slide->is_active)
                                        <span class="as-car-badge off">Inactivo</span>
                                    @endif
                                </div>
                                <h3 class="as-car-title">
                                    {{ $slide->title ?? 'Sin Título' }}
                                    <small>({{ $slide->subtitle ?? 'Sin Subtítulo' }})</small>
                                </h3>
                                <p class="as-car-desc">{{ $slide->description ?? 'Sin descripción.' }}</p>
                            </div>

                            {{-- Acciones --}}
                            <div class="as-car-item-actions">
                                <button type="button" @click="editingSlide = (editingSlide === {{ $slide->id }} ? null : {{ $slide->id }}); editImagePreview = null" class="as-btn edit">
                                    Editar
                                </button>
                                <form action="{{ route('admin.carousel.destroy', $slide->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta diapositiva?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="as-btn del">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Edición Form --}}
                        <div x-show="editingSlide === {{ $slide->id }}" x-cloak class="as-car-edit">
                            <h4>Editar Diapositiva #{{ $slide->order }}</h4>
                            <form action="{{ route('admin.carousel.update', $slide->id) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label class="as-label">Reemplazar Imagen del Banner</label>
                                    <label class="as-car-upload">
                                        <template x-if="editingSlide === {{ $slide->id }} && editImagePreview">
                                            <img :src="editImagePreview" alt="Vista previa del banner">
                                        </template>
                                        <template x-if="!(editingSlide === {{ $slide->id }} && editImagePreview)">
                                            <img src="{{ $slide->image_path }}" alt="Imagen actual">
                                        </template>
                                        <div class="as-car-upload-ov"><span>Cambiar imagen</span></div>
                                        <input type="file" name="image" accept="image/*" class="hidden" @change="previewSlideImage($event, 'edit')">
                                    </label>
                                    <p class="as-imghelp">Recomendado: banner panorámico <strong>21:9</strong>, mínimo 1600×686px.</p>
                                </div>

                                <div>
                                    <label class="as-label">O Reemplazar con URL externa</label>
                                    <input type="url" name="image_url" value="{{ !str_contains($slide->image_path, 'cloudinary.com') ? $slide->image_path : '' }}" placeholder="https://res.cloudinary.com/..." class="as-input">
                                </div>

                                <div class="as-car-form-grid3">
                                    <div>
                                        <label class="as-label">Subtítulo</label>
                                        <input type="text" name="subtitle" value="{{ $slide->subtitle }}" class="as-input">
                                    </div>
                                    <div>
                                        <label class="as-label">Título Principal</label>
                                        <input type="text" name="title" value="{{ $slide->title }}" class="as-input">
                                    </div>
                                    <div>
                                        <label class="as-label">Orden</label>
                                        <input type="number" name="order" value="{{ $slide->order }}" required class="as-input">
                                    </div>
                                </div>
                                <div>
                                    <label class="as-label">Descripción</label>
                                    <textarea name="description" rows="2" class="as-input" style="resize: none;">{{ $slide->description }}</textarea>
                                </div>
                                <div class="as-car-active-row">
                                    <input type="checkbox" id="edit_active_{{ $slide->id }}" name="is_active" value="1" {{ $slide->is_active ? 'checked' : '' }} style="width: 14px; height: 14px;">
                                    <label for="edit_active_{{ $slide->id }}">Mostrar diapositiva en el carrusel (Activo)</label>
                                </div>
                                <div class="as-car-edit-foot">
                                    <button type="button" @click="editingSlide = null" class="as-btn cancel">Cancelar</button>
                                    <button type="submit" class="as-btn">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>

        {{-- MODAL DE RECORTE DE BANNER (Cropper.js, compartido) --}}
        <div x-show="cropperOpen" x-cloak class="as-crop-overlay" @click.self="cancelSlideCrop()">
            <div class="as-crop-modal" @click.stop>
                <div class="as-crop-hd">
                    <h3>Ajustar banner</h3>
                    <p>Mueve y haz zoom para elegir qué parte se mostrará (formato 21:9)</p>
                </div>
                <div class="as-crop-body">
                    <template x-if="cropperSrc">
                        <img x-ref="slideCropperImg" :src="cropperSrc">
                    </template>
                </div>
                <div class="as-crop-foot">
                    <span class="as-crop-hint">Se guardará como imagen 1600×686px</span>
                    <div class="as-crop-actions">
                        <button type="button" @click="cancelSlideCrop()" class="as-btn cancel" style="width:auto;padding:8px 16px;">Cancelar</button>
                        <button type="button" @click="confirmSlideCrop()" class="as-btn" style="width:auto;padding:8px 16px;">Recortar y usar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function carouselManager() {
    return {
        editingSlide: null,
        showAddForm: false,
        newImagePreview: null,
        editImagePreview: null,

        cropperOpen: false,
        cropperSrc: null,
        cropperInstance: null,
        cropperTarget: null,   // 'new' | 'edit'
        cropperInputEl: null,  // el <input type="file"> real que disparó el cambio

        previewSlideImage(e, target) {
            const file = e.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                alert('Por favor selecciona un archivo de imagen válido (JPG, PNG, WEBP, etc.)');
                e.target.value = '';
                return;
            }
            this.cropperTarget  = target;
            this.cropperInputEl = e.target;
            const reader = new FileReader();
            reader.onload = ev => {
                this.cropperSrc  = ev.target.result;
                this.cropperOpen = true;
                this.$nextTick(() => this.initSlideCropper());
            };
            reader.readAsDataURL(file);
        },

        initSlideCropper() {
            if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
            this.cropperInstance = new Cropper(this.$refs.slideCropperImg, {
                aspectRatio: 21 / 9,
                viewMode: 1,
                autoCropArea: 1,
                background: false,
                movable: true,
                zoomable: true,
                scalable: false,
                responsive: true,
            });
        },

        confirmSlideCrop() {
            if (!this.cropperInstance) return;
            const canvas = this.cropperInstance.getCroppedCanvas({
                width: 1600, height: 686, imageSmoothingQuality: 'high',
            });
            canvas.toBlob((blob) => {
                const file = new File([blob], 'banner-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                this.cropperInputEl.files = dt.files;

                const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                if (this.cropperTarget === 'new') {
                    this.newImagePreview = dataUrl;
                } else {
                    this.editImagePreview = dataUrl;
                }

                this.cropperOpen = false;
                this.cropperInstance.destroy();
                this.cropperInstance = null;
                this.cropperSrc = null;
            }, 'image/jpeg', 0.9);
        },

        cancelSlideCrop() {
            this.cropperOpen = false;
            if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
            this.cropperSrc = null;
            if (this.cropperInputEl) this.cropperInputEl.value = '';
        },
    }
}
</script>

@endsection