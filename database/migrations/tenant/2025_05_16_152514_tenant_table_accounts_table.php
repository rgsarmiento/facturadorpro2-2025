<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantTableAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('table_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account');
            $table->char('state', 1)->default('');
            $table->decimal('price', 16, 6)->default(0.000000);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('item_id')->default(0);
            $table->string('item_description', 50)->default('');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unsignedInteger('user_id')->default(0);
            $table->char('prefix', 5)->nullable()->default(null);
            $table->string('number')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_accounts');
    }
}
