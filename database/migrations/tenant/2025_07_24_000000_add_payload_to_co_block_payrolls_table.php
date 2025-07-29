<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayloadToCoBlockPayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_block_payrolls', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('deductions_total');
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
            $table->dropColumn('payload');
        });
    }
}
