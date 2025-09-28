<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaldoToCuentasContablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cuentas_contables', function (Blueprint $table) {
            // Agregar el campo saldo que se actualiza con los asientos contables
            $table->decimal('saldo', 15, 2)->default(0)->after('saldo_actual');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cuentas_contables', function (Blueprint $table) {
            $table->dropColumn('saldo');
        });
    }
}
