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

        // Verificar si la tabla está vacía o tiene muy pocos registros (menos de 5)
        $count = DB::table('co_payroll_type_document_identifications')->count();

        if ($count < 5) {
            // La tabla está vacía o incompleta, cargar todos los datos desde CSV
            // Eliminar registros existentes si los hay
            if ($count > 0) {
                DB::table('co_payroll_type_document_identifications')->delete();
            }

            // Intentar cargar con RegularizeDataHelper si existe
            if (class_exists('Modules\Factcolombia1\Helpers\RegularizeDataHelper')) {
                try {
                    \Modules\Factcolombia1\Helpers\RegularizeDataHelper::insertDataFromSeeder('co_payroll_type_document_identifications');
                } catch (\Exception $e) {
                    // Si falla, cargar manualmente los registros básicos
                    $this->loadBasicRecords();
                }
            } else {
                // Si RegularizeDataHelper no existe, cargar registros básicos manualmente
                $this->loadBasicRecords();
            }
        }

        // Verificar si el registro PPT ya existe
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
     * Cargar registros básicos manualmente si RegularizeDataHelper falla
     */
    protected function loadBasicRecords()
    {
        $records = [
            ['id' => 1, 'name' => 'Registro civil', 'code' => '11'],
            ['id' => 2, 'name' => 'Tarjeta de identidad', 'code' => '12'],
            ['id' => 3, 'name' => 'Cédula de ciudadanía', 'code' => '13'],
            ['id' => 4, 'name' => 'Tarjeta de extranjería', 'code' => '21'],
            ['id' => 5, 'name' => 'Cédula de extranjería', 'code' => '22'],
            ['id' => 6, 'name' => 'NIT', 'code' => '31'],
            ['id' => 7, 'name' => 'Pasaporte', 'code' => '41'],
            ['id' => 8, 'name' => 'Documento de identificación extranjero', 'code' => '42'],
            ['id' => 9, 'name' => 'PEP', 'code' => '47'],
            ['id' => 10, 'name' => 'NIT de otro país', 'code' => '50'],
        ];

        foreach ($records as $record) {
            $record['created_at'] = now();
            $record['updated_at'] = now();
            DB::table('co_payroll_type_document_identifications')->insert($record);
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
