function digits(n,dig = 2){
  const format = new Intl.NumberFormat('es-AR',{minimumFractionDigits: dig,maximumFractionDigits: dig});
  const ret = format.format(n);
  return ret == "NaN"? n : ret;
}

function convertirLinks(tds,id_plataforma,modo){
  tds.each(function(){
    const codigo = $(this).text();
    if(codigo == 'TOTAL') return;//@HACK: Se rompe si hay un juego/jugador con ID Total (lol)
    const a = $('<a>').attr('href',`/informeContableJuego/${id_plataforma}/${modo}/${codigo}`).attr('target','_blank').text(codigo);
    $(this).text('').append(a);
  });
}

$(document).ready(function(){
  $('#informesAuditoria').removeClass().addClass('subMenu2 collapse in');
  $('.tituloSeccionPantalla').text('Informe de Plataforma');
  $('#opcInformePlataforma').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcInformePlataforma').addClass('opcionesSeleccionado');
  $('#dtpFechaDesde').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    showClear: true,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
  $('#dtpFechaHasta').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    showClear: true,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#juegosFaltantesConMovimientos table').data('buscar',function(page = 1){
    const formData = { page: page, page_size: 30, ...sortBy(`#juegosFaltantesConMovimientos`), };
    $('#juegosFaltantesConMovimientos tbody').empty();
    GET('#juegosFaltantesConMovimientos tbody','obtenerJuegosFaltantes',formData,function(data){
      const total = data.total;
      if(total == 0) return;
      generarTablaFaltantes('Juego',data.data,formData.page,Math.ceil(total/formData.page_size));
    });
  })
  .data('buscar')(1);

  $('#jugadoresFaltantesConMovimientos table').data('buscar',function(page = 1){
    const formData = { page: page, page_size: 30, ...sortBy(`#jugadoresFaltantesConMovimientos`), };
    $('#jugadoresFaltantesConMovimientos tbody').empty();
    GET('#jugadoresFaltantesConMovimientos tbody','obtenerJugadoresFaltantes',formData,function(data){
      const total = data.total;
      if(total == 0) return;
      generarTablaFaltantes('Jugador',data.data,formData.page,Math.ceil(total/formData.page_size));
    });
  })
  .data('buscar')(1);

  $('#buscadorPlataforma').change();
});

function generarTablaFaltantes(tipo,faltantes,page,pages){
  const div = $('#div'+tipo+'FaltantesConMovimientos');
  const fila = $('#molde'+tipo+'FaltantesConMovimientos').clone().removeAttr('id').show();
  for(const falidx in faltantes){
    const fltnt = faltantes[falidx];
    const f = fila.clone().css('display','block');//Lo pone como table-row, por algun motivo y se ve mal
    for(const columna in fltnt){
      let val = digits(fltnt[columna],columna.indexOf('pdev') != -1? 3 : 2);
      if(columna == 'jugador' || columna == 'codigo') val = fltnt[columna];
      f.find('.'+columna).text(val).attr('title',val);
    }
    div.find('tbody').append(f);
  }
  div.find('.previewPage').val(page).data('old_val',page);
  div.find('.previewTotal').val(pages);
  div.find('.prevPreview').attr('disabled',page <= 1);
  div.find('.nextPreview').attr('disabled',page >= pages);
  convertirLinks(div.find('tbody').find('.cod_juego,.jugador'),$('#buscadorPlataforma').val(),tipo.toLowerCase());
}

$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');
});

