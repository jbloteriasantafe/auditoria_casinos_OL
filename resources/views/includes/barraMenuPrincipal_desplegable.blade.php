
@if($primer_nivel)
<div class='card dropdown' style='{{$divli_style}};'>
  <a class='dropdown-toggle' data-toggle='dropdown' style='{{$link_style}};'>
    {!! $op !!}
  </a>
@else
<li class='dropdown-submenu' style='{{$divli_style}};'>
  <a class='desplegar-menu' tabindex='-1' href='#' style='{{$link_style}};'>
    {!! $op !!}
  </a>
@endif
  <ul class='dropdown-menu'>
    @if(count($hijos) == 0)
      @component('includes.barraMenuPrincipal_link',[
        'primer_nivel' => false,
        'divli_style'  => $divli_style,
        'link_style'   => $link_style,
        'link'         => $link,
        'op'           => $op,
      ])
      @endcomponent
    @else
      @foreach($hijos as $op => $datos)
        @component('includes.barraMenuPrincipal_desplegable',[
          'primer_nivel' => false,
          'divli_style'  => $datos['divli_style'],
          'link_style'   => $datos['link_style'],
          'link'         => $datos['link'],
          'op'           => $op,
          'hijos'        => $datos['hijos'],
        ])
        @endcomponent
      @endforeach
    @endif
  </ul>
@if($primer_nivel)
</div>
@else
</li>
@endif