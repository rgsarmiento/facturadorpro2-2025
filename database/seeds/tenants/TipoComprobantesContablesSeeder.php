<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoComprobantesContablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tipos_comprobantes = [
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

        foreach ($tipos_comprobantes as $tipo) {
            DB::table('tipo_comprobantes_contables')->updateOrInsert(
                ['codigo' => $tipo['codigo']],
                [
                    'nombre' => $tipo['nombre'],
                    'prefijo' => $tipo['prefijo'],
                    'consecutivo_actual' => DB::raw('COALESCE(consecutivo_actual,0)'),
                    'estado' => 'ACTIVO',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
