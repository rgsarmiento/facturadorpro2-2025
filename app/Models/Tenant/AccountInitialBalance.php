<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;

class AccountInitialBalance extends ModelTenant
{
    protected $table = 'account_initial_balances';

    protected $fillable = [
        'cuenta_contable_id',
        'person_id',
        'period_id',
        'balance_date',
        'debito',
        'credito',
        'balance',
        'asiento_contable_id',
        'status',
        'is_posted',
        'posted_at',
        'notes',
        'created_by',
        'posted_by',
    ];

    protected $casts = [
        'balance_date' => 'date',
        'debito' => 'decimal:2',
        'credito' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    // Relaciones

    public function cuentaContable()
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_contable_id');
    }

    public function tercero()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function asientoContable()
    {
        return $this->belongsTo(AsientoContable::class, 'asiento_contable_id');
    }

    public function usuarioCreacion()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usuarioContabilizacion()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // Scopes

    public function scopeDraft(Builder $query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted(Builder $query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeByPeriod(Builder $query, $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopeByAccount(Builder $query, $accountId)
    {
        return $query->where('cuenta_contable_id', $accountId);
    }

    // Métodos de negocio

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPosted()
    {
        return $this->status === 'posted';
    }

    public function calculateBalance()
    {
        // Calcular saldo según naturaleza de la cuenta
        $cuenta = $this->cuentaContable;

        if (!$cuenta) {
            return 0;
        }

        if ($cuenta->naturaleza === 'debito') {
            // Para cuentas deudoras: Débito - Crédito
            return $this->debito - $this->credito;
        } else {
            // Para cuentas acreedoras: Crédito - Débito
            return $this->credito - $this->debito;
        }
    }

    public function validate()
    {
        $errors = [];

        // Validar cuenta existe
        if (!$this->cuentaContable) {
            $errors[] = 'La cuenta contable no existe';
        }

        // Validar cuenta permite movimiento
        if ($this->cuentaContable && !$this->cuentaContable->permite_movimiento) {
            $errors[] = "La cuenta {$this->cuentaContable->codigo} no permite movimientos directos";
        }

        // Validar tercero obligatorio
        if ($this->cuentaContable && $this->cuentaContable->requiere_tercero && !$this->person_id) {
            $errors[] = "La cuenta {$this->cuentaContable->codigo} requiere un tercero";
        }

        // Validar que no haya débito y crédito simultáneos
        if ($this->debito > 0 && $this->credito > 0) {
            $errors[] = 'No se puede tener débito y crédito simultáneamente';
        }

        // Validar que al menos uno tenga valor
        if ($this->debito == 0 && $this->credito == 0) {
            $errors[] = 'Debe registrar un valor en débito o crédito';
        }

        return empty($errors) ? true : $errors;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-calcular balance antes de guardar
            $model->balance = $model->calculateBalance();
        });
    }
}
