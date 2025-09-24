<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCuentasContablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuentas_contables', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo', 20)->unique()->comment('Código único de la cuenta contable');
            $table->string('nombre', 255)->comment('Nombre descriptivo de la cuenta');
            $table->enum('tipo_cuenta', ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'])->comment('Tipo de cuenta según clasificación contable');
            $table->enum('naturaleza', ['debito', 'credito'])->comment('Naturaleza de la cuenta (débito o crédito)');
            $table->integer('nivel')->unsigned()->comment('Nivel jerárquico en el plan de cuentas');
            $table->unsignedInteger('cuenta_padre_id')->nullable()->comment('ID de la cuenta padre en la jerarquía');
            $table->text('descripcion')->nullable()->comment('Descripción detallada de la cuenta');
            $table->boolean('activa')->default(true)->comment('Estado activo/inactivo de la cuenta');
            $table->boolean('permite_movimiento')->default(true)->comment('Define si la cuenta permite movimientos contables');
            $table->decimal('saldo_inicial', 15, 2)->default(0)->comment('Saldo inicial de la cuenta');
            $table->decimal('saldo_actual', 15, 2)->default(0)->comment('Saldo actual de la cuenta');
            $table->string('codigo_niif', 20)->nullable()->comment('Código equivalente en NIIF');
            $table->json('configuracion_adicional')->nullable()->comment('Configuraciones adicionales en formato JSON');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['tipo_cuenta', 'activa']);
            $table->index(['nivel', 'cuenta_padre_id']);
            $table->index(['codigo']);
            $table->index(['activa', 'permite_movimiento']);

            // Relación de llave foránea con auto-referencia
            $table->foreign('cuenta_padre_id')->references('id')->on('cuentas_contables')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cuentas_contables');
    }
}
