<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Tenant\CuentaContable;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LoadDefaultPlanCuentas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Solo ejecutar si no hay cuentas contables existentes
        if (CuentaContable::count() == 0) {
            $this->loadPlanCuentasFromExcel();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar todas las cuentas contables
        CuentaContable::truncate();
    }

    /**
     * Cargar el plan de cuentas desde el archivo Excel
     */
    private function loadPlanCuentasFromExcel()
    {
        try {
            $filePath = public_path('formats/PUC_inicial.xlsx');

            if (!file_exists($filePath)) {
                echo "Archivo PUC_inicial.xlsx no encontrado, saltando carga inicial...\n";
                return;
            }

            echo "Cargando plan de cuentas inicial desde Excel...\n";

            // Deshabilitar validaciones del modelo durante la importación
            CuentaContable::$skipValidationOnSaving = true;

            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $cuentasData = [];
            $highestRow = $worksheet->getHighestRow();

            // Leer datos del Excel
            for ($row = 2; $row <= $highestRow; $row++) {
                $codigo = trim($worksheet->getCell('A' . $row)->getCalculatedValue() ?? '');

                if (empty($codigo)) continue;

                $cuentasData[] = [
                    'fila' => $row,
                    'codigo' => $codigo,
                    'nombre' => trim($worksheet->getCell('B' . $row)->getCalculatedValue() ?? ''),
                    'tipo_cuenta' => strtolower(trim($worksheet->getCell('C' . $row)->getCalculatedValue() ?? '')),
                    'naturaleza' => strtolower(trim($worksheet->getCell('D' . $row)->getCalculatedValue() ?? '')),
                    'nivel' => (int)trim($worksheet->getCell('E' . $row)->getCalculatedValue() ?? 1),
                    'codigo_padre' => trim($worksheet->getCell('F' . $row)->getCalculatedValue() ?? ''),
                    'descripcion' => trim($worksheet->getCell('G' . $row)->getCalculatedValue() ?? ''),
                    'activa' => filter_var(trim($worksheet->getCell('H' . $row)->getCalculatedValue() ?? 'true'), FILTER_VALIDATE_BOOLEAN),
                    'permite_movimiento' => filter_var(trim($worksheet->getCell('I' . $row)->getCalculatedValue() ?? 'true'), FILTER_VALIDATE_BOOLEAN),
                    'requiere_tercero' => filter_var(trim($worksheet->getCell('J' . $row)->getCalculatedValue() ?? 'false'), FILTER_VALIDATE_BOOLEAN),
                ];
            }

            echo "Datos leídos: " . count($cuentasData) . " cuentas\n";

            // Ordenar por nivel para procesar padres antes que hijos
            usort($cuentasData, function($a, $b) {
                if ($a['nivel'] == $b['nivel']) {
                    return strcmp($a['codigo'], $b['codigo']);
                }
                return $a['nivel'] - $b['nivel'];
            });

            $cuentasImportadas = 0;
            $errores = [];

            // FASE 1: Crear todas las cuentas sin padre
            foreach ($cuentasData as $cuentaData) {
                try {
                    if (empty($cuentaData['codigo']) || empty($cuentaData['nombre'])) {
                        continue;
                    }

                    // Normalizar tipo de cuenta
                    $tipoNormalizado = $this->normalizarTipoCuenta($cuentaData['tipo_cuenta']);
                    if (!$tipoNormalizado) {
                        $errores[] = "Fila {$cuentaData['fila']}: Tipo de cuenta inválido: {$cuentaData['tipo_cuenta']}";
                        continue;
                    }

                    $cuenta = new CuentaContable();
                    $cuenta->codigo = $cuentaData['codigo'];
                    $cuenta->nombre = $cuentaData['nombre'];
                    $cuenta->tipo_cuenta = $tipoNormalizado;
                    $cuenta->naturaleza = $cuentaData['naturaleza'];
                    $cuenta->nivel = $cuentaData['nivel'];
                    $cuenta->descripcion = $cuentaData['descripcion'];
                    $cuenta->activa = $cuentaData['activa'];
                    $cuenta->permite_movimiento = $cuentaData['permite_movimiento'];
                    $cuenta->requiere_tercero = $cuentaData['requiere_tercero'];
                    $cuenta->saldo_inicial = 0;
                    $cuenta->saldo_actual = 0;

                    $cuenta->save();
                    $cuentasImportadas++;

                } catch (\Exception $e) {
                    $errores[] = "Fila {$cuentaData['fila']}: " . $e->getMessage();
                }
            }

            // FASE 2: Asignar relaciones padre-hijo
            foreach ($cuentasData as $cuentaData) {
                if (!empty($cuentaData['codigo_padre'])) {
                    try {
                        $cuenta = CuentaContable::where('codigo', $cuentaData['codigo'])->first();
                        $cuentaPadre = CuentaContable::where('codigo', $cuentaData['codigo_padre'])->first();

                        if ($cuenta && $cuentaPadre) {
                            $cuenta->cuenta_padre_id = $cuentaPadre->id;
                            $cuenta->save();
                        }
                    } catch (\Exception $e) {
                        $errores[] = "Error asignando padre para {$cuentaData['codigo']}: " . $e->getMessage();
                    }
                }
            }

            // Reestablecer validaciones
            CuentaContable::$skipValidationOnSaving = false;

            echo "Plan de cuentas cargado exitosamente: {$cuentasImportadas} cuentas\n";
            if (!empty($errores)) {
                echo "Errores encontrados: " . count($errores) . "\n";
                foreach (array_slice($errores, 0, 5) as $error) {
                    echo "- " . $error . "\n";
                }
            }

        } catch (\Exception $e) {
            // Reestablecer validaciones en caso de error
            CuentaContable::$skipValidationOnSaving = false;
            echo "Error cargando plan de cuentas: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Normalizar tipo de cuenta
     */
    private function normalizarTipoCuenta($tipo)
    {
        $tipo = strtolower(trim($tipo));

        // Mapeo completo de tipos de cuenta
        $mapeoTipos = [
            // Tipos básicos
            'activo' => 'activo',
            'pasivo' => 'pasivo',
            'patrimonio' => 'patrimonio',
            'ingreso' => 'ingreso',
            'gasto' => 'gasto',
            'costo' => 'costo',

            // Variaciones plurales
            'activos' => 'activo',
            'pasivos' => 'pasivo',
            'ingresos' => 'ingreso',
            'gastos' => 'gasto',
            'costos' => 'costo',

            // Tipos específicos de costos
            'costos de venta' => 'costo',
            'costos de ventas' => 'costo',
            'costo de venta' => 'costo',
            'costo de ventas' => 'costo',
            'costos de produccion' => 'costo',
            'costos de producción' => 'costo',
            'costos de operacion' => 'costo',
            'costos de operación' => 'costo',
            'costos de produccion o de operacion' => 'costo',
            'costos de producción o de operación' => 'costo',

            // Cuentas de orden (las tratamos como activos por defecto)
            'cuentas de orden' => 'activo',
            'cuentas de orden deudoras' => 'activo',
            'cuentas de orden acreedoras' => 'pasivo',
            'cuenta de orden' => 'activo',
            'cuenta de orden deudora' => 'activo',
            'cuenta de orden acreedora' => 'pasivo',

            // Mapeo con caracteres especiales corruptos
            hex2bin('636f73746f732064652070726f6475636369e3b36e206f206465206f706572616369e3b36e') => 'costo',
        ];

        return $mapeoTipos[$tipo] ?? null;
    }
}
