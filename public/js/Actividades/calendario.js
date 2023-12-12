import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-calendario]').each(function(){
  const $calendario = $(this);
  const $icalendario = $calendario.find('[data-js-inner-calendario]');
  
  let mostrar_sin_completar = null;
  $calendario.on('set_mostrar_sin_completar',function(e,val){
    mostrar_sin_completar = val;
    $calendario.trigger('actualizar_eventos');
  });
  
  function ISOString(f){
    return f.toISOString().split('T')[0];
  }
  
  $calendario.find('.fa-spinner').hide();
  $icalendario.show().fullCalendar({
    nowIndicator: true,
    showNonCurrentDates: false,//no mostrar cosas del mes anterior/posterior
    fixedWeekCount: false,//cantidad de semanas en el calendario segun el mes
    eventDurationEditable: false,//no permitir cambiar "tamaño"
    locale: 'es',
    defaultView: 'basicWeek',
    header:{
      left: 'basicWeek,listWeek,month',
      center: 'title',
      right:'prev,next today',
    },
    googleCalendarApiKey: 'AIzaSyAtOBqjaKwycXtSb_H1GXhbPBusz64ZCX4',
    eventSources: [
      {
        googleCalendarId: 'es.ar#holiday@group.v.calendar.google.com',
        color: 'red',
        textColor: 'white',
        cache: true
      },
      {
        events: [],
        id: 'eventos_bd',
        allDayDefault: true,
        color: 'green',
        textColor: 'black',
      }
    ],
    editable: true,
    selectable: true,
    allDaySlot: false,
    eventClick:  function(event, jsEvent, view) {
      $('[data-js-calendario]').trigger('clickeo_evento',[event.numero,event.es_tarea]);
    },
    select: function(start, end, jsEvent) {
      start = start.toISOString();
      
      end   = new Date(end._i);
      end.setDate(end.getDate()-1);//hacerlo inclusivo
      end = ISOString(end);
      
      $('[data-js-calendario]').trigger('selecciono_fechas',[start,end]);
    },
    /*eventDrop: function(event, delta){
      console.log(event,delta);
    },
    eventResize: function(event) {
      console.log(event);
    },*/
    eventDrop: function(event,delta,revertFunc){
      if(event.fecha === undefined || event.es_tarea){
        return revertFunc();
      }
      const fecha_nueva = new Date(event.fecha+'T00:00');
      fecha_nueva.setDate(fecha_nueva.getDate()+delta.asDays());
      
      const s_fecha_nueva = ISOString(fecha_nueva);
      
      if(s_fecha_nueva == event.fecha) return;
      
      $calendario.trigger('cambio_fecha_evento',[event.numero,ISOString(fecha_nueva)]);
    },
    eventRender: function(event,element,view){
      $(element).toggleClass('finalizado',!!(event.finalizado ?? false));
      $(element).toggleClass('es_tarea',!(event.es_actividad ?? false));
    },
    viewRender: actualizar_eventos
  });
  
  function actualizar_eventos(){
    if(mostrar_sin_completar === null) return;
    
    const event_source = $icalendario.fullCalendar('getEventSourceById','eventos_bd');
    event_source.setRawEventDefs([]);
    $calendario.find('.fa-spinner').show();
    $icalendario.hide().fullCalendar('refetchEventSources',event_source);
    $calendario.trigger('recibio_actividades',[[]]);
    
    const view = $icalendario.fullCalendar('getView');
    const desde = ISOString(view.start);
    let hasta   = view.end._i;
    hasta = new Date(hasta);
    hasta.setDate(hasta.getDate()-1);//hacerlo inclusivo
    hasta = ISOString(hasta);

    AUX.GET(
      '/actividades/buscar',
      {desde: desde,hasta: hasta,mostrar_sin_completar: mostrar_sin_completar+0},
      function(actividades){        
        const eventos = [];
        actividades.forEach(function(a){
          if(a.fecha >= desde && a.fecha <= hasta){                  
            eventos.push({
              title: a.titulo,
              start: moment(a.fecha),
              numero: a.numero,
              fecha: a.fecha,
              es_actividad: a.es_actividad,
              finalizado: a.finalizado,
              backgroundColor: a.color_fondo ?? 'green',
              textColor: a.color_texto ?? 'black',
              borderColor: a.color_borde ?? 'green',
            });
          }
        });
        event_source.setRawEventDefs(eventos);
        $calendario.find('.fa-spinner').hide();
        $icalendario.show().fullCalendar('refetchEventSources',event_source);
        
        $calendario.trigger('recibio_actividades',[actividades]);
      }
    );
  }
  
  $calendario.on('actualizar_eventos',actualizar_eventos);
}); });
