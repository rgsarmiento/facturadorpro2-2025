<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantIncreaseDecimalColumnsPurchaseItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('total_tax', 15, 2)->change();
            $table->decimal('subtotal', 15, 2)->change();
            $table->decimal('discount', 15, 2)->change();
            $table->decimal('total', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('total_tax', 10, 2)->change();
            $table->decimal('subtotal', 10, 2)->change();
            $table->decimal('discount', 10, 2)->change();
            $table->decimal('total', 10, 2)->change();
        });
    }
}