function GET(loadingselect,url,data,success,error=function(x){}){
  let midx = 0;
  const loading = setInterval(function(){const m = ['―','/','|','\\'];$(loadingselect).text(m[midx%4]);midx++;},100);
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  const data_default = {
    id_plataforma: $('#buscadorPlataforma').val(),
    id_tipo_moneda: 1,
    beneficio_alertas: 0,
    pdev_alertas: 0,
    page: 1,
    page_size: 30,
    fecha_desde: $('#dtpFechaDesde input').val(),
    fecha_hasta: $('#dtpFechaHasta input').val(),
  };
  $.ajax({
    type: 'GET',
    url: '/informePlataforma/'+url,
    data: {
      ...data_default,
      ...data
    },
    success: function(data){
      clearInterval(loading);
      $(loadingselect).empty();
      success(data);
    },
    error: function(data){
      clearInterval(loading);
      $(loadingselect).text('ERROR DE CARGA');
      error(data);
    },
  });
}
$('#buscadorPlataforma').change(function(e){
  e.preventDefault();
  $('#btn-buscar').attr('disabled',$(this).val() == "");
})

function sortBy(select){
  const activa = $(`${select} .activa`);
  return {columna: activa.attr('value'),orden: activa.attr('estado')};
}

$('#btn-buscar').click(function(e){
  const id = $('#buscadorPlataforma').val();
  if(id == "") return;

  $('#graficos').empty();
  $('#tablas').empty();
  $('#juegosFaltantesConMovimientos tbody').empty();
  $('#jugadoresFaltantesConMovimientos tbody').empty();
  {
    let titulo = $('#buscadorPlataforma option:selected').text() + ' ';
    const desde = $('#fecha_desde').val();
    const hasta = $('#fecha_hasta').val();
    if(desde || hasta) titulo += (desde? desde : '####-##-##') + '/' + (hasta? hasta : '####-##-##');
    $('#tituloModal').text(titulo);
  }
  $('#modalPlataforma').modal('show');
  $('.tabContent').hide();
  $('.tab').eq(0).click();

  GET('#graficos','obtenerClasificacion',{},function(data){
    for(const clasificacion in data){
      setTimeout(function(){
        generarGraficos(clasificacion,data[clasificacion]);
      },250);
    }
  });
  GET('#tablas','obtenerPdevs',{},function(data){
    for(const clasificacion in data){
      generarTabla(clasificacion,data[clasificacion]);
    }
  });

  $('#juegosFaltantesConMovimientos table').data('buscar')();
  $('#jugadoresFaltantesConMovimientos table').data('buscar')();

  $('#btn-buscarAlertasJuegos').click();
  $('#btn-buscarAlertasJugadores').click();
});

