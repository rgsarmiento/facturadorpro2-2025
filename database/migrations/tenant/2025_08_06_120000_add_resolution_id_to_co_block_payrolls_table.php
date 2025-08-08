<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResolutionIdToCoBlockPayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_block_payrolls', function (Blueprint $table) {
            $table->unsignedInteger('resolution_id')->nullable()->after('establishment_id');
            // Agregar foreign key constraint hacia la tabla de resoluciones
            $table->foreign('resolution_id')->references('id')->on('co_type_documents');
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
            // Eliminar foreign key constraint
            $table->dropForeign(['resolution_id']);
            $table->dropColumn('resolution_id');
        });
    }
}
