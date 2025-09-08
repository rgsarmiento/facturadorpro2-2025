<?php
namespace App\Http\Controllers\System;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\System\CompanyResource;
use App\Models\System\Configuration;
use App\Http\Requests\System\CompanyRequest;

class CompanyController extends Controller
{
    public function create()
    {
        return view('tenant.companies.form');
    }

    public function tables()
    {
        $soap_sends = config('tables.system.soap_sends');
        $soap_types = SoapType::all();
        return compact('soap_types', 'soap_sends');
    }

    public function record()
    {
        $configuration = Configuration::first();
        $record = new CompanyResource($configuration);
        return $record;
    }

    public function store(CompanyRequest $request)
    {
       // $id = $request->input('id');
        $company = Configuration::first();
        $company->fill($request->all());
        $company->save();

        return [
            'success' => true,
            'message' => 'Empresa actualizada'
        ];
    }

    public function uploadFile(Request $request)
    {
        if ($request->hasFile('file')) {

            $company = Company::active();

            $type = $request->input('type');

            $file = $request->file('file');
            $ext = $file->getClientOriginalExtension();
            $name = $type.'_'.$company->number.'.'.$ext;


            if (($type === 'logo')) request()->validate(['file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048']);

            $file->storeAs(($type === 'logo') ? 'public/uploads/logos' : 'certificates', $name);

            if (($type === 'logo_store')) request()->validate(['file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048']);

            $file->storeAs(($type === 'logo_store') ? 'public/uploads/logos' : 'certificates', $name);


            $company->$type = $name;

            $company->save();

            return [
                'success' => true,
                'message' => __('app.actions.upload.success'),
                'name' => $name,
                'type' => $type
            ];
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    /**
     * Obtiene el estado actual del modo de mantenimiento
     */
    public function getMaintenanceMode()
    {
        try {
            $config = Configuration::first();
            
            $allowedCompanies = $config->maintenance_allowed_companies 
                ? json_decode($config->maintenance_allowed_companies, true) 
                : [];

            return response()->json([
                'success' => true,
                'data' => [
                    'maintenance_mode' => $config->maintenance_mode ?? false,
                    'maintenance_message' => $config->maintenance_message ?? '',
                    'maintenance_allowed_companies' => $allowedCompanies,
                    'maintenance_started_at' => $config->maintenance_started_at,
                    'maintenance_started_by' => $config->maintenance_started_by,
                    'active_companies_count' => count($allowedCompanies)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado de mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activa o desactiva el modo de mantenimiento
     */
    public function toggleMaintenanceMode(Request $request)
    {
        try {
            \Log::info('Toggle maintenance request:', $request->all());
            
            $request->validate([
                'maintenance_mode' => 'required',
                'maintenance_message' => 'nullable|string|max:1000',
                'maintenance_allowed_companies' => 'nullable|array',
                'maintenance_allowed_companies.*' => 'integer'
            ]);

            $config = Configuration::first();
            if (!$config) {
                $config = new Configuration();
            }

            $wasInMaintenance = $config->maintenance_mode;
            $isEnteringMaintenance = (bool) $request->maintenance_mode;

            // Actualizar configuración
            $config->maintenance_mode = $isEnteringMaintenance;
            $config->maintenance_message = $request->maintenance_message;
            $config->maintenance_allowed_companies = $request->maintenance_allowed_companies 
                ? json_encode($request->maintenance_allowed_companies) 
                : null;

            // Si se está activando el mantenimiento
            if (!$wasInMaintenance && $isEnteringMaintenance) {
                $config->maintenance_started_at = now();
                $config->maintenance_started_by = auth()->user()->name ?? 'Sistema';
                
                \Log::info('MAINTENANCE_MODE_ACTIVATED', [
                    'started_by' => $config->maintenance_started_by,
                    'allowed_companies' => $request->maintenance_allowed_companies,
                    'message' => $request->maintenance_message
                ]);
            }
            
            // Si se está desactivando el mantenimiento
            if ($wasInMaintenance && !$isEnteringMaintenance) {
                \Log::info('MAINTENANCE_MODE_DEACTIVATED', [
                    'deactivated_by' => auth()->user()->name ?? 'Sistema',
                    'duration' => $config->maintenance_started_at 
                        ? now()->diffForHumans($config->maintenance_started_at) 
                        : 'Unknown'
                ]);
            }

            $config->save();

            $status = $isEnteringMaintenance ? 'activado' : 'desactivado';
            $allowedCount = $request->maintenance_allowed_companies ? count($request->maintenance_allowed_companies) : 0;

            return response()->json([
                'success' => true,
                'message' => "Modo de mantenimiento {$status} exitosamente",
                'data' => [
                    'maintenance_mode' => $config->maintenance_mode,
                    'allowed_companies_count' => $allowedCount,
                    'started_at' => $config->maintenance_started_at,
                    'started_by' => $config->maintenance_started_by
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('TOGGLE_MAINTENANCE_ERROR', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar modo de mantenimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene la lista de todas las empresas para el selector
     */
    public function getCompaniesForMaintenance()
    {
        try {
            $companies = \DB::table('co_companies')
                ->select('id', 'name', 'identification_number', 'subdomain', 'email', 'created_at')
                ->where('locked', false) // Solo empresas activas
                ->whereNull('deleted_at') // No eliminadas
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $companies
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener empresas: ' . $e->getMessage()
            ], 500);
        }
    }
}
