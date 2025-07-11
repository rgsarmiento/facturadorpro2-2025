<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('table_number');
            $table->char('table_state', 1);
            $table->unsignedInteger('establishment_id');
            $table->date('created_at')->nullable();
            $table->date('updated_at')->nullable();

            $table->foreign('establishment_id')->references('id')->on('establishments')->onDelete('no action')->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tables');
    }
}
