<?php

namespace Modules\Inventory\Models;

use App\Models\Tenant\Item;
use App\Models\Tenant\ModelTenant;

class ItemWarehouse extends ModelTenant
{
    protected $table = 'item_warehouse';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'stock',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getGlobalPurchaseUnitPrice()
    {
        return number_format($this->item->purchase_unit_price * $this->stock, 6, ".", "");
    }

    public function getGlobalSaleUnitPrice()
    {
        return number_format($this->item->sale_unit_price * $this->stock, 6, ".", "");
    }

    /**
     * Calcula el stock en una fecha específica basado en el kardex de inventario
     * @param string|null $date Fecha en formato Y-m-d
     * @return float Stock calculado hasta esa fecha
     */
    public function getStockByDate($date = null)
    {
        // Si no hay fecha, retornar el stock actual
        if (empty($date)) {
            return $this->stock;
        }

        // Calcular el stock sumando todos los movimientos del kardex hasta la fecha
        $stock = InventoryKardex::where('item_id', $this->item_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->whereDate('date_of_issue', '<=', $date)
            ->sum('quantity');

        return $stock;
    }

    // Scope para filtrar por fecha
    public function scopeWhereFilterDate($query, $date)
    {
        // Si no hay fecha, no filtrar (mostrar stock actual)
        if (empty($date)) {
            return $query;
        }

        // Si hay fecha, filtrar por items que tenían movimientos hasta esa fecha
        // Esto asegura que solo se muestren items que existían en esa fecha
        $query->whereHas('item', function($q) use ($date) {
            $q->whereHas('inventory_kardex', function($kardex) use ($date) {
                $kardex->whereDate('date_of_issue', '<=', $date);
            });
        });

        return $query;
    }

}
