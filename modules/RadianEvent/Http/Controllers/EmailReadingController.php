<?php

namespace Modules\RadianEvent\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Factcolombia1\Helpers\HttpConnectionApi;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany
};
use Exception;
use Modules\RadianEvent\Models\{
    ReceivedDocument,
    EmailReading,
};
use Modules\RadianEvent\Http\Resources\{
    EmailReadingCollection
};


class EmailReadingController extends Controller
{

    public function index()
    {
        return view('radianevent::process-emails.index');
    }

    public function columns()
    {
        return [
            'start_date' => 'Fecha inicio',
            'start_time' => 'Hora inicio',
            'email_user' => 'Correo',
        ];
    }


    public function details($id)
    {
        $record = EmailReading::with('details')->findOrFail($id);

        return $record->details->transform(function($row){
            return $row->getRowResource();
        });
    }


    public function records(Request $request)
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'desc');

        $validColumns = ['email_user', 'start_date', 'end_date', 'search_start_date', 'success', 'errors'];
        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = 'id';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $records = EmailReading::where($request->column, 'like', "%{$request->value}%")
            ->orderBy($sortColumn, $sortDirection);

        return new EmailReadingCollection($records->paginate(config('tenant.items_per_page')));
    }



}
