

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Informe de Plataforma');
  const iso_dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    showClear: true,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  };
  $('#dtpFechaDesde').datetimepicker(iso_dtp);
  $('#dtpFechaHasta').datetimepicker(iso_dtp);

  ['Juego','Jugador'].forEach(function(tipo1){
    ['FaltantesConMovimientos','AlertasDiarias'].forEach(function (tipo2){
      crearPaginado($(`#div${tipo1}${tipo2}`),tipo1,`obtener${tipo1}${tipo2}`);
    });
  });

  $('#buscadorPlataforma').change();
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');
});

function GET(loadingselect,url,data,success,error=function(x){}){
  let midx = 0;
  const loading = setInterval(function(){const m = ['―','/','|','\\'];loadingselect.text(m[midx%4]);midx++;},100);
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
      loadingselect.empty();
      success(data);
    },
    error: function(data){
      clearInterval(loading);
      loadingselect.text('ERROR DE CARGA');
      error(data);
    },
  });
}
$('#buscadorPlataforma').change(function(e){
  e.preventDefault();
  $('#btn-buscar').attr('disabled',$(this).val() == "");
})

$('#btn-buscar').click(function(e){
  if($('#buscadorPlataforma').val() == "") return;

  $('#graficos').empty();
  $('#tablas').empty();
  {
    let titulo = $('#buscadorPlataforma option:selected').text() + ' ';
    const desde = $('#fecha_desde').val();
    const hasta = $('#fecha_hasta').val();
    if(desde || hasta) titulo += (desde? desde : '####-##-##') + '/' + (hasta? hasta : '####-##-##');
    $('#tituloModal').text(titulo);
  }
  $('.tabContent').hide();
  $('.tab').eq(0).click();
  $('#modalPlataforma').modal('show');
});

$('#modalPlataforma').on('shown.bs.modal',function(){
  GET($('#graficos,#tablas'),'obtenerCantidadesPdevs',{},function(data){
    const clases = Object.keys(data);
    for(const cidx in clases){
      const clase = clases[cidx];
      generarGraficos(clase,data[clase]);
      generarTabla(clase,data[clase]);
    }
  });
  $('.divTablaPaginada').each(function(){
    $(this).data('buscar')();
  });
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
  $(this).closest('table').parent().data('buscar')();
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
  const tipos = Object.keys(valores);
  for(const tidx in tipos){
    const tipo = tipos[tidx];
    const val = valores[tipo];
    const f = filaModelo.clone().removeClass('filaModelo');
    f.find('.fila').text(tipo).attr('title',tipo);
    const pdev           = clearNull(val['pdev']).toLocaleString();
    const pdev_esperado  = clearNull(val['pdev_esperado']).toLocaleString();
    const pdev_producido = clearNull(val['pdev_producido']).toLocaleString();
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
  const tipos = Object.keys(valores);
  for(const tidx in tipos){
    const tipo = tipos[tidx];
    dataseries.push([tipo,valores[tipo].cantidad]);
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

$('#tabEvolucionCategorias').click(function(e){
  e.preventDefault();
  GET($('#divEvolucionCategorias'),'obtenerEvolucionCategorias',{},function(data){
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

$(document).on('click','#btn-buscarPaginado',function(e){
  e.preventDefault();
  $(this).closest('.tabContent').find('.divTablaPaginada').data('buscar')();
})

$(document).on('click','.prevPreview,.nextPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).closest('.paginado').find('.previewPage').val());
  const next = p + ($(this).hasClass('nextPreview')? 1 : -1);
  $(this).closest('.tabContent').find('.divTablaPaginada').data('buscar')(next);
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
  $(this).closest('.tabContent').find('.divTablaPaginada').data('buscar')(val);
});

function digits(n,dig = 2){
  const format = new Intl.NumberFormat('es-AR',{minimumFractionDigits: dig,maximumFractionDigits: dig});
  const ret = format.format(n);
  return ret == "NaN"? n : ret;
}

function crearPaginado(div,tipo,url){
  div.find('.divTablaPaginada').data('buscar',function(page = 1){
    const tabla = div.find('.tablaPaginada');
    const formData = { 
      page: page, page_size: 30, 
      columna: tabla.find('.activa').attr('value'),
      orden: tabla.find('.activa').attr('estado'),
      beneficio_alertas: div.find('#inputBeneficio').val(),
      pdev_alertas: div.find('#inputPdev').val(),
    };
    tabla.find('tbody').empty();
    GET(tabla.find('tbody'),url,formData,function(data){
      generarTablaPaginada(div,tipo,data.data ?? [],formData.page,Math.ceil(data.total/formData.page_size));
    });
  });
}

function generarTablaPaginada(div,tipo,data,page,pages){
  const molde = div.find('.moldeFila').clone().removeClass('moldeFila').css('display','block');
  const tbody = div.find('.tablaPaginada').find('tbody');
  for(const didx in data){
    const d = data[didx];
    const f = molde.clone();//Lo pone como table-row, por algun motivo y se ve mal
    for(const columna in d){
      let val = digits(d[columna],columna.indexOf('pdev') != -1? 3 : 2);
      if(columna == 'jugador' || columna == 'cod_juego') val = d[columna];
      f.find('.'+columna).text(val).attr('title',val);
    }
    tbody.append(f);
  }
  div.find('.previewPage').val(page).data('old_val',page);
  div.find('.previewTotal').val(pages);
  div.find('.prevPreview').attr('disabled',page <= 1);
  div.find('.nextPreview').attr('disabled',page >= pages);
  tbody.find('.cod_juego,.jugador').each(function(){
    const codigo = $(this).text();
    if(codigo == 'TOTAL') return;//@HACK: Se rompe si hay un juego/jugador con ID Total (lol)
    const modo = tipo.toLowerCase();
    const id_plataforma = $('#buscadorPlataforma').val();
    const a = $('<a>').attr('href',`/informeContableJuego/${id_plataforma}/${modo}/${codigo}`).attr('target','_blank').text(codigo);
    $(this).text('').append(a);
  });
}
