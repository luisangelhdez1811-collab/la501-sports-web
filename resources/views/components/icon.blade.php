@props(['name', 'label' => null, 'size' => 18])
{!! \App\Support\Icons::svg($name, $attributes->get('class', ''), $label, $size) !!}
