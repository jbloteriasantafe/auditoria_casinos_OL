<style>
    .chip {  align-items: center; padding: 8px 16px; background-color: #ffffff; border: 1px solid #C4C3E3; border-radius: 50px; color: #4a4f87; font-weight: 500; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(80, 78, 118, 0.1); height: 80% }
    .chip:hover { background-color: #4a4f87; border-color: #4a4f87; color: #ffffff; text-decoration: none; box-shadow: 0 4px 8px rgba(80, 78, 118, 0.2); transform: translateY(-1px); }
    .chip i { margin-right: 8px; font-size: 1.1em; color: #4a4f87; transition: color 0.2s ease; }
    .chip:hover i { color: #FCDD9D; }
</style>

<div style="height: 50px; display: flex; flex-direction: row; justify-content: space-around; width: 100%; align-items: center">
    @if(!isset($pantalla) || $pantalla !== 'index')
    <a href="/aadd" class="chip " >
        <i class="fa fa-arrow-left"></i> Menú Principal
    </a>
    @endif

    @if(!isset($pantalla) || $pantalla !== 'beneficio-diario')
    <a href="/aadd/beneficio-diario" class="chip" >
        <i class="fa fa-line-chart"></i> Beneficio Diario
    </a>
    @endif

    @if(!isset($pantalla) || $pantalla !== 'datos-rapidos')
    <a href="/aadd/datos-rapidos" class="chip" >
        <i class="fa fa-table"></i> Comparación de beneficios por plataforma
    </a>
    @endif

    @if(!isset($pantalla) || ($pantalla !== 'importaciones' && $pantalla !== 'datos-rapidos'))
    <a href="/aadd/importaciones" class="chip i" >
        <i class="fa fa-upload"></i> Importador de Archivos
    </a>
    @endif
</div>
