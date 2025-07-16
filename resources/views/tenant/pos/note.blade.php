@extends('tenant.layouts.app')

@section('content')
    <tenant-pos-note-form :note="{{ json_encode($note) }}" :invoice="{{ json_encode($invoice) }}" :command="{{ json_encode($command) }}"></tenant-pos-note-form>
@endsection
