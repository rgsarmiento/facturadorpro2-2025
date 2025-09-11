<!-- HEADER MODERNO PARA FACTURADOR PRO -->
<header class="header modern-header" data-plugin-options="{'stickyEnabled': true, 'stickyEnableOnBoxed': true, 'stickyEnableOnMobile': false, 'stickyChangeLogo': false, 'stickyStartAt': 45, 'stickyHeaderContainerHeight': 70}">
    <div class="header-body">
        <div class="header-container container-fluid">
            <div class="header-row">

                <!-- LOGO Y TOGGLE -->
                <div class="header-column">
                    <div class="header-row">
                        <div class="header-logo">
                            <!-- Toggle para menú lateral -->
                            @if(!($vc_horizontal_menu ?? true))
                                <button type="button" class="btn header-btn-collapse-nav sidebar-toggle" data-bs-toggle="collapse" data-bs-target="#sidebar">
                                    <i class="fas fa-bars modern-icon"></i>
                                </button>
                            @endif

                            <!-- Logo de la empresa -->
                            <a href="{{ route('tenant.dashboard.index') }}" class="logo-link">
                                @if(file_exists(public_path('storage/uploads/logos/' . $company->logo)) && $company->logo)
                                    <img alt="{{ $company->name }}"
                                         width="120"
                                         height="auto"
                                         src="{{ asset('storage/uploads/logos/' . $company->logo) }}"
                                         class="company-logo modern-logo">
                                @else
                                    <div class="logo-placeholder">
                                        <i class="fas fa-building"></i>
                                        <span>{{ $company->name ?? 'Facturador PRO' }}</span>
                                    </div>
                                @endif
                            </a>
                        </div>
                    </div>
                </div>

                <!-- NAVEGACIÓN HORIZONTAL (solo cuando está activa) -->
                @if($vc_horizontal_menu ?? true)
                    <div class="header-column justify-content-center">
                        <div class="header-row">
                            <div class="header-nav header-nav-line header-nav-top-line header-nav-top-line-with-border">
                                <nav class="horizontal-nav-container">
                                    <!-- Aquí se incluiría la navegación horizontal -->
                                    <ul class="horizontal-nav-menu">
                                        <li class="nav-item {{ request()->routeIs('tenant.dashboard.*') ? 'active' : '' }}">
                                            <a href="{{ route('tenant.dashboard.index') }}" class="nav-link">
                                                <i class="fas fa-tachometer-alt"></i>
                                                <span>Dashboard</span>
                                            </a>
                                        </li>
                                        <!-- Más elementos de navegación aquí -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ACCIONES DEL HEADER -->
                <div class="header-column justify-content-end">
                    <div class="header-row">

                        <!-- Notificaciones -->
                        <div class="header-nav-feature header-nav-notifications dropdown">
                            <a class="header-nav-features-toggle" href="#" role="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell modern-icon"></i>
                                <span class="badge badge-danger notifications-count">3</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notifications-dropdown" aria-labelledby="notificationsDropdown">
                                <div class="dropdown-header">
                                    <h6 class="dropdown-title">Notificaciones</h6>
                                    <small class="text-muted">Tienes 3 notificaciones nuevas</small>
                                </div>
                                <div class="dropdown-body">
                                    <div class="notification-item">
                                        <div class="notification-icon bg-primary">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                        <div class="notification-content">
                                            <h6>Nueva factura generada</h6>
                                            <p class="small text-muted">Factura #0001-00000123</p>
                                            <small class="text-muted">Hace 5 minutos</small>
                                        </div>
                                    </div>
                                    <div class="notification-item">
                                        <div class="notification-icon bg-success">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="notification-content">
                                            <h6>Pago recibido</h6>
                                            <p class="small text-muted">Cliente ABC Corp.</p>
                                            <small class="text-muted">Hace 1 hora</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-footer">
                                    <a href="#" class="dropdown-link">Ver todas las notificaciones</a>
                                </div>
                            </div>
                        </div>

                        <!-- Perfil de usuario -->
                        <div class="header-nav-feature header-nav-account dropdown">
                            <a class="header-nav-features-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <div class="user-avatar">
                                    @if(auth()->user()->avatar ?? false)
                                        <img src="{{ asset('storage/uploads/users/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="avatar-img">
                                    @else
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="user-info d-none d-md-block">
                                    <span class="user-name">{{ auth()->user()->name }}</span>
                                    <small class="user-role text-muted">{{ ucfirst(auth()->user()->type ?? 'Usuario') }}</small>
                                </div>
                                <i class="fas fa-chevron-down user-dropdown-arrow"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
                                <div class="dropdown-header">
                                    <div class="user-avatar-large">
                                        @if(auth()->user()->avatar ?? false)
                                            <img src="{{ asset('storage/uploads/users/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="avatar-img">
                                        @else
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="user-details">
                                        <h6>{{ auth()->user()->name }}</h6>
                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('tenant.advanced.users.index') }}">
                                    <i class="fas fa-user-circle"></i>
                                    Mi Perfil
                                </a>
                                <a class="dropdown-item" href="{{ route('tenant.companies.index') }}">
                                    <i class="fas fa-building"></i>
                                    Configuración
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</header>

