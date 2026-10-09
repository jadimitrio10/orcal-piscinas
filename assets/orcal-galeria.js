/* Botón "Ver fotos": abre la galería a pantalla completa desde la primera foto */
(function(){
  document.addEventListener('click',function(e){
    var btn=e.target.closest&&e.target.closest('.orcal-galeria-btn');
    if(!btn)return;
    var g=document.getElementById(btn.getAttribute('data-galeria'));
    var first=g&&g.querySelector('.gallery-item a');
    if(first)first.click();
  });
})();
