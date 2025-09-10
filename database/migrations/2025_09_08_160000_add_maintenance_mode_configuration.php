<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaintenanceModeConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->boolean('maintenance_mode')->default(false)->after('certificate');
            $table->text('maintenance_message')->nullable()->after('maintenance_mode');
            $table->json('maintenance_allowed_companies')->nullable()->after('maintenance_message');
            $table->timestamp('maintenance_started_at')->nullable()->after('maintenance_allowed_companies');
            $table->string('maintenance_started_by')->nullable()->after('maintenance_started_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_mode',
                'maintenance_message', 
                'maintenance_allowed_companies',
                'maintenance_started_at',
                'maintenance_started_by'
            ]);
        });
    }
}