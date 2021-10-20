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

$('#btn-buscar').click(function(e){
  const id = $('#buscadorPlataforma').val();
  if(id == "") return;
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  const beneficio_alertas = $('#inputBeneficio').val() == ""? $('#inputBeneficio').attr("default") : $('#inputBeneficio').val();
  const GET = function(url,success,error = function(data){console.log(data.responseJSON);}){
    $.ajax({
      type: 'GET',
      url: '/informePlataforma/'+url+'/'+id,
      data: { beneficio_alertas : beneficio_alertas },
      success: success,
      error: error,
    });
  };

  $('#graficos').empty();
  $('#tablas').empty();
  $('#juegosFaltantesConMovimientos tbody').empty();
  $('.tablaAlertas').not('#moldeAlerta').remove();
  $('#modalPlataforma').modal('show');
  $('.tabContent').hide();
  $('.tab').eq(0).click();

  let midx = 0;
  const loading = setInterval(function(){const m = ['―','/','|','\\'];$('#graficos').text(m[midx%4]);midx++;},100);
  GET('obtenerEstadisticas',function(data){
    clearInterval(loading);
    for(const clasificacion in data){
      if(data[clasificacion].length > 0)
        generarTabla(clasificacion,data[clasificacion]);
    }
    $('#graficos').empty();
    for(const clasificacion in data){
      setTimeout(function(){
        if(data[clasificacion].length > 0 && clasificacion != 'Total')
          generarGraficos(clasificacion,data[clasificacion]);
      },250);
    }
  },function(data){clearInterval(loading);console.log(data.responseJSON);});

  GET('obtenerJuegosFaltantes',function(data){
    for(const jidx in data){
      const j = data[jidx];
      const fila = $('#filaEjemploJuegosFaltantesConMovimientos').clone().removeAttr('id');
      for(const k in j){
        fila.find('.'+k).text(j[k]).attr('title',j[k]);
      }
      $('#juegosFaltantesConMovimientos tbody').append(fila);
    }
  });

  GET('obtenerAlertas',function(data){
    for(const moneda in data.juegos){
      const alertas = data.juegos[moneda];
      if(alertas.length == 0) continue;
      generarTablaAlertas('JUEGOS',moneda,alertas);
    }
    for(const moneda in data.jugadores){
      const alertas = data.jugadores[moneda];
      if(alertas == 0) continue;
      generarTablaAlertas('JUGADORES',moneda,alertas);
    }
  });
});

function generarTablaAlertas(tipo,moneda,alertas){
  const div = $('#moldeAlerta').clone().removeAttr('id').show();
  div.find('.descripcion_alerta').text(tipo+' '+moneda)
  const fila = $('#moldeFilaAlerta').clone().removeAttr('id');
  for(const aidx in alertas){
    const a = alertas[aidx];
    const f = fila.clone();
    for(const columna in a){
      f.find('.'+columna).text(a[columna]).attr('title',a[columna]);
    }
    div.find('tbody').append(f);
  }
  $('#divAlertasDiarias').append(div);
}

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
