<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequiereTerceroToCuentasContablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cuentas_contables', function (Blueprint $table) {
            $table->boolean('requiere_tercero')->default(false)->after('permite_movimiento')->comment('Define si la cuenta requiere información de terceros');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cuentas_contables', function (Blueprint $table) {
            $table->dropColumn('requiere_tercero');
        });
    }
}
