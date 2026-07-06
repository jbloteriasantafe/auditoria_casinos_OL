<script>
    window.paginarTabla = function(tableSelector, filasPorPagina) {
        var $table = $(tableSelector);
        $table.next('.paginador-custom').remove();
        
        var $tbody = $table.find('tbody');
        var $tr = $tbody.find('tr:not(.no-paginar)');
        var totalFilas = $tr.length;
        var totalPaginas = Math.ceil(totalFilas / filasPorPagina);
        
        if (totalPaginas <= 1) return;
        
        var $paginador = $('<div class="paginador-custom text-center" style="margin-top: 20px; padding: 10px; background-color: #ffffff; border-radius: 8px; border: 1px solid #C4C3E3;"></div>');
        for (var i = 1; i <= totalPaginas; i++) {
            var $btn = $('<button class="btn btn-default btn-sm" style="margin: 0 3px;">' + i + '</button>');
            $btn.data('page', i);
            $paginador.append($btn);
        }
        var $container = $table.closest('.table-responsive-moderna');
        if ($container.length) {
            $container.after($paginador);
        } else {
            $table.after($paginador);
        }
        
        function mostrarPagina(page) {
            $paginador.find('button').removeClass('btn-primary').addClass('btn-default');
            $paginador.find('button').filter(function() { return $(this).data('page') === page; }).removeClass('btn-default').addClass('btn-primary');
            
            var inicio = (page - 1) * filasPorPagina;
            var fin = inicio + filasPorPagina;
            
            $tr.hide();
            $tr.slice(inicio, fin).show();
        }
        
        $paginador.on('click', 'button', function(e) {
            e.preventDefault();
            mostrarPagina($(this).data('page'));
        });
        
        mostrarPagina(1);
    };
</script>
