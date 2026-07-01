<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaAaddBeneficioMensual extends Migration
{
    /**
     * Ejecutar las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aadd_t_beneficio_mensual', function (Blueprint $tabla) {
            $tabla->increments('id');
            $tabla->unsignedInteger('id_plataforma'); // Identificador de la plataforma (enum en PHP)
            $tabla->unsignedInteger('id_archivo'); // Relación con el archivo de origen
            $tabla->timestampTz('fecha'); // Instante en el tiempo (con zona horaria) que representa el mes
            $tabla->integer('jugadores_activos'); // Cantidad de jugadores activos en el mes
            $tabla->integer('apuestas_realizadas'); // Cantidad de apuestas realizadas en el mes
            $tabla->decimal('turnover', 20, 2); // Valor monetario total apostado (Turnover)
            $tabla->decimal('turnover_bonos', 20, 2); // Valor monetario apostado proveniente de bonos
            $tabla->decimal('payout', 20, 2); // Valor monetario total retornado/pagado (Payout)
            $tabla->decimal('payout_bonos', 20, 2); // Valor monetario pagado proveniente de bonos
            $tabla->decimal('ggr', 20, 2); // Ingreso bruto del juego (Gross Gaming Revenue = Turnover - Payout)
            $tabla->timestamps();

            // Índice compuesto único para acelerar consultas y evitar duplicados de una misma plataforma/mes
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
        Schema::dropIfExists('aadd_t_beneficio_mensual');
    }
}
