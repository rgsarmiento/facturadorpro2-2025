<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->increments('id');

            // Período
            $table->integer('year')->comment('Año fiscal');
            $table->integer('month')->nullable()->comment('Mes (1-12) o NULL para anual');
            $table->date('start_date')->comment('Fecha inicial');
            $table->date('end_date')->comment('Fecha final');

            // Estado
            $table->enum('status', ['open', 'closed', 'locked'])->default('open');
            $table->boolean('allow_modifications')->default(true);

            // Control de cierre
            $table->dateTime('closed_at')->nullable();
            $table->unsignedInteger('closed_by')->nullable();

            // Saldos de cierre
            $table->decimal('closing_debit_balance', 20, 2)->default(0);
            $table->decimal('closing_credit_balance', 20, 2)->default(0);

            // Notas
            $table->text('closing_notes')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('closed_by')->references('id')->on('users');

            // Índices
            $table->unique(['year', 'month']);
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_periods');
    }
}
