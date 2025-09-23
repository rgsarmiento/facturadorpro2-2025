<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaContable extends ModelTenant
{
    // use SoftDeletes; // Comentado temporalmente - agregar columna deleted_at si se quiere usar

    protected $table = 'cuentas_contables';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_cuenta',
        'naturaleza',
        'nivel',
        'cuenta_padre_id',
        'descripcion',
        'activa',
        'permite_movimiento',
        'saldo_inicial',
        'saldo_actual',
        'codigo_niif',
        'configuracion_adicional'
    ];

    protected $casts = [
        'activa' => 'boolean',
        'permite_movimiento' => 'boolean',
        'saldo_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'configuracion_adicional' => 'array',
        'nivel' => 'integer'
    ];

    // Enums para validaciones
    public const TIPOS_CUENTA = [
        'activo',
        'pasivo',
        'patrimonio',
        'ingreso',
        'gasto',
        'costo'
    ];

    public const NATURALEZAS = [
        'debito',
        'credito'
    ];

    // Mapeo de naturaleza por tipo de cuenta según normas contables
    public const NATURALEZA_POR_TIPO = [
        'activo' => 'debito',
        'gasto' => 'debito',
        'costo' => 'debito',
        'pasivo' => 'credito',
        'patrimonio' => 'credito',
        'ingreso' => 'credito'
    ];

    /**
     * Relación con cuenta padre (auto-relación)
     */
    public function cuentaPadre()
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_padre_id');
    }

    /**
     * Relación con cuentas hijas
     */
    public function cuentasHijas()
    {
        return $this->hasMany(CuentaContable::class, 'cuenta_padre_id');
    }

    /**
     * Obtener todas las cuentas descendientes (recursivo)
     */
    public function descendientes()
    {
        return $this->cuentasHijas()->with('descendientes');
    }

    /**
     * Obtener todas las cuentas ascendientes hasta la raíz
     */
    public function ascendientes()
    {
        $ascendientes = collect();
        $cuenta = $this->cuentaPadre;

        while ($cuenta) {
            $ascendientes->push($cuenta);
            $cuenta = $cuenta->cuentaPadre;
        }

        return $ascendientes;
    }

    /**
     * Validar si es cuenta de movimiento (hoja del árbol)
     */
    public function esCuentaMovimiento()
    {
        return $this->cuentasHijas()->count() === 0 && $this->permite_movimiento;
    }

    /**
     * Obtener el path completo de la cuenta
     */
    public function getPathCompleto()
    {
        $path = collect([$this->nombre]);
        $cuenta = $this->cuentaPadre;

        while ($cuenta) {
            $path->prepend($cuenta->nombre);
            $cuenta = $cuenta->cuentaPadre;
        }

        return $path->implode(' > ');
    }

    /**
     * Scope para filtrar por tipo de cuenta
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_cuenta', $tipo);
    }

    /**
     * Scope para filtrar cuentas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    /**
     * Scope para filtrar cuentas que permiten movimiento
     */
    public function scopeMovimiento($query)
    {
        return $query->where('permite_movimiento', true);
    }

    /**
     * Scope para filtrar por nivel
     */
    public function scopeNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Scope para obtener cuentas raíz (sin padre)
     */
    public function scopeRaiz($query)
    {
        return $query->whereNull('cuenta_padre_id');
    }

    /**
     * Obtener la naturaleza correcta según el tipo de cuenta
     */
    public static function getNaturalezaPorTipo($tipoCuenta)
    {
        return self::NATURALEZA_POR_TIPO[$tipoCuenta] ?? null;
    }

    /**
     * Validaciones personalizadas
     */
    public static function rules($id = null)
    {
        return [
            'codigo' => 'required|string|max:20',
            'nombre' => 'required|string|max:255',
            'tipo_cuenta' => 'required|in:' . implode(',', self::TIPOS_CUENTA),
            'naturaleza' => 'required|in:' . implode(',', self::NATURALEZAS),
            'nivel' => 'required|integer|min:1|max:10',
            'cuenta_padre_id' => 'nullable|integer',
            'descripcion' => 'nullable|string',
            'activa' => 'boolean',
            'permite_movimiento' => 'boolean',
            'saldo_inicial' => 'numeric|between:-999999999999.99,999999999999.99',
            'saldo_actual' => 'numeric|between:-999999999999.99,999999999999.99',
            'codigo_niif' => 'nullable|string|max:20',
            'configuracion_adicional' => 'nullable|array'
        ];
    }

    /**
     * Validar consistencia de jerarquía
     */
    public function validarJerarquia()
    {
        $errores = [];

        // Validar que la naturaleza sea consistente con el tipo
        $naturalezaEsperada = self::getNaturalezaPorTipo($this->tipo_cuenta);
        if ($this->naturaleza !== $naturalezaEsperada) {
            $errores[] = "La naturaleza '{$this->naturaleza}' no es consistente con el tipo de cuenta '{$this->tipo_cuenta}'. Debe ser '{$naturalezaEsperada}'.";
        }

        // Validar nivel con respecto al padre
        if ($this->cuentaPadre) {
            if ($this->nivel !== ($this->cuentaPadre->nivel + 1)) {
                $errores[] = "El nivel debe ser " . ($this->cuentaPadre->nivel + 1) . " para mantener la jerarquía.";
            }

            // Validar que el tipo sea compatible con el padre
            if ($this->tipo_cuenta !== $this->cuentaPadre->tipo_cuenta) {
                $errores[] = "El tipo de cuenta debe ser igual al de la cuenta padre.";
            }
        } else {
            // Cuenta raíz debe ser nivel 1
            if ($this->nivel !== 1) {
                $errores[] = "Las cuentas raíz deben tener nivel 1.";
            }
        }

        return $errores;
    }

    /**
     * Actualizar saldo actual
     */
    public function actualizarSaldo($monto, $tipoMovimiento = 'debito')
    {
        if ($tipoMovimiento === $this->naturaleza) {
            $this->saldo_actual += $monto;
        } else {
            $this->saldo_actual -= $monto;
        }

        $this->save();
    }

    /**
     * Boot del modelo para validaciones automáticas
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cuenta) {
            // Validar jerarquía antes de guardar
            $errores = $cuenta->validarJerarquia();
            if (!empty($errores)) {
                throw new \InvalidArgumentException(implode(' ', $errores));
            }

            // Si no se especifica naturaleza, asignar automáticamente
            if (!$cuenta->naturaleza && $cuenta->tipo_cuenta) {
                $cuenta->naturaleza = self::getNaturalezaPorTipo($cuenta->tipo_cuenta);
            }
        });

        static::deleting(function ($cuenta) {
            // Prevenir eliminación si tiene cuentas hijas
            if ($cuenta->cuentasHijas()->count() > 0) {
                throw new \InvalidArgumentException('No se puede eliminar una cuenta que tiene cuentas hijas.');
            }

            // Prevenir eliminación si tiene movimientos (esto se implementaría cuando se creen los movimientos contables)
            // if ($cuenta->movimientos()->count() > 0) {
            //     throw new \InvalidArgumentException('No se puede eliminar una cuenta que tiene movimientos contables.');
            // }
        });
    }
}
