{{--
    Casilla de consentimiento. Nunca viene marcada, tampoco después de un error: la persona la marca cada vez.
    Parámetros: $name (campo), $slot o $texto (texto con enlaces ya escapados), $requerida (true por defecto).
--}}
@php($requerida = $requerida ?? true)
<label class="casilla-legal @error($name) is-invalid @enderror"
       style="display:flex; gap:10px; align-items:flex-start; margin:14px 0 4px; font-size:13px; line-height:1.5; color: var(--txt, inherit); cursor:pointer;">
    <input type="checkbox" name="{{ $name }}" value="1" @if($requerida) required aria-required="true" @endif
           style="flex:none; width:18px; height:18px; margin-top:2px; accent-color:#F97316; cursor:pointer;">
    <span>{!! $texto !!}</span>
</label>
@error($name)
    <p role="alert" style="margin:0 0 8px 28px; font-size:12px; color:#EF4444;">{{ $message }}</p>
@enderror
