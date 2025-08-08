<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniquePeriodIndexToCoBlockPayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_block_payrolls', function (Blueprint $table) {
            // Agregar columnas virtuales para extraer las fechas del JSON period
            $table->date('period_start_virtual')->virtualAs('JSON_UNQUOTE(JSON_EXTRACT(`period`, "$.period_start"))')->after('period');
            $table->date('period_end_virtual')->virtualAs('JSON_UNQUOTE(JSON_EXTRACT(`period`, "$.period_end"))')->after('period_start_virtual');

            // Crear índice único compuesto sobre las columnas virtuales
            $table->unique(['period_start_virtual', 'period_end_virtual'], 'unique_period_range');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_block_payrolls', function (Blueprint $table) {
            // Eliminar el índice único
            $table->dropUnique('unique_period_range');

            // Eliminar las columnas virtuales
            $table->dropColumn(['period_start_virtual', 'period_end_virtual']);
        });
    }
}
