        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="{{route('system.co-companies')}}" class="logo">
                    <img src="{{asset('assets/images/favicon_dian.ico')}}" alt="Logo" style="width: 24px; height: 24px;">
                    <span class="logo-text"><b>Bee</b></span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <ul class="sidebar-list">
                    <li>
                        <a class="sidebar-list-item active" href="{{route('system.co-companies')}}">
                            <i class="far fa-building"></i>
                            <span>Compañias</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <style>
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #343a40;
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #495057;
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
        }

        .logo:hover {
            color: white;
            text-decoration: none;
        }

        .logo-text {
            margin-left: 10px;
            font-size: 18px;
            font-weight: bold;
        }

        .sidebar-nav {
            padding: 0;
        }

        .sidebar-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-list-item {
            display: block;
            padding: 15px 20px;
            color: #adb5bd;
            text-decoration: none;
            border-bottom: 1px solid #495057;
            transition: all 0.3s ease;
        }

        .sidebar-list-item:hover {
            background-color: #495057;
            color: white;
            text-decoration: none;
        }

        .sidebar-list-item.active {
            background-color: #007bff;
            color: white;
        }

        .sidebar-list-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }
        </style>
