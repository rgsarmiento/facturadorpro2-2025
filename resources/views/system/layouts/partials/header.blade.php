<header class="header d-flex align-items-center justify-content-between" style="left:10px; height: 60px;">
    <div class="logo-container d-flex align-items-center h-100">
        <a href="{{route('system.co-companies')}}" class="logo d-flex align-items-center">
            @if (file_exists(public_path('theme/logo.svg')))
                <img class="uk-logo-inverse" width="100" height="auto" src="{{asset('theme/logo.svg')}}" alt="Logo"/>
            @else
                <i class="fa fa-circle fa-3x"></i>
            @endif
        </a>
        <!-- Sistema Title -->
        <div class="system-title d-none d-md-flex align-items-center ml-3">
            <h5 class="mb-0 font-weight-bold" style="color: #667eea; font-size: 1.1rem; letter-spacing: 0.5px;">
                Facturador PRO - {{ config('app.name') }}
            </h5>
        </div>
        <div class="d-md-none toggle-sidebar-left d-flex align-items-center" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars" aria-label="Toggle sidebar"></i>
        </div>
    </div>
    <!-- start: search & user box -->
    <div class="header-right d-flex align-items-center h-100">
        <span class="separator"></span>
        <div id="userbox" class="userbox d-flex align-items-center">
            <a href="#" data-toggle="dropdown" class="d-flex align-items-center text-decoration-none">
                <figure class="profile-picture mb-0 mr-2">
                    {{-- <img src="{{asset('img/%21logged-user.jpg')}}" alt="Joseph Doe" class="rounded-circle" data-lock-picture="img/%21logged-user.jpg" /> --}}
                    <div class="border rounded-circle text-center d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <i class="fas fa-user"></i>
                    </div>
                </figure>
                <div class="profile-info d-none d-md-block" data-lock-name="{{ \Auth::getUser()->email }}" data-lock-email="{{ \Auth::getUser()->email }}">
                    <span class="name d-block" style="line-height: 1.2;">{{ \Auth::getUser()->name }}</span>
                    <span class="role d-block" style="line-height: 1.2; font-size: 0.85rem;">{{ \Auth::getUser()->role }}</span>
                </div>
                <i class="fa custom-caret ml-2"></i>
            </a>
            <div class="dropdown-menu">
                <ul class="list-unstyled mb-2">
                    <li class="divider"></li>
                    <li>
                        <a role="menuitem" href="{{ route('system.users.create') }}"><i class="fas fa-users"></i> Gestionar Usuarios</a>
                        <a role="menuitem" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-power-off"></i> @lang('app.buttons.logout')
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- end: search & user box -->
</header>
