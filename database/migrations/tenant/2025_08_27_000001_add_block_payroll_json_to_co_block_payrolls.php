<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBlockPayrollJsonToCoBlockPayrolls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_block_payrolls', function (Blueprint $table) {
            $table->json('block_payroll_json')->nullable()->after('payload')->comment('JSON individual por empleado para generar documentos de nómina');
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
            $table->dropColumn('block_payroll_json');
        });
    }
}
