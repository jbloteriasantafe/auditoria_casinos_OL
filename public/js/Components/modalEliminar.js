$(function(){
  $('[data-js-modal-eliminar]').each(function(){
    const M = $(this);
    let eliminar_callback = function(){};
    let habia_modal_abierto = false;
    M.on('mostrar_para_eliminar',function(e,callback){
      habia_modal_abierto = $('body').hasClass('modal-open');
      eliminar_callback = callback;
      M.modal('show');
    });
    M.find('[data-js-modal-eliminar-eliminar]').on('click',function(e){
      eliminar_callback();
    });
    M.on('esconder',function(e){
      M.modal('hide');
    });
    M.on('hidden.bs.modal',function(e){
      //Reinicio el scrolleo/overflow eliminado por el modal dismissal del modal de eliminar
      $('body').toggleClass('modal-open',habia_modal_abierto);
    });
  });
});
