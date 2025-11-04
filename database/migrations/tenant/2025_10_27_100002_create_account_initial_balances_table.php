<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountInitialBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_initial_balances', function (Blueprint $table) {
            $table->increments('id');

            // Cuenta contable
            $table->unsignedInteger('cuenta_contable_id');

            // Tercero (obligatorio para cuentas que lo requieren)
            $table->unsignedInteger('person_id')->nullable()->comment('Tercero (cliente/proveedor/empleado)');

            // Período de inicio
            $table->unsignedInteger('period_id')->nullable();
            $table->date('balance_date')->comment('Fecha del saldo inicial');

            // Valores
            $table->decimal('debito', 20, 2)->default(0);
            $table->decimal('credito', 20, 2)->default(0);
            $table->decimal('balance', 20, 2)->default(0)->comment('Saldo neto (según naturaleza)');

            // Comprobante de apertura generado
            $table->unsignedInteger('asiento_contable_id')->nullable()->comment('ID del asiento generado');

            // Estado
            $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
            $table->boolean('is_posted')->default(false);
            $table->dateTime('posted_at')->nullable();

            // Descripción
            $table->text('notes')->nullable();

            // Control
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('posted_by')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('cuenta_contable_id')->references('id')->on('cuentas_contables')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('persons')->onDelete('restrict');
            $table->foreign('period_id')->references('id')->on('accounting_periods')->onDelete('restrict');
            $table->foreign('asiento_contable_id')->references('id')->on('asientos_contables')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('posted_by')->references('id')->on('users');

            // Índices
            $table->index('cuenta_contable_id');
            $table->index('person_id');
            $table->index('period_id');
            $table->index('balance_date');
            $table->index('status');
            $table->index('is_posted');

            // Evitar duplicados: una cuenta solo puede tener un saldo inicial por período
            $table->unique(['cuenta_contable_id', 'person_id', 'period_id'], 'unique_initial_balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_initial_balances');
    }
}
