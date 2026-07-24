<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarSoftDeletesAArchivo extends Migration
{
    /**
     * Ejecutar las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('aadd')->table('aadd_t_archivo', function (Blueprint $tabla) {
            $tabla->softDeletes();
        });
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('aadd')->table('aadd_t_archivo', function (Blueprint $tabla) {
            $tabla->dropSoftDeletes();
        });
    }
}
