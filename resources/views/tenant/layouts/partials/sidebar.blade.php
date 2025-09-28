@php
    $path = explode('/', request()->path());
    $path[1] = (array_key_exists(1, $path)> 0) ? $path[1] : '';
    $path[2] = (array_key_exists(2, $path)> 0) ? $path[2] : '';
    $path[0] = ($path[0] === '') ? 'documents' : $path[0];

    // Determinar el tipo de menú a mostrar
    $useHorizontalMenu = $vc_horizontal_menu ?? true;
@endphp

@if($useHorizontalMenu)
    {{-- MENÚ HORIZONTAL --}}

<style>
.horizontal-navbar {
    background: #fff;
    border-bottom: 1px solid #e3e6f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9998;
    padding: 0.25rem 1rem;
    height: 60px;
    display: flex;
    align-items: center;
    overflow: visible !important;
}

.navbar-brand {
    display: flex;
    align-items: center;
    margin-right: 1.5rem;
    flex-shrink: 0;
}

.navbar-brand img {
    height: 35px;
    max-width: 120px;
}

.horizontal-nav {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: visible !important;
    margin: 0;
    padding: 0;
    list-style: none;
    flex: 1;
    position: static;
}

.horizontal-nav::-webkit-scrollbar {
    height: 4px;
}

.horizontal-nav::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.horizontal-nav::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.horizontal-nav > li {
    position: static;
    display: flex;
    align-items: center;
    margin-right: 0.25rem;
    flex-shrink: 0;
}

.horizontal-nav > li > a {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    color: #5a5c69;
    text-decoration: none;
    border-radius: 0.25rem;
    transition: all 0.2s;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 500;
}

.horizontal-nav > li > a:hover {
    background-color: #f8f9fc;
    color: #5a5c69;
    text-decoration: none;
}

.horizontal-nav > li.nav-active > a {
    background-color: #4e73df;
    color: white;
}

.horizontal-nav > li > a i {
    margin-right: 0.375rem;
    font-size: 0.75rem;
}

.dropdown-menu-horizontal {
    position: fixed;
    top: 58px;
    z-index: 99999;
    display: none;
    min-width: 280px;
    background-color: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 0.375rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    max-height: calc(100vh - 80px);
    overflow-y: auto;
    padding: 0.5rem 0;
    margin-top: 2px;
}

/* Área invisible para mantener conexión con el menú padre */
.dropdown-menu-horizontal::before {
    content: '';
    position: absolute;
    top: -6px;
    left: 0;
    right: 0;
    height: 8px;
    background: transparent;
    z-index: 99998;
}

/* Área de conexión más amplia para mejor usabilidad */
.nav-parent::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: transparent;
    z-index: 99998;
}

/* Hover habilitado temporalmente para debug */
.horizontal-nav > li:hover .dropdown-menu-horizontal {
    display: block !important;
    animation: fadeIn 0.2s ease-in-out;
}
/* Habilitado temporalmente */

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.dropdown-menu-horizontal li {
    display: block;
    width: 100%;
}

.dropdown-menu-horizontal li a {
    display: block;
    padding: 0.5rem 1rem;
    color: #5a5c69;
    text-decoration: none;
    transition: background-color 0.2s;
    font-size: 0.875rem;
    border-left: 3px solid transparent;
}

.dropdown-menu-horizontal li a:hover {
    background-color: #f8f9fc;
    border-left-color: #4e73df;
    text-decoration: none;
}

.dropdown-menu-horizontal li.nav-active a {
    background-color: #4e73df;
    color: white;
    border-left-color: #2e59d9;
}

.dropdown-menu-horizontal .nav-parent > a {
    font-weight: 600;
    color: #3a3b45;
    background-color: #f8f9fc;
    margin: 0.25rem 0;
    position: relative;
    cursor: pointer;
}

.dropdown-menu-horizontal .nav-parent {
    cursor: pointer;
}

.dropdown-menu-horizontal .nav-parent > a::after {
    content: '▶';
    position: absolute;
    right: 1rem;
    font-size: 0.7rem;
    transition: transform 0.2s;
    color: #6c757d;
}

.dropdown-menu-horizontal .nav-parent:hover > a {
    background-color: #e9ecef;
    border-left-color: #4e73df;
}

.dropdown-menu-horizontal .nav-parent:hover > a::after {
    color: #4e73df;
}

/* Submenús laterales - estilo Windows */
.dropdown-menu-horizontal .nav-parent {
    position: relative;
}

.dropdown-menu-horizontal .nav-parent > .dropdown-menu-horizontal {
    position: fixed !important;
    z-index: 100005 !important;
    display: none;
    min-width: 280px;
    background-color: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 0.375rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    padding: 0.5rem 0;
    animation: slideInRight 0.15s ease-out;
}

/* Efecto de aparición suave */

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Área de conexión invisible para mantener hover */
.dropdown-menu-horizontal .nav-parent::after {
    content: '';
    position: absolute;
    right: -5px;
    top: 0;
    width: 8px;
    height: 100%;
    background: transparent;
    z-index: 100004;
}

/* Estilos para elementos de submenu lateral */
.dropdown-menu-horizontal .nav-parent > .dropdown-menu-horizontal li {
    display: block;
    width: 100%;
}

.dropdown-menu-horizontal .nav-parent > .dropdown-menu-horizontal li a {
    display: block;
    padding: 0.6rem 1rem;
    color: #5a5c69;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 0.875rem;
    border-left: 3px solid transparent;
    white-space: nowrap;
}

.dropdown-menu-horizontal .nav-parent > .dropdown-menu-horizontal li a:hover {
    background-color: #f8f9fc;
    border-left-color: #4e73df;
    color: #3a3b45;
    text-decoration: none;
    transform: translateX(2px);
}

.dropdown-menu-horizontal .nav-parent > .dropdown-menu-horizontal li.nav-active a {
    background-color: #4e73df;
    color: white;
    border-left-color: #2e59d9;
}

/* Asegurar que los menús estén visibles */
.dropdown-menu-horizontal.show {
    display: block !important;
    animation: fadeIn 0.2s ease-in-out;
}

/* Control de menús por JavaScript */

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #5a5c69;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 0.25rem;
}

.mobile-menu-toggle:hover {
    background-color: #f8f9fc;
}

