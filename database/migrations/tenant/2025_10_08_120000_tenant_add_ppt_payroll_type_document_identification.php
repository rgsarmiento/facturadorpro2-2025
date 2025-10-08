<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class TenantAddPptPayrollTypeDocumentIdentification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Asegurarnos de que la tabla exista (en instalaciones antiguas podría no haberse creado aún)
        if (!Schema::hasTable('co_payroll_type_document_identifications')) {
            return; // No hacemos nada si la tabla no existe
        }

        $exists = DB::table('co_payroll_type_document_identifications')
            ->where('code', '48')
            ->exists();

        if (!$exists) {
            DB::table('co_payroll_type_document_identifications')->insert([
                'name'       => 'PPT (Permiso Protección Temporal)',
                'code'       => '48',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('co_payroll_type_document_identifications')) {
            return;
        }

        DB::table('co_payroll_type_document_identifications')
            ->where('code', '48')
            ->where('name', 'PPT (Permiso Protección Temporal)')
            ->delete();
    }
}
