$(document).ready(function() {
    //Resetear componentes
    $('#selectPlataforma').val("").change();

    $('#barraJuegos').attr('aria-expanded', 'true');
    $('#juegos').removeClass().addClass('subMenu1 collapse in');
    $('#informesJuegos').removeClass();
    $('#informesJuegos').addClass('subMenu2 collapse in');

    $('.tituloSeccionPantalla').text('Informe Contable');
    $('#gestionarJuegos').attr('style', 'border-left: 6px solid #3F51B5;');
    $('#opcInformesContableJuego').attr('style', 'border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcInformesContableJuego').addClass('opcionesSeleccionado');
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function() {
    if ($(this).data("minimizar") == true) {
        $('.modal-backdrop').css('opacity', '0.1');
        $(this).data("minimizar", false);
    } else {
        $('.modal-backdrop').css('opacity', '0.5');
        $(this).data("minimizar", true);
    }
});

$('#selectPlataforma').change(function(e) {
    const id_plat = $(this).val();

    if (id_plat != "") {
        $('#inputCodigo').borrarDataList();

        let url = 'informeContableJuego/';
        const tipo = $('#selectTipoCodigo').val(); 
        if(tipo == 'juego') url += 'obtenerJuegoPlataforma/';
        else if(tipo == 'jugador') url += 'obtenerJugadorPlataforma/';

        $('#inputCodigo').generarDataList(url + id_plat, 'busqueda', 'id', 'codigo', 1);
        $('#inputCodigo').setearElementoSeleccionado(0, '');
        $('#btn-verDetalles').prop('disabled', false);
        $('#inputCodigo').prop('disabled', false);
    } else {
        $('#btn-verDetalles').prop('disabled', true);
        $('#inputCodigo').prop('disabled', true);
    }
    $('#inputCodigo').change();
});

$('#selectTipoCodigo').change(function(e){
    e.preventDefault();
    $('#selectPlataforma').change();
})

$('#inputCodigo').change(function(e){
    e.preventDefault();
    habilitarBotonDetalle($(this).obtenerElementoSeleccionado() != 0)
});

$('#inputCodigo').on('seleccionado', function() {
    habilitarBotonDetalle(true);
});

$('#inputCodigo').on('deseleccionado', function() {
    habilitarBotonDetalle(false);
});

function habilitarBotonDetalle(valor) {
    $('#btn-verDetalles').prop('disabled', !valor);
}

function limpiarNull(str, c = '-') {
    return str === null ? c : str;
}

const default_page_size = 30;

function verJuego(id_juego,id_plataforma){
    $.get("informeContableJuego/obtenerInformeDeJuego/" + id_juego, function(data) {
        $('#codigo').text(limpiarNull(data.juego.cod_juego));
        $('#proveedor').text(limpiarNull(data.juego.proveedor));
        $('#denominacion').text(limpiarNull(data.juego.denominacion_juego));
        $('#categoria').text(limpiarNull(data.categoria.nombre));
        $('#moneda').text(limpiarNull(data.moneda.descripcion));
        $('#devolucion').text(limpiarNull(data.juego.porcentaje_devolucion));
        {
            const e = data.juego.escritorio;
            const m = data.juego.movil;
            if(e && m) $('#tipo').text('ESCRITORIO/MÓVIL');
            else if(e) $('#tipo').text('ESCRITORIO');
            else if(m) $('#tipo').text('MÓVIL');
            else       $('#tipo').text('ERROR S/ TIPO');
        }

        for(const pidx in data.estados){
            const e = data.estados[pidx];
            if(e.id_plataforma == id_plataforma){
                $('#estado').text(e.estado);
                break;
            }
        }

        $('#modalJuegoContable').modal('show');
    });
}

$('#btn-verDetalles').click(function(e) {
    $('#proveedor,#denominacion,#categoria,#moneda,#devolucion,#tipo,#producidoEsperado').text('-');
    const codigo = $('#inputCodigo').val();
    $('#codigo').text(codigo);
    const cod_plat = $('#selectPlataforma option:selected').attr('data-codigo');
    $('#plataforma').text(cod_plat);
    $('#verTodosProducidos').prop('checked',false);

    const id_plataforma = $('#selectPlataforma').val();
    const tipo = $('#selectTipoCodigo').val(); 
    if(tipo == 'juego'){
        $('.de_juego').show();
        $('.de_jugador').hide();
        $('#estado').text('Produciendo (NO EN BD)');
        const id_juego = $('#inputCodigo').obtenerElementoSeleccionado();
        cargarProducidos(id_plataforma,codigo,1,default_page_size,function(){
            if(id_juego == -1){
                $('#modalJuegoContable').modal('show');
                return;
            }
            verJuego(id_juego,id_plataforma);
        });
    }
    else if(tipo == 'jugador'){
        $('.de_juego').hide();
        $('.de_jugador').show();
        $('#estado').text('-');
        cargarProducidos(id_plataforma,codigo,1,default_page_size,function(){
            $('#modalJuegoContable').modal('show');
        });
    }
});

function cargarProducidos(id_plataforma,codigo,pagina,page_size,after = function(){}){
    let url = 'informeContableJuego/';
    const tipo = $('#selectTipoCodigo').val(); 
    if(tipo == 'juego') url += 'obtenerProducidosDeJuego';
    else if(tipo == 'jugador') url += 'obtenerProducidosDeJugador';

    $.get(`${url}/${id_plataforma}/${codigo}/${(pagina-1)*page_size}/${page_size}`,function(data){
        $('#apuesta').text(data.total? data.total.apuesta : '-');
        $('#premio').text(data.total? data.total.premio : '-');
        $('#producido').text(data.total? data.total.beneficio : '-');
        $('#pdev').text(data.total? (100*data.total.pdev).toFixed(3) : '-');
        const pdev = parseFloat($('#devolucion').text())/100;
        $('#producidoEsperado').text(data.total && !isNaN(pdev)? (data.total.apuesta*(1-pdev)).toFixed(2) : '-');

        const fechas = [];
        const producidos = [];
        $('#tablaBodyProducidos tbody').empty();
        data.producidos.forEach(function(p) {
            const fila = $('#filaEjemploProducido').clone().removeAttr('id');
            fila.find('.fecha').text(p.fecha);
            fila.find('.moneda').text(p.moneda);
            fila.find('.categoria').text(p.categoria);
            fila.find('.jugadores').text(p.jugadores);
            fila.find('.juegos').text(p.juegos);
            fila.find('.apuesta_efectivo').text(p.apuesta_efectivo);
            fila.find('.apuesta_bono').text(p.apuesta_bono);
            fila.find('.apuesta').text(p.apuesta);
            fila.find('.premio_efectivo').text(p.premio_efectivo);
            fila.find('.premio_bono').text(p.premio_bono);
            fila.find('.premio').text(p.premio);
            fila.find('.beneficio_efectivo').text(p.beneficio_efectivo);
            fila.find('.beneficio_bono').text(p.beneficio_bono);
            fila.find('.beneficio').text(p.beneficio);
            $('#tablaBodyProducidos tbody').append(fila);

            fechas.push(p.fecha);
            producidos.push(parseFloat(p.beneficio));
        });
        $('#previewPage').text(pagina);
        const cantidad = data.total? data.total.cantidad : 0;
        const total = Math.ceil(cantidad/page_size);
        $('#previewTotal').text(total);
        if(page_size <= 0) $('#previewTotal').text(1);

        $('#prevPreview').attr('disabled',pagina <= 1);
        $('#nextPreview').attr('disabled',pagina >= total);

        after();

        setTimeout(function(){
            //Para el grafico lo queremos de orden mas viejo a mas nuevo
            generarGraficoJuego(fechas.reverse(),producidos.reverse());
        },500);
    });
}


function cambiarPagina(sumar){
    const pag_actual    = parseInt($('#previewPage').text());
    const max_pag       = parseInt($('#previewTotal').text());
    $('#prevPreview').attr('disabled',pag_actual <= 1);
    $('#nextPreview').attr('disabled',pag_actual >= max_pag);
    
    if((pag_actual <= 1 && sumar < 0) || (pag_actual >= max_pag && sumar > 0)) return;

    const cod_juego     = $('#codigo').text();
    const id_plataforma = $('#selectPlataforma').val();
    cargarProducidos(id_plataforma,cod_juego,pag_actual+sumar,default_page_size);
}

$('#prevPreview').click(function(e){
    e.preventDefault();
    cambiarPagina(-1);
});
  
$('#nextPreview').click(function(e){
    e.preventDefault();
    cambiarPagina(+1);
});

$('#verTodosProducidos').change(function(e){
    e.preventDefault();
    const checked = $(this).prop('checked');
    cargarProducidos($('#selectPlataforma').val(),$('#inputCodigo').val(),1,checked? -1 : default_page_size);
})

function generarGraficoJuego(fechas,producidos) {
    let accum = 0;
    const producidosAccum = [];
    for(const idx in producidos){
        accum += producidos[idx];
        producidosAccum.push(accum);
    }
    Highcharts.chart('graficoSeguimientoProducido', {
        chart: {
            backgroundColor: "#fff",
            type: 'area',
            events: {
                click: function(e) {
                    console.log(e.xAxis[0].value,e.yAxis[0].value);
                }
            }
        },
        title: {
            text: ' '
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            categories: fechas,
            tickmarkPlacement: 'on',
            title: {
                enabled: false
            },
            visible: false,
        },
        yAxis: {
            title: {
                text: ''
            },
        },
        tooltip: {
            split: true,
            valuePrefix: '$ ',
        },
        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function(e) {
                            $('#tablaBodyProducidos tbody tr.filaResaltada').removeClass('filaResaltada');
                            $('#tablaBodyProducidos tbody tr').eq(fechas.length - this.index - 1).addClass('filaResaltada').get(0).scrollIntoView();
                        }
                    }
                },
                fillOpacity: 0.4
            }
        },
        series: [
            {
                name: 'Producido acumulado',
                data: producidosAccum,
                color: 'rgba(0%,50%,80%,20%)',
            },
            {
                name: 'Producido diario',
                data: producidos,
                color: $('#producido').css('color'),
            },
        ]
    });

}