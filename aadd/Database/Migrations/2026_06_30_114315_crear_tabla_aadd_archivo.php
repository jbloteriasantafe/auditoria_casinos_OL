<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CrearTablaAaddArchivo extends Migration
{
    /**
     * Ejecutar las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aadd_t_archivo', function (Blueprint $tabla) {
            $tabla->increments('id');
            $tabla->string('nombre'); // Nombre original del archivo
            $tabla->binary('contenido'); // Contenido binario del archivo
            $tabla->unsignedInteger('id_plataforma'); // Identificador de la plataforma (enum en PHP)
            $tabla->date('fecha'); // Fecha asociada al archivo
            $tabla->string('tipo_archivo'); // Tipo de archivo (enum en PHP)
            $tabla->timestamps();
        });

        // Modificar la columna contenido para soportar archivos de hasta 16MB (MediumBlob) en MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE aadd_t_archivo MODIFY contenido MEDIUMBLOB");
        }
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('aadd_t_archivo');
    }
}
