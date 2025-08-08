<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStateBlockIdToCoBlockPayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_block_payrolls', function (Blueprint $table) {
            $table->unsignedInteger('state_block_id')->nullable()->after('resolution_id');
            $table->foreign('state_block_id')->references('id')->on('co_state_documents')->onDelete('cascade');
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
            $table->dropForeign(['state_block_id']);
            $table->dropColumn('state_block_id');
        });
    }
}
