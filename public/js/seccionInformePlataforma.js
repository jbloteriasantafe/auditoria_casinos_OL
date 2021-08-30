$(document).ready(function(){
  $('#barraJuegos').attr('aria-expanded','true');
  $('#juegos').removeClass().addClass('subMenu1 collapse in').show();
  $('#informesJuegos').removeClass().addClass('subMenu2 collapse in');

  $('.tituloSeccionPantalla').text('Informe de Plataforma');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
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

  $('#graficos').empty();
  $('#tablas').empty();
  $.get('/informePlataforma/obtenerEstado/' + id , function(data){
      if(data.estadisticas.length == 0) return;

      for(const clasificacion in data.estadisticas){
        if(data.estadisticas[clasificacion].length > 0)
          generarTabla(clasificacion,data.estadisticas[clasificacion]);
      }

      $('#modalPlataforma').modal('show');
      $('.tabContent').hide();
      $('.tab').eq(0).click();
      for(const clasificacion in data.estadisticas){
        setTimeout(function(){
          if(data.estadisticas[clasificacion].length > 0 && clasificacion != 'Total')
            generarGraficos(clasificacion,data.estadisticas[clasificacion]);
        },250);
      }
  });
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
    f.find('.fila').text(val[nombre]);
    const pdev = parseFloat(val['pdev']).toFixed(2);
    const pdev_esperado = parseFloat(val['pdev_esperado']).toFixed(2);
    const pdev_producido = parseFloat(val['pdev_producido']).toFixed(2);
    f.find('.pdev').text(clearNaN(pdev));
    f.find('.pdev_esperado').text(clearNaN(pdev_esperado));
    f.find('.pdev_producido').text(clearNaN(pdev_producido));
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
  const grafico = $('<div>').addClass('grafico col-md-4');
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
    title: { text: nombre},
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
