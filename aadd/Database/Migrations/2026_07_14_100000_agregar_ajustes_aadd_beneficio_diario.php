<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarAjustesAaddBeneficioDiario extends Migration
{
    /**
     * Ejecutar las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('aadd')->table('aadd_t_beneficio_diario', function (Blueprint $tabla) {
            $tabla->decimal('ggr_calculado', 20, 2)->default(0)->after('ggr');
            $tabla->decimal('diferencia', 20, 2)->default(0)->after('ggr_calculado');
            $tabla->decimal('ajuste', 20, 2)->nullable()->after('diferencia');
            $tabla->decimal('ajuste_aud', 20, 2)->nullable()->after('ajuste');
            $tabla->text('observacion_ajuste')->nullable()->after('ajuste_aud');
        });
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('aadd')->table('aadd_t_beneficio_diario', function (Blueprint $tabla) {
            $tabla->dropColumn(['ggr_calculado', 'diferencia', 'ajuste', 'ajuste_aud', 'observacion_ajuste']);
        });
    }
}