@media (max-width: 992px) {
    .horizontal-nav {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border-top: 1px solid #e3e6f0;
        flex-direction: column;
        align-items: stretch;
        max-height: calc(100vh - 60px);
        overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .horizontal-nav.show {
        display: flex;
    }

    .horizontal-nav > li {
        margin-right: 0;
        border-bottom: 1px solid #f8f9fc;
    }

    .horizontal-nav > li > a {
        padding: 0.75rem 1rem;
        justify-content: flex-start;
    }

    .mobile-menu-toggle {
        display: block;
    }

    .dropdown-menu-horizontal {
        position: static;
        display: block;
        box-shadow: none;
        border: none;
        background-color: #f8f9fc;
        margin: 0;
        padding: 0;
        border-radius: 0;
        z-index: auto;
    }

    .dropdown-menu-horizontal li a {
        padding-left: 2rem;
        border-left: none;
        border-bottom: 1px solid #e9ecef;
    }
}

/* Ajuste para el contenido principal - solo para menú horizontal */
html.horizontal-menu body {
    padding-top: 60px;
}

/* Resetear padding para menú lateral */
html.lateral-menu body {
    padding-top: 0;
}

/* Ocultar navbar horizontal en modo lateral */
html.lateral-menu .horizontal-navbar {
    display: none;
}

/* Efectos de sidebar contraído en modo horizontal */
html.horizontal-menu.sidebar-left-collapsed .horizontal-navbar {
    /* Extender el navbar cuando el sidebar está contraído */
    left: 0;
    right: 0;
}

html.horizontal-menu.sidebar-left-collapsed body {
    /* Mantener el padding top pero ajustar para más espacio */
    padding-top: 60px;
}

/* Aplicar efectos de sidebar contraído a todo el contenido en modo horizontal */
html.horizontal-menu.sidebar-left-collapsed .content-body,
html.horizontal-menu.sidebar-left-collapsed .inner-wrapper,
html.horizontal-menu.sidebar-left-collapsed .content {
    margin-left: 0 !important;
    padding-left: 0 !important;
}

/* Asegurar que el sidebar lateral esté completamente oculto en modo horizontal */
html.horizontal-menu #sidebar-left,
html.horizontal-menu .sidebar-left {
    display: none !important;
    width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Maximizar espacio de trabajo en modo horizontal contraído */
html.horizontal-menu.sidebar-left-collapsed .inner-wrapper > .content,
html.horizontal-menu.sidebar-left-collapsed .inner-wrapper {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 15px !important;
}

/* Sobreescribir estilos del tema Porto para modo horizontal */
html.fixed.horizontal-menu.sidebar-left-collapsed .page-header {
    left: 0 !important;
}

html.fixed.horizontal-menu.sidebar-left-collapsed .content-body {
    margin-left: 0 !important;
    padding: 60px 15px 15px 15px !important;
}

/* Asegurar que todos los elementos de contenido usen el ancho completo */
html.horizontal-menu.sidebar-left-collapsed .page-content-wrapper,
html.horizontal-menu.sidebar-left-collapsed .content-with-menu,
html.horizontal-menu.sidebar-left-collapsed .inner-wrapper,
html.horizontal-menu.sidebar-left-collapsed .content {
    width: 100% !important;
    margin-left: 0 !important;
    padding-left: 15px !important;
    padding-right: 15px !important;
}

/* Mejoras adicionales */
.navbar-brand:hover {
    text-decoration: none;
}

.dropdown-menu-horizontal::-webkit-scrollbar {
    width: 6px;
}

.dropdown-menu-horizontal::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.dropdown-menu-horizontal::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

/* Reglas para menús desplegables - híbrido CSS/JavaScript */
.nav-parent {
    position: relative !important;
}

.horizontal-nav > .nav-parent > .dropdown-menu-horizontal {
    position: fixed !important;
    top: 58px !important;
    z-index: 99999 !important;
    display: none;
}

.dropdown-menu-horizontal .nav-parent > .dropdown-menu-horizontal {
    position: fixed !important;
    left: 100% !important;
    top: 0 !important;
    z-index: 100002 !important;
    display: none;
    min-width: 280px !important;
}

/* Comportamiento básico de hover como respaldo - HABILITADO TEMPORALMENTE */
.horizontal-nav > li.nav-parent:hover > .dropdown-menu-horizontal {
    display: block !important;
}
/* Habilitado temporalmente para debug */

/* Estilos para submenus flotantes */
.floating-submenu {
    position: fixed !important;
    z-index: 100010 !important;
    display: block !important;
    min-width: 280px !important;
    background-color: #fff !important;
    border: 1px solid #e3e6f0 !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 15px 35px rgba(0,0,0,0.25) !important;
    animation: slideInRight 0.2s ease-out !important;
}

/* Animación para los submenus flotantes */
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Estilos compactos para el navbar horizontal */
.horizontal-nav {
    gap: 0px !important; /* Sin espacio entre elementos */
}

.horizontal-nav li {
    margin: 0 !important; /* Sin margen entre elementos */
}

.horizontal-nav li a.nav-link {
    padding: 6px 8px !important; /* Padding aún más reducido */
    font-size: 13px !important; /* Reducir tamaño de fuente */
    border-radius: 3px !important;
    margin: 0 1px !important; /* Mínima separación visual */
}

.horizontal-nav li a.nav-link i {
    margin-right: 6px !important; /* Reducir espacio entre icono y texto */
    font-size: 12px !important;
}

.horizontal-nav li a.nav-link span {
    font-weight: 500;
}

/* Estilo para mostrar el nombre del usuario */
.horizontal-nav li.nav-user-info {
    margin-left: auto !important; /* Empuja hacia la derecha */
    margin-right: 15px !important;
}

.horizontal-nav li.nav-user-info .user-name {
    color: #ffffff !important;
    font-size: 12px !important;
    font-weight: 500;
    padding: 6px 10px;
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
    display: flex;
    align-items: center;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.horizontal-nav li.nav-user-info .user-name i {
    margin-right: 6px !important;
    font-size: 11px !important;
    color: #ffffff !important;
}

/* Estilo especial para la opción Salir */
.horizontal-nav li.nav-logout a.nav-link {
    background-color: rgba(220, 53, 69, 0.8) !important;
    color: white !important;
}

.horizontal-nav li.nav-logout a.nav-link:hover {
    background-color: rgba(220, 53, 69, 1) !important;
}

/* Dropdown menus más compactos */
.dropdown-menu-horizontal {
    padding: 4px 0 !important;
}

.dropdown-menu-horizontal li a {
    padding: 6px 12px !important;
    font-size: 12px !important;
}

/* Responsive para pantallas pequeñas */
@media (max-width: 768px) {
    .horizontal-nav li.nav-user-info .user-name {
        font-size: 11px !important;
        padding: 4px 6px !important;
    }

    .horizontal-nav li a.nav-link {
        padding: 5px 6px !important;
        font-size: 12px !important;
    }

    .horizontal-nav li a.nav-link span {
        display: none; /* Ocultar texto en móviles, solo mostrar iconos */
    }

    .horizontal-nav li.nav-user-info .user-name,
    .horizontal-nav li.nav-logout a.nav-link span {
        display: flex !important; /* Mantener usuario y salir visibles */
    }
}


</style>

<nav class="horizontal-navbar" style="overflow: visible !important;">
    <div class="d-flex align-items-center w-100" style="overflow: visible !important;">
        <a href="{{route('tenant.dashboard.index')}}" class="navbar-brand">
            @if($vc_company->logo)
                <img src="{{ asset('storage/uploads/logos/'.$vc_company->logo) }}" alt="Logo"/>
            @else
                <img src="{{asset('logo/tulogo.png')}}" alt="Logo"/>
            @endif
        </a>

        <button class="mobile-menu-toggle" type="button" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="horizontal-nav" id="horizontal-nav">
                    @if(in_array('dashboard', $vc_modules))
                    <li class="{{ ($path[0] === 'dashboard')?'nav-active':'' }}">
                        <a class="nav-link" href="{{ route('tenant.dashboard.index') }}">
<!--                            <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span>    -->
                            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('documents', $vc_modules))
                    <li class="nav-parent
                        {{ ($path[0] === 'documents')?'nav-active':'' }}
                        {{ ($path[0] === 'items')?'nav-active':'' }}
                        {{ ($path[0] === 'persons' && $path[1] === 'customers')?'nav-active':'' }}
                        {{ ($path[0] === 'summaries')?'nav-active':'' }}
                        {{ ($path[0] === 'voided')?'nav-active':'' }}
                        {{ ($path[0] === 'quotations')?'nav-active':'' }}
                        {{ ($path[0] === 'sale-notes')?'nav-active':'' }}
                        {{ ($path[0] === 'contingencies')?'nav-active':'' }}
                        {{ ($path[0] === 'person-types')?'nav-active':'' }}
                        {{ ($path[0] === 'brands')?'nav-active':'' }}
                        {{ ($path[0] === 'categories')?'nav-active':'' }}
                        {{ ($path[0] === 'incentives')?'nav-active':'' }}
                        {{ ($path[0] === 'order-notes')?'nav-active':'' }}
                        {{ ($path[0] === 'sale-opportunities')?'nav-active':'' }}
                        {{ ($path[0] === 'contracts')?'nav-active':'' }}
                        {{ ($path[0] === 'production-orders')?'nav-active':'' }}
                        {{ ($path[0] === 'technical-services')?'nav-active':'' }}
                        {{ ($path[0] === 'user-commissions')?'nav-active':'' }}
                        {{ ($path[0] === 'co-documents')?'nav-active':'' }}
                        {{ ($path[0] === 'co-items')?'nav-active':'' }}
                        {{ ($path[0] === 'co-clients')?'nav-active':'' }}
                        {{ ($path[0] === 'co-taxes')?'nav-active':'' }}
                        {{ ($path[0] === 'co-documents-aiu')?'nav-active':'' }}
                        {{ ($path[0] === 'co-documents-health')?'nav-active':'' }}
                        {{ ($path[0] === 'co-remissions')?'nav-active':'' }}">
                        <a href="#">
                            <i class="fas fa-file-invoice" aria-hidden="true"></i>
                            <span>Ventas</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            @if(auth()->user()->type != 'integrator' && $vc_company->soap_type_id != '03')
                                @if(in_array('documents', $vc_modules))
                                    @if(in_array('new_document', $vc_module_levels))
                                        <li class="{{ ($path[0] === 'co-documents'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents.create')}}">
                                                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                                Nueva Factura Electronica
                                            </a>
                                        </li>

                                        <li class="{{ ($path[0] === 'co-documents-contingency-3'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents-contingency-3.create')}}">
                                                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                                Nueva F.E. Contingencia Tipo 3
                                            </a>
                                        </li>

                                        @if(in_array('invoicehealth', $vc_modules))
                                            <li class="{{ ($path[0] === 'co-documents-health'  && $path[1] === 'create')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.co-documents-health.create')}}">
                                                    <i class="fas fa-heartbeat" aria-hidden="true"></i>
                                                    Nueva F.E. Sector Salud
                                                </a>
                                            </li>
                                        @endif

                                        <li class="{{ ($path[0] === 'co-documents-aiu'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents-aiu.create')}}">
                                                <i class="fas fa-file-contract" aria-hidden="true"></i>
                                                Nueva Factura Electronica AIU
                                            </a>
                                        </li>

                                        <li class="{{ ($path[0] === 'co-documents-unreferenced-note'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents-unreferenced-note.create')}}">
                                                <i class="fas fa-sticky-note" aria-hidden="true"></i>
                                                Nueva Nota Contable Sin Referencia A Factura Electronica
                                            </a>
                                        </li>

                                        {{-- <li class="{{ ($path[0] === 'documents' && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.documents.create')}}">
                                                Nuevo comprobante electrónico
                                            </a>
                                        </li> --}}
                                    @endif
                                @endif
                            @endif

                            @if(in_array('documents', $vc_modules) && $vc_company->soap_type_id != '03')

                                @if(in_array('list_document', $vc_module_levels))
                                    {{-- <li class="{{ ($path[0] === 'documents' && $path[1] != 'create' && $path[1] != 'not-sent')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.documents.index')}}">
                                            Listado de comprobantes
                                        </a>
                                    </li> --}}

                                    <li class="{{ ($path[0] === 'co-documents'  && $path[1] != 'create'  )?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.co-documents.index')}}">
                                            <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                            Listado de comprobantes
                                        </a>
                                    </li>
                                @endif

                            @endif

                            {{-- @if(in_array('documents', $vc_modules) && $vc_company->soap_type_id != '03')

                                @if(in_array('document_not_sent', $vc_module_levels))
                                    <li class="{{ ($path[0] === 'documents' && $path[1] === 'not-sent')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.documents.not_sent')}}">
                                            Comprobantes no enviados
                                        </a>
                                    </li>
                                @endif

                            @endif --}}

                            @if(auth()->user()->type != 'integrator' && in_array('documents', $vc_modules) )

                                {{-- @if(auth()->user()->type != 'integrator' && in_array('document_contingengy', $vc_module_levels) && $vc_company->soap_type_id != '03')
                                <li class="{{ ($path[0] === 'contingencies' )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.contingencies.index')}}">
                                        Documentos de contingencia
                                    </a>
                                </li>
                                @endif --}}

                                @if(in_array('catalogs', $vc_module_levels))

                                    <li class="nav-parent
                                        {{ ($path[0] === 'items')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'co-items')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'co-clients')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'categories')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'brands')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'person-types')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'co-taxes')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'persons' && $path[1] === 'customers')?'nav-active nav-expanded':'' }}
                                        ">
                                        <a class="nav-link" href="#">
                                            Catálogos
                                        </a>
                                        <ul class="dropdown-menu-horizontal">
                                            <li class="{{ ($path[0] === 'co-taxes')?'nav-active':'' }}">
                                                <a href="{{route('tenant.co-taxes.index')}}">
                                                    <i class="fas fa-percentage" aria-hidden="true"></i>
                                                    Impuestos colombia
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'items')?'nav-active':'' }}">
                                                <a href="{{route('tenant.items.index')}}">
                                                    <i class="fas fa-box" aria-hidden="true"></i>
                                                    Productos
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'categories')?'nav-active':'' }}">
                                                <a href="{{route('tenant.categories.index')}}">
                                                    <i class="fas fa-tags" aria-hidden="true"></i>
                                                    Categorías
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'brands')?'nav-active':'' }}">
                                                <a href="{{route('tenant.brands.index')}}">
                                                    <i class="fas fa-copyright" aria-hidden="true"></i>
                                                    Marcas
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'persons' && $path[1] === 'customers')?'nav-active':'' }}">
                                                <a href="{{route('tenant.persons.index', ['type' => 'customers'])}}">
                                                    <i class="fas fa-users" aria-hidden="true"></i>
                                                    Clientes
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                {{-- @if(in_array('summary_voided', $vc_module_levels) && $vc_company->soap_type_id != '03')

                                    <li class="nav-parent
                                        {{ ($path[0] === 'summaries')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'voided')?'nav-active nav-expanded':'' }}
                                        ">
                                        <a class="nav-link" href="#">
                                            Resúmenes y Anulaciones
                                        </a>
                                        <ul class="nav nav-children">
                                            <li class="{{ ($path[0] === 'summaries')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.summaries.index')}}">
                                                    Resúmenes
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'voided')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.voided.index')}}">
                                                    Anulaciones
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif --}}

                                {{-- <li class="{{ ($path[0] === 'sale-opportunities')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.sale_opportunities.index')}}">
                                        Oportunidad de venta
                                    </a>
                                </li> --}}

                                @if(in_array('quotations', $vc_module_levels))

                                    <li class="{{ ($path[0] === 'quotations')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.quotations.index')}}">
                                            Cotizaciones
                                        </a>
                                    </li>
                                @endif

                                @if(in_array('remissions', $vc_module_levels))
                                <li class="{{ ($path[0] === 'co-remissions')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.co-remissions.index')}}">
                                        Remisiones
                                    </a>
                                </li>
                                @endif

                                {{-- <li class="nav-parent
                                    {{ ($path[0] === 'contracts')?'nav-active nav-expanded':'' }}
                                    {{ ($path[0] === 'production-orders')?'nav-active nav-expanded':'' }}
                                    ">
                                    <a class="nav-link" href="#">
                                        Contratos
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ ($path[0] === 'contracts')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.contracts.index')}}">
                                                Listado
                                            </a>
                                        </li>
                                        <li class="{{ ($path[0] === 'production-orders')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.production_orders.index')}}">
                                                Ordenes de Producción
                                            </a>
                                        </li>
                                    </ul>
                                </li> --}}


                                {{-- <li class="{{ ($path[0] === 'order-notes')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.order_notes.index')}}">
                                        Pedidos
                                    </a>
                                </li> --}}

                                {{--@if(in_array('sale_notes', $vc_module_levels))

                                    <li class="{{ ($path[0] === 'sale-notes')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.sale_notes.index')}}">
                                            Notas de Venta
                                        </a>
                                    </li>
                                @endif --}}

                              {{--  <li class="{{ ($path[0] === 'technical-services')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.technical_services.index')}}">
                                        Servicio de soporte técnico
                                    </a>
                                </li> --}}

                                @if(in_array('incentives', $vc_module_levels))
                                    <li class="nav-parent
                                        {{ ($path[0] === 'incentives')?'nav-active':'' }}
                                        {{ ($path[0] === 'user-commissions')?'nav-active':'' }}">
                                        <a href="#">
                                            Comisiones
                                        </a>
                                        <ul class="dropdown-menu-horizontal">
                                            <li class="{{ ($path[0] === 'user-commissions')?'nav-active':'' }}">
                                                <a href="{{route('tenant.user_commissions.index')}}">
                                                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                                                    Vendedores
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'incentives')?'nav-active':'' }}">
                                                <a href="{{route('tenant.incentives.index')}}">
                                                    <i class="fas fa-gift" aria-hidden="true"></i>
                                                    Productos
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->type != 'integrator')
                        @if(in_array('pos', $vc_modules))
                        <li class="nav-parent
                        {{ ($path[0] === 'pos')?'nav-active':'' }}
                        {{ ($path[0] === 'cash')?'nav-active':'' }}
                        {{ ($path[0] === 'item-sets')?'nav-active':'' }}
                        {{ ($path[0] === 'document-pos')?'nav-active':'' }}">
                            <a href="#">
                                <i class="fas fa-cash-register" aria-hidden="true"></i>
                                <span>Punto de Venta P.O.S.</span>
                            </a>
                            <ul class="dropdown-menu-horizontal">
                                <li class="{{ ($path[0] === 'pos'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.pos.index')}}">
                                        <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                        Punto de venta
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'cash'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.cash.index')}}">
                                        <i class="fas fa-coins" aria-hidden="true"></i>
                                        Caja chica
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'item-sets'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.item_sets.index')}}">
                                        <i class="fas fa-cubes" aria-hidden="true"></i>
                                        Conjuntos/Packs/Promociones
                                    </a>
                                </li>
                                @if(auth()->user()->type == 'admin')
                                    <li class="{{ ($path[0] === 'item-sets'  )?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.pos.configuration')}}">
                                            <i class="fas fa-cog" aria-hidden="true"></i>
                                            Configuración
                                        </a>
                                    </li>
                                @endif
                                <li class="{{ ($path[0] === 'document-pos'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.document_pos.index')}}">
                                        <i class="fas fa-list" aria-hidden="true"></i>
                                        Lista Documentos
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                    @endif

                    @if(in_array('accounting', $vc_modules))
                    <li class="nav-parent
                        {{ ($path[0] === 'account')?'nav-active':'' }}
                        {{ ($path[0] === 'contabilidad')?'nav-active':'' }}
                        {{ ($path[0] === 'puc')?'nav-active':'' }}
                        ">
                        <a href="#">
                            <i class="fas fa-calculator" aria-hidden="true"></i>
                            <span>Contabilidad</span>
                            <i class="fas fa-wrench text-warning ml-1" style="font-size: 10px;" title="En construcción"></i>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            <li class="{{(($path[0] === 'contabilidad') && ($path[1] === 'cuentas-contables')) ? 'nav-active' : ''}}">
                                <a href="{{ route('tenant.cuentas_contables.index') }}">
                                    <i class="fas fa-list-alt" aria-hidden="true"></i>
                                    Plan único de cuentas - PUC
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'contabilidad') && ($path[1] === 'asientos-contables')) ? 'nav-active' : ''}}">
                                <a href="{{ route('tenant.asientos_contables.index') }}">
                                    <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                    Asientos contables
                                    <i class="fas fa-wrench text-warning ml-1" style="font-size: 8px;" title="En construcción"></i>
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'account') && ($path[1] === 'format')) ? 'nav-active' : ''}}">
                                <a href="{{ route('tenant.account_format.index') }}">
                                    <i class="fas fa-file-export" aria-hidden="true"></i>
                                    Exportar formatos
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'account') && ($path[1] == ''))   ? 'nav-active' : ''}}">
                                <a href="{{ route('tenant.account.index') }}">
                                    <i class="fas fa-file-code" aria-hidden="true"></i>
                                    Exportar formatos - Sis. Contable
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'account') && ($path[1] == 'summary-report'))   ? 'nav-active' : ''}}">
                                <a href="{{ route('tenant.account_summary_report.index') }}">
                                    <i class="fas fa-chart-bar" aria-hidden="true"></i>
                                    Reporte resumen cuentas
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(in_array('ecommerce', $vc_modules))
                    <li class="nav-parent {{ in_array($path[0], ['ecommerce','items_ecommerce', 'tags', 'promotions', 'orders', 'configuration'])?'nav-active':'' }}">
                        <a href="#">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            <span>Tienda Virtual</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            <li class="">
                                <a class="nav-link" onclick="window.open('{{ route("tenant.ecommerce.index") }}')">
                                    Ir a Tienda
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'orders')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant_orders_index')}}">
                                    Pedidos
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'items_ecommerce')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.items_ecommerce.index')}}">
                                    Productos Tienda Virtual
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'tags')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.tags.index')}}">
                                    Tags - Categorias
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'promotions')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.promotion.index')}}">
                                    Promociones
                                </a>
                            </li>
                            <li class="{{ ($path[1] === 'configuration')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant_ecommerce_configuration')}}">
                                    Configuración
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->type != 'integrator')

                        @if(in_array('purchases', $vc_modules))
                        <li class="nav-parent
                            {{ ($path[0] === 'purchases')?'nav-active':'' }}
                            {{ ($path[0] === 'persons' && $path[1] === 'suppliers')?'nav-active':'' }}
                            {{ ($path[0] === 'expenses')?'nav-active':'' }}
                            {{ ($path[0] === 'purchase-quotations')?'nav-active':'' }}
                            {{ ($path[0] === 'purchase-orders')?'nav-active':'' }}
                            {{ ($path[0] === 'fixed-asset')?'nav-active':'' }}
                            {{ ($path[0] === 'support-documents')?'nav-active':'' }}
                            {{ ($path[0] === 'support-document-adjust-notes')?'nav-active':'' }}">
                            <a href="#">
                                <i class="fas fa-cart-plus" aria-hidden="true"></i>
                                <span>Compras</span>
                            </a>
                            <ul class="dropdown-menu-horizontal">



                                <li class="{{ ($path[0] === 'purchases' && $path[1] === 'create')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchases.create')}}">
                                        Nuevo
                                    </a>
                                </li>

                                <li class="{{ ($path[0] === 'purchases' && $path[1] != 'create')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchases.index')}}">
                                        Listado
                                    </a>
                                </li>

                                <li class="{{ ($path[0] === 'purchase-orders')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchase-orders.index')}}">
                                        Ordenes de compra
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'expenses' )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.expenses.index')}}">
                                        Gastos diversos
                                    </a>
                                </li>

                                <li class="nav-parent
                                    {{ ($path[0] === 'persons' && $path[1] === 'suppliers')?'nav-active':'' }}
                                    {{ ($path[0] === 'purchase-quotations')?'nav-active':'' }}">
                                    <a href="#">
                                        Proveedores
                                    </a>
                                    <ul class="dropdown-menu-horizontal">
                                        <li class="{{ ($path[0] === 'persons' && $path[1] === 'suppliers')?'nav-active':'' }}">
                                            <a href="{{route('tenant.persons.index', ['type' => 'suppliers'])}}">
                                                Listado
                                            </a>
                                        </li>
                                        <li class="{{ ($path[0] === 'purchase-quotations')?'nav-active':'' }}">
                                            <a href="{{route('tenant.purchase-quotations.index')}}">
                                                Solicitar cotización
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                {{-- documento de soporte --}}

                                <li class="nav-parent
                                    {{ ($path[0] === 'support-documents')?'nav-active':'' }}
                                    {{ ($path[0] === 'support-document-adjust-notes')?'nav-active':'' }}">
                                    <a href="#">
                                        Documentos de soporte (DSNOF)
                                    </a>
                                    <ul class="dropdown-menu-horizontal">
                                        <li class="{{ ($path[0] === 'support-documents' && $path[1] === 'create')?'nav-active':'' }}">
                                            <a href="{{route('tenant.support-documents.create')}}">
                                                Nuevo
                                            </a>
                                        </li>
                                        <li class="{{ (in_array($path[0], ['support-documents', 'support-document-adjust-notes']) && $path[1] === '')?'nav-active':'' }}">
                                            <a href="{{route('tenant.support-documents.index')}}">
                                                Listado
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                {{-- documento de soporte --}}


                                {{-- <li class="nav-parent
                                    {{ ($path[0] === 'fixed-asset' )?'nav-active nav-expanded':'' }}
                                    ">
                                    <a class="nav-link" href="#">
                                        Activos fijos
                                    </a>
                                    <ul class="nav nav-children">

                                        <li class="{{ ($path[0] === 'fixed-asset' && $path[1] === 'items')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.fixed_asset_items.index')}}">
                                                Ítems
                                            </a>
                                        </li>
                                        <li class="{{ ($path[0] === 'fixed-asset' && $path[1] === 'purchases')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.fixed_asset_purchases.index')}}">
                                                Compras
                                            </a>
                                        </li>
                                    </ul>
                                </li> --}}
                            </ul>
                        </li>
                        @endif

                        @if(in_array('inventory', $vc_modules))
                        <li class="nav-parent {{ (in_array($path[0], ['inventory', 'warehouses', 'moves', 'transfers']) ||
                                                ($path[0] === 'reports' && in_array($path[1], ['kardex', 'inventory', 'valued-kardex'])))?'nav-active':'' }}">
                            <a href="#">
                                <i class="fas fa-warehouse" aria-hidden="true"></i>
                                <span>Inventario</span>
                            </a>
                            <ul class="dropdown-menu-horizontal">
                                <li class="{{ ($path[0] === 'warehouses')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('warehouses.index')}}">Almacenes</a>
                                </li>
                                <li class="{{ ($path[0] === 'inventory')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('inventory.index')}}">Movimientos</a>
                                </li>
                                <li class="{{ ($path[0] === 'transfers')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('transfers.index')}}">Traslados</a>
                                </li>
                                <li class="{{(($path[0] === 'reports') && ($path[1] === 'kardex')) ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('reports.kardex.index')}}">
                                        Reporte Kardex
                                    </a>
                                </li>
                                <li class="{{(($path[0] === 'reports') && ($path[1] == 'inventory')) ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('reports.inventory.index')}}">
                                        Reporte Inventario
                                    </a>
                                </li>
                                <li class="{{(($path[0] === 'reports') && ($path[1] === 'valued-kardex')) ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('reports.valued_kardex.index')}}">
                                        Kardex valorizado
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

                    @endif

                    @if(in_array('configuration', $vc_modules))
                    <li class="nav-parent {{ in_array($path[0], ['users', 'establishments'])?'nav-active':'' }}">
                        <a href="#">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <span>Usuarios/Locales & Series</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            <li class="{{ ($path[0] === 'users')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.users.index')}}">
                                    Usuarios
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'establishments')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.establishments.index')}}">
                                    Establecimientos
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- @if(in_array('advanced', $vc_modules) && $vc_company->soap_type_id != '03')
                    <li class="
                        nav-parent
                        {{ ($path[0] === 'retentions')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'dispatches')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'perceptions')?'nav-active nav-expanded':'' }}
                        ">
                        <a class="nav-link" href="#">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <span>Comprobantes avanzados</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            <li class="{{ ($path[0] === 'retentions')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.retentions.index')}}">
                                    Retenciones
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'dispatches')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.dispatches.index')}}">
                                    Guías de remisión
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'perceptions')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.perceptions.index')}}">
                                Percepciones
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif --}}

                    @if(in_array('reports', $vc_modules))
                    <li class="nav-parent {{  ($path[0] === 'reports' && in_array($path[1], ['report-taxes','purchases', 'search','sales','customers','items',
                                        'general-items','consistency-documents', 'quotations', 'sale-notes','cash','commissions','document-hotels',
                                        'validate-documents', 'document-detractions','commercial-analysis', 'order-notes-consolidated', 'document-pos',
                                        'order-notes-general', 'sales-consolidated', 'user-commissions', 'co-remissions', 'co-items-sold', 'co-sales-book'])) ? 'nav-active' : ''}}">

                        <a href="#">
                            <i class="fas fa-chart-area" aria-hidden="true"></i>
                            <span>Reportes</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'purchases')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.purchases.index')}}">
                                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                                    Compras
                                </a>
                            </li>

                            <li class="nav-parent {{  ($path[0] === 'reports' &&
                                    in_array($path[1], ['sales','customers','items','quotations', 'sale-notes', 'document-detractions', 'document-pos',
                                    'commissions',  'general-items','sales-consolidated', 'user-commissions', 'co-remissions'])) ? 'nav-active' : ''}}">

                                <a href="#">
                                    Ventas
                                </a>
                                <ul class="dropdown-menu-horizontal">
                                    @if($vc_company->soap_type_id != '03')
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'sales')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sales.index')}}">
                                            <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
                                            Documentos
                                        </a>
                                    </li>
                                    @endif
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'customers')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.customers.index')}}">
                                            <i class="fas fa-users" aria-hidden="true"></i>
                                            Clientes
                                        </a>
                                    </li>

                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'document-pos')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.document_pos.index')}}">
                                            <i class="fas fa-receipt" aria-hidden="true"></i>
                                            Documentos POS
                                        </a>
                                    </li>

                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'items')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.items.index')}}">
                                            <i class="fas fa-search" aria-hidden="true"></i>
                                            Producto - busqueda individual
                                        </a>
                                    </li>
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'general-items')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.general_items.index')}}">
                                            <i class="fas fa-box-open" aria-hidden="true"></i>
                                            Productos
                                        </a>
                                    </li>
                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'quotations')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.quotations.index')}}">
                                            Cotizaciones
                                        </a>
                                    </li>
                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'co-remissions')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.co-remissions.index')}}">
                                            Remisiones
                                        </a>
                                    </li>
                                   <!-- <li class="{{(($path[0] === 'reports') && ($path[1] == 'sale-notes')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sale_notes.index')}}">
                                            Notas de Venta
                                        </a>
                                    </li>-->
                                    {{-- @if($vc_company->soap_type_id != '03')
                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'document-detractions')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.document_detractions.index')}}">
                                            Detracciones
                                        </a>
                                    </li>
                                    @endif --}}


                                    <li class="nav-parent
                                        {{ (($path[0] === 'reports') && ($path[1] == 'commissions')) ?'nav-active':'' }}
                                        {{ (($path[0] === 'reports') && ($path[1] == 'user-commissions')) ?'nav-active':'' }}">
                                        <a href="#">
                                            Comisiones
                                        </a>
                                        <ul class="dropdown-menu-horizontal">
                                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'user-commissions')) ? 'nav-active' : ''}}">
                                                <a href="{{route('tenant.reports.user_commissions.index')}}">
                                                    Utilidad ventas
                                                </a>
                                            </li>
                                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'commissions')) ? 'nav-active' : ''}}">
                                                <a href="{{route('tenant.reports.commissions.index')}}">
                                                    Ventas
                                                </a>
                                            </li>
                                        </ul>
                                    </li>


                                    {{-- <li class="{{(($path[0] === 'reports') && ($path[1] == 'sales-consolidated')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sales_consolidated.index')}}">
                                            Consolidado de items
                                        </a>
                                    </li> --}}
                                </ul>
                            </li>

                            {{-- <li class="nav-parent {{  ($path[0] === 'reports' &&
                                    in_array($path[1], ['order-notes-consolidated', 'order-notes-general'])) ? 'nav-active nav-expanded' : ''}}">

                                <a class="nav-link" href="#">
                                    Pedidos
                                </a>
                                <ul class="nav nav-children">

                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'order-notes-general')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.order_notes_general.index')}}">
                                            General
                                        </a>
                                    </li>

                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'order-notes-consolidated')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.order_notes_consolidated.index')}}">
                                            Consolidado de items
                                        </a>
                                    </li>
                                </ul>
                            </li> --}}

                            {{-- @if($vc_company->soap_type_id != '03')
                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'consistency-documents')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.consistency-documents.index')}}">Consistencia documentos</a>
                            </li>

                             <li class="{{(($path[0] === 'reports') && ($path[1] == 'validate-documents')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.validate_documents.index')}}">
                                    Validador de documentos
                                </a>
                            </li>
                            @endif --}}
                            @if(in_array('hotel', $vc_business_turns))
                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'document-hotels')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.document_hotels.index')}}">
                                    Giro negocio hoteles
                                </a>
                            </li>
                            @endif
                            <!-- <li class="{{(($path[0] === 'reports') && ($path[1] == 'commercial-analysis')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.commercial_analysis.index')}}">
                                    Análisis comercial
                                </a>
                            </li> -->
                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'report-taxes')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.taxes')}}">
                                    Impuestos
                                </a>
                            </li>

                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'co-items-sold')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.co-items-sold.index')}}">
                                    Artículos vendidos
                                </a>
                            </li>

                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'co-sales-book')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.co-sales-book.index')}}">
                                    Libro de ventas
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif

                    @if(in_array('finance', $vc_modules))
                    <li class="nav-parent {{$path[0] === 'finances' && in_array($path[1], [
                                                'global-payments', 'balance','payment-method-types', 'unpaid', 'to-pay', 'income'
                                            ])
                                            ? 'nav-active' : ''}}">

                        <a href="#">
                            <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                            <span>Finanzas</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            @if(auth()->user()->type != 'integrator')
                                <li class="{{($path[0] === 'finances') ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('tenant.catalogs.index')}}">
                                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                                        Catálogos
                                    </a>
                                </li>
                            @endif
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'global-payments')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.global_payments.index')}}">
                                    <i class="fas fa-credit-card" aria-hidden="true"></i>
                                    Pagos
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'balance')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.balance.index')}}">
                                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                                    Balance
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'payment-method-types')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.payment_method_types.index')}}">
                                    <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                                    Ingresos y Egresos - M. Pago
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'unpaid')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.unpaid.index')}}">
                                    <i class="fas fa-clock" aria-hidden="true"></i>
                                    Cuentas por cobrar
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'to-pay')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.to_pay.index')}}">
                                    <i class="fas fa-calendar-times" aria-hidden="true"></i>
                                    Cuentas por pagar
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'income')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.income.index')}}">
                                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                                    Ingresos
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif



                    @if(in_array('payroll', $vc_modules))

                    <li class="nav-parent {{$path[0] === 'payroll' &&
                                            in_array($path[1], [
                                                'workers', 'document-payrolls', 'document-payroll-adjust-notes'
                                            ])
                                            // &&
                                            // in_array($path[2], [
                                            //     'create'
                                            // ])
                                            ? 'nav-active nav-expanded' : ''}}">

                        <a href="#">
                            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                            <span>Nóminas</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            <li class="{{(($path[0] === 'payroll') && ($path[1] == 'workers')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.workers.index')}}">
                                    Empleados
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'payroll') && ($path[1] == 'document-payrolls') && $path[2] == 'create') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.document-payrolls.create')}}">
                                    Nueva nómina
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'payroll') && (in_array($path[1], ['document-payrolls', 'document-payroll-adjust-notes'])) && ($path[2] !== 'create')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.document-payrolls.index')}}">
                                    Listado de nóminas
                                </a>
                            </li>
                            <li class="nav-parent {{ ($path[0] === 'payroll') ? 'nav-active nav-expanded' : '' }}
                                                  {{ ($path[0] === 'block-payrolls') ? 'nav-active nav-expanded' : '' }}">
                                <a class="nav-link" href="#">
                                    Nóminas en bloque
                                </a>
                                <ul class="dropdown-menu-horizontal">
                                    <li class="{{ ($path[0] === 'block-payrolls') ? 'nav-active' : '' }}">
                                        <a href="{{route('tenant.block-payrolls.index')}}">
                                            Listado de bloques
                                        </a>
                                    </li>
                                    <li class="{{ ($path[0] === 'new-block-payroll') ? 'nav-active' : '' }}">
                                        <a href="{{route('tenant.block-payrolls.create')}}">
                                            Nuevo bloque de nóminas
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        {{-- <ul class="nav nav-children" style="">
                            <li class="{{(($path[0] === 'payroll') && ($path[1] == 'type-workers')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.type-workers.index')}}">
                                    Tipos de empleados
                                </a>
                            </li>
                        </ul> --}}
                    </li>
                    @endif


                    @if(in_array('configuration', $vc_modules))
                    <li class="nav-parent {{in_array($path[0], [
                        'co-configuration-change-ambient', 'co-configuration', 'co-configuration-documents',
                        'companies', 'catalogs', 'advanced', 'tasks', 'inventories','company_accounts','bussiness_turns',
                        'offline-configurations','series-configurations','configurations','co-advanced-configuration'
                    ]) ? 'nav-active' : ''}}">
                        <a href="#">
                            <i class="fas fa-cogs" aria-hidden="true"></i>
                            <span>Configuración</span>
                        </a>
                        <ul class="dropdown-menu-horizontal">
                            <li class="{{($path[0] === 'co-configuration-change-ambient') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.configuration.change.ambient')}}">
                                    Cambiar ambiente
                                </a>
                            </li>
                            <li class="{{($path[0] === 'co-configuration-documents') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.configuration.documents')}}">
                                    Documentos
                                </a>
                            </li>
                            <li class="{{($path[0] === 'co-configuration') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.configuration')}}">
                                    Empresa
                                </a>
                            </li>
                            {{-- <li class="{{($path[0] === 'companies') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.companies.create')}}">
                                    Empresa
                                </a>
                            </li>
                            <li class="{{($path[0] === 'company_accounts') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.company_accounts.create')}}">
                                    Cuentas contables
                                </a>
                            </li>
                            <li class="{{($path[0] === 'bussiness_turns') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.bussiness_turns.index')}}">
                                    Giro de negocio
                                </a>
                            </li>
                            @if(auth()->user()->type != 'integrator')
                            <li class="{{($path[0] === 'catalogs') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.catalogs.index')}}">
                                    Catálogos
                                </a>
                            </li>
                            @endif  --}}

                            <li class="{{($path[0] === 'co-advanced-configuration') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.co-advanced-configuration.index')}}">
                                    Avanzado
                                </a>
                            </li>

                            {{-- <li class="{{($path[0] === 'advanced') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.advanced.index')}}">
                                    Avanzado
                                </a>
                            </li>

                            <li class="{{($path[1] === 'pdf_templates') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.advanced.pdf_templates')}}">
                                    Plantillas PDF
                                </a>
                            </li>
                            @if($vc_company->soap_type_id != '03')
                            <li class="{{($path[0] === 'offline-configurations') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.offline_configurations.index')}}">
                                    Modo offline
                                </a>
                            </li>
                            <li class="{{($path[0] === 'series-configurations') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.series_configurations.index')}}">
                                    Numeración de facturación
                                </a>
                            </li>
                            @endif --}}
                            {{-- @if(auth()->user()->type != 'integrator' && $vc_company->soap_type_id != '03')
                            <li class="{{($path[0] === 'tasks') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.tasks.index')}}">Tareas programadas</a>
                            </li>
                            @endif --}}

                            <li class="{{($path[0] === 'inventories' && $path[1] === 'configuration') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.inventories.configuration.index')}}">Inventarios</a>
                            </li>

                            <li class="{{($path[0] === 'backup') ? 'nav-active': ''}}">
                                <a class="nav-link" href="{{route('tenant.backup.index')}}">Copias de seguridad</a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(in_array('radian', $vc_modules))
                        <li class="nav-parent {{in_array($path[0], ['co-radian-events', 'co-email-reading']) ? 'nav-active' : ''}}">
                            <a href="#">
                                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                                <span>Eventos RADIAN</span>
                            </a>
                            <ul class="dropdown-menu-horizontal">
                                <li class="{{($path[0] === 'co-email-reading' && $path[1] == 'process-emails') ? 'nav-active' : ''}}">
                                    <a href="{{route('tenant.co-email-reading-process-emails.index')}}">
                                        Procesar correos
                                    </a>
                                </li>
                                <li class="{{($path[0] === 'co-radian-events' && $path[1] == 'reception') ? 'nav-active' : ''}}">
                                    <a href="{{route('tenant.co-radian-events-reception.index')}}">
                                        Recepción de documentos
                                    </a>
                                </li>
                                <li class="{{($path[0] === 'co-radian-events' && $path[1] == 'manage') ? 'nav-active' : ''}}">
                                    <a href="{{route('tenant.co-radian-events-manage.index')}}">
                                        Gestionar eventos Doc. Procesados
                                    </a>
                                </li>
                                <li class="{{($path[0] === 'co-radian-events' && $path[1] == 'radian-cufe') ? 'nav-active' : ''}}">
                                    <a href="{{route('tenant.co-radian-cufe.index')}}">
                                        Gestionar eventos con CUFE
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif


                    {{-- @if(in_array('cuenta', $vc_modules))
                    <li class=" nav-parent
                        {{ ($path[0] === 'cuenta')?'nav-active nav-expanded':'' }}">
                        <a class="nav-link" href="#">
                            <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span>
                            <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                            <span>Mis Pagos</span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="{{ (($path[0] === 'cuenta') && ($path[1] === 'configuration')) ?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.configuration.index')}}">
                                    Configuracion
                                </a>
                            </li>
                            <li class="{{ (($path[0] === 'cuenta') && ($path[1] === 'payment_index')) ?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.payment.index')}}">
                                    Lista de Pagos
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif --}}

                    <!-- Separador y nombre del usuario -->
                    <li class="nav-user-info">
                        <span class="user-name">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            {{ $vc_user->name }}
                        </span>
                    </li>

                    <!-- Opción de Salir al final del menú -->
                    <li class="nav-logout">
                        <a class="nav-link" href="#" onclick="confirmLogout()" title="Cerrar Sesión">
                            <i class="fas fa-power-off" aria-hidden="true"></i>
                            <span>Salir</span>
                        </a>
                        <form id="logout-form-horizontal" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
        </ul>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const nav = document.getElementById('horizontal-nav');
    nav.classList.toggle('show');
}

