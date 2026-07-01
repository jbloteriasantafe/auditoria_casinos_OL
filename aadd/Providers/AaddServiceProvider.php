<?php

namespace AADD\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AaddServiceProvider extends ServiceProvider
{
    /**
     * Iniciar los servicios del módulo.
     *
     * @return void
     */
    public function boot()
    {
        // Registrar el espacio de nombres para las vistas del módulo AADD
        View::addNamespace('aadd', base_path('aadd/Views'));

        // Cargar las migraciones del módulo de forma automática
        $this->loadMigrationsFrom(base_path('aadd/Database/Migrations'));
    }

    /**
     * Registrar los servicios del módulo en el contenedor.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
