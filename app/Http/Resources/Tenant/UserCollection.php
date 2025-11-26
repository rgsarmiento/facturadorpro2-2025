<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {
            $type = '';
            switch ($row->type) {
                case 'admin':
                    $type =  'Administrador' ;
                    break;
                case 'seller':
                    $type =  'Vendedor' ;
                        break;
                case 'client':
                    $type =  'Cliente' ;
                    break;
                default:
                    # code...
                    break;
            }
            return [
                'id' => $row->id,
                'email' => $row->email,
                'name' => $row->name,
                'api_token' => $row->api_token,
                'establishment_description' => optional($row->establishment)->description,
                'type' => $type,
                'prefix' => $row->prefix,
                'locked' => (bool) $row->locked,
                'active' => (bool) $row->active,
                'fe_resolution_id' => $row->fe_resolution_id,
                'nc_resolution_id' => $row->nc_resolution_id,
                'nd_resolution_id' => $row->nd_resolution_id,
                'ni_resolution_id' => $row->ni_resolution_id,
            ];
        });
    }
}