function setearEstadoColumna(col,estado){
  const tabla = col.closest('table');
  tabla.find('th').removeClass('activa');
  col.addClass('activa').attr('estado',estado).children('i').removeClass().addClass(`fa fa-sort-${estado}`);
  if(estado == '') col.removeClass('activa');
  tabla.find('th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
}
function obtenerProximoEstadoColumna(col){
  if(col.children('i').hasClass('fa-sort'))           return 'desc';
  else if(col.children('i').hasClass('fa-sort-desc')) return 'asc';
  return '';
}
$(document).on('click','tr th[value]',function(e){
  const estado = obtenerProximoEstadoColumna($(this));
  setearEstadoColumna($(this),estado);
  $(this).closest('table').data('buscar')();
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

function generarTabla(nombre,valores){
  function clearNull(v){
    return v? v : '-';
  }
  const table = $('#tablaModelo').clone().removeAttr('id').show();
  table.find('.dato').text(nombre);
  const filaModelo = table.find('.filaModelo');
  for(const idx in valores){
    const val = valores[idx];
    const f = filaModelo.clone().removeClass('filaModelo');
    f.find('.fila').text(idx).attr('title',idx);
    const pdev           = clearNull(val['pdev']);
    const pdev_esperado  = clearNull(val['pdev_esperado']);
    const pdev_producido = clearNull(val['pdev_producido']);
    f.find('.pdev').text(pdev).attr('title',pdev);
    f.find('.pdev_esperado').text(pdev_esperado).attr('title',pdev_esperado);
    f.find('.pdev_producido').text(pdev_producido).attr('title',pdev_producido);
    //Si no tiene nada no lo muestro
    if (pdev != '-' || pdev_esperado != '-' || pdev_producido != '-') table.find('tbody').append(f);
  }
  filaModelo.remove();
  $('#tablas').append(table);
}

function generarGraficos(nombre,valores){
  const dataseries = [];
  for(const idx in valores){
    const val = valores[idx];
    dataseries.push([val['clase'],val['juegos']]);
  }
  const grafico = $('<div>').addClass('grafico col-md-4').css('padding-top','50px');
  $('#graficos').append(grafico);
  Highcharts.chart(grafico[0], {
    chart: {
      spacingBottom: 0,
      marginBottom: 0,
      spacingTop: 0,
      marginTop: 0,
      height: 350,
      backgroundColor: "#fff",
      type: 'pie',
      options3d: {
        enabled: true,
        alpha: 45,
        beta: 0
      },
    },
    title: { 
      text: nombre, 
      style: {
        fontWeight: 'bold'
      }
    },
    legend: {
      labelFormatter: function () {
        return this.name + " " + this.percentage.toFixed(2) + " %";
      },
      layout: 'horizontal',
      align: 'center',
      verticalAlign: 'bottom',
      y: 0,
      padding: 0,
      itemMarginTop: 0,
      itemMarginBottom: 0,
    },
    tooltip: { pointFormat: '{point.y} - <b>{point.percentage:.2f}%</b>'},
    plotOptions: {
      pie: {
        allowPointSelect: true,
        cursor: 'pointer',
        depth: 35,
        dataLabels: {
          enabled: false,
          format: '{point.name}'
        },
        showInLegend: true
      }
    },
    series: [{
      type: 'pie',
      data: dataseries
    }]
  });
}

$('.tab').click(function(){
  $('.tabContent').hide();
  $('.tab[activa]').removeAttr('activa');
  $(this).attr('activa','activa');
  $($(this).attr('div-asociado')).show();
});

function generarAlertasDiarias(tipo,page){
  const beneficio_alertas = $('#inputBeneficio'+tipo).val() == ""? "0" : $('#inputBeneficio'+tipo).val();
  const pdev_alertas = $('#inputPdev'+tipo).val() == ""? "0" : $('#inputPdev'+tipo).val();
  const page_size = 30;
  const sort_by = sortBy(`#divAlertasDiarias${tipo}`);
  const data = { beneficio_alertas: beneficio_alertas, pdev_alertas: pdev_alertas, 
    page: page, page_size: page_size,
    ...sort_by,
  };

  $(`#divAlertasDiarias${tipo} .tablaAlertas`).remove();
  GET('#loadingAlertasDiarias'+tipo,'obtenerAlertas'+tipo,data,function(data){
    const alertas = data.data;
    const total = data.total;
    if(total == 0) return;
    generarTablaAlertas(tipo,'ARS',alertas,page,Math.ceil(total/page_size),sort_by);

    $(`#divAlertasDiarias${tipo} table`).data('buscar',function(){//Necesario porque el event handler lo usa para los orderBy 
      generarAlertasDiarias(tipo,page);
    });
  });
}

$('#btn-buscarAlertasJuegos').click(function(e){
  e.preventDefault();
  generarAlertasDiarias('Juegos',1);
});

$('#btn-buscarAlertasJugadores').click(function(e){
  e.preventDefault();
  generarAlertasDiarias('Jugadores',1);
});

function generarTablaAlertas(tipo,moneda,alertas,page,pages,sort_by){
  const div = $('#moldeAlerta'+tipo).clone().removeAttr('id').show();
  div.find('.moneda').text(moneda)
  const fila = div.find('.moldeFilaAlerta').clone().removeClass('moldeFilaAlerta');
  for(const aidx in alertas){
    const a = alertas[aidx];
    const f = fila.clone();
    for(const columna in a){
      let val = digits(a[columna],columna.indexOf('pdev') != -1? 3 : 2);
      //@HACK, en BPLAY y CCO los IDs de jugadores son todos numeros por lo que no lo detecta el chequeo común
      // En BPLAY los codigos de juegos son numeros tambien
      if(columna == 'jugador' || columna == 'codigo') val = a[columna];
      f.find('.'+columna).text(val).attr('title',val);
    }
    div.find('tbody').append(f);
  }
  div.find('.previewPage').val(page).data('old_val',page);
  div.find('.previewTotal').val(pages);
  div.find('.prevPreview').attr('disabled',page <= 1);
  div.find('.nextPreview').attr('disabled',page >= pages);
  $('#divAlertasDiarias'+tipo).append(div);
  //Pongo de vuelta el ordenamiento porque borramos la tabla entera y lo perdemos
  //Tengo que hacerlo despues del append
  if(sort_by && sort_by.columna){//Hay columna activa
    const columna = $(`#divAlertasDiarias${tipo} table th`).filter(function(){
      return $(this).attr('value') == sort_by.columna;
    });
    setearEstadoColumna(columna,sort_by.orden);
  }
  const modo = tipo == 'Juegos'? 'juego' : (tipo == 'Jugadores'? 'jugador' : null);
  if(modo != null){
    let td = null;
    if(modo == 'juego')        td = 'codigo';
    else if(modo == 'jugador') td = 'jugador';
    if(td != null) convertirLinks($(`#divAlertasDiarias${tipo} tbody .${td}`),$('#buscadorPlataforma').val(),modo);
  }
}

function rebuscar($this,next){
  if($this.closest('.tablaAlertas').hasClass('tablaAlertasJuegos')){
    generarAlertasDiarias('Juegos',next);
    return;
  }
  if($this.closest('.tablaAlertas').hasClass('tablaAlertasJugadores')){
    generarAlertasDiarias('Jugadores',next);
    return;
  }
  if($this.closest('#divJuegoFaltantesConMovimientos').length > 0){
    $('#juegosFaltantesConMovimientos table').data('buscar')(next);
    return;
  }
  if($this.closest('#divJugadorFaltantesConMovimientos').length > 0){
    $('#jugadoresFaltantesConMovimientos table').data('buscar')(next);
    return;
  }
}

$(document).on('click','.prevPreview,.nextPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).closest('.paginado').find('.previewPage').val());
  const next = p + ($(this).hasClass('nextPreview')? 1 : -1);
  rebuscar($(this),next);
});

$(document).on('focusin','.previewPage',function(e){
  $(this).data('old_val',$(this).val());
});

$(document).on('change','.previewPage',function(e){
  const old   = parseInt($(this).data('old_val'));
  const val   = parseInt($(this).val());
  const total = parseInt($(this).parent().find('.previewTotal').val());
  if(val > total || val <= 0){
    $(this).val(old);
    return;
  }
  rebuscar($(this),val)
});

$('#tabEvolucionCategorias').click(function(e){
  e.preventDefault();
  GET('#divEvolucionCategorias','obtenerEvolucionCategorias',{},function(data){
    setTimeout(function(){
      generarEvolucionCategorias(data);
    },250);
  });
})

function generarEvolucionCategorias(graphs) {
  const series = [];
  for(const name in graphs){
    series.push({
      name: name,
      data: graphs[name].map(function(v){return [v.x,v.y];}),
    });
  }
  Highcharts.chart('divEvolucionCategorias', {
      chart: {
          backgroundColor: "#fff",
          type: 'line',
          events: {
              click: function(e) {
                  console.log(e.xAxis[0].value,e.yAxis[0].value);
              }
          }
      },
      title: { text: ' ' },
      subtitle: { text: '' },
      xAxis: {
          tickmarkPlacement: 'on',
          title: { enabled: false },
          visible: false,
      },
      yAxis: { title: { text: '' },  },
      tooltip: {
          split: true,
          valueSuffix: '%',
      },
      plotOptions: {
          series: {
              cursor: 'pointer',
              point: {
                  events: {
                      click: function(e) {
                        //NOP
                      }
                  }
              },
              fillOpacity: 0.4
          }
      },
      series: series
  });
}