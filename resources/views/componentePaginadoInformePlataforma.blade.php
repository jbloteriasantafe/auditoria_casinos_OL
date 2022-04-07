<?php
$convertir_a_nombre = function($str){
  return strtoupper(str_replace("_"," ",$str));
};
?>

<div id="{{$id}}" class="row tabContent">
  <div class="botonesPaginadoInformePlataforma">
    {{$botones}}
  </div>
  <div class="divTablaPaginada col-md-12" style="padding: 0px !important;">
    <table class="col-md-12 table table-fixed tablesorter tablaPaginada" style="padding: 0px !important;">
      <thead>
        <tr>
          @foreach($columnas as $idx => $col)
          <th value="{{$col['sql']}}" class="{{$col['alias']}}"
          @if($idx == 0)
          estado="asc" class="activa"
          @endif
          >{{$convertir_a_nombre($col['alias'])}}<i class="fa fa-sort"></i></th>
          @endforeach
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
    <table hidden>
      <tr class="moldeFila">
        @foreach($columnas as $idx => $col)
        <td class="{{$col['alias']}}">XXX</td>
        @endforeach
      </tr>
    </table>
    <div class="row paginado">
      <div class="col-md-1 col-md-offset-3"><button type="button" class="btn btn-link prevPreview" disabled="disabled"><i class="fas fa-arrow-left"></i></button></div>
      <div class="col-md-4">
        <div class="input-group">
          <input class="form-control previewPage" type="number" style="text-align: center;" value="9">
          <span class="input-group-addon">/</span>
          <input class="form-control previewTotal" type="number" style="text-align: center;" value="99" disabled="disabled">
        </div>
      </div>
      <div class="col-md-1"><button type="button" class="btn btn-link nextPreview"><i class="fas fa-arrow-right"></i></button></div>
    </div>
  </div>
</div>
