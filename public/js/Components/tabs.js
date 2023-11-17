$(function(){
  $('[data-js-tabs]').each(function(){
    const tabs = $(this);
    const tab_titles = tabs.find('[data-js-tab-titles]');
    const tab_contents = tabs.find('[data-js-tab-contents]');
    
    function mostrarTabActivo(){
      let aidx = null;
      tab_titles.children().each(function(ttidx,tt){
        if($(tt).hasClass('activa')) aidx = ttidx;
      });
      
      if(aidx !== null){
        tab_contents.children().hide().eq(aidx).show();
      }
      else{
        tab_titles.children().eq(0).click();
      }
    }
    
    tab_titles.children().click(function(e){
      tab_titles.children('.activa').removeClass('activa');
      $(this).addClass('activa');
      mostrarTabActivo();
    });
    
    mostrarTabActivo();
  });
});
