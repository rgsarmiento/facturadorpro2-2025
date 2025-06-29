@extends('tenant.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pos.css') }}"/>
@endpush

@section('content')
    <!--<div class="row">
    <div class="col text-center">
      <a target="_blank" href="pos_full" class="btn btn-primary"> <i class="fas fa-arrows-alt"></i> Pantalla completa</a></a>
    </div>
  </div>-->
    <tenant-pos-index
     	:configuration="{{ $configuration}}"
        :tables_quantity="{{ json_encode($tables_quantity) }}"
        :cuentas="{{ json_encode($cuentas) }}"
     	:soap-company="{{ json_encode($soap_company) }}">
    </tenant-pos-index>
@endsection

@push('scripts')
<script src="{{ asset('js/qz-tray/sha-256.min.js') }}"></script>
<script src="{{ asset('js/qz-tray/qz-tray.js') }}"></script>
<script src="{{ asset('js/qz-tray/rsvp-3.1.0.min.js') }}"></script>
<script src="{{ asset('js/qz-tray/jsrsasign-all-min.js') }}"></script>
<script src="{{ asset('js/qz-tray/sign-message.js') }}"></script>
<script src="{{ asset('js/qz-tray/function-qztray.js') }}"></script>
@endpush
