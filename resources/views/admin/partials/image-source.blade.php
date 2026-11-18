{{-- B-36: de dónde salió la imagen. Obligatorio al subir una nueva; si no se cambia la imagen se conserva. --}}
<div style="margin-top: 10px; text-align: left;">
    <label for="{{ $id }}" style="display: block; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--sub, var(--txt-sub, #71717a)); margin-bottom: 4px;">Origen de la imagen</label>
    <input type="text" id="{{ $id }}" name="image_source" maxlength="150" list="{{ $id }}-sugerencias"
           value="{{ $valor ?? '' }}"
           placeholder="Ej: Foto propia · Unsplash (autor) · Proveedor"
           style="width: 100%; box-sizing: border-box; padding: 9px 12px; border-radius: 8px; border: 1px solid var(--bdr, #e4e4e7); background: var(--input, var(--bg-input, transparent)); color: inherit; font-size: 13px;">
    <datalist id="{{ $id }}-sugerencias">
        <option value="Foto propia del restaurante">
        <option value="Unsplash (licencia Unsplash)">
        <option value="Pexels (licencia Pexels)">
        <option value="Proveedor de la marca">
    </datalist>
    <p style="font-size: 11px; line-height: 1.5; margin: 4px 0 0; color: var(--sub, var(--txt-sub, #71717a));">Obligatorio al subir una imagen nueva. No subas logos de equipos o ligas ni fotos de transmisiones.</p>
</div>
