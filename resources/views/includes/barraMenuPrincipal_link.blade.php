@if($primer_nivel)
<div class='card' style='{{$divli_style}};'>
  <a tabindex='-1' href='{{$link}}' style='{{$link_style}};'>
    {!! $op !!}
  </a>
</div>
@else
<li style='{{$divli_style}};'>
  <a tabindex='-1' href='{{$link}}' style='{{$link_style}};'>
    {!! $op !!}
  </a>
</li>
@endif