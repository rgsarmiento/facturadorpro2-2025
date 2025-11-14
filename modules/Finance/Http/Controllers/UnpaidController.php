<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Finance\Models\GlobalPayment;
use App\Models\Tenant\Cash;
use App\Models\Tenant\BankAccount;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Tenant\Company;
use Modules\Finance\Traits\FinanceTrait;
use Modules\Finance\Http\Resources\GlobalPaymentCollection;
use Modules\Finance\Exports\BalanceExport;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Tenant\Establishment;
use Carbon\Carbon;
use App\Models\Tenant\Person;
use Modules\Dashboard\Helpers\DashboardView;
use App\Exports\AccountsReceivable;

class UnpaidController extends Controller
{

    use FinanceTrait;

    public function index(){

        return view('finance::unpaid.index');
    }


    public function filter(){

        $customers = Person::whereType('customers')->orderBy('name')->take(100)->get()->transform(function($row) {
            return [
                'id' => $row->id,
                'description' => $row->number.' - '.$row->name,
                'name' => $row->name,
                'number' => $row->number,
                'identity_document_type_id' => $row->identity_document_type_id,
            ];
        });

        $establishments = DashboardView::getEstablishments();

        return compact('customers', 'establishments');
    }


    public function records(Request $request)
    {
        $records = (new DashboardView())->getUnpaid($request->all());

        // Aplicar ordenamiento
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'asc');

        $validColumns = ['date_of_issue', 'date_of_due', 'number_full', 'customer_name', 'delay_payment', 'total_to_pay', 'total'];

        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = null;
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        if ($sortColumn) {
            $records = collect($records)->sortBy(function($item) use ($sortColumn) {
                $value = $item[$sortColumn] ?? 0;
                // Convertir a número si es necesario
                return is_numeric($value) ? (float)$value : $value;
            });

            if ($sortDirection === 'desc') {
                $records = $records->reverse();
            }
        }

        return [
            'records' => $records
        ];

    }

    public function unpaidall()
    {

        return Excel::download(new AccountsReceivable, 'Allclients.xlsx');

    }


}