// Variables globales para manejo de menús
let currentOpenDropdown = null;
let hideTimeout = null;

// Posicionamiento dinámico de menús desplegables - con submenús
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.horizontal-nav > li.nav-parent');

    if (navItems.length === 0) {
        return;
    }

    navItems.forEach(function(item, index) {
        const dropdown = item.querySelector('.dropdown-menu-horizontal');
        const itemText = item.querySelector('span') ? item.querySelector('span').textContent.trim() : 'Sin texto';

        if (dropdown) {
            let isDropdownOpen = false;

            // Función para mostrar el menú
            function showDropdown() {


                // Cancelar timeout si existe
                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }

                // LIMPIAR TODOS los submenus flotantes y de tercer nivel al abrir un nuevo dropdown
                const allFloatingSubmenus = document.querySelectorAll(`[id^="floating-submenu-"]`);
                allFloatingSubmenus.forEach(floating => {
                    try {
                        document.body.removeChild(floating);

                    } catch (e) {

                    }
                });

                const allThirdLevelMenus = document.querySelectorAll(`[id^="floating-third-level-"]`);
                allThirdLevelMenus.forEach(thirdLevel => {
                    try {
                        document.body.removeChild(thirdLevel);

                    } catch (e) {

                    }
                });

                // Ocultar menú anterior
                if (currentOpenDropdown && currentOpenDropdown !== dropdown) {

                    currentOpenDropdown.style.display = 'none';
                }

                // Posicionar y mostrar menú
                const rect = item.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.left = rect.left + 'px';
                dropdown.style.top = '58px';
                dropdown.style.zIndex = '99999';
                dropdown.style.display = 'block';
                currentOpenDropdown = dropdown;
                isDropdownOpen = true;


            }

            // Función para ocultar el menú
            function hideDropdown() {

                dropdown.style.display = 'none';

                // CERRAR TODOS LOS SUBMENUS AL CERRAR EL DROPDOWN PRINCIPAL


                // Limpiar TODOS los submenus flotantes (no solo de este dropdown)
                const allFloatingSubmenus = document.querySelectorAll(`[id^="floating-submenu-"]`);
                allFloatingSubmenus.forEach(floating => {
                    try {
                        document.body.removeChild(floating);

                    } catch (e) {

                    }
                });

                // Limpiar TODOS los submenus de tercer nivel
                const allThirdLevelMenus = document.querySelectorAll(`[id^="floating-third-level-"]`);
                allThirdLevelMenus.forEach(thirdLevel => {
                    try {
                        document.body.removeChild(thirdLevel);

                    } catch (e) {

                    }
                });

                // Ocultar submenus normales
                const submenus = dropdown.querySelectorAll('.dropdown-menu-horizontal');
                submenus.forEach(function(submenu) {
                    submenu.style.display = 'none';
                    submenu.dataset.openedByClick = 'false';
                });

                if (currentOpenDropdown === dropdown) {
                    currentOpenDropdown = null;
                }
                isDropdownOpen = false;

            }

            // Mostrar menú al hacer CLICK en el elemento principal
            const mainLink = item.querySelector('a');
            if (mainLink) {

                mainLink.addEventListener('click', function(e) {
                    e.preventDefault();


                    if (isDropdownOpen) {

                        hideDropdown();
                    } else {

                        showDropdown();
                    }
                });
            } else {

            }

            // Mostrar menú al pasar el mouse por el elemento principal
            item.addEventListener('mouseenter', function() {

                showDropdown();
            });

            // Ocultar menú con retraso al salir del elemento principal
            item.addEventListener('mouseleave', function() {

                hideTimeout = setTimeout(function() {
                    // Verificar si el mouse está sobre el dropdown o algún submenu flotante
                    let mouseOverMenu = dropdown.matches(':hover');

                    const allFloatingSubmenus = document.querySelectorAll(`[id^="floating-submenu-"]`);
                    let mouseOverFloating = false;

                    allFloatingSubmenus.forEach(floating => {
                        if (floating.matches(':hover')) {
                            mouseOverFloating = true;
                        }
                    });

                    // Solo cerrar si el mouse no está sobre ningún menú
                    if (!mouseOverMenu && !mouseOverFloating) {


                        // Limpiar TODOS los submenus flotantes de segundo nivel
                        allFloatingSubmenus.forEach(floating => {
                            try {
                                document.body.removeChild(floating);
                            } catch (e) {

                            }
                        });

                        // Limpiar TODOS los submenus de tercer nivel
                        const allThirdLevelMenus = document.querySelectorAll(`[id^="floating-third-level-"]`);
                        allThirdLevelMenus.forEach(thirdLevel => {
                            try {
                                document.body.removeChild(thirdLevel);

                            } catch (e) {

                            }
                        });

                        hideDropdown();
                    } else {

                    }
                }, 200);
            });

            // Mantener menú visible cuando el mouse está sobre él
            dropdown.addEventListener('mouseenter', function() {

                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }
            });

            // Ocultar menú al salir del dropdown
            dropdown.addEventListener('mouseleave', function(e) {


                // Verificar si el mouse está moviéndose hacia un submenu flotante
                const allFloatingSubmenus = document.querySelectorAll(`[id^="floating-submenu-"]`);
                let movingToFloatingSubmenu = false;

                // Verificar si el mouse está sobre algún submenu flotante
                allFloatingSubmenus.forEach(floating => {
                    const rect = floating.getBoundingClientRect();
                    if (e.clientX >= rect.left && e.clientX <= rect.right &&
                        e.clientY >= rect.top && e.clientY <= rect.bottom) {
                        movingToFloatingSubmenu = true;
                    }
                });

                if (!movingToFloatingSubmenu) {
                    // Programar cierre con delay para dar tiempo al mouse de moverse
                    setTimeout(() => {
                        // Verificar de nuevo si el mouse está sobre algún submenu flotante
                        let mouseOverFloating = false;
                        const floatingMenus = document.querySelectorAll(`[id^="floating-submenu-"]`);

                        floatingMenus.forEach(floating => {
                            if (floating.matches(':hover')) {
                                mouseOverFloating = true;
                            }
                        });

                        if (!mouseOverFloating) {

                            allFloatingSubmenus.forEach(floating => {
                                try {
                                    document.body.removeChild(floating);
                                } catch (e) {

                                }
                            });
                            hideDropdown();
                        }
                    }, 200);
                } else {

                }
            });

            // MANEJO DE SUBMENÚS LATERALES (ESTILO WINDOWS)
            const subMenuItems = dropdown.querySelectorAll('.nav-parent');


            subMenuItems.forEach(function(subItem, subIndex) {
                const subDropdown = subItem.querySelector('.dropdown-menu-horizontal');


                if (subDropdown) {
                    let subMenuTimeout = null;
                    let isSubMenuOpen = false;
                    let openedByClick = false; // Para distinguir apertura por click vs hover

                    // Función para mostrar submenu
                    function showSubMenu(byClick = false) {


                        // Cancelar timeout de ocultado
                        if (subMenuTimeout) {
                            clearTimeout(subMenuTimeout);
                            subMenuTimeout = null;
                        }
                        if (hideTimeout) {
                            clearTimeout(hideTimeout);
                            hideTimeout = null;
                        }

                        // Si es un click, cerrar TODOS los otros submenus flotantes primero
                        if (byClick) {
                            const allFloatingSubmenus = document.querySelectorAll('[id^="floating-submenu-"]');
                            allFloatingSubmenus.forEach(floating => {

                                try {
                                    document.body.removeChild(floating);
                                } catch (e) {

                                }
                            });
                        }

                        // Verificar si hay submenu flotante existente para este item específico
                        const existingFloating = document.querySelector(`[id="floating-submenu-${subIndex}"]`);

                        // Si ya está abierto por click (flotante) y esto es hover, no hacer nada
                        if (existingFloating && !byClick) {

                            return;
                        }

                        // Calcular posición para submenu lateral (estilo Windows)
                        const parentRect = subItem.getBoundingClientRect();
                        const dropdownRect = dropdown.getBoundingClientRect();

                        // Obtener dimensiones del submenu
                        subDropdown.style.display = 'block';
                        subDropdown.style.visibility = 'hidden';
                        const subMenuWidth = subDropdown.offsetWidth || 280;
                        const subMenuHeight = subDropdown.offsetHeight || 200;
                        subDropdown.style.visibility = 'visible';

                        // Posición base: al lado derecho del elemento padre
                        let leftPos = parentRect.right + 5;
                        let topPos = parentRect.top;

                        // Ajustar si se sale por la derecha
                        if (leftPos + subMenuWidth > window.innerWidth - 10) {
                            leftPos = parentRect.left - subMenuWidth - 5; // Mostrar a la izquierda

                        }

                        // Ajustar si se sale por abajo
                        if (topPos + subMenuHeight > window.innerHeight - 10) {
                            topPos = window.innerHeight - subMenuHeight - 10;

                        }

                        // Asegurar que no se salga por arriba
                        if (topPos < 70) { // 70px para evitar el navbar
                            topPos = 70;
                        }

                        // Si se abre por click, crear submenu flotante
                        if (byClick) {
                            // First, remove any existing floating submenu for this subIndex
                            const existingFloating = document.querySelector(`[id="floating-submenu-${subIndex}"]`);
                            if (existingFloating) {

                                document.body.removeChild(existingFloating);
                            }

                            // Crear un clon del submenu y agregarlo al body
                            const clonedSubmenu = subDropdown.cloneNode(true);
                            clonedSubmenu.id = `floating-submenu-${subIndex}`;
                            clonedSubmenu.className = 'dropdown-menu-horizontal floating-submenu';
                            clonedSubmenu.style.position = 'fixed';
                            clonedSubmenu.style.left = leftPos + 'px';
                            clonedSubmenu.style.top = topPos + 'px';
                            clonedSubmenu.style.zIndex = '100010';
                            clonedSubmenu.style.display = 'block';
                            clonedSubmenu.dataset.openedByClick = 'true';
                            clonedSubmenu.dataset.subIndex = subIndex;



                            // Agregar al body
                            document.body.appendChild(clonedSubmenu);

                            // PROCESAR SUBMENUS DE TERCER NIVEL dentro del submenu flotante
                            const thirdLevelItems = clonedSubmenu.querySelectorAll('.nav-parent');


                            thirdLevelItems.forEach(function(thirdLevelItem, thirdIndex) {
                                const thirdLevelDropdown = thirdLevelItem.querySelector('.dropdown-menu-horizontal');

                                if (thirdLevelDropdown) {


                                    // Agregar click listener para tercer nivel
                                    thirdLevelItem.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();



                                        // Cerrar otros submenus de tercer nivel
                                        const otherThirdLevel = document.querySelectorAll(`[id^="floating-third-level-"]`);
                                        otherThirdLevel.forEach(other => {
                                            try {
                                                document.body.removeChild(other);
                                            } catch (e) {

                                            }
                                        });

                                        // Crear submenu flotante de tercer nivel
                                        const thirdLevelRect = thirdLevelItem.getBoundingClientRect();
                                        const clonedThirdLevel = thirdLevelDropdown.cloneNode(true);

                                        clonedThirdLevel.id = `floating-third-level-${subIndex}-${thirdIndex}`;
                                        clonedThirdLevel.className = 'dropdown-menu-horizontal floating-submenu';
                                        clonedThirdLevel.style.position = 'fixed';
                                        clonedThirdLevel.style.left = (thirdLevelRect.right + 5) + 'px';
                                        clonedThirdLevel.style.top = thirdLevelRect.top + 'px';
                                        clonedThirdLevel.style.zIndex = '100015';
                                        clonedThirdLevel.style.display = 'block';

                                        // Ajustar posición si se sale de la pantalla
                                        if (thirdLevelRect.right + 285 > window.innerWidth) {
                                            clonedThirdLevel.style.left = (thirdLevelRect.left - 285) + 'px';
                                        }

                                        document.body.appendChild(clonedThirdLevel);


                                        // Event listeners para el tercer nivel
                                        clonedThirdLevel.addEventListener('mouseenter', function() {

                                        });

                                        clonedThirdLevel.addEventListener('mouseleave', function() {

                                            setTimeout(() => {
                                                if (!clonedThirdLevel.matches(':hover')) {
                                                    try {
                                                        document.body.removeChild(clonedThirdLevel);
                                                    } catch (e) {

                                                    }
                                                }
                                            }, 200);
                                        });
                                    });

                                    // Hover para tercer nivel
                                    thirdLevelItem.addEventListener('mouseenter', function() {


                                        // Crear submenu flotante de tercer nivel por hover
                                        const existingThirdLevel = document.querySelector(`[id="floating-third-level-${subIndex}-${thirdIndex}"]`);
                                        if (!existingThirdLevel) {
                                            const thirdLevelRect = thirdLevelItem.getBoundingClientRect();
                                            const clonedThirdLevel = thirdLevelDropdown.cloneNode(true);

                                            clonedThirdLevel.id = `floating-third-level-${subIndex}-${thirdIndex}`;
                                            clonedThirdLevel.className = 'dropdown-menu-horizontal floating-submenu';
                                            clonedThirdLevel.style.position = 'fixed';
                                            clonedThirdLevel.style.left = (thirdLevelRect.right + 5) + 'px';
                                            clonedThirdLevel.style.top = thirdLevelRect.top + 'px';
                                            clonedThirdLevel.style.zIndex = '100015';
                                            clonedThirdLevel.style.display = 'block';
                                            clonedThirdLevel.dataset.openedByHover = 'true';

                                            // Ajustar posición si se sale de la pantalla
                                            if (thirdLevelRect.right + 285 > window.innerWidth) {
                                                clonedThirdLevel.style.left = (thirdLevelRect.left - 285) + 'px';
                                            }

                                            document.body.appendChild(clonedThirdLevel);

                                        }
                                    });

                                    thirdLevelItem.addEventListener('mouseleave', function() {
                                        setTimeout(() => {
                                            const thirdLevelMenu = document.querySelector(`[id="floating-third-level-${subIndex}-${thirdIndex}"]`);
                                            if (thirdLevelMenu && thirdLevelMenu.dataset.openedByHover === 'true' && !thirdLevelMenu.matches(':hover')) {
                                                try {
                                                    document.body.removeChild(thirdLevelMenu);
                                                } catch (e) {

                                                }
                                            }
                                        }, 300);
                                    });
                                }
                            });

                            // Agregar eventos para mantener el submenu abierto cuando el mouse está sobre él
                            clonedSubmenu.addEventListener('mouseenter', function() {

                                // Cancelar cualquier timeout de cierre del menú principal
                                if (hideTimeout) {
                                    clearTimeout(hideTimeout);
                                    hideTimeout = null;
                                }
                            });

                            // Agregar evento para manejar cuando el mouse sale del submenu flotante
                            clonedSubmenu.addEventListener('mouseleave', function(e) {


                                // Verificar si el mouse está regresando al menú principal
                                const dropdownRect = dropdown.getBoundingClientRect();
                                const movingToDropdown = (e.clientX >= dropdownRect.left && e.clientX <= dropdownRect.right &&
                                                        e.clientY >= dropdownRect.top && e.clientY <= dropdownRect.bottom);

                                if (!movingToDropdown) {
                                    // Si no se está moviendo al menú principal, programar cierre
                                    setTimeout(() => {
                                        // Verificar si el mouse está en algún menú de tercer nivel
                                        const allThirdLevelMenus = document.querySelectorAll(`[id^="floating-third-level-"]`);
                                        let mouseOverThirdLevel = false;

                                        allThirdLevelMenus.forEach(thirdLevel => {
                                            if (thirdLevel.matches(':hover')) {
                                                mouseOverThirdLevel = true;
                                            }
                                        });

                                        if (!dropdown.matches(':hover') && !clonedSubmenu.matches(':hover') && !mouseOverThirdLevel) {


                                            // Limpiar menús de tercer nivel ANTES de cerrar el submenu flotante
                                            allThirdLevelMenus.forEach(thirdLevel => {
                                                try {
                                                    document.body.removeChild(thirdLevel);

                                                } catch (e) {

                                                }
                                            });

                                            try {
                                                document.body.removeChild(clonedSubmenu);
                                            } catch (e) {

                                            }
                                            hideDropdown();
                                        }
                                    }, 200);
                                }
                            });

                            // Agregar evento para cerrar al hacer click fuera DESPUÉS de un pequeño delay
                            setTimeout(() => {
                                function closeFloatingSubmenu(e) {
                                    // Verificar si el click fue en algún menú de tercer nivel
                                    const allThirdLevelMenus = document.querySelectorAll(`[id^="floating-third-level-"]`);
                                    let clickedOnThirdLevel = false;

                                    allThirdLevelMenus.forEach(thirdLevel => {
                                        if (thirdLevel.contains(e.target)) {
                                            clickedOnThirdLevel = true;
                                        }
                                    });

                                    if (!clonedSubmenu.contains(e.target) && !subItem.contains(e.target) && !clickedOnThirdLevel) {


                                        // Limpiar menús de tercer nivel
                                        allThirdLevelMenus.forEach(thirdLevel => {
                                            try {
                                                document.body.removeChild(thirdLevel);

                                            } catch (e) {

                                            }
                                        });

                                        if (document.body.contains(clonedSubmenu)) {
                                            document.body.removeChild(clonedSubmenu);
                                        }
                                        document.removeEventListener('click', closeFloatingSubmenu);
                                        isSubMenuOpen = false;
                                        openedByClick = false;
                                    }
                                }
                                document.addEventListener('click', closeFloatingSubmenu);
                            }, 200);

                            isSubMenuOpen = true;
                            openedByClick = true;

                        } else {
                            // Comportamiento normal para hover
                            subDropdown.style.position = 'fixed';
                            subDropdown.style.left = leftPos + 'px';
                            subDropdown.style.top = topPos + 'px';
                            subDropdown.style.zIndex = '100005';
                            subDropdown.style.display = 'block';
                            subDropdown.dataset.openedByClick = 'false';
                            isSubMenuOpen = true;
                            openedByClick = false;

                        }

                        isSubMenuOpen = true;
                        openedByClick = byClick;
                    }

                    // Función para ocultar submenu
                    function hideSubMenu() {


                        // Buscar y eliminar TODOS los submenus flotantes (no solo de este item)
                        const allFloatingSubmenus = document.querySelectorAll(`[id^="floating-submenu-"]`);
                        allFloatingSubmenus.forEach(floating => {

                            try {
                                document.body.removeChild(floating);
                            } catch (e) {

                            }
                        });

                        // Buscar y eliminar TODOS los submenus de TERCER NIVEL
                        const allThirdLevelMenus = document.querySelectorAll(`[id^="floating-third-level-"]`);
                        allThirdLevelMenus.forEach(thirdLevel => {

                            try {
                                document.body.removeChild(thirdLevel);
                            } catch (e) {

                            }
                        });

                        // Ocultar submenu normal
                        subDropdown.style.display = 'none';
                        subDropdown.dataset.openedByClick = 'false';
                        isSubMenuOpen = false;
                        openedByClick = false;
                    }

                    // Click en elemento con submenu - AGREGAR AL ELEMENTO LI
                    const subLink = subItem.querySelector('a');
                    if (subLink) {


                        // Prevenir navegación del enlace
                        subLink.addEventListener('click', function(e) {
                            e.preventDefault();
                        });

                        // Agregar click listener al elemento LI completo
                        subItem.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation(); // Evitar propagación del evento

                            // PRIMERO: Cerrar TODOS los otros submenus flotantes
                            const allFloatingSubmenus = document.querySelectorAll('[id^="floating-submenu-"]');
                            allFloatingSubmenus.forEach(floating => {

                                try {
                                    document.body.removeChild(floating);
                                } catch (e) {

                                }
                            });

                            // Verificar si hay submenu flotante existente para este item específico
                            const existingFloating = document.querySelector(`[id="floating-submenu-${subIndex}"]`);




                            if (!existingFloating) {

                                showSubMenu(true); // true = abierto por click
                            } else {

                                isSubMenuOpen = false;
                                openedByClick = false;
                            }
                        });
                    }

                    subItem.addEventListener('mouseenter', function() {


                        // Cancelar cualquier timeout de cierre
                        if (subMenuTimeout) {
                            clearTimeout(subMenuTimeout);
                            subMenuTimeout = null;

                        }

                        // Verificar si hay submenu flotante para este item específico
                        const existingFloating = document.querySelector(`[id="floating-submenu-${subIndex}"]`);


                        if (!existingFloating && !isSubMenuOpen) {

                            showSubMenu(false); // false = abierto por hover
                        } else if (existingFloating) {

                        } else if (isSubMenuOpen) {

                        }
                    });

                    subItem.addEventListener('mouseleave', function(e) {


                        // Verificar si hay submenu flotante (no cerrar)
                        const existingFloating = document.querySelector(`[id="floating-submenu-${subIndex}"]`);
                        if (existingFloating) {

                            return;
                        }

                        // Solo programar cierre para hover, no para click
                        if (!openedByClick && isSubMenuOpen) {

                            subMenuTimeout = setTimeout(function() {
                                if (!openedByClick) { // Verificar de nuevo antes de cerrar

                                    hideSubMenu();
                                }
                            }, 300);
                        }
                    });

                    // Mantener submenu visible cuando el mouse está sobre él
                    subDropdown.addEventListener('mouseenter', function() {

                        if (subMenuTimeout) {
                            clearTimeout(subMenuTimeout);
                            subMenuTimeout = null;

                        }
                    });

                    subDropdown.addEventListener('mouseleave', function() {


                        // Solo cerrar si no fue abierto por click
                        if (!openedByClick) {

                            setTimeout(() => {
                                if (!openedByClick) {
                                    hideSubMenu();
                                }
                            }, 100);
                        } else {

                        }
                    });
                }
            });
        }
    });
});

