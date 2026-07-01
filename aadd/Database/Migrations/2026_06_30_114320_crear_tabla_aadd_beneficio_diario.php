<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaAaddBeneficioDiario extends Migration
{
    /**
     * Ejecutar las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('aadd')->create('aadd_t_beneficio_diario', function (Blueprint $tabla) {
            $tabla->increments('id');
            $tabla->string('id_plataforma', 50); // Identificador de la plataforma (enum en PHP)
            $tabla->unsignedInteger('id_archivo'); // Relación con el archivo de origen
            $tabla->timestampTz('fecha'); // Fecha y hora exacta (con zona horaria)
            $tabla->integer('jugadores_activos'); // Cantidad de jugadores activos en el día
            $tabla->integer('apuestas_realizadas'); // Cantidad de apuestas realizadas en el día
            $tabla->decimal('turnover', 20, 2); // Valor monetario total apostado (Turnover)
            $tabla->decimal('turnover_bonos', 20, 2); // Valor monetario apostado proveniente de bonos
            $tabla->decimal('payout', 20, 2); // Valor monetario total retornado/pagado (Payout)
            $tabla->decimal('payout_bonos', 20, 2); // Valor monetario pagado proveniente de bonos
            $tabla->decimal('ggr', 20, 2); // Ingreso bruto del juego (Gross Gaming Revenue = Turnover - Payout)
            $tabla->timestamps();

            // Índice compuesto único para acelerar consultas y evitar duplicados de un mismo día/plataforma
            $tabla->unique(['id_plataforma', 'fecha']);
        });
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('aadd')->dropIfExists('aadd_t_beneficio_diario');
    }
}
