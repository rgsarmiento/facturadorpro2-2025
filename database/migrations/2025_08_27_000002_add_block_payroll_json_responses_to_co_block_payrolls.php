<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBlockPayrollJsonResponsesToCoBlockPayrolls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->table('co_block_payrolls', function (Blueprint $table) {
            $table->json('block_payroll_json_responses')->nullable()->after('block_payroll_json');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->table('co_block_payrolls', function (Blueprint $table) {
            $table->dropColumn('block_payroll_json_responses');
        });
    }
}
