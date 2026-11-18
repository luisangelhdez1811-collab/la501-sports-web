{{-- Pinta los bloques de un documento legal (resources/legal/...). Mismo formato que usa la app. --}}
@foreach($blocks as $bloque)
    @switch($bloque['type'])
        @case('h2')
            <h2 class="lg-h2" id="{{ $bloque['id'] ?? \Illuminate\Support\Str::slug($bloque['text']) }}">{{ $bloque['text'] }}</h2>
            @break
        @case('h3')
            <h3 class="lg-h3">{{ $bloque['text'] }}</h3>
            @break
        @case('p')
            <p class="lg-p">@if(!empty($bloque['strong']))<strong>{{ $bloque['strong'] }}</strong>@endif{{ $bloque['text'] }}@if(!empty($bloque['link']))<a href="{{ route($bloque['link']['route']) }}" style="color: var(--lg-accent); font-weight: 600;">{{ $bloque['link']['label'] }}</a>.@endif</p>
            @break
        @case('list')
            <ul class="lg-list">
                @foreach($bloque['items'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            @break
        @case('table')
            <div class="lg-table-wrap">
                <table class="lg-table">
                    <tbody>
                        @foreach($bloque['rows'] as [$titulo, $texto])
                            <tr>
                                <th scope="row">{{ $titulo }}</th>
                                <td>{{ $texto }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @break
    @endswitch
@endforeach