// Cerrar menús al hacer clic fuera
document.addEventListener('click', function(event) {
    const nav = document.getElementById('horizontal-nav');
    const toggle = document.querySelector('.mobile-menu-toggle');

    // Cerrar menú móvil
    if (!nav.contains(event.target) && !toggle.contains(event.target)) {
        nav.classList.remove('show');
    }

    // Verificar si el click fue fuera de cualquier menú o submenu flotante
    const clickedInsideMenu = nav.contains(event.target);
    const clickedInsideFloatingSubmenu = Array.from(document.querySelectorAll('[id^="floating-submenu-"]'))
        .some(floating => floating.contains(event.target));
    const clickedInsideThirdLevel = Array.from(document.querySelectorAll('[id^="floating-third-level-"]'))
        .some(thirdLevel => thirdLevel.contains(event.target));

    // Si el click fue fuera de menús, submenus flotantes y menús de tercer nivel, cerrar todos
    if (!clickedInsideMenu && !clickedInsideFloatingSubmenu && !clickedInsideThirdLevel) {


        // Limpiar menús de tercer nivel
        const allThirdLevelMenus = document.querySelectorAll('[id^="floating-third-level-"]');
        allThirdLevelMenus.forEach(thirdLevel => {

            try {
                document.body.removeChild(thirdLevel);
            } catch (e) {

            }
        });

        // Limpiar submenus flotantes
        const allFloatingSubmenus = document.querySelectorAll('[id^="floating-submenu-"]');
        allFloatingSubmenus.forEach(floating => {

            try {
                document.body.removeChild(floating);
            } catch (e) {

            }
        });
    }

    // Cerrar menús desplegables si se hace click fuera
    const clickedInsideDropdown = event.target.closest('.dropdown-menu-horizontal');
    const clickedOnNavParent = event.target.closest('.horizontal-nav > li.nav-parent > a');

    if (!clickedInsideDropdown && !clickedOnNavParent) {

        const dropdowns = document.querySelectorAll('.dropdown-menu-horizontal');
        dropdowns.forEach(function(dropdown) {
            dropdown.style.display = 'none';
        });

        // Limpiar todos los submenus flotantes
        const floatingSubmenus = document.querySelectorAll('[id^="floating-submenu-"]');
        floatingSubmenus.forEach(function(floating) {

            document.body.removeChild(floating);
        });

        if (currentOpenDropdown) {
            currentOpenDropdown = null;
        }
    }
});

// Mejorar la navegación con teclado
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('horizontal-nav').classList.remove('show');

        // También cerrar menús desplegables
        const dropdowns = document.querySelectorAll('.dropdown-menu-horizontal');
        dropdowns.forEach(function(dropdown) {
            dropdown.style.display = 'none';
        });
    }
});

// Función para confirmar logout
function confirmLogout() {
    // Verificar si SweetAlert2 está disponible
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: '¿Está seguro que desea cerrar sesión?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form-horizontal').submit();
            }
        });
    } else {
        // Fallback a confirm() básico si SweetAlert2 no está disponible
        if (confirm('¿Está seguro que desea cerrar sesión?')) {
            document.getElementById('logout-form-horizontal').submit();
        }
    }
}

</script>

@else
    {{-- MENÚ LATERAL --}}
    @include('tenant.layouts.partials.sidebar_lateral')
@endif
