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
        Schema::connection('aadd')->create('aadd_t_archivo', function (Blueprint $tabla) {
            $tabla->increments('id');
            $tabla->string('nombre'); // Nombre original del archivo
            $tabla->binary('contenido'); // Contenido binario del archivo
            $tabla->string('id_plataforma', 50); // Identificador de la plataforma (enum en PHP)
            $tabla->date('fecha'); // Fecha asociada al archivo
            $tabla->string('tipo_archivo'); // Tipo de archivo (enum en PHP)
            $tabla->timestamps();

            // Índices para optimizar consultas por rango de fecha y selección del último ID
            $tabla->index('fecha');
            $tabla->index(['id_plataforma', 'fecha']);
        });

        // Modificar la columna contenido para soportar archivos de hasta 16MB (MediumBlob) en MySQL
        // TODO: Revisar, corresponde chequear tamaño de archivos antes de subir a BD? 
        if (config('database.connections.aadd.driver') === 'mysql') {
            DB::connection('aadd')->statement("ALTER TABLE aadd_t_archivo MODIFY contenido MEDIUMBLOB");
        }
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('aadd')->dropIfExists('aadd_t_archivo');
    }
}
