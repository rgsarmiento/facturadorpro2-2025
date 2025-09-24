<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PucTemplateExport implements FromArray, WithHeadings
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Datos de ejemplo básicos del PUC colombiano
        return [
            ['1', 'ACTIVO', 'activo', 'debito', '1', '', 'Representa todos los bienes y derechos de la empresa', '1', '0', '0'],
            ['11', 'DISPONIBLE', 'activo', 'debito', '2', '1', 'Efectivo y equivalentes de efectivo', '1', '0', '0'],
            ['1105', 'CAJA', 'activo', 'debito', '3', '11', 'Dinero en efectivo en caja', '1', '1', '0'],
            ['110505', 'CAJA GENERAL', 'activo', 'debito', '4', '1105', 'Caja principal de la empresa', '1', '1', '0'],
            ['110510', 'CAJAS MENORES', 'activo', 'debito', '4', '1105', 'Cajas menores para gastos menores', '1', '1', '0'],
            ['1110', 'BANCOS', 'activo', 'debito', '3', '11', 'Cuentas corrientes y de ahorros en bancos', '1', '0', '0'],
            ['111005', 'MONEDA NACIONAL', 'activo', 'debito', '4', '1110', 'Cuentas bancarias en pesos colombianos', '1', '1', '0'],
            ['111010', 'MONEDA EXTRANJERA', 'activo', 'debito', '4', '1110', 'Cuentas bancarias en moneda extranjera', '1', '1', '0'],
            ['12', 'INVERSIONES', 'activo', 'debito', '2', '1', 'Inversiones temporales y permanentes', '1', '0', '0'],
            ['1205', 'ACCIONES', 'activo', 'debito', '3', '12', 'Inversiones en acciones', '1', '1', '0'],
            ['13', 'DEUDORES', 'activo', 'debito', '2', '1', 'Cuentas por cobrar a terceros', '1', '0', '0'],
            ['1305', 'CLIENTES', 'activo', 'debito', '3', '13', 'Cuentas por cobrar a clientes', '1', '1', '1'],
            ['130505', 'CLIENTES NACIONALES', 'activo', 'debito', '4', '1305', 'Clientes del territorio nacional', '1', '1', '1'],
            ['130510', 'CLIENTES DEL EXTERIOR', 'activo', 'debito', '4', '1305', 'Clientes del exterior', '1', '1', '1'],
            ['14', 'INVENTARIOS', 'activo', 'debito', '2', '1', 'Mercancías y materias primas', '1', '0', '0'],
            ['1435', 'MERCANCÍAS NO FABRICADAS POR LA EMPRESA', 'activo', 'debito', '3', '14', 'Productos para la venta no fabricados', '1', '1', '0'],
            ['15', 'PROPIEDADES PLANTA Y EQUIPO', 'activo', 'debito', '2', '1', 'Activos fijos tangibles', '1', '0', '0'],
            ['1504', 'TERRENOS', 'activo', 'debito', '3', '15', 'Terrenos de propiedad de la empresa', '1', '1', '0'],
            ['1516', 'CONSTRUCCIONES Y EDIFICACIONES', 'activo', 'debito', '3', '15', 'Edificios y construcciones', '1', '1', '0'],
            ['1520', 'MAQUINARIA Y EQUIPO', 'activo', 'debito', '3', '15', 'Maquinaria y equipos de producción', '1', '1', '0'],
            ['1524', 'EQUIPO DE OFICINA', 'activo', 'debito', '3', '15', 'Equipos y muebles de oficina', '1', '1', '0'],
            ['1528', 'EQUIPO DE COMPUTACIÓN Y COMUNICACIÓN', 'activo', 'debito', '3', '15', 'Computadores y equipos de comunicación', '1', '1', '0'],
            ['2', 'PASIVO', 'pasivo', 'credito', '1', '', 'Obligaciones y deudas de la empresa', '1', '0', '0'],
            ['21', 'OBLIGACIONES FINANCIERAS', 'pasivo', 'credito', '2', '2', 'Préstamos y obligaciones bancarias', '1', '0', '0'],
            ['2105', 'BANCOS NACIONALES', 'pasivo', 'credito', '3', '21', 'Obligaciones con bancos nacionales', '1', '1', '1'],
            ['22', 'PROVEEDORES', 'pasivo', 'credito', '2', '2', 'Cuentas por pagar a proveedores', '1', '0', '0'],
            ['2205', 'PROVEEDORES NACIONALES', 'pasivo', 'credito', '3', '22', 'Deudas con proveedores nacionales', '1', '1', '1'],
            ['3', 'PATRIMONIO', 'patrimonio', 'credito', '1', '', 'Capital y utilidades de los socios', '1', '0', '0'],
            ['31', 'CAPITAL SOCIAL', 'patrimonio', 'credito', '2', '3', 'Aportes de los socios', '1', '0', '0'],
            ['3115', 'APORTES SOCIALES', 'patrimonio', 'credito', '3', '31', 'Capital aportado por socios', '1', '1', '0'],
            ['4', 'INGRESOS', 'ingreso', 'credito', '1', '', 'Ingresos operacionales y no operacionales', '1', '0', '0'],
            ['41', 'OPERACIONALES', 'ingreso', 'credito', '2', '4', 'Ingresos por la actividad principal', '1', '0', '0'],
            ['4135', 'COMERCIO AL POR MAYOR Y AL POR MENOR', 'ingreso', 'credito', '3', '41', 'Ventas de mercancías', '1', '1', '0'],
            ['5', 'GASTOS', 'gasto', 'debito', '1', '', 'Gastos operacionales y no operacionales', '1', '0', '0'],
            ['51', 'OPERACIONALES DE ADMINISTRACIÓN', 'gasto', 'debito', '2', '5', 'Gastos de administración', '1', '0', '0'],
            ['5105', 'GASTOS DE PERSONAL', 'gasto', 'debito', '3', '51', 'Gastos relacionados con personal', '1', '0', '0'],
            ['510506', 'SUELDOS', 'gasto', 'debito', '4', '5105', 'Sueldos de empleados', '1', '1', '1'],
            ['6', 'COSTOS DE VENTAS', 'costo', 'debito', '1', '', 'Costos directos de la mercancía vendida', '1', '0', '0'],
            ['61', 'COSTO DE VENTAS Y DE PRESTACIÓN DE SERVICIOS', 'costo', 'debito', '2', '6', 'Costo de productos vendidos', '1', '0', '0'],
            ['6135', 'COMERCIO AL POR MAYOR Y AL POR MENOR', 'costo', 'debito', '3', '61', 'Costo de mercancías vendidas', '1', '1', '0']
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'codigo',
            'nombre',
            'tipo_cuenta',
            'naturaleza',
            'nivel',
            'cuenta_padre_codigo',
            'descripcion',
            'activa',
            'permite_movimiento',
            'requiere_tercero'
        ];
    }
}