<!-- ESTILOS CSS ESPECÍFICOS PARA EL HEADER -->
<style>
.modern-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
    box-shadow: var(--shadow-lg) !important;
    border-bottom: none !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 1030 !important;
}

.header-body {
    background: transparent !important;
}

.header-container {
    padding: 0 24px !important;
}

.header-row {
    min-height: 70px !important;
    align-items: center !important;
}

/* Logo y Toggle */
.header-logo {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
}

.sidebar-toggle {
    background: rgba(255, 255, 255, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: var(--border-radius-sm) !important;
    padding: 8px 12px !important;
    transition: var(--transition-fast) !important;
}

.sidebar-toggle:hover {
    background: rgba(255, 255, 255, 0.25) !important;
    color: white !important;
}

.logo-link {
    display: flex !important;
    align-items: center !important;
    text-decoration: none !important;
}

.company-logo {
    max-height: 40px !important;
    width: auto !important;
    filter: brightness(0) invert(1) !important;
}

.logo-placeholder {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    color: white !important;
    font-weight: 700 !important;
    font-size: 18px !important;
}

.logo-placeholder i {
    font-size: 24px !important;
}

/* Navegación horizontal */
.horizontal-nav-container {
    margin: 0 !important;
}

.horizontal-nav-menu {
    display: flex !important;
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    gap: 8px !important;
}

.horizontal-nav-menu .nav-item {
    position: relative !important;
}

.horizontal-nav-menu .nav-link {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 12px 16px !important;
    color: rgba(255, 255, 255, 0.85) !important;
    text-decoration: none !important;
    border-radius: var(--border-radius-sm) !important;
    transition: var(--transition-fast) !important;
    font-weight: 500 !important;
}

.horizontal-nav-menu .nav-link:hover,
.horizontal-nav-menu .nav-item.active .nav-link {
    background: rgba(255, 255, 255, 0.15) !important;
    color: white !important;
}

/* Notificaciones */
.header-nav-notifications {
    position: relative !important;
    margin-right: 16px !important;
}

.header-nav-features-toggle {
    display: flex !important;
    align-items: center !important;
    padding: 8px 12px !important;
    color: rgba(255, 255, 255, 0.85) !important;
    text-decoration: none !important;
    border-radius: var(--border-radius-sm) !important;
    transition: var(--transition-fast) !important;
    position: relative !important;
}

.header-nav-features-toggle:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: white !important;
}

.modern-icon {
    font-size: 18px !important;
}

.notifications-count {
    position: absolute !important;
    top: -4px !important;
    right: -4px !important;
    font-size: 10px !important;
    min-width: 18px !important;
    height: 18px !important;
    line-height: 18px !important;
    text-align: center !important;
    border-radius: 50% !important;
}

.notifications-dropdown {
    width: 320px !important;
    max-width: 90vw !important;
    border: none !important;
    border-radius: var(--border-radius) !important;
    box-shadow: var(--shadow-xl) !important;
    margin-top: 8px !important;
}

