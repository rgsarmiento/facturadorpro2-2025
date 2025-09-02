<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSearchIndexesToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // Índices para optimizar las búsquedas de productos
            $table->index(['name', 'active'], 'items_name_active_index');
            $table->index(['internal_id', 'active'], 'items_internal_id_active_index');
            $table->index(['unit_type_id', 'active'], 'items_unit_type_active_index');
            $table->index(['active', 'is_set'], 'items_active_set_index');

            // Índice compuesto para las consultas más comunes
            $table->index(['active', 'is_set', 'unit_type_id', 'name'], 'items_search_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_name_active_index');
            $table->dropIndex('items_internal_id_active_index');
            $table->dropIndex('items_unit_type_active_index');
            $table->dropIndex('items_active_set_index');
            $table->dropIndex('items_search_composite_index');
        });
    }
}
