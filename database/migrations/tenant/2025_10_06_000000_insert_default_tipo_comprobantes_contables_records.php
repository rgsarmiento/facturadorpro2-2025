<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertDefaultTipoComprobantesContablesRecords extends Migration
{
    /**
     * Run the migrations.
     * Insert (idempotente) los 24 tipos de comprobantes contables si no existen.
     *
     * Nota: Se mueve esta lógica aquí porque en el entorno multitenant el seeder no estaba ejecutándose
     * de forma confiable. Al estar en una migración dentro de database/migrations/tenant se garantiza
     * su ejecución para cada tenant al correr: php artisan tenancy:migrate
     */
    public function up()
    {
        if (!Schema::hasTable('tipo_comprobantes_contables')) {
            // La tabla aún no existe (orden de ejecución inesperado), salimos silenciosamente
            return;
        }

        $now = now();
        $records = [
            ['codigo' => '01', 'nombre' => 'Ajustes Contables', 'prefijo' => 'AJ'],
            ['codigo' => '02', 'nombre' => 'Comprobante de Egresos', 'prefijo' => 'CE'],
            ['codigo' => '03', 'nombre' => 'Comprobante de Ingreso', 'prefijo' => 'CI'],
            ['codigo' => '04', 'nombre' => 'Comprobante de Venta o Facturación', 'prefijo' => 'CV'],
            ['codigo' => '05', 'nombre' => 'Comprobante de Compras o Cuentas por Pagar', 'prefijo' => 'CC'],
            ['codigo' => '06', 'nombre' => 'Nota Crédito', 'prefijo' => 'NC'],
            ['codigo' => '07', 'nombre' => 'Nota Débito', 'prefijo' => 'ND'],
            ['codigo' => '08', 'nombre' => 'Depreciación', 'prefijo' => 'DP'],
            ['codigo' => '09', 'nombre' => 'Costeo', 'prefijo' => 'CS'],
            ['codigo' => '10', 'nombre' => 'Diferidos', 'prefijo' => 'DF'],
            ['codigo' => '11', 'nombre' => 'Legalización de Viáticos', 'prefijo' => 'LV'],
            ['codigo' => '12', 'nombre' => 'Legalización de Caja Menor', 'prefijo' => 'CM'],
            ['codigo' => '13', 'nombre' => 'Obligaciones Financieras', 'prefijo' => 'OF'],
            ['codigo' => '14', 'nombre' => 'Ajuste Contable de Cartera', 'prefijo' => 'AC'],
            ['codigo' => '15', 'nombre' => 'Nómina', 'prefijo' => 'NM'],
            ['codigo' => '16', 'nombre' => 'Comprobante de Consignación y Traslados', 'prefijo' => 'CT'],
            ['codigo' => '17', 'nombre' => 'Comprobante de Nómina', 'prefijo' => 'CN'],
            ['codigo' => '18', 'nombre' => 'Comprobante de Nómina Provisión y Seguridad Social', 'prefijo' => 'CP'],
            ['codigo' => '19', 'nombre' => 'Comprobante de Liquidación de Contrato', 'prefijo' => 'LC'],
            ['codigo' => '20', 'nombre' => 'Comprobante de Liquidación de Primas', 'prefijo' => 'LP'],
            ['codigo' => '21', 'nombre' => 'Comprobante de Liquidación de Cesantías', 'prefijo' => 'LCS'],
            ['codigo' => '22', 'nombre' => 'Comprobante de Desembolso Nómina', 'prefijo' => 'DN'],
            ['codigo' => '23', 'nombre' => 'Cierre de Año', 'prefijo' => 'CA'],
            ['codigo' => '24', 'nombre' => 'Saldos Iniciales', 'prefijo' => 'SI'],
        ];

        foreach ($records as $r) {
            // updateOrInsert mantiene idempotencia por 'codigo'
            DB::table('tipo_comprobantes_contables')->updateOrInsert(
                ['codigo' => $r['codigo']],
                [
                    'nombre' => $r['nombre'],
                    'prefijo' => $r['prefijo'],
                    // Si ya existe, respetamos su consecutivo_actual actual (no lo forzamos a 0)
                    'estado' => 'ACTIVO',
                    'updated_at' => $now,
                    // created_at sólo si es nuevo (si existe se mantendrá el original)
                    'created_at' => DB::raw('COALESCE(created_at, "'.$now.'")')
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     * Eliminar sólo los códigos que añadimos (para no afectar datos ajenos en environments productivos si se hace rollback intencional).
     */
    public function down()
    {
        if (!Schema::hasTable('tipo_comprobantes_contables')) {
            return;
        }
        DB::table('tipo_comprobantes_contables')->whereIn('codigo', [
            '01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24'
        ])->delete();
    }
}
