<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantCreateCoBlockPayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('co_block_payrolls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date_of_issue')->index();
            $table->time('time_of_issue')->index();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('establishment_id');
            $table->json('establishment');
            $table->json('period');
            $table->unsignedBigInteger('workers_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->decimal('accrued_total', 18, 2)->default(0);
            $table->decimal('deductions_total', 18, 2)->default(0);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('establishment_id')->references('id')->on('establishments');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('co_block_payrolls');
    }

}
