<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsientosAdjuntosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asientos_adjuntos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('asiento_contable_id');
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->string('tipo_archivo', 10);
            $table->integer('tamaño_archivo');
            $table->timestamp('fecha_carga');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('asiento_contable_id')->references('id')->on('asientos_contables')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asientos_adjuntos');
    }
}
