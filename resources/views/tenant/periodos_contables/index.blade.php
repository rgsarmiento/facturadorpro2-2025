@extends('tenant.layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="content-header-left mb-0">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h1 class="content-header-title float-left pr-1 mb-0">
                            <i class="fas fa-calendar-alt"></i> Períodos Contables
                        </h1>
                        <div class="breadcrumb-wrapper col-12">
                            <ol class="breadcrumb p-0 mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard.index') }}">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="#">Contabilidad</a></li>
                                <li class="breadcrumb-item active">Períodos Contables</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <periodos-contables-index></periodos-contables-index>
                </div>
            </div>
        </div>
    </div>
@endsection
