@extends('tenant.layouts.app')

@push('styles')
<style>
    .content-header-left h1 {
        display: flex;
        align-items: center;
    }
    .construction-icon {
        margin-left: 10px;
        color: #ff9800;
        font-size: 20px;
    }
    .detalle-row {
        padding: 5px 0;
    }
    .balance-info {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .balance-desbalanceado {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    .balance-balanceado {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    /* Estilos para Select2 */
    .select2-container {
        width: 100% !important;
    }

    .select2-container--bootstrap .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        padding-left: 0;
        line-height: 1.5;
    }

    .select2-container--bootstrap .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + 0.75rem);
    }

    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    .select2-search--dropdown .select2-search__field {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    /* Mejoras visuales */
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    .balance-info {
        padding: 10px;
        border-radius: 5px;
        font-size: 0.9em;
    }

    .btn-block {
        font-weight: 500;
    }

    /* Espaciado optimizado */
    .mb-2 {
        margin-bottom: 0.5rem !important;
    }

    .row.align-items-end .col-md-1 {
        text-align: center;
    }

    /* Estilos para tabla de detalles */
    .table-sm th, .table-sm td {
        padding: 0.3rem;
        vertical-align: middle;
    }

    .table thead th {
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.9em;
    }

    .form-control-sm {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }

    .table-responsive {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }

    /* Mejorar espaciado de la columna tercero */
    .text-muted.small {
        font-size: 0.75rem;
        font-style: italic;
    }

    /* Espaciado entre secciones */
    .mt-4 {
        margin-top: 2rem !important;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    {{ isset($asiento) ? 'Editar' : 'Crear' }} Asiento Contable
                    <i class="fas fa-tools construction-icon" title="Módulo en construcción"></i>
                </h3>
                <div class="card-tools">
                    <a href="{{ route('tenant.asientos_contables.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <asiento-form-vue-component
                    :asiento-data="{{ isset($asiento) ? $asiento->toJson() : 'null' }}"
                    :is-editing="{{ isset($asiento) ? 'true' : 'false' }}">
                </asiento-form-vue-component>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts-after-vue')
<script>
    // Script específico para asientos contables si es necesario
    console.log('Vista de asientos contables cargada');
</script>
@endpush
