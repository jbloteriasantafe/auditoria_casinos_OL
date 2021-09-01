$(document).ready(function() {
    //Resetear componentes
    $('#selectPlataforma').val("").change();

    $('#barraJuegos').attr('aria-expanded', 'true');
    $('#juegos').removeClass().addClass('subMenu1 collapse in');
    $('#informesJuegos').removeClass();
    $('#informesJuegos').addClass('subMenu2 collapse in');

    $('.tituloSeccionPantalla').text('Informe de Juego');
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
        $('#inputJuego').borrarDataList();
        $('#inputJuego').generarDataList("informeContableJuego/obtenerJuegoPlataforma/" + id_plat, 'juegos', 'id_juego', 'cod_juego', 1);
        $('#inputJuego').setearElementoSeleccionado(0, '');
        $('#btn-buscarJuego').prop('disabled', false);
        $('#inputJuego').prop('disabled', false);
    } else {
        $('#btn-buscarJuego').prop('disabled', true);
        $('#inputJuego').prop('disabled', true);
    }
    $('#inputJuego').change();
});

$('#inputJuego').change(function(e){
    e.preventDefault();
    habilitarBotonDetalle($(this).obtenerElementoSeleccionado() != 0)
});

/* CONTROLAR SELECCIÓN DE MÁQUINA */
$('#inputJuego').on('seleccionado', function() {
    habilitarBotonDetalle(true);
});

$('#inputJuego').on('deseleccionado', function() {
    habilitarBotonDetalle(false);
});

function habilitarBotonDetalle(valor) {
    $('#btn-buscarJuego').prop('disabled', !valor);
}

function limpiarNull(str, c = '-') {
    return str === null ? c : str;
}

const default_page_size = 30;

$('#btn-buscarJuego').click(function(e) {
    const id_juego = $('#inputJuego').obtenerElementoSeleccionado();
    const cod_juego = $('#inputJuego').val();
    const id_plataforma = $('#selectPlataforma').val();
    const cod_plat = $('#selectPlataforma option:selected').attr('data-codigo');
    $('.clonado').remove();
    $('#proveedor,#denominacion,#categoria,#moneda,#devolucion,#tipo').text('-');
    $('#estado').text('Produciendo (NO EN BD)');
    $('#codigo').text(cod_juego);
    $('#plataforma').text(cod_plat);

    const cargaprod = function (){
        cargarProducidos(id_plataforma,$('#inputJuego').val(),1,default_page_size,function(){
            $('#prevPreview').click();//Actualizar estados de las flechas
            $('#modalJuegoContable').modal('show');
        });
    }

    if(id_juego == -1){
        cargaprod();
        return;
    }

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

        if (data.historial.length) {
            data.historial.forEach(h =>{
                const fila = $('#hist').clone().addClass('clonado').show();
                fila.find('.fecha').text(limpiarNull(h.fecha));
                fila.find('.motivo').empty().append((h.motivo == "null"? "" : h.motivo).split('\n').join('<br />'));
                $('#listaHistorial').append(fila);
            });
        }

        cargaprod();
        return;
    });
});

function cargarProducidos(id_plataforma,cod_juego,pagina,page_size,after = function(){}){
    $.get(`informeContableJuego/obtenerProducidosDeJuego/${id_plataforma}/${cod_juego}/${(pagina-1)*page_size}/${page_size}`,function(data){
        $('#producido').text(data.total);
        $('#tablaProducidos tbody').empty();
        const fechas = [];
        const beneficios = [];
        data.producidos.forEach(function(p) {
            const fila = $('#filaEjemploProducido').clone().removeAttr('id');
            fila.find('.fecha').text(p.fecha);
            fila.find('.moneda').text(p.moneda);
            fila.find('.categoria').text(p.categoria);
            fila.find('.jugadores').text(p.jugadores);
            fila.find('.apuesta_efectivo').text(p.apuesta_efectivo);
            fila.find('.apuesta_bono').text(p.apuesta_bono);
            fila.find('.apuesta').text(p.apuesta);
            fila.find('.premio_efectivo').text(p.premio_efectivo);
            fila.find('.premio_bono').text(p.premio_bono);
            fila.find('.premio').text(p.premio);
            fila.find('.beneficio_efectivo').text(p.beneficio_efectivo);
            fila.find('.beneficio_bono').text(p.beneficio_bono);
            fila.find('.beneficio').text(p.beneficio);
            $('#tablaProducidos tbody').append(fila);

            fechas.push(p.fecha);
            beneficios.push(parseFloat(p.beneficio));
        });
        $('#previewPage').text(pagina);
        $('#previewTotal').text(Math.ceil(data.count/page_size));
        if(page_size <= 0) $('#previewTotal').text(1);

        after();

        setTimeout(function(){
            generarGraficoJuego(fechas,beneficios);
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

    $('#prevPreview').attr('disabled',(pag_actual+sumar) <= 1);
    $('#nextPreview').attr('disabled',(pag_actual+sumar) >= max_pag);
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
    cargarProducidos($('#selectPlataforma').val(),$('#inputJuego').val(),1,$(this).prop('checked')? -1 : default_page_size);
})

function generarGraficoJuego(fechas, data) {
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
            }
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
                            $('#tablaProducidos tbody tr.filaResaltada').removeClass('filaResaltada');
                            $('#tablaProducidos tbody tr').eq(this.index).addClass('filaResaltada');
                        }
                    }
                },
                fillOpacity: 0.4
            }
        },
        series: [{
            name: 'Beneficio',
            data: data,
            color: '#00E676',
        }]
    });

}