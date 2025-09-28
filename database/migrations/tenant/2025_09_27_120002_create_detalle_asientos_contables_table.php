<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDetalleAsientosContablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_asientos_contables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('asiento_contable_id');
            $table->unsignedInteger('cuenta_contable_id');
            $table->unsignedInteger('person_id')->nullable(); // Tercero
            $table->decimal('debito', 15, 2)->default(0);
            $table->decimal('credito', 15, 2)->default(0);
            $table->string('concepto', 500);
            $table->integer('orden')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('asiento_contable_id')->references('id')->on('asientos_contables')->onDelete('cascade');
            $table->foreign('cuenta_contable_id')->references('id')->on('cuentas_contables');
            $table->foreign('person_id')->references('id')->on('persons');

            // Indexes
            $table->index(['asiento_contable_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalle_asientos_contables');
    }
}
