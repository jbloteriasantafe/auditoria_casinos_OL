import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-calendario]').each(function(){
  const $calendario = $(this);
  const $icalendario = $calendario.find('[data-js-inner-calendario]');
  
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
      $('[data-js-calendario]').trigger('clickeo_evento',[event.numero]);
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
      $(element).toggleClass('finalizado',event.finalizado ?? false);
      $(element).toggleClass('es_tarea',event.es_tarea ?? true);
    },
    viewRender: actualizar_eventos
  });
  
  function actualizar_eventos(){
    const event_source = $icalendario.fullCalendar('getEventSourceById','eventos_bd');
    const view = $icalendario.fullCalendar('getView');
    
    const desde = ISOString(view.start);
    let hasta   = view.end._i;
    hasta = new Date(hasta);
    hasta.setDate(hasta.getDate()-1);//hacerlo inclusivo
    hasta = ISOString(hasta);
    
    const eventos = [];
    const actividades_visibles = {};
    
    event_source.setRawEventDefs(eventos);
    $calendario.find('.fa-spinner').show();
    $icalendario.hide().fullCalendar('refetchEventSources',event_source);
    $calendario.trigger('recibio_actividades',[actividades_visibles]);

    AUX.GET(
      '/actividades/buscar',
      {desde: desde,hasta: hasta},
      function(actividades){
        Object.keys(actividades).forEach(function(numero){
          const aux = actividades[numero].filter(function(a){
            return !a.deleted_at;
          });
          
          if(aux.length == 0) return;
          const a = aux[0];
                      
          eventos.push({
            title: a.titulo,
            start: moment(a.fecha),
            numero: numero,
            fecha: a.fecha,
            es_tarea: a.parent !== null,
            finalizado: ['HECHO','CERRADO SIN SOLUCIÓN','CERRADO'].includes(a.estado),
            backgroundColor: a.color_fondo ?? 'green',
            textColor: a.color_texto ?? 'black',
            borderColor: a.color_borde ?? 'green',
          });
          
          actividades_visibles[numero] =  actividades[numero];
        });
        
        event_source.setRawEventDefs(eventos);
        $calendario.find('.fa-spinner').hide();
        $icalendario.show().fullCalendar('refetchEventSources',event_source);
        $calendario.trigger('recibio_actividades',[actividades_visibles]);
      }
    );
  }
  
  $calendario.on('actualizar_eventos',actualizar_eventos);
}); });
