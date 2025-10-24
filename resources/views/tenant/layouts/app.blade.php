@php
    $currentRouteName = request()->route()->getName();
    $useHorizontalMenu = $vc_horizontal_menu ?? true;

    // Aplicar sidebar collapsed independientemente del tipo de menú
    $sidebar_collapse = $vc_compact_sidebar->compact_sidebar == true ? 'sidebar-left-collapsed' : '';
    if($currentRouteName === 'tenant.pos.index') {
        $sidebar_collapse = 'sidebar-left-collapsed';
    }

    // Solo aplicar clases de sidebar lateral cuando no es horizontal
    if($useHorizontalMenu) {
        $is_dark_sidebar = '';
    } else {
        $is_dark_sidebar = ($visual->sidebars == 'dark' || $visual->bg == 'dark') ? 'sidebar-dark' : 'sidebar-white sidebar-light';
    }

    $is_dark_header = $visual->header == 'dark' ? 'header-dark' : '';
    $is_dark_theme = $visual->bg == 'dark' ? 'dark' : '';
    $menu_type_class = $useHorizontalMenu ? 'horizontal-menu' : 'lateral-menu';

    $paths = [
        'tenant.co-documents-aiu.create',
        'tenant.co-documents-health.create',
        'tenant.co-documents.create',
        'tenant.purchases.create',
        'tenant.purchase-orders.create',
        'reports.inventory.index',
    ];
    $is_form = in_array($currentRouteName, $paths) ? 'newinvoice' : '';

    // Obtener paleta de colores de la configuración
    $color_palette = $vc_configurations->color_palette ?? 'corporativo';
    $palette_class = 'palette-' . $color_palette;

    // Debug temporal para verificar la paleta
    // dd('Paleta cargada: ' . $color_palette, $vc_configurations);
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="fixed no-mobile-device custom-scroll modern-minimal {{ $palette_class }} {{$sidebar_collapse}} {{ $is_dark_header }} {{ $is_dark_sidebar }} {{ $is_dark_theme}} {{ $is_form }} {{ $menu_type_class }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Facturador PRO') }}</title>

    <!-- Scripts -->

    <!-- Fonts -->
    {{--<link rel="dns-prefetch" href="https://fonts.gstatic.com">--}}
    {{--<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">--}}

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/searchable-select-dropdown.css') }}" rel="stylesheet">

    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="{{ asset('porto-light/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/animate/animate.css') }}" />
    {{-- <link rel="stylesheet" href="{{ asset('porto-light/vendor/font-awesome/css/fontawesome-all.min.css') }}" /> --}}
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/font-awesome/5.11/css/all.min.css') }}" />
    <!-- FontAwesome CDN como respaldo -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/select2/css/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/datatables/media/css/dataTables.bootstrap4.css') }}" />

    {{--<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" />--}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.26.29/sweetalert2.min.css" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css')}}" />

    <!-- Specific Page Vendor CSS -->
    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-ui/jquery-ui.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-ui/jquery-ui.theme.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/select2/css/select2.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/select2-bootstrap-theme/select2-bootstrap.min.css')}}" />

    <!-- Daterange picker plugins css -->
    <link href="{{ asset('porto-light/vendor/bootstrap-timepicker/css/bootstrap-timepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('porto-light/vendor/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{asset('porto-light/vendor/bootstrap-timepicker/css/bootstrap-timepicker.css')}}" />

    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-loading/dist/jquery.loading.css')}}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('porto-light/master/style-switcher/style-switcher.css')}}">

    <link rel="stylesheet" href="{{ asset('porto-light/css/theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/css/custom.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/css/skins/theme-modern.css')}}" />

    @if (file_exists(public_path('theme/custom_styles.css')))
        <link rel="stylesheet" href="{{ asset('theme/custom_styles.css') }}" />
    @endif

    @if (file_exists(public_path('theme/custom_styles_ecommerce.css')))
        <link rel="stylesheet" href="{{ asset('theme/custom_styles_ecommerce.css') }}" />
    @endif

    <!-- Tema Mínimo SIN interferencias -->
    <link rel="stylesheet" href="{{ asset('theme/modern-minimal.css') }}" />
    <link rel="stylesheet" href="{{ asset('theme/modern-palettes.css') }}" />

    <!-- Estilos personalizados para sidebar lateral mejorado -->
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}" />

    <!-- Estilos modernos y corporativos para Element UI -->
    <link rel="stylesheet" href="{{ asset('css/modern-element-ui.css') }}" />

    <!-- Estilos modernos para formularios complejos -->
    <link rel="stylesheet" href="{{ asset('css/modern-forms.css') }}" />

    <!-- Estilos personalizados para sidebar lateral -->
    <link rel="stylesheet" href="{{ asset('css/custom-sidebar.css') }}" />

    <!-- Override agresivo para colores hardcodeados -->
    <style>
        /* FORZAR cambios de colores hardcodeados con máxima especificidad */
        .modern-minimal .card-header,
        .modern-minimal .panel-heading,
        .modern-minimal div[style*="background-color: #0088cc"],
        .modern-minimal div[style*="background-color: #007bff"],
        .modern-minimal div[style*="background: #0088cc"],
        .modern-minimal div[style*="background: #007bff"],
        .modern-minimal .bg-primary,
        .modern-minimal .configuration-section .card-header,
        .modern-minimal .config-panel .panel-heading {
            background-color: var(--primary-color) !important;
            background-image: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }

        /* Forzar títulos de sección */
        .modern-minimal .card-header h1,
        .modern-minimal .card-header h2,
        .modern-minimal .card-header h3,
        .modern-minimal .card-header h4,
        .modern-minimal .card-header h5,
        .modern-minimal .card-header h6,
        .modern-minimal .panel-heading h1,
        .modern-minimal .panel-heading h2,
        .modern-minimal .panel-heading h3,
        .modern-minimal .panel-heading h4,
        .modern-minimal .panel-heading h5,
        .modern-minimal .panel-heading h6 {
            color: white !important;
        }

        /* Forzar links y elementos de acento */
        .modern-minimal a:not(.btn):not(.nav-link) {
            color: var(--accent-color) !important;
        }

        .modern-minimal a:not(.btn):not(.nav-link):hover {
            color: var(--primary-color) !important;
        }
    </style>

    <!-- Fix específico para iconos -->
    <style>
        /* Override urgente para iconos FontAwesome */
        .modern-theme .fa,
        .modern-theme .fas,
        .modern-theme .far,
        .modern-theme .fab,
        .modern-theme .fal,
        .modern-theme [class*="fa-"] {
            font-family: "Font Awesome 5 Free", "FontAwesome" !important;
            font-weight: 900 !important;
            display: inline-block !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-rendering: auto !important;
            line-height: 1 !important;
            -webkit-font-smoothing: antialiased !important;
        }

        .modern-theme .far { font-weight: 400 !important; }
        .modern-theme .fab {
            font-weight: 400 !important;
            font-family: "Font Awesome 5 Brands", "FontAwesome" !important;
        }
    </style>


    @stack('styles')


    <script src="{{ asset('porto-light/vendor/modernizr/modernizr.js') }}"></script>

    <style>
        .descarga {
            color:black;
            padding:5px;
        }
        .header .logo {
            height: 100%;
            margin-top: 5px;
        }

        .header .logo img {
            height: 45px;
        }

        html.sidebar-light:not(.dark) ul.nav-main > li.nav-active > a {
            color: #0088CC;
        }


        .el-checkbox__label {
            font-size: 13px;
        }
        .center-el-checkbox {
            display: flex;
            align-items: center;
        }
        .center-el-checkbox .el-checkbox {
            margin-bottom: 0
        }

    </style>

</head>
<body class="pr-0">
    <!-- Contenedor para dropdowns de SearchableSelect -->
    <div id="searchable-dropdown-container"></div>

    <section class="body">
        <!-- start: header -->
        @include('tenant.layouts.partials.header')
        <!-- end: header -->
        <div class="inner-wrapper">
            <!-- start: sidebar -->
            @include('tenant.layouts.partials.sidebar')
            <!-- end: sidebar -->
            <section role="main" class="content-body" id="main-wrapper">
              @yield('content')
              @include('tenant.layouts.partials.sidebar_styles')
            </section>
        </div>
    </section>
    @if($show_ws)
        @if(strlen($phone_whatsapp) > 0)
        <a class='ws-flotante' href='https://wa.me/{{$phone_whatsapp}}' target="BLANK" style="background-image: url('{{asset('logo/ws.png')}}'); background-size: 70px; background-repeat: no-repeat;" ></a>
        @endif
    @endif


    <!-- Vendor -->
    <script src="{{ asset('porto-light/vendor/jquery/jquery.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-browser-mobile/jquery.browser.mobile.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-cookie/jquery-cookie.js')}}"></script>
    {{-- <script src="{{ asset('porto-light/master/style-switcher/style.switcher.js')}}"></script> --}}
    <script src="{{ asset('porto-light/vendor/popper/umd/popper.min.js')}}"></script>
    <!-- <script src="{{ asset('porto-light/vendor/bootstrap/js/bootstrap.js')}}"></script> -->
    {{-- <script src="{{ asset('porto-light/vendor/common/common.js')}}"></script> --}}
    <script src="{{ asset('porto-light/vendor/nanoscroller/nanoscroller.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/magnific-popup/jquery.magnific-popup.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-placeholder/jquery-placeholder.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/select2/js/select2.js') }}"></script>
    <script src="{{ asset('porto-light/vendor/datatables/media/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/datatables/media/js/dataTables.bootstrap4.min.js')}}"></script>

    {{-- Specific Page Vendor --}}
    <script src="{{asset('porto-light/vendor/jquery-ui/jquery-ui.js')}}"></script>
    <script src="{{asset('porto-light/vendor/jqueryui-touch-punch/jqueryui-touch-punch.js')}}"></script>
    <!--<script src="{{asset('porto-light/vendor/select2/js/select2.js')}}"></script>-->

    <script src="{{asset('porto-light/vendor/jquery-loading/dist/jquery.loading.js')}}"></script>

    <!--<script src="assets/vendor/select2/js/select2.js"></script>-->
    {{--<script src="{{asset('porto-light/vendor/bootstrap-multiselect/bootstrap-multiselect.js')}}"></script>--}}

    <!-- Moment -->
    {{--<script src="{{ asset('porto-light/vendor/moment/moment.js') }}"></script>--}}

    <!-- DatePicker -->
    <script src="{{asset('porto-light/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js')}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js"></script>

    <!-- Date range Plugin JavaScript -->
    {{--<script src="{{ asset('porto-light/vendor/bootstrap-timepicker/bootstrap-timepicker.js') }}"></script>--}}
    {{--<script src="{{ asset('porto-light/vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>--}}

    <!-- Theme Initialization Files -->
    {{-- <script src="{{asset('porto-light/js/theme.init.js')}}"></script> --}}

    {{--<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>--}}
    {{--<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>--}}

    @stack('scripts')

    <script src="{{ asset('js/app.js') }}"></script>

    <!-- Scripts that need Vue to be loaded -->
    @stack('scripts-after-vue')

    <!-- Theme Base, Components and Settings -->
    <script src="{{asset('porto-light/js/theme.js')}}"></script>

    <!-- Theme Custom -->
    <script src="{{asset('porto-light/js/custom.js')}}"></script>
    <script src="{{asset('porto-light/js/jquery.xml2json.js')}}"></script>
    <script>

        function parseXMLToJSON(source)
        {
            let transform = $.xml2json(source);
            return transform
        }

    </script>

    <!-- Script mínimo para iconos -->
    <script>
        $(document).ready(function() {
            // Solo asegurar que iconos sin clase específica tengan 'fas'
            setTimeout(function() {
                $('i[class*="fa-"]:not(.fas):not(.far):not(.fab)').addClass('fas');
            }, 50);
        });
    </script>

    <!-- <script src="//code.tidio.co/1vliqewz9v7tfosw5wxiktpkgblrws5w.js"></script> -->


</body>
</html>
