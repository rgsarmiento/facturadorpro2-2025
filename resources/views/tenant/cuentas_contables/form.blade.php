@extends('tenant.layouts.app')

@section('content')
    <tenant-cuentas-contables-form
        :cuenta="{{isset($cuenta) ? json_encode($cuenta) : 'null'}}"
        :type-user="{{json_encode(Auth::user()->type)}}">
    </tenant-cuentas-contables-form>
@endsection
