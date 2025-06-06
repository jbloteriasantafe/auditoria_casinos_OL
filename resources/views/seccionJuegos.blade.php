@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/fileinput.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
@endsection

@section('contenidoVista')

<?php
setlocale(LC_TIME, 'es_ES.UTF-8');
?>
<datalist id="datalistCertificados">
@foreach($certificados as $cert)
<option data-id="{{$cert->id_gli_soft}}">{{$cert->nro_archivo}}</option>
@endforeach
</datalist>
<datalist id="datalistProveedores">
@foreach($proveedores as $p)
<option>{{$p}}</option>
@endforeach
</datalist>

              <div class="row">
                  <div class="col-lg-12 col-xl-9">
                    <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div id="contenedorFiltros" class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                <div class="col-md-3">
                                    <h5>Plataforma</h5>
                                    <select id="buscadorPlataforma" class="form-control">
                                      <option value="">- Todas -</option>
                                      @foreach($plataformas as $p)
                                      <option value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
                                      @endforeach
                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Categoría</h5>
                                    <select id="buscadorCategoria" class="form-control">
                                      <option value="">- Todas -</option>
                                      @foreach($categoria_juego as $c)
                                      <option value="{{$c->id_categoria_juego}}">{{$c->nombre}}</option>
                                      @endforeach
                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Estado</h5>
                                    <select id="buscadorEstado" class="form-control">
                                      <option value="">- Todos -</option>
                                      @foreach($estado_juego as $e)
                                      <option value="{{$e->id_estado_juego}}">{{$e->nombre}}</option>
                                      @endforeach
                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Sistema</h5>
                                    <select id="buscadorSistema" class="form-control">
                                      <option value="">Escritorio/Móvil</option>
                                      <option value="1">Escritorio</option>
                                      <option value="2">Móvil</option>
                                      <option value="3">Escritorio y Móvil</option>
                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Nombre del juego</h5>
                                    <input id="buscadorNombre" class="form-control" placeholder="Nombre del juego">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Código del Juego</h5>
                                    <input id="buscadorCodigoJuego" class="form-control" placeholder="Código del Juego">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Proveedor</h5>
                                    <input id="buscadorProveedor" class="form-control" placeholder="Proveedor">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Código de certificado</h5>
                                    <input id="buscadorCodigo" class="form-control" placeholder="Código de identificación">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Porcentaje Devolución</h5>
                                    <div class="input-group" style="width: 10em;">
                                      <input id="buscadorPdevMenor" class="form-control" type="text" placeholder="0"   style="width: 4em;text-align: center;">
                                      <span class="input-group-addon" style="width: 100%;">-</span>
                                      <input id="buscadorPdevMayor" class="form-control" type="text" placeholder="100" style="width: 4em;text-align: center;">
                                    </div>
                                  </div>
                                </div>
                                <br>
                                <div class="row">
                                  <div class="col-md-1 col-md-offset-5">
                                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row">
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>TODOS LOS JUEGOS</h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaResultados" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-3" value="juego.nombre_juego" estado="">NOMBRE DEL JUEGO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="juego.id_categoria_juego" estado="">CATEG. <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" estado="">ESTADO</th>
                                    <th class="col-xs-2" value="juego.cod_juego" estado="">CÓDIGO DEL JUEGO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-3" value="certificados" estado="">CÓDIGO DEL CERTIFICADO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="" estado="">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla" style="height: 350px;">

                                </tbody>
                              </table>
                              <!--Comienzo indices paginacion-->
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>
                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->


                <div class="col-lg-4 col-xl-3">
                  <div class="row">
                    <div class="col-lg-12">
                     <a href="" id="btn-nuevo" style="text-decoration: none;">
                      <div class="panel panel-default panelBotonNuevo">
                          <center><img class="imgNuevo" src="/img/logos/juegos_white.png"><center>
                          <div class="backgroundNuevo"></div>
                          <div class="row">
                              <div class="col-xs-12">
                                <center>
                                    <h5 class="txtLogo">+</h5>
                                    <h4 class="txtNuevo">NUEVO JUEGO</h4>
                                </center>
                              </div>
                          </div>
                      </div>
                     </a>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-12">
                      <a href="/juegosCSV" target="_blank" style="text-decoration: none;">
                        <div class="panel panel-default panelBotonNuevo">
                          <center>
                            <img class="imgNuevo" src="/img/logos/CSV_white.png">
                          </center>
                          <div class="backgroundNuevo"></div>
                          <div class="row">
                            <div class="col-xs-12">
                              <center>
                                <h5 class="txtLogo" style="font-size: 165px !important;">↓</h5>
                                <h4 class="txtNuevo"><br>DESCARGAR JUEGOS</h4>
                              </center>
                            </div>
                          </div>
                        </div>
                      </a>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-12">
                     <a href="" id="btn-importar" style="text-decoration: none;">
                      <div class="panel panel-default panelBotonNuevo">
                          <center><img class="imgNuevo" src="/img/logos/juegos_white.png"><center>
                          <div class="backgroundNuevo"></div>
                          <div class="row">
                              <div class="col-xs-12">
                                <center>
                                    <h5 class="txtLogo">↑</h5>
                                    <h4 class="txtNuevo">CARGA MASIVA</h4>
                                </center>
                              </div>
                          </div>
                      </div>
                     </a>
                    </div>
                  </div>
                </div>
          </div> <!--/columna TABLA -->


    <!-- Modal Juego -->
    <div class="modal fade" id="modalJuego" tabindex="-1" data-backdrop="static" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header modalNuevo">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>

                  <h3 class="modal-title"> | NUEVO JUEGO</h3>

                </div>

          <!-- Panel que se minimiza -->
          <div id="colapsado" class="collapse in">

          <div class="modal-body">
            <div id="juegoPlegado" class="row">
                <div class="row">
                  <div class="row">
                    <div class="col-md-12">
                      <div class="col-md-4">
                          <h5>Nombre Juego</h5>
                          <input id="inputJuego" class="form-control" type="text" autocomplete="off" placeholder="Nombre juego"/>
                      </div>
                      <div class="col-md-4">
                        <h5>Categoría</h5>
                        <select id="selectCategoria" class="form-control">
                          <option value="">- Seleccionar -</option>
                          @foreach($categoria_juego as $c)
                          <option value="{{$c->id_categoria_juego}}">{{$c->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-4">
                        <h5>Historia</h5>
                        <select id="selectLogJuego" class="form-control">
                        </select>
                      </div>
                      <div class="col-md-4" id="tipos">
                        <h5>EN</h5>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" value="" id="escritorio">
                          <label class="form-check-label" for="escritorio">Escritorio</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" value="" id="movil">
                          <label class="form-check-label" for="movil">Móvil</label>
                        </div>
                      </div>
                      <div class="col-md-4" id="plataformas">
                        <h5>Plataformas</h5>
                        @foreach($plataformas as $idx => $p)
                        <div class="row">
                          <span>{{$p->nombre}}</span>
                          <select class="form-control plataforma" data-id="{{$p->id_plataforma}}">
                            <option value="">No disponible</option>
                            @foreach($estado_juego as $e)
                            <option value="{{$e->id_estado_juego}}">{{$e->nombre}}</option>
                            @endforeach
                          </select>
                        </div>
                        @endforeach
                      </div>
                      <div class="col-md-4">
                        <h5>Código Juego</h5>
                        <input id="inputCodigoJuego" class="form-control" type="text" autocomplete="off" placeholder="Código Juego" />
                      </div>
                      <div class="col-md-4">
                        <h5>Codigo de operador</h5>
                        <input id="inputCodigoOperador" class="form-control" type="text"  autocomplete="off" placeholder="-" maxlength="100"/>
                      </div>
                      <div class="col-md-4">
                        <h5>Proveedor</h5>
                        <input id="inputProveedor" class="form-control" type="text"  autocomplete="off" placeholder="-" maxlength="100" list="datalistProveedores"/>
                      </div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-12">
                      <h5 style="display:inline;">Certificados</h5>
                      <button style="display:inline;" id="btn-agregarCertificado" class="btn btn-success borrarFila" type="button">
                        <i class="fa fa-fw fa-link"></i>
                      </button>
                      <div id="listaSoft" class="pre-scrollable" style="margin-top:15px;max-height: 150px;">
                      </div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-12">
                      <div class="col-md-4">
                        <h5>Denominación Juego</h5>
                        <input id="denominacion_juego" class="form-control" type="text" autocomplete="off" placeholder="-" />
                      </div>
                      <div class="col-md-4">
                        <h5>Porcentaje Devolución</h5>
                        <input id="porcentaje_devolucion" class="form-control" type="text" autocomplete="off" placeholder="-" />
                      </div>
                      <div class="col-md-4">
                        <h5>Moneda</h5>
                        <select id="tipo_moneda" class="form-control">
                          <option value="">- Seleccionar - </option>
                          @foreach ($monedas as $moneda)
                          <option value="{{$moneda->id_tipo_moneda}}">{{$moneda->descripcion}}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-12">
                      <h5>Motivo</h5>
                      <textarea class="form-control" id="motivo" rows="3" maxlength="256"></textarea>
                    </div>
                  </div>
                </div>
              </div>
          </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button id='boton-cancelar' type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <button id='boton-salir' type="button" class="btn btn-default" data-dismiss="modal" style="display: none;">SALIR</button>
                  <input type="hidden" id="id_juego" name="id_juego" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

               <div  id="colapsado" class="collapse in">
                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmJuego" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div id="mensajeEliminar" class="col-xs-12">
                            <strong>¿Seguro desea eliminar el JUEGO?</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
              </div>
            </div>
          </div>
    </div>

@component('seccionJuegosComponenteCargaMasiva',[
  'plataformas' => $plataformas,
  'categoria_juego' => $categoria_juego,
  'laboratorios' => $laboratorios,
  'datalistCertificados' => 'datalistCertificados',
  'datalistProveedores' => 'datalistProveedores'
])
@endcomponent

<table hidden>
  <tr id="moldeFilaJuego">
    <td class="col-xs-3 nombre_juego">JUEGO</td>
    <td class="col-xs-1 categoria">CATEGORIA</td>
    <td class="col-xs-1 estado">ESTADO</td>
    <td class="col-xs-2 codigo_juego">CODIGO</td>
    <td class="col-xs-3 codigo_certif">CERTIFICADOS</td>
    <td class="col-xs-2">
      <button title="VER MÁS" class="btn btn-info detalle"><i class="fa fa-fw fa-search-plus"></i></button>
      <button title="MODIFICAR" class="btn btn-warning modificar"><i class="fa fa-fw fa-pencil-alt"></i></button>
      <button title="ELIMINAR" class="btn btn-danger eliminar"><i class="fa fa-fw fa-trash-alt"></i></button>
    </td>
  </tr>
</table>
<div hidden>
  <div id="moldeCertificado" class="row col-md-12" style="padding-top: 2px;padding-bottom: 2px;">
    <div class="col-md-10">
      <input class="codigo form-control" value="" list="datalistCertificados">
    </div>
    <div class="col-md-1">
      <button class="btn btn-link verCertificado">
        <i class="fa fa-fw fa-search"></i>
      </button>
    </div>
    <div class="col-md-1">
      <button class="btn borrarFila borrarCertificado">
        <i class="fa fa-fw fa-trash"></i>
      </button>
    </div>
  </div>
</div>
    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| JUEGOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Juegos</h5>
      <p>
        Define juegos para ser utilizados en otros modulos.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')

    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" charset="utf-8"></script>

    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/seccionJuegos.js?5" charset="utf-8" type="module"></script>
    @endsection
