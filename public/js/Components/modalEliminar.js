$(function(){
  $('[data-js-modal-eliminar]').each(function(){
    const M = $(this);
    let eliminar_callback = function(){};
    M.on('mostrar_para_eliminar',function(e,callback){
      eliminar_callback = callback;
      M.modal('show');
    });
    M.find('[data-js-modal-eliminar-eliminar]').on('click',function(e){
      eliminar_callback();
    });
  });
});
