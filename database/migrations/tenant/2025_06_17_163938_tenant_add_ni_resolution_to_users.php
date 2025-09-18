<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddNiResolutionToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'ni_resolution_id')) {
                $table->unsignedInteger('ni_resolution_id')->nullable()->default(null)->after('fe_resolution_id');
                $table->foreign('ni_resolution_id')->references('id')->on('co_type_documents')->onDelete('set null')->onUpdate('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ni_resolution_id')) {
                $table->dropColumn('ni_resolution_id');
            }
        });
    }
}
