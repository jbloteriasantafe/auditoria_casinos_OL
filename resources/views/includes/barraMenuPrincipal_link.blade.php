@if($primer_nivel)
<div class='card' style='{{$divli_style}};'>
@else
<li style='{{$divli_style}};'>
@endif
  <a tabindex='-1' href='{{$link}}' style='{{$link_style}};'>
    {!! $op !!}
  </a>
@if($primer_nivel)
</div>
@else
</li>
@endif