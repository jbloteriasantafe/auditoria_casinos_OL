$(document).ready(function(){
  /* Deshabilito desplegar el menu principal en hover
  $('#barraMenuPrincipal > .dropdown > .dropdown-toggle:not(.no_abrir_en_mouseenter)').mouseenter(function(e){
    $(this).click();
  });*/
  $('#barraMenuPrincipal > .card > .dropdown-menu a').mouseenter(function(e){
    e.preventDefault();
    e.stopPropagation();
    const submenu = $(this).next('ul');
    $(this).closest('ul.dropdown-menu')//voy para el menu de arriba
    .find('ul.dropdown-menu').not(submenu).hide();//escondo todos los submenues menos el propio
    submenu.toggle();//Toggleo el submenu
  });
  $(document).on('hidden.bs.dropdown','.dropdown',function(e){
    //Escondo todos los submenues cuando se esconde un menu de 1er nivel
    $(this).find('li.dropdown-submenu').find('ul.dropdown-menu').hide();
  });
  $(document).on('click','#menuDesplegable .menu_con_opciones > span,#menuDesplegable .menu_con_opciones_desplegado > span',function(e){
    if($(this).parent().hasClass('menu_con_opciones_desplegado')){//Si esta desplegado solo escondo todo lo por debajo
      //Submenues
      $(this).parent().find('.menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
      //Padre
      $(this).parent().removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
      return;
    }
    //Si hizo click en otro menu, escondo todo y desplego el arbol hasta ahi
    //Escondo todo
    $('#menuDesplegable .menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
    //Abro todos los padres
    $(this).parents('.menu_con_opciones').removeClass('menu_con_opciones').addClass('menu_con_opciones_desplegado');
  });
  $('#botonMenuDesplegable').click(function(e){
    //Busco la opcion basado en la URL y la diferencio
    const opcion_actual = $('#menuDesplegable a').filter(function(){
      return $(this).attr('href') == ("/"+window.location.pathname.split("/")[1]);
    });
    //Lo marco como que es la opción actual mostrandose
    opcion_actual.parent().toggleClass('opcion_actual');
    //Desplego la opcion
    opcion_actual.closest('.menu_con_opciones').children('span').click();
    //Muestro el menu
    $($(this).attr('data-toggle')).toggle();
  });
});