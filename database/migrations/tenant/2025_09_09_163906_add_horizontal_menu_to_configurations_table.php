<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddHorizontalMenuToConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->boolean('horizontal_menu')->default(true)->after('compact_sidebar');
        });

        // Actualizar todos los registros existentes para que tengan horizontal_menu = true por defecto
        DB::table('configurations')->whereNull('horizontal_menu')->update(['horizontal_menu' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('horizontal_menu');
        });
    }
}
