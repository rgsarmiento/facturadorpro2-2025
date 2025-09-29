<?php

namespace App\Models\Tenant;

// use App\Models\Tenant\Catalogs\Country;
// use App\Models\Tenant\Catalogs\Department;
// use App\Models\Tenant\Catalogs\District;
// use App\Models\Tenant\Catalogs\Province;


class TableAccount extends ModelTenant
{

    protected $fillable = [
        'account',
        'state',
        'price',
        'quantity',
        'item_id',
        'item_descripcion',
        'comentario',
        'created_at',
        'updated_at',
        'user_id'
    ];
}
