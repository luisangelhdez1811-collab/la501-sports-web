{{-- B-19 (LFPDPPP): aviso de privacidad simplificado donde se piden datos: quién es el responsable, para qué
     se usan y dónde leer el aviso completo. --}}
<p class="aviso-corto" style="margin: 14px 0 0; font-size: 12px; line-height: 1.6; color: var(--txt-sub, var(--sub, #78716C)); text-align: {{ $alinear ?? 'left' }};">
    <strong>Privacidad:</strong> La 501 Sports Restaurant (Andrés Rivera Jiménez) usa estos datos para {{ $finalidad }}.
    @isset($extra){{ $extra }}@endisset
    Consulta el <a href="{{ route('aviso-privacidad') }}" target="_blank" rel="noopener" style="color: inherit; text-decoration: underline;">Aviso de Privacidad</a>.
</p>
