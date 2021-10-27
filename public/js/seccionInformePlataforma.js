$(document).ready(function(){
  $('#barraJuegos').attr('aria-expanded','true');
  $('#juegos').removeClass().addClass('subMenu1 collapse in').show();
  $('#informesJuegos').removeClass().addClass('subMenu2 collapse in');

  $('.tituloSeccionPantalla').text('Informe de Plataforma');
  $('#gestionarJuegos').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcInformePlataforma').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcInformePlataforma').addClass('opcionesSeleccionado');
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');
});

function GET(loadingselect,url,data,success,error){
  let midx = 0;
  const loading = setInterval(function(){const m = ['―','/','|','\\'];$(loadingselect).text(m[midx%4]);midx++;},100);
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: 'GET',
    url: '/informePlataforma/'+url,
    data: data,
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

$('#btn-buscar').click(function(e){
  const id = $('#buscadorPlataforma').val();
  if(id == "") return;

  $('#graficos').empty();
  $('#tablas').empty();
  $('#juegosFaltantesConMovimientos tbody').empty();
  $('#modalPlataforma').modal('show');
  $('.tabContent').hide();
  $('.tab').eq(0).click();

  GET('#graficos,#tablas','obtenerEstadisticas/'+id,{},function(data){
    for(const clasificacion in data){
      if(data[clasificacion].length > 0)
        generarTabla(clasificacion,data[clasificacion]);
    }
    for(const clasificacion in data){
      setTimeout(function(){
        if(data[clasificacion].length > 0 && clasificacion != 'Total')
          generarGraficos(clasificacion,data[clasificacion]);
      },250);
    }
  });

  GET('#juegosFaltantesConMovimientos tbody','obtenerJuegosFaltantes/'+id,{},function(data){
    for(const jidx in data){
      const j = data[jidx];
      const fila = $('#filaEjemploJuegosFaltantesConMovimientos').clone().removeAttr('id');
      for(const k in j){
        fila.find('.'+k).text(j[k]).attr('title',j[k]);
      }
      $('#juegosFaltantesConMovimientos tbody').append(fila);
    }
  });

  $('#btn-buscarAlertasJuegos').click();
  $('#btn-buscarAlertasJugadores').click();
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

function generarTabla(nombre,valores){
  function clearNaN(v){
    return isNaN(v)? '-' : v;
  }
  const table = $('#tablaModelo').clone().removeAttr('id').show();
  table.find('.dato').text(nombre);
  const filaModelo = table.find('.filaModelo');
  for(const idx in valores){
    const val = valores[idx];
    const f = filaModelo.clone().removeClass('filaModelo');
    f.find('.fila').text(val[nombre]).attr('title',val[nombre]);
    const pdev = clearNaN(parseFloat(val['pdev']).toFixed(2));
    const pdev_esperado = clearNaN(parseFloat(val['pdev_esperado']).toFixed(2));
    const pdev_producido = clearNaN(parseFloat(val['pdev_producido']).toFixed(2));
    f.find('.pdev').text(pdev).attr('title',pdev);
    f.find('.pdev_esperado').text(pdev_esperado).attr('title',pdev_esperado);
    f.find('.pdev_producido').text(pdev_producido).attr('title',pdev_producido);
    table.find('tbody').append(f);
  }
  filaModelo.remove();
  $('#tablas').append(table);
}

function generarGraficos(nombre,valores){
  const dataseries = [];
  for(const idx in valores){
    const val = valores[idx];
    dataseries.push([val[nombre],val['juegos']]);
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

function generarAlertasDiarias(tipo,id_tipo_moneda,page){
  const beneficio_alertas = $('#inputBeneficio'+tipo).val() == ""? "0" : $('#inputBeneficio'+tipo).val();
  const pdev_alertas = $('#inputPdev'+tipo).val() == ""? "0" : $('#inputPdev'+tipo).val();
  const page_size = 30;
  const moneda_str= ['ARS','USD'];
  const data = { beneficio_alertas: beneficio_alertas, pdev_alertas: pdev_alertas, 
    id_tipo_moneda: id_tipo_moneda, page: page, page_size: page_size };
  const id_plataforma = $('#buscadorPlataforma').val();
  $('#divAlertasDiarias'+tipo).find('.tablaAlertas').remove();
  GET('#loadingAlertasDiarias'+tipo,'obtenerAlertas'+tipo+'/'+id_plataforma,data,function(data){
    const alertas = data.data;
    const total = data.total;
    if(total == 0) return;
    generarTablaAlertas(tipo,moneda_str[id_tipo_moneda-1],alertas,page,Math.ceil(total/page_size));
  });
}

$('#btn-buscarAlertasJuegos').click(function(e){
  e.preventDefault();
  generarAlertasDiarias('Juegos',1,1);
});

$(document).on('click','#divAlertasDiariasJuegos .prevPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).parent().find('.previewPage').text())
  generarAlertasDiarias('Juegos',1,p-1);
});

$(document).on('click','#divAlertasDiariasJuegos .nextPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).parent().find('.previewPage').text())
  generarAlertasDiarias('Juegos',1,p+1);
});

function generarTablaAlertas(tipo,moneda,alertas,page,pages){
  const div = $('#moldeAlerta'+tipo).clone().removeAttr('id').show();
  div.find('.moneda').text(moneda)
  const fila = div.find('.moldeFilaAlerta').clone().removeClass('moldeFilaAlerta');
  for(const aidx in alertas){
    const a = alertas[aidx];
    const f = fila.clone();
    for(const columna in a){
      f.find('.'+columna).text(a[columna]).attr('title',a[columna]);
    }
    div.find('tbody').append(f);
  }
  div.find('.previewPage').text(page);
  div.find('.previewTotal').text(pages);
  div.find('.prevPreview').attr('disabled',page <= 1);
  div.find('.nextPreview').attr('disabled',page >= pages);
  $('#divAlertasDiarias'+tipo).append(div);
}

$('#btn-buscarAlertasJugadores').click(function(e){
  e.preventDefault();
  generarAlertasDiarias('Jugadores',1,1);
});

$(document).on('click','#divAlertasDiariasJugadores .prevPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).parent().find('.previewPage').text())
  generarAlertasDiarias('Jugadores',1,p-1);
});

$(document).on('click','#divAlertasDiariasJugadores .nextPreview',function(e){
  e.preventDefault();
  const p = parseInt($(this).parent().find('.previewPage').text())
  generarAlertasDiarias('Jugadores',1,p+1);
});