.dropdown-header {
    padding: 16px 20px !important;
    border-bottom: 1px solid var(--gray-200) !important;
    background: var(--gray-50) !important;
}

.dropdown-title {
    margin: 0 !important;
    font-weight: 700 !important;
    color: var(--gray-800) !important;
}

.dropdown-body {
    max-height: 300px !important;
    overflow-y: auto !important;
    padding: 8px 0 !important;
}

.notification-item {
    display: flex !important;
    align-items: flex-start !important;
    padding: 12px 20px !important;
    transition: var(--transition-fast) !important;
    border-bottom: 1px solid var(--gray-100) !important;
}

.notification-item:hover {
    background: var(--gray-50) !important;
}

.notification-icon {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin-right: 12px !important;
    color: white !important;
    font-size: 16px !important;
}

.notification-content h6 {
    margin: 0 0 4px 0 !important;
    font-weight: 600 !important;
    color: var(--gray-800) !important;
    font-size: 14px !important;
}

.notification-content p {
    margin: 0 0 4px 0 !important;
    color: var(--gray-600) !important;
}

.dropdown-footer {
    padding: 12px 20px !important;
    border-top: 1px solid var(--gray-200) !important;
    background: var(--gray-50) !important;
    text-align: center !important;
}

.dropdown-link {
    color: var(--primary-color) !important;
    text-decoration: none !important;
    font-weight: 600 !important;
    font-size: 14px !important;
}

.dropdown-link:hover {
    color: var(--primary-dark) !important;
}

/* Perfil de usuario */
.header-nav-account .header-nav-features-toggle {
    padding: 8px !important;
    gap: 12px !important;
}

.user-avatar {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
}

.user-avatar .avatar-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

.user-avatar .avatar-placeholder {
    width: 100% !important;
    height: 100% !important;
    background: rgba(255, 255, 255, 0.2) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 16px !important;
    color: white !important;
}

.user-info {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
}

.user-name {
    font-weight: 600 !important;
    color: white !important;
    font-size: 14px !important;
    line-height: 1.2 !important;
}

.user-role {
    font-size: 12px !important;
    color: rgba(255, 255, 255, 0.7) !important;
}

.user-dropdown-arrow {
    font-size: 12px !important;
    color: rgba(255, 255, 255, 0.7) !important;
    transition: var(--transition-fast) !important;
}

.user-dropdown {
    width: 280px !important;
    border: none !important;
    border-radius: var(--border-radius) !important;
    box-shadow: var(--shadow-xl) !important;
    margin-top: 8px !important;
}

.user-dropdown .dropdown-header {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 20px !important;
}

.user-avatar-large {
    width: 50px !important;
    height: 50px !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    border: 2px solid var(--gray-200) !important;
}

.user-avatar-large .avatar-img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

.user-avatar-large .avatar-placeholder {
    width: 100% !important;
    height: 100% !important;
    background: var(--gray-200) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 20px !important;
    color: var(--gray-600) !important;
}

.user-details h6 {
    margin: 0 !important;
    font-weight: 700 !important;
    color: var(--gray-800) !important;
}

.user-details small {
    color: var(--gray-600) !important;
}

.user-dropdown .dropdown-item {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 12px 20px !important;
    color: var(--gray-700) !important;
    text-decoration: none !important;
    transition: var(--transition-fast) !important;
}

.user-dropdown .dropdown-item:hover {
    background: var(--gray-50) !important;
    color: var(--gray-800) !important;
}

.user-dropdown .dropdown-item i {
    width: 16px !important;
    text-align: center !important;
}

/* Responsive */
@media (max-width: 768px) {
    .header-container {
        padding: 0 16px !important;
    }

    .user-info {
        display: none !important;
    }

    .notifications-dropdown,
    .user-dropdown {
        width: 280px !important;
        max-width: calc(100vw - 32px) !important;
    }

    .horizontal-nav-container {
        display: none !important;
    }
}
</style>
