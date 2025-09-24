@extends('tenant.layouts.app')

@section('content')
    <tenant-backup-index
        :type-user="{{json_encode(Auth::user()->type)}}"
        :configuration="{}"
    ></tenant-backup-index>
@endsection
