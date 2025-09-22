@extends('tenant.layouts.app')

@section('content')
    <tenant-cuentas-contables-index :type-user="{{json_encode(Auth::user()->type)}}"></tenant-cuentas-contables-index>
@endsection
