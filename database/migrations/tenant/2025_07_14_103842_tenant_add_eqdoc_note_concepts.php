<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Factcolombia1\Models\Tenant\NoteConcept;

class TenantAddEqDocNoteConcepts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::connection('tenant')->table('co_type_documents')->updateOrInsert(
            ['id' => 998],
            ['code' => 26, 'name' => 'Nota de crédito al Documento Equivalente', 'resolution_number' => '12345', 'template' => 'face_c', 'prefix' => 'NCDE', 'from' => 1, 'to' => 99999999]
        );
        \DB::connection('tenant')->table('co_type_documents')->updateOrInsert(
            ['id' => 999],
            ['code' => 25, 'name' => 'Nota de debito al Documento Equivalente', 'resolution_number' => '12345', 'template' => 'face_d', 'prefix' => 'NDDE', 'from' => 1, 'to' => 99999999]
        );
        DB::connection('tenant')->table('co_type_documents')->where('code', 25)->where('prefix', 'NDDE')->update(['id' => 999]);
        DB::connection('tenant')->table('co_type_documents')->where('code', 26)->where('prefix', 'NCDE')->update(['id' => 998]);
        NoteConcept::updateOrCreate(['id' => 9], ['id' => 9, 'type_document_id' => 998, 'name' => 'Devolución parcial de los bienes y/o no aceptación parcial del servicio', 'code' => '1']);
        NoteConcept::updateOrCreate(['id' => 10], ['id' => 10, 'type_document_id' => 998, 'name' => 'Anulación del documento equivalente', 'code' => '2']);
        NoteConcept::updateOrCreate(['id' => 11], ['id' => 11, 'type_document_id' => 998, 'name' => 'Rebaja  o descuento parcial o total', 'code' => '3']);
        NoteConcept::updateOrCreate(['id' => 12], ['id' => 12, 'type_document_id' => 998, 'name' => 'Ajuste de precio', 'code' => '4']);
        NoteConcept::updateOrCreate(['id' => 13], ['id' => 13, 'type_document_id' => 998, 'name' => 'Otros', 'code' => '5']);
        NoteConcept::updateOrCreate(['id' => 14], ['id' => 14, 'type_document_id' => 999, 'name' => 'Intereses', 'code' => '1']);
        NoteConcept::updateOrCreate(['id' => 15], ['id' => 15, 'type_document_id' => 999, 'name' => 'Gastos por cobrar', 'code' => '2']);
        NoteConcept::updateOrCreate(['id' => 16], ['id' => 16, 'type_document_id' => 999, 'name' => 'Cambio del valor', 'code' => '3']);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 1)->where('type_document_id', 998)->update(['id' => 9]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 2)->where('type_document_id', 998)->update(['id' => 10]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 3)->where('type_document_id', 998)->update(['id' => 11]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 4)->where('type_document_id', 998)->update(['id' => 12]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 5)->where('type_document_id', 998)->update(['id' => 13]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 1)->where('type_document_id', 999)->update(['id' => 14]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 2)->where('type_document_id', 999)->update(['id' => 15]);
        DB::connection('tenant')->table('co_note_concepts')->where('code', 3)->where('type_document_id', 999)->update(['id' => 16]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        NoteConcept::find(9)->forceDelete();
        NoteConcept::find(10)->forceDelete();
        NoteConcept::find(11)->forceDelete();
        NoteConcept::find(12)->forceDelete();
        NoteConcept::find(13)->forceDelete();
        NoteConcept::find(14)->forceDelete();
        NoteConcept::find(15)->forceDelete();
        NoteConcept::find(16)->forceDelete();
    }
}
