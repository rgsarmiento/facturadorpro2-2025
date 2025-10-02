@extends('tenant.layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">TEST: Blade Template Working</h3>
            </div>
            <div class="card-body">
                <div id="asiento-form-app">
                    <h1>TEST: Content section rendered successfully</h1>
                    <p>This confirms the Blade template system is working.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log("TEST: Script in blade template executed");
console.log("Element found:", document.getElementById("asiento-form-app"));
</script>
@endsection
