@component('Components/include_guard',['nombre' => 'tabs'])
<style>
  .tabs {
    height: 100%;
    width: 100%;
  }
  .tabs .tab_titles {
    display: flex;
    height: 2em;
  }
  .tabs .tab_contents {
    height: calc(100% - 2em);
  }
  .tabs .tab_titles > * {
    flex: 1;
    text-align: center;
    background: #eee;
    border: 0.2vmin solid #ddd;
    border-bottom: 0.4vmin solid #ccc;
    cursor: pointer;
    user-select: none;
    border-top-left-radius: 0.75vmax;
    border-top-right-radius: 0.75vmax;
    font-weight: bold;
    color: rgb(68, 68, 68);
    text-shadow: white 0.05em 0.05em;
  }
  .tabs .tab_titles > *:not(.activa):hover {
    background: orange;
    border-color: gold;
  }
  .tabs .tab_titles > *.activa {
    border-bottom-color: orange;
    cursor: auto;
  }
</style>
@endcomponent

<?php $uid = uniqid(); ?>
<div id="{{$uid}}" class="tabs" data-js-tabs>
  <div class="tab_titles" data-js-tab-titles>
  {!! $tabs !!}
  </div>
  <div class="tab_contents" data-js-tab-contents>
  {!! $tab_contents !!}
  </div>
</div>
