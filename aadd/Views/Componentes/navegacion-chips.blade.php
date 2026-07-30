<style>
    /* El contenedor crece y envuelve en vez de tener alto fijo: en pantallas
       angostas o con zoom alto los chips se acomodan en varias filas. Con alto
       fijo, una etiqueta larga desbordaba la pildora. */
    .chips-navegacion { display: flex; flex-flow: row wrap; justify-content: space-around; align-items: center; gap: 10px; width: 100%; min-height: 50px; padding: 5px 0; }
    .chip { display: inline-flex; align-items: center; padding: 8px 16px; background-color: #ffffff; border: 1px solid #C4C3E3; border-radius: 50px; color: #4a4f87; font-weight: 500; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(80, 78, 118, 0.1); }
    .chip:hover { background-color: #4a4f87; border-color: #4a4f87; color: #ffffff; text-decoration: none; box-shadow: 0 4px 8px rgba(80, 78, 118, 0.2); transform: translateY(-1px); }
    .chip i { margin-right: 8px; font-size: 1.1em; color: #4a4f87; transition: color 0.2s ease; }
    .chip:hover i { color: #FCDD9D; }
</style>

<div class="chips-navegacion">
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
