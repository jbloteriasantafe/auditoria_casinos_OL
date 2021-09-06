
function concatAñomes(año,mes){
  return año+(mes < 10? '-0' : '-') + mes;
}

$(document).ready(function(){
  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Estadísticas Generales');
  $('#opcInformesGenerales').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesGenerales').addClass('opcionesSeleccionado');

  const total_por_plataforma = {};
  const total_por_plataforma_por_mes = {};
  const añomeses = {};
  $('#beneficiosMensuales option').each(function(){
    const op = $(this);
    const plat = op.attr('data-plataforma');
    const beneficio = parseFloat(op.val());
    if(plat in total_por_plataforma){
      total_por_plataforma[plat] += beneficio;
    }
    else{
      total_por_plataforma[plat] = beneficio;
      total_por_plataforma_por_mes[plat] = {};
    }

    const año = op.attr('data-año');
    const mes = op.attr('data-mes');
    const añomes = concatAñomes(año,mes);
    total_por_plataforma_por_mes[plat][añomes] = beneficio;    
    añomeses[añomes] = 1;//Evito duplicados agregandolo en un diccionario
  });
  generarGraficoTorta('#divBeneficiosMensuales','BENEFICIOS TOTALES (ULTIMO AÑO)',total_por_plataforma);
  generarGraficoBarras('#divBeneficiosMensualesEnMeses','BENEFICIOS MENSUALES (ULTIMO AÑO)',total_por_plataforma_por_mes,'Pesos','Año-mes',Object.keys(añomeses));
});

function format(f){
  return Highcharts.numberFormat(f,2,',','.');
}
function format$(f){
  return '$ '+format(f);
}
function formatPje(f){
  return format(f)+' %';
}

function generarGraficoTorta(div,titulo,valores){//viene plat1 => val1, plat2 => val2
  const dataseries = [];
  for(const idx in valores){
    const val = valores[idx];
    dataseries.push([idx,val]);
  }
  const grafico = $('<div>').addClass('grafico col-md-12');
  $(div).append(grafico);
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
      text: titulo, 
      style: {
        fontWeight: 'bold'
      }
    },
    legend: {
      labelFormatter: function () {
        return this.name + " " + formatPje(this.percentage);
      },
      layout: 'horizontal',
      align: 'center',
      verticalAlign: 'bottom',
      y: 0,
      padding: 0,
      itemMarginTop: 0,
      itemMarginBottom: 0,
    },
    tooltip: { 
      formatter: function(){return `${format$(this.y)} - <b>${formatPje(this.percentage)}</b>`;}
    },
    plotOptions: {
      pie: {
        allowPointSelect: true,
        cursor: 'pointer',
        depth: 35,
        dataLabels: {
          enabled: false,
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


function generarGraficoBarras(div,titulo,valores,nombrey,nombrex,labels){//viene plat1 => [x1 => y1,x2 => y2], plat2 => [x1 => y1, x2 => y2]
  const dataseries = [];
  for(const idx in valores){
    const series = {name: idx, data: [],formatter: format$};
    for(const labelidx in labels){
      const label = labels[labelidx];
      if(label in valores[idx]) series.data.push(valores[idx][label]);
      else series.data.push(0);
    }
    dataseries.push(series);
  }

  const grafico = $('<div>').addClass('grafico col-md-12');
  $(div).append(grafico);
  Highcharts.chart(grafico[0], {
    chart: {
      height: 450,
      backgroundColor: "#fff",
      type: 'column',
    },
    tooltip: { 
      formatter: function(){return `${format$(this.y)} - <b>${formatPje(this.percentage)}</b>`;}
    },
    plotOptions: {
      column: {
          stacking: 'normal',
          dataLabels: {
              enabled: true
          }
      }
    },
    title: { 
      text: titulo, 
      style: {
        fontWeight: 'bold'
      }
    },
    series: dataseries,
    yAxis: {
      title: { text: nombrey },
    },
    xAxis: {
      title: { text: nombrex },
      categories: labels,
    },
  });
}