<?php
namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\Catalogs\Department;
use App\Models\Tenant\Catalogs\District;
use App\Models\Tenant\Catalogs\Province;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Table;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\EstablishmentRequest;
use App\Http\Resources\Tenant\EstablishmentResource;
use App\Http\Resources\Tenant\EstablishmentCollection;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;

use Modules\Factcolombia1\Models\Tenant\{
    TypeIdentityDocument,
    TypePerson,
    TypeRegime,
    Country,
};


class EstablishmentController extends Controller
{
    public function index()
    {
        return view('tenant.establishments.index');
    }

    public function create()
    {
        return view('tenant.establishments.form');
    }

    public function tables()
    {
        $countries = Country::get();
        // $departments = Department::whereActive()->orderByDescription()->get();
        // $provinces = Province::whereActive()->orderByDescription()->get();
        // $districts = District::whereActive()->orderByDescription()->get();

        return compact('countries');
    }

    public function record($id)
    {
        $record = new EstablishmentResource(Establishment::findOrFail($id));
        return $record;
    }

    public function store(EstablishmentRequest $request)
    {
        $id = $request->input('id');
        $establishment = Establishment::firstOrNew(['id' => $id]);
        $establishment->fill($request->all());
        $establishment->save();

        if(!$id) {
            $warehouse = new Warehouse();
            $warehouse->establishment_id = $establishment->id;
            $warehouse->description = 'Almacén - '.$establishment->description;
            $warehouse->save();
        }

        $tables_quantity = Table::where('establishment_id', $establishment->id)->count();

        if ($request->has('tables') && is_numeric($request->tables) && $request->tables > 0) {
            $tablesData = [];
            $tablesDataUpdate = [];

            if ($tables_quantity == 0) {
                for ($i = 1; $i <= $request->tables; $i++) {
                    $tablesData[] = [
                        'table_number' => $i,
                        'table_state' => 'A',
                        'establishment_id' => $establishment->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Table::insert($tablesData);
            } else {
                for ($i = 1; $i <= $request->tables; $i++) {
                    if ($i <= $tables_quantity) {

                        $tablesDataUpdate[] = [
                            'table_number' => $i,
                            'table_state' => 'A', // Activa
                            'establishment_id' => $establishment->id,
                            'updated_at' => now(),
                        ];
                    } else {

                        $tablesData[] = [
                            'table_number' => $i,
                            'table_state' => 'A', // Activa
                            'establishment_id' => $establishment->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                foreach ($tablesDataUpdate as $data) {
                    Table::where([
                        ['establishment_id', '=', $data['establishment_id']],
                        ['table_number', '=', $data['table_number']]
                    ])->update(['table_state' => $data['table_state'], 'updated_at' => $data['updated_at']]);
                }

                if (!empty($tablesData)) {
                    Table::insert($tablesData);
                }

                Table::where('establishment_id', $establishment->id)
                    ->where('table_number', '>', $request->tables)
                    ->update(['table_state' => 'I', 'updated_at' => now()]);
            }
        }

        return [
            'success' => true,
            'message' => ($id)?'Establecimiento actualizado':'Establecimiento registrado'
        ];
    }

    public function records(Request $request)
    {
        $query = Establishment::query();

        // Aplicar ordenamiento si se proporciona
        if ($request->has('sort_column') && $request->sort_column) {
            $sortColumn = $request->sort_column;
            $sortDirection = in_array($request->sort_direction, ['asc', 'desc']) ? $request->sort_direction : 'asc';

            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('description', 'asc');
        }

        $records = $query->get();
        return new EstablishmentCollection($records);
    }

    public function destroy($id)
    {
        $establishment = Establishment::findOrFail($id);
        $establishment->delete();

        return [
            'success' => true,
            'message' => 'Establecimiento eliminado con éxito'
        ];
    }
}
