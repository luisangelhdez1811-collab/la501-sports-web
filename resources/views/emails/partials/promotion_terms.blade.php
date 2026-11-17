<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FAF9F7;border:1px solid #E7E5E4;border-radius:10px;">
    <tr>
        <td style="padding:14px 16px;font-size:13px;line-height:1.65;color:#57534E;">
            <strong style="color:#292524;">Vigencia:</strong>
            {{ $promotion->vigenciaLabel() }}<br>
            @if(filled($promotion->conditions))
                <strong style="color:#292524;">Condiciones:</strong> {{ $promotion->conditions }}
            @endif
            @if($promotion->advertisesAlcohol())
                <br><strong style="color:#292524;">{{ \App\Services\AlcoholPolicy::legend() }}</strong>
                Venta de bebidas alcohólicas solo a mayores de 18 años.
            @endif
        </td>
    </tr>
</table>
