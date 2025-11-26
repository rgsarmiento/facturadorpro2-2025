<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UserRequest;
use App\Http\Resources\Tenant\UserResource;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Module;
use App\Models\Tenant\User;
use App\Models\Tenant\ConfigurationPos;
use App\Http\Resources\Tenant\UserCollection;
use Modules\LevelAccess\Models\ModuleLevel;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('tenant.users.index');
    }

    public function record(User $user = null)
    {
        if (!$user) {
            $user = auth()->user(); // Devuelve el usuario autenticado si no se proporciona un usuario específico
        }
        return new UserResource($user);
    }

    public function tables()
    {
        $modules = Module::whereIn('id', auth()->user()->getAllowedModulesForSystem())
            ->with(['levels' => function($query){
                $query->whereIn('id', [1,2,5,7,8,9,10]);
            }])
            ->orderBy('description')
            ->get();

        $establishments = Establishment::orderBy('description')->get();
        $types = [['type' => 'admin', 'description'=>'Administrador'], ['type' => 'seller', 'description'=>'Vendedor'], ['type' => 'comand', 'description'=>'Comandero']];;
        $prefixs = ConfigurationPos::select('prefix')->distinct()->get();
        $fe_resolutions = TypeDocument::where('code', 1)->where('id', '!=', 1)->selectRaw("*, CONCAT(COALESCE(prefix, ''), ' / ', COALESCE(resolution_number, ''), ' / ', COALESCE(`from`, ''), ' / ', COALESCE(`to`, ''), ' / ', COALESCE(resolution_date_end, '')) as description")->orderBy('prefix')->get();
        $nc_resolutions = TypeDocument::where('code', 4)->selectRaw("*, CONCAT(COALESCE(prefix, ''), ' / ', COALESCE(resolution_number, ''), ' / ', COALESCE(`from`, ''), ' / ', COALESCE(`to`, ''), ' / ', COALESCE(resolution_date_end, '')) as description")->orderBy('prefix')->get();
        $nd_resolutions = TypeDocument::where('code', 5)->selectRaw("*, CONCAT(COALESCE(prefix, ''), ' / ', COALESCE(resolution_number, ''), ' / ', COALESCE(`from`, ''), ' / ', COALESCE(`to`, ''), ' / ', COALESCE(resolution_date_end, '')) as description")->orderBy('prefix')->get();
        $ni_resolutions = TypeDocument::where('code', 9)->selectRaw("*, CONCAT(COALESCE(prefix, ''), ' / ', COALESCE(resolution_number, ''), ' / ', COALESCE(`from`, ''), ' / ', COALESCE(`to`, ''), ' / ', COALESCE(resolution_date_end, '')) as description")->orderBy('prefix')->get();
        $today = date('Y-m-d');
        $fe_resolutions->each(function($item) use ($today) {
            $item->vencida = ($item->resolution_date_end === null) ? false : ($item->resolution_date_end < $today);
        });
        $nc_resolutions->each(function($item) use ($today) {
            $item->vencida = ($item->resolution_date_end === null) ? false : ($item->resolution_date_end < $today);
        });
        $nd_resolutions->each(function($item) use ($today) {
            $item->vencida = ($item->resolution_date_end === null) ? false : ($item->resolution_date_end < $today);
        });
        $ni_resolutions->each(function($item) use ($today) {
            $item->vencida = ($item->resolution_date_end === null) ? false : ($item->resolution_date_end < $today);
        });
        return compact('modules', 'establishments','types','prefixs', 'fe_resolutions', 'nc_resolutions', 'nd_resolutions', 'ni_resolutions');
    }

    public function store(UserRequest $request)
    {
        $id = $request->input('id');
        if(!$id)  //VALIDAR EMAIL DISPONIBLE
        {
            $verify = User::where('email', $request->input('email'))->first();
            if($verify)
            {
                return [
                    'success' => false,
                    'message' => 'Email no disponible. Ingrese otro Email'
                ];
            }
        }
        $user = User::firstOrNew(['id' => $id]);
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->establishment_id = $request->input('establishment_id');
        $user->fe_resolution_id = $request->input('fe_resolution_id');
        $user->nc_resolution_id = $request->input('nc_resolution_id');
        $user->nd_resolution_id = $request->input('nd_resolution_id');
        $user->ni_resolution_id = $request->input('ni_resolution_id');
        $user->type = $request->input('type');
        $user->prefix = $request->input('prefix');

        // Establecer active como true por defecto al crear nuevo usuario
        if (!$id) {
            $user->active = true;
            $user->api_token = str_random(50);
            $user->password = bcrypt($request->input('password'));
        }
        elseif ($request->has('password')) {
            if (config('tenant.password_change')) {
                $user->password = bcrypt($request->input('password'));
            }
        }
        $user->save();
        $modules = collect($request->input('modules'))->where('checked', true)->pluck('id')->toArray();
        $user->modules()->sync($modules);
        $levels = collect($request->input('levels'))->where('checked', true)->pluck('id')->toArray();
        $user->levels()->sync($levels);
        // dd($user->getModules()->transform(function($row, $key) {
        //     return [
        //         'id' => $row->id,
        //         'privot_id' => $row->pivot,
        //         'privot_user' => $row->pivot->user_id,
        //         'privot_module' => $row->pivot->module_id,
        //     ];
        // }));
        return [
            'success' => true,
            'message' => ($id) ? 'Usuario actualizado' : 'Usuario registrado'
        ];
    }

    public function records(Request $request)
    {
        $query = User::query();

        // Aplicar ordenamiento si se proporciona
        if ($request->has('sort_column') && $request->sort_column) {
            $sortColumn = $request->sort_column;
            $sortDirection = in_array($request->sort_direction, ['asc', 'desc']) ? $request->sort_direction : 'asc';

            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }

        $records = $query->get();
        return new UserCollection($records);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return [
            'success' => true,
            'message' => 'Usuario eliminado con éxito'
        ];
    }

    /**
     *
     * Data para componente filtros
     *
     * @return array
     */
    public function searchData()
    {
        return User::getDataForFilters();
    }

    /**
     * Cambiar el estado activo/inactivo de un usuario
     * Solo el administrador puede cambiar estados
     * El usuario administrador (id = 1) no puede ser desactivado
     */
    public function toggleActive(Request $request, $id)
    {
        // Verificar que el usuario autenticado sea administrador
        if (auth()->user()->type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Solo el administrador puede cambiar el estado de los usuarios'
            ], 403);
        }

        // No permitir desactivar al usuario administrador (id = 1)
        if ($id == 1) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario administrador no puede ser desactivado'
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->active = !$user->active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->active ? 'Usuario activado' : 'Usuario desactivado',
            'active' => $user->active
        ]);
    }

    /**
     * Verificar el estado activo del usuario autenticado
     */
    public function checkStatus(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'active' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $active = isset($user->active) ? (bool)$user->active : true;

        return response()->json([
            'active' => $active,
            'user_id' => $user->id
        ]);
    }

}
