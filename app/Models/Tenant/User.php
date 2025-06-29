<?php

namespace App\Models\Tenant;

use Hyn\Tenancy\Traits\UsesTenantConnection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\LevelAccess\Models\ModuleLevel;
use Modules\Sale\Models\UserCommission;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;

class User extends Authenticatable
{
    use Notifiable, UsesTenantConnection;

    protected $with = ['establishment'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'establishment_id', 'type', 'prefix', 'locked', 'identity_document_type_id', 'number', 'address', 'telephone', 'fe_resolution_id', 'nc_resolution_id', 'nd_resolution_id', 'ni_resolution_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    public function modules()
    {
        return $this->belongsToMany(Module::class);
    }

    public function levels()
    {
        return $this->belongsToMany(ModuleLevel::class);
    }

    public function fe_resolution()
    {
        return $this->belongsTo(TypeDocument::class, 'fe_resolution_id');
    }

    public function nc_resolution()
    {
        return $this->belongsTo(TypeDocument::class, 'nc_resolution_id');
    }

    public function nd_resolution()
    {
        return $this->belongsTo(TypeDocument::class, 'nd_resolution_id');
    }

    public function ni_resolution()
    {
        return $this->belongsTo(TypeDocument::class, 'ni_resolution_id');
    }

    public function authorizeModules($modules)
    {
        if ($this->hasAnyModule($modules)) {
            return true;
        }
        abort(401, 'Esta acción no está autorizada.');
    }

    public function hasAnyModule($modules)
    {
        if (is_array($modules)) {
            foreach ($modules as $module)
            {
                if ($this->hasModule($module)) {
                    return true;
                }
            }
        } else {
            if ($this->hasModule($modules)) {
                return true;
            }
        }
        return false;
    }

    public function hasModule($module)
    {
        if ($this->modules()->where('name', $module)->first()) {
            return true;
        }
        return false;
    }

    public function getModule()
    {
        $module = $this->modules()->orderBy('id')->first();
        if ($module) {
            return $module->value;
        }
        return null;
    }

    public function getModules()
    {
        $modules = $this->modules()->get();
        if ($modules) {
            return $modules;
        }
        return null;
    }

    public function searchModule($module)
    {
        if ($this->modules()->where('value', $module)->first()) {
            return true;
        }
        return false;
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function sale_notes()
    {
        return $this->hasMany(SaleNote::class);
    }

    public function scopeWhereTypeUser($query)
    {
        $user = auth()->user();
        return ($user->type == 'seller') ? $query->where('id', $user->id) : null;
    }

    public function getLevel()
    {
        $level = $this->levels()->orderBy('id')->first();
        if ($level) {
            return $level->value;
        }
        return null;
    }

    public function getLevels()
    {
        $levels = $this->levels()->get();
        if ($levels) {
            return $levels;
        }
        return null;
    }

    public function searchLevel($level)
    {
        if ($this->levels()->where('value', $level)->first()) {
            return true;
        }
        return false;
    }

    public function user_commission()
    {
        return $this->hasOne(UserCommission::class);
    }

    /**
     * Retorna un arreglo con los ids de los modulos permitidos por el sistema
     *
     * @return array
     */
    public function getAllowedModulesForSystem()
    {
        // 1 - Ventas
        // 2 - Compras
        // 4 - Reportes
        // 5 - Configuración
        // 6 - Punto de venta (POS)
        // 7 - Dashboard
        // 8 - Inventario
        // 10 - Ecommerce
        // 12 - Finanzas
        // 13 - Nóminas
        // 20 - Factura del sector salud
        // 21 - RADIAN

        return [1,2,4,5,6,7,8,10,12,13,20,21];
    }

    public function scopeFilterWithOutRelations($query)
    {
        return $query->withOut(['establishment']);
    }

    /**
     *
     * Data para componente filtros
     *
     * @return array
     */
    public static function getDataForFilters()
    {
        return self::filterWithOutRelations()
                    ->select(['id', 'name'])
                    ->whereIn('type', ['admin', 'seller'])
                    ->get();
    }
}
