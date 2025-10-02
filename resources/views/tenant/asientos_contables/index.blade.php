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
    .btn-group .btn {
        border-radius: 0;
    }
    .btn-group .btn:first-child {
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
    }
    .btn-group .btn:last-child {
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    .estado-badge {
        font-size: 0.8em;
        padding: 3px 8px;
        border-radius: 12px;
    }
    .estado-borrador {
        background-color: #ffc107;
        color: #000;
    }
    .estado-confirmado {
        background-color: #28a745;
        color: #fff;
    }
    .estado-anulado {
        background-color: #dc3545;
        color: #fff;
    }

    /* Reducir espaciado vertical en toda la vista */
    .form-control {
        padding: 0.25rem 0.5rem;
        margin-bottom: 0.25rem;
    }

    .row {
        margin-bottom: 0.25rem;
    }

    .table td, .table th {
        padding: 0.5rem;
    }

    .col-md-1, .col-md-2, .col-md-3 {
        padding-bottom: 0.25rem;
    }

    /* Asegurar que los botones estén a la derecha */
    .card-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .card-tools {
        margin-left: auto !important;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Asientos Contables
                    <i class="fas fa-tools construction-icon" title="Módulo en construcción"></i>
                </h3>
                <div class="card-tools">
                    <a href="{{ route('tenant.asientos_contables.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Asiento
                    </a>
                </div>
            </div>
            <div class="card-body">
                <asientos-contables-index></asientos-contables-index>
            </div>
        </div>
    </div>
</div>
@endsection