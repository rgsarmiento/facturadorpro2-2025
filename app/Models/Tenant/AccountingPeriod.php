<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AccountingPeriod extends ModelTenant
{
    protected $table = 'accounting_periods';

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
        'allow_modifications',
        'closed_at',
        'closed_by',
        'closing_debit_balance',
        'closing_credit_balance',
        'closing_notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'allow_modifications' => 'boolean',
        'closing_debit_balance' => 'decimal:2',
        'closing_credit_balance' => 'decimal:2',
    ];

    // Relaciones

    public function asientosContables()
    {
        return $this->hasMany(AsientoContable::class, 'period_id');
    }

    public function saldosIniciales()
    {
        return $this->hasMany(AccountInitialBalance::class, 'period_id');
    }

    public function usuarioCierre()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Scopes

    public function scopeOpen(Builder $query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeLocked(Builder $query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeCurrent(Builder $query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now);
    }

    // Métodos de negocio

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    public function isLocked()
    {
        return $this->status === 'locked';
    }

    public function allowsModifications()
    {
        return $this->allow_modifications && $this->isOpen();
    }

    public function close($userId, $notes = null)
    {
        if ($this->isClosed() || $this->isLocked()) {
            throw new \Exception('El período ya está cerrado o bloqueado');
        }

        // Calcular totales de cierre
        $totals = $this->calculateClosingBalances();

        $this->status = 'closed';
        $this->allow_modifications = false;
        $this->closed_at = now();
        $this->closed_by = $userId;
        $this->closing_debit_balance = $totals['debit'];
        $this->closing_credit_balance = $totals['credit'];
        $this->closing_notes = $notes;
        $this->save();

        return true;
    }

    public function reopen()
    {
        if ($this->isLocked()) {
            throw new \Exception('El período está bloqueado y no puede reabrirse');
        }

        $this->status = 'open';
        $this->allow_modifications = true;
        $this->closed_at = null;
        $this->closed_by = null;
        $this->save();

        return true;
    }

    public function lock()
    {
        if (!$this->isClosed()) {
            throw new \Exception('Solo se pueden bloquear períodos cerrados');
        }

        $this->status = 'locked';
        $this->save();

        return true;
    }

    private function calculateClosingBalances()
    {
        $totals = DetalleAsientoContable::on('tenant')
            ->whereHas('asientoContable', function($q) {
                $q->where('period_id', $this->id)
                  ->where('estado', 'CONFIRMADO');
            })
            ->selectRaw('SUM(debito) as total_debit, SUM(credito) as total_credit')
            ->first();

        return [
            'debit' => $totals->total_debit ?? 0,
            'credit' => $totals->total_credit ?? 0,
        ];
    }

    public function getNameAttribute()
    {
        if ($this->month) {
            $monthName = Carbon::create()->month($this->month)->locale('es')->monthName;
            return ucfirst($monthName) . ' ' . $this->year;
        }
        return 'Año ' . $this->year;
    }

    public function containsDate($date)
    {
        $date = Carbon::parse($date);
        return $date->between($this->start_date, $this->end_date);
    }

    public static function findOrCreateCurrent()
    {
        $now = now();
        $year = $now->year;
        $month = $now->month;

        $period = self::where('year', $year)
                     ->where('month', $month)
                     ->first();

        if (!$period) {
            $period = self::create([
                'year' => $year,
                'month' => $month,
                'start_date' => $now->startOfMonth()->toDateString(),
                'end_date' => $now->endOfMonth()->toDateString(),
                'status' => 'open',
                'allow_modifications' => true,
            ]);
        }

        return $period;
    }
}
