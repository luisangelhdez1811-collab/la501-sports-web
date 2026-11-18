@extends('emails.layout')

@section('title', $promotion->title)
@section('preheader', ($esNueva ? 'Nueva promoción: ' : 'Promoción actualizada: ') . $promotion->title)
@section('eyebrow', $esNueva ? 'Nueva promoción' : 'Promoción actualizada')

@section('content')
    @if($promotion->image)
        <img src="{{ (str_starts_with($promotion->image, 'http://') || str_starts_with($promotion->image, 'https://')) ? $promotion->image : asset('storage/' . $promotion->image) }}"
             alt="{{ $promotion->title }}" width="480"
             style="display:block;width:100%;max-width:480px;height:auto;border:0;border-radius:10px;margin:0 auto 22px;">
    @endif

    <h1 class="h1" style="margin:0 0 6px;font-size:24px;line-height:1.25;font-weight:800;color:#1C1917;">
        {{ $promotion->title }}
    </h1>
    <p style="margin:0 0 14px;font-size:22px;font-weight:800;color:#C2410C;">{{ $promotion->price_text }}</p>
    <p style="margin:0 0 18px;color:#57534E;">{{ $promotion->description }}</p>

    {{-- Vigencia y condiciones (LFPC art. 46 a 48) --}}
    @include('emails.partials.promotion_terms', ['promotion' => $promotion])

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" class="btn" style="margin:24px auto 0;">
        <tr>
            <td align="center" style="border-radius:10px;background-color:#C2410C;">
                <a href="{{ route('promociones') }}"
                   style="display:inline-block;padding:14px 30px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:10px;">
                    Ver la promoción
                </a>
            </td>
        </tr>
    </table>
@endsection

@section('unsubscribe')
    Recibes este correo porque te suscribiste a las promociones de La 501 Sports.
    <a href="{{ route('newsletter.unsubscribe', $unsubscribeToken) }}" style="color:#E7E5E4;">Darme de baja</a>
@endsection
