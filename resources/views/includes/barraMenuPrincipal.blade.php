<ul id="barraMenuPrincipal">
  <div class="card" style="width: 8vw; flex: unset;">
    <?php $fondoOL = '/img/tarjetas/banner_OL'.(rand(0,1) + 1).'.jpg'; ?>
    <a tabindex="-1" href="/inicio">
      <span><img src="/img/logos/logo_nuevo2_bn.png" style="width: 8vw;"></span>
    </a>
  </div>
  <div class="card" style="width: 8vw; flex: unset;">
    <a tabindex="-1" href="/configCuenta">
      <?php
      $img_user = $tiene_imagen ? '/usuarios/imagen' : '/img/img_user.jpg';
      ?>
      <span>
        <img src='{{$img_user}}' class='img-circle' style="width: 2vw;">
      </span>
      {{$usuario->nombre}} 
      {{'@'.$usuario->user_name}}
    </a>
  </div>
  <div id="btn-ayuda" class="card" style="background-color: rgb(61, 106, 41);">
    @section('headerLogo')
    @show
    <span class="tituloSeccionPantalla" style="text-align: center;">---</span>
  </div>
  @foreach($opciones as $op => $datos)
    @if(count($datos['hijos']) == 0)
      @component('includes.barraMenuPrincipal_link',[
        'primer_nivel' => true,
        'divli_style'  => $datos['divli_style'],
        'link_style'   => $datos['link_style'],
        'link'         => $datos['link'],
        'op'           => $op,
      ])
      @endcomponent
    @else
      @component('includes.barraMenuPrincipal_desplegable',[
        'primer_nivel' => true,
        'divli_style'  => $datos['divli_style'],
        'link_style'   => $datos['link_style'],
        'hijos'        => $datos['hijos'],
        'op'           => $op,
      ])
      @endcomponent
    @endif
  @endforeach
  <div class="card dropdown" style="width: 5%;flex: unset;"  onclick="markNotificationAsRead('{{count($usuario->unreadNotifications)}}')">
    <a class="dropdown-toggle no_abrir_en_mouseenter" type="button" data-toggle="dropdown">
      <span>
        <i class="far fa-bell"></i>
        <span class="badge" style="background: white;color: black;text-align: center;">{{count($usuario->unreadNotifications)}}</span>
      </span>
    </a>
    <ul class="dropdown-menu" style="max-height: 300px; overflow-y:auto; width:350px;">
      @forelse ($usuario->unreadNotifications as $notif)
      <div style="background: #E6E6E6;">
          @include('includes.notifications.'.snake_case(class_basename($notif->type)))
      </div>
      @empty
      @forelse($usuario->lastNotifications() as $notif)
          @include('includes.notifications.'.snake_case(class_basename($notif->type)))
      @empty
      <a href="#" style="display: inline-block;width: 100%;">No hay nuevas Notificaciones</a>
      @endforelse
      @endforelse
    </ul>
  </div>
  @if($usuario->es_superusuario || $usuario->es_auditor)
  <div class="card" style="width:5%;flex: unset;">
    <a id="ticket" tabindex="-1" href="#">
      <span><i id="ticket" class="far fa-envelope"></i></span>
    </a>
  </div>
  @endif
  <div class="card" style="width:5%;flex: unset;">
    <a id="calendario" tabindex="-1" href="/calendario_eventos">
      <span><i  class="far fa-fw fa-calendar-alt"></i></span>
    </a>
  </div>
  <div class="card" style="width:5%;flex: unset;">
    <a class="etiquetaLogoSalida"  tabindex="-1" href="#">
      <span><img src="/img/logos/salida.png"></span>
    </a>
  </div>
</ul>