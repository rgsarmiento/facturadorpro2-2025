<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Factcolombia1\Models\TenantService\Company;
use Modules\Factcolombia1\Models\Tenant\Company as TenantCompany;

class SyncRadianConfigurationToApi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            // Obtener la configuración avanzada existente
            $advancedConfig = AdvancedConfiguration::first();

            if (!$advancedConfig) {
                \Log::info('No se encontró configuración avanzada para sincronizar con API RADIAN');
                return;
            }

            // Verificar si existen datos RADIAN para enviar
            if ($this->hasRadianConfiguration($advancedConfig)) {
                $this->sendRadianConfigurationToAPI($advancedConfig);
                \Log::info('Configuración RADIAN sincronizada con API exitosamente');
            } else {
                \Log::info('No hay configuración RADIAN válida para sincronizar');
            }

        } catch (\Exception $e) {
            \Log::error('Error al sincronizar configuración RADIAN: ' . $e->getMessage());
            // No lanzamos la excepción para evitar que falle toda la migración
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Esta migración no modifica la estructura de la base de datos,
        // por lo que no hay nada que revertir
    }

    /**
     * Verifica si existe configuración RADIAN válida
     */
    private function hasRadianConfiguration($config)
    {
        return !empty($config->radian_imap_host) &&
               !empty($config->radian_imap_user) &&
               !empty($config->radian_imap_password) &&
               !empty($config->radian_imap_encryption) &&
               !empty($config->radian_imap_port);
    }

    /**
     * Envía la configuración RADIAN al endpoint externo
     */
    private function sendRadianConfigurationToAPI($advancedConfig)
    {
        try {
            // Obtener datos de las compañías
            $company = Company::first(); // TenantService\Company
            $company1 = TenantCompany::first(); // Tenant\Company

            if (!$company || !$company1) {
                \Log::error('No se encontraron datos de compañía para enviar configuración RADIAN en migración');
                return;
            }

            // Construir el endpoint
            $baseUrl = config('tenant.service_fact');
            $endpoint = "{$baseUrl}ubl2.1/config/{$company->identification_number}/{$company->dv}";

            // Construir el payload
            $payload = [
                "type_document_identification_id" => $company->type_document_identification_id,
                "type_organization_id" => $company->type_organization_id,
                "type_regime_id" => $company->type_regime_id,
                "type_liability_id" => $company->type_liability_id,
                "business_name" => $company1->name,
                "merchant_registration" => $company->merchant_registration,
                "municipality_id" => $company->municipality_id,
                "address" => $company->address,
                "phone" => $company->phone,
                "email" => $company1->email,
                "imap_server" => $advancedConfig->radian_imap_host,
                "imap_user" => $advancedConfig->radian_imap_user,
                "imap_password" => $advancedConfig->radian_imap_password,
                "imap_encryption" => $advancedConfig->radian_imap_encryption,
                "imap_port" => $advancedConfig->radian_imap_port
            ];

            // Realizar la petición HTTP
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log para debugging
            \Log::info('Configuración RADIAN enviada desde migración', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
                'http_code' => $httpCode
            ]);

            if ($httpCode >= 200 && $httpCode < 300) {
                \Log::info('Configuración RADIAN enviada exitosamente desde migración');
            } else {
                \Log::error('Error al enviar configuración RADIAN desde migración', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Excepción al enviar configuración RADIAN desde migración: ' . $e->getMessage());
            throw $e; // Re-lanzar para que la migración maneje el error
        }
    }
}
