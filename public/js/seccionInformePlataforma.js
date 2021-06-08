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

      const keys = Object.keys(data.estadisticas[0]);
      let cantidades = [];
      let pdevs = [];
      for(const kidx in keys){
        const k = keys[kidx];//k podria ser "Estado","Categoria", etc
        if(k == "pdev" || k == "juegos") continue;
        let cantidad = {};
        let pdev = {};
        let pdev_aux = {};
        for(let i = 0;i<data.estadisticas.length;i++){
          const est = data.estadisticas[i];
          //est[k] podria ser por ejemplo "Activo","inactivo"
          if(est[k] in cantidad){
            cantidad[est[k]] += est.juegos;//Si ya estaba, lo sumo
            pdev[est[k]]     += parseFloat(est.pdev);
            pdev_aux[est[k]] += 1;
          }
          else{
            cantidad[est[k]]  = est.juegos;//Sino, lo inicio
            pdev[est[k]]      = parseFloat(est.pdev);
            pdev_aux[est[k]]  = 1;
          }
        }

        for(const fila in pdev){
          pdev[fila]/=pdev_aux[fila];
        }
        
        cantidades[k] = cantidad;
        pdevs[k]      = pdev;
      }

      for(const nombre in pdevs){
        generarTabla(nombre,pdevs[nombre]);
      }

      $('#modalPlataforma').modal('show');

      for(const nombre in cantidades){
        setTimeout(function(){
          generarGraficos(nombre,cantidades[nombre]);
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

function generarTabla(nombre,pdev){
  const table = $('#tablaModelo').clone().removeAttr('id').show();
  table.find('.dato').text(nombre);
  const filaModelo = table.find('.filaModelo');
  for(const k in pdev){
    const f = filaModelo.clone().removeClass('filaModelo');
    f.find('.fila').text(k);
    f.find('.pdev').text(pdev[k].toFixed(2));
    table.find('tbody').append(f);
  }
  filaModelo.remove();
  $('#tablas').append(table);
}

function generarGraficos(nombre,cantidad){
  const dataseries = [];
  for(const k in cantidad){
    dataseries.push([k,cantidad[k]]);
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
