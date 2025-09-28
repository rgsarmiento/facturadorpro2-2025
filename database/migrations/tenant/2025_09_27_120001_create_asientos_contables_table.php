<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsientosContablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asientos_contables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tipo_comprobante_id');
            $table->string('numero_comprobante', 20);
            $table->integer('consecutivo');
            $table->date('fecha_asiento');
            $table->string('concepto', 500);
            $table->decimal('total_debito', 15, 2);
            $table->decimal('total_credito', 15, 2);
            $table->enum('tipo_origen', ['MANUAL', 'AUTOMATICO'])->default('MANUAL');
            $table->enum('modulo_origen', ['VENTAS', 'COMPRAS', 'INVENTARIO', 'NOMINA', 'MANUAL'])->nullable();
            $table->unsignedInteger('documento_origen_id')->nullable();
            $table->enum('estado', ['BORRADOR', 'CONFIRMADO', 'ANULADO'])->default('BORRADOR');
            $table->unsignedInteger('usuario_creacion');
            $table->timestamp('fecha_creacion');
            $table->unsignedInteger('usuario_confirmacion')->nullable();
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->unsignedInteger('usuario_anulacion')->nullable();
            $table->timestamp('fecha_anulacion')->nullable();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('tipo_comprobante_id')->references('id')->on('tipo_comprobantes_contables');
            $table->foreign('usuario_creacion')->references('id')->on('users');
            $table->foreign('usuario_confirmacion')->references('id')->on('users');
            $table->foreign('usuario_anulacion')->references('id')->on('users');

            // Indexes
            $table->index(['fecha_asiento', 'estado']);
            $table->index('numero_comprobante');
            $table->unique(['tipo_comprobante_id', 'consecutivo']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asientos_contables');
    }
}
