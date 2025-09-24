        <header class="header" style="margin-left: 250px; background: white; border-bottom: 1px solid #dee2e6; padding: 10px 20px; position: fixed; right: 0; top: 0; z-index: 999;">
            <ul class="header-nav" style="list-style: none; margin: 0; padding: 0; display: flex; align-items: center;">
                <li>
                    <button id="js-toggle-sidebar" class="header-nav-item btn btn-link">
                        <i class="fas fa-bars"></i>
                    </button>
                </li>
            </ul>
            <ul class="header-nav pull-right" style="list-style: none; margin: 0; padding: 0; display: flex; align-items: center; margin-left: auto;">
                {{-- <notification-notification :user="{{auth()->user()}}"></notification-notification> --}}
                <menu-popover :user="{{auth()->user()}}"></menu-popover>
            </ul>
        </header>

        <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-nav {
            display: flex;
            align-items: center;
        }

        .header-nav-item {
            border: none;
            background: none;
            color: #495057;
            padding: 8px;
        }

        .header-nav-item:hover {
            color: #007bff;
        }

        .pull-right {
            margin-left: auto;
        }
        </style>
