<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
//use Illuminate\Support\Str;
//use App\Http\Requests\Tenant\OrderRequest;
use App\Http\Resources\Tenant\OrderCollection;
use Exception;
use Illuminate\Http\Request;
use App\Models\Tenant\Order;
use App\Models\Tenant\ItemWarehouse;
use App\Http\Resources\Tenant\ItemWarehouseCollection;

class OrderController extends Controller
{
    public function index()
    {
        return view('tenant.orders.index');
    }

    public function columns()
    {
        return [
            'id' => 'Codigo de Pedido',
            'number_document' => 'Comprobante Electronico',
        ];
    }

    public function records(Request $request)
    {
        $records = Order::where($request->column, 'like', "%{$request->value}%");

        // Aplicar ordenamiento
        if ($request->has('sort_column') && $request->sort_column) {
            $sortColumn = $request->sort_column;
            $sortDirection = $request->sort_direction ?? 'asc';
            $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc';

            $numericColumns = ['total'];
            if (in_array($sortColumn, $numericColumns)) {
                $records = $records->orderByRaw("CAST({$sortColumn} AS DECIMAL(10,2)) {$sortDirection}");
            } else {
                $records = $records->orderBy($sortColumn, $sortDirection);
            }
        } else {
            $records = $records->latest();
        }

        return new OrderCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function updateStatusOrders(Request $request)
    {

      // if ($request->record['status_order_id'] == 3) {
      //   for ($i=0; $i <= count($request->discount)-1; $i++) {
      //     if (isset($request->discount[$i]['id'])) {
      //       $itemWarehouse = ItemWarehouse::where('id', $request->discount[$i]['id'])->first();

      //       //if ($itemWarehouse->stock >= $request->discount[$i]['cantidad']) {
      //         ItemWarehouse::where('id', $itemWarehouse->id)->update(['stock' => ($itemWarehouse->stock - $request->discount[$i]['cantidad'])]);

      //       //}
      //     }
      //   }
      //   Order::where('id', $request->record['id'])->update(['status_order_id' => $request->record['status_order_id']]);

      //   return [
      //     'message' => 'Estatus y Stock actualizado'
      //   ];
      // }

      Order::where('id', $request->record['id'])->update(['status_order_id' => $request->record['status_order_id']]);
      return [
        'message' => 'Estatus actualizado'
      ];

    }

    public function searchWarehouse(Request $request)
    {
      $product = ItemWarehouse::whereIn('item_id', $request->item_id)->orderBy('item_id')->get();
      return new ItemWarehouseCollection($product);
    }

}
