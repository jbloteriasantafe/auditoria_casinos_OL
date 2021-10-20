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
    const añomes = año_mes(año,mes);
    total_por_plataforma_por_mes[plat][añomes] = beneficio;    
    añomeses[añomes] = 1;//Evito duplicados agregandolo en un diccionario
  });

  generarGraficoTorta('#divBeneficiosMensuales','BENEFICIOS TOTALES (ULTIMO AÑO)',total_por_plataforma);
  generarGraficoBarras('#divBeneficiosMensualesEnMeses','BENEFICIOS MENSUALES (ULTIMO AÑO)',total_por_plataforma_por_mes,'Pesos','Mes',Object.keys(añomeses));
  generarCalendario('#divCalendarioActividadesCompletadas','ESTADO AUDITORIA DIARIO',
    $('#estadoDia option').first().attr('fecha'),
    $('#estadoDia option').last().attr('fecha'),
    generarLeyendaCalendario,setearCeldaCalendario);
});

function color_func(t){
  function lerpColor(t,c0,c1){
    function lerpF(t,x0,x1){//(t=0,x0),(t=1,x1)
      return (1-t)*x0+t*x1;
    }
    return [lerpF(t,c0[0],c1[0]),lerpF(t,c0[1],c1[1]),lerpF(t,c0[2],c1[2])]
  }
  const lowColor     = [1.,0.35,0.];
  const controlPoint = [1.25,1.25,0.]
  const highColor    = [0.,1.,0.];
  //Bezier curve interpolation
  const rgb = lerpColor(t,lerpColor(t,lowColor,controlPoint),lerpColor(t,controlPoint,highColor));
  return [256*rgb[0],256*rgb[1],256*rgb[2]];
}

function formatPopoverCelda(data){
  const text = [];
  function formatRow(key,value){
    const k = $('<div>').css('text-align','left').css('display','inline-block').css('width','80%').css('font-size','75%').append(key);
    const v = $('<div>').css('text-align','right').css('display','inline-block').css('width','20%').css('font-size','75%').append(value.length == 0? '-' : value);
    return $('<div>').append(k).append(v).css('border-bottom','1px solid #ddd');
  }
  const prod = formatRow('PRODUCIDOS: ',data['producidos'].join(', '));
  const prod_jug = formatRow('PROD. JUGADORES: ',data['producidos_jugadores'].join(', '));
  const ben = formatRow('BENEFICIOS: ',data['beneficios'].join(', '));
  return $('<div>').append(prod).append(prod_jug).append(ben)[0].outerHTML;
}

function celdaPopover(dia,celda){//@WARNING: CALLBACK HELL
  //Clickeo en la celda con el popover ya desplegado
  //Lo destruyo, le saco el evento y lo asigno de vuelta para que lo pueda regenerar
  if(typeof celda.attr('aria-describedby') !== 'undefined'){
    celda.popover('destroy');
    celda.off('click').click(function(){
      celdaPopover(dia,celda);
    });
    return;
  }
  //Clickeo en otra celda, destruyo todos los popovers y le creo el evento
  $('.celda').popover('destroy');
  $.get('/informesGenerales/infoAuditoria/'+dateToIso(dia),function(data){
    celda.popover({
      html:true,
      content: formatPopoverCelda(data),
    }).popover('show');
    celda.attr('title',toPje(data.total));
    celda.off('click').click(function(){
      celdaPopover(dia,celda);
    });
  });
}

function setearCeldaCalendario(dia,celda){
  const op = $(`#estadoDia option[fecha="${dateToIso(dia)}"]`);
  if(op.length == 0) return celda;
  const estado = parseFloat(op.text());
  const color = color_func(estado);
  celda.css('background-color','rgb('+color.join(',')+')').attr('title',toPje(estado));
  if(estado == 1.0) celda.css('font-weight','bold');
  celda.click(function(){
    celdaPopover(dia,celda);
  });
  return celda;
}
function generarLeyendaCalendario(){
  const leyenda = $('<div>').css('text-align','center').css('display','flex').css('flex-flow','row nowrap').css('justify-content','center');
  const gradients = 4;
  for(let i=0;i<=gradients;i++){
    const color = 'rgb('+color_func(i/gradients).join(',')+')';
    const celda = $('<div>').append(Math.round(100*i/gradients)+'%').css('width','5%').css('background-color',color);
    if(i == gradients) celda.css('font-weight','bold');
    leyenda.append(celda);
  }
  return leyenda;
}

function toPje(s){
  return Math.round(parseFloat(s)*10000)/100+'%';
}
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
          enabled: true,
          formatter: function(){return format$(this.y);},
          distance: 20,
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
            enabled: true,
            formatter: function(){return format$(this.y);}
        },
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

function isoToDate(f){
  return new Date(f+'T00:00:00');
}
function dateToIso(f){
  const y = f.getFullYear();
  const m = f.getMonth()+1;
  const d = f.getDate();
  return y+(m<10?'-0':'-')+m+(d<10?'-0':'-')+d;
}

function generarCalendario(div,titulo,desde,hasta,leyenda = function(){return $('<div>');},setear_celda = function(dia,celda){return celda}){
  let d   = isoToDate(desde);
  const h = isoToDate(hasta);
  const dias_por_mes = {};
  while(d.getTime() < h.getTime()){
    const mes = año_mes(d.getFullYear(),d.getMonth()+1);
    if(!(mes in dias_por_mes)) dias_por_mes[mes] = [];
    dias_por_mes[mes].push(new Date(d));
    d.setDate(d.getDate()+1);
  }
  $(div).append($('<div>').addClass('titulo_ala_highchart').text(titulo));
  $(div).append(leyenda());
  $(div).append($('<br>'));
  const contenido = $('<div>').addClass('row contenido').css('overflow-y','scroll').css('max-height','300px');
  for(const mes in dias_por_mes){
    const tabla = $('#moldeMes').clone().removeAttr('id').css('display','inline-block').css('float','left');
    tabla.find('.mesTitulo').text(mes);
    const dias = dias_por_mes[mes];
    for(const didx in dias){
      const dia = dias[didx];
      const d = dia.getDate();
      const celda = setear_celda(dia,$('<div>').addClass('celda').text(d));
      tabla.append(celda);
    }
    {//Completo 31 celdas si el mes esta incompleto
      const dias_agregados = tabla.find('div').not('.mesTitulo').length;
      if(dias_agregados < 31){
        for(let i=0;i<(31-dias_agregados);i++){
          tabla.append($('<div>').addClass('celda').append('&nbsp;'));
        }
      }
    }
    contenido.append(tabla);
  }
  $(div).append(contenido);
}

function año_mes(año,mes){
  const meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DEC'];
  return meses[mes-1]+' '+año;
}