<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModificarTablaBeneficiosUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('aadd')->table('aadd_t_beneficio_diario', function (Blueprint $table) {
            $table->dropUnique(['id_plataforma', 'fecha']);
            $table->index(['id_plataforma', 'fecha'], 'aadd_t_beneficio_diario_plataforma_fecha_index');
        });

        Schema::connection('aadd')->table('aadd_t_beneficio_mensual', function (Blueprint $table) {
            $table->dropUnique(['id_plataforma', 'fecha']);
            $table->index(['id_plataforma', 'fecha'], 'aadd_t_beneficio_mensual_plataforma_fecha_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('aadd')->table('aadd_t_beneficio_diario', function (Blueprint $table) {
            $table->dropIndex('aadd_t_beneficio_diario_plataforma_fecha_index');
            $table->unique(['id_plataforma', 'fecha']);
        });

        Schema::connection('aadd')->table('aadd_t_beneficio_mensual', function (Blueprint $table) {
            $table->dropIndex('aadd_t_beneficio_mensual_plataforma_fecha_index');
            $table->unique(['id_plataforma', 'fecha']);
        });
    }
}
