@extends('tenant.layouts.app')

@section('content')
    <tenant-advanced-configuration-index :env-service-fact='"{{ $env_service_fact }}"' :identification-number='"{{ $identification_number }}"'></tenant-advanced-configuration-index>
@endsection
