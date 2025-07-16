<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddEqDocsNotesTypeInvoiceToTypeInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('co_type_invoices')->insert([
            ['id'=> 25, 'name' => 'Nota de debito al Documento Equivalente', 'code' => 93],
            ['id'=> 26, 'name' => 'Nota de crédito al Documento Equivalente', 'code' => 94],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('co_type_invoices')->where('id', 25)->delete();
        DB::table('co_type_invoices')->where('id', 26)->delete();
    }
}
