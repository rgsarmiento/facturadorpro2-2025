<?php

namespace App\Models\Tenant;

// use App\Models\Tenant\Catalogs\Country;
// use App\Models\Tenant\Catalogs\Department;
// use App\Models\Tenant\Catalogs\District;
// use App\Models\Tenant\Catalogs\Province;


class Table extends ModelTenant
{

    protected $fillable = [
        'table_number',
        'table_state',
        'created_at',
        'establishment_id',
    ];

}
