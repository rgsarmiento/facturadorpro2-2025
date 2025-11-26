@extends('tenant.layouts.auth')

@section('content')
    <div class="login-container">
        <!-- Imagen de fondo corporativa -->
        <div class="login-background">
            <div class="overlay"></div>
        </div>

        <!-- Panel de login -->
        <div class="login-panel">
            <div class="login-content">
                <!-- Logo/Título corporativo -->
                <div class="login-header">
                    <div class="logo-container">
                        @if ($vc_company->logo ?? false)
                            <img src="{{ asset('storage/uploads/logos/'.$vc_company->logo) }}" alt="Logo" class="company-logo" />
                        @endif
                        <h1 class="company-name">{{ $vc_company->trade_name ?? 'FacturadorPRO' }}</h1>
                        <p class="company-subtitle">Sistema de Facturación Empresarial</p>
                    </div>
                </div>

                <!-- Formulario de login -->
                <div class="login-form-container">
                    <h2 class="form-title">Bienvenido</h2>
                    <p class="form-subtitle">Ingresa a tu cuenta para continuar</p>

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px; border-radius: 8px; background-color: #fee; border: 1px solid #fcc; color: #c00;">
                            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                            <strong>Error:</strong> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="opacity: 0.8;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="login-form">
                        @csrf

                        <!-- Campo Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                    value="{{ old('email') }}"
                                    placeholder="usuario@ejemplo.com"
                                    required
                                    autofocus
                                >
                            </div>
                            @if ($errors->has('email'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('email') }}
                                </div>
                            @endif
                        </div>

                        <!-- Campo Password -->
                        <div class="form-group">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                    placeholder="••••••••"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            @if ($errors->has('password'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif
                        </div>

                        <!-- Recordar sesión -->
                        <div class="form-options">
                            <div class="checkbox-wrapper">
                                <input
                                    type="checkbox"
                                    id="remember"
                                    name="remember"
                                    class="custom-checkbox"
                                    {{ old('remember') ? 'checked' : '' }}
                                >
                                <label for="remember" class="checkbox-label">Recordar mi sesión</label>
                            </div>
                        </div>

                        <!-- Botón de login -->
                        <button type="submit" class="btn-login">
                            <span class="btn-text">Iniciar Sesión</span>
                            <i class="fas fa-arrow-right btn-icon"></i>
                        </button>
                    </form>
                </div>

                <!-- Footer -->
                <div class="login-footer">
                    <p>&copy; {{ date('Y') }} {{ $vc_company->trade_name ?? 'FacturadorPRO' }}. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .login-container {
            min-height: 100vh;
            height: auto; /* Cambiado de 100vh a auto */
            display: flex;
            position: relative;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-y: auto; /* Permitir scroll vertical */
            overflow-x: hidden;
        }

        .login-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #1a237e; /* Color de respaldo */
            @php
                $background_image = $vc_company->logo_login ?? '';
                if ($background_image) {
                    echo "background-image: url('" . asset('storage/uploads/logos/' . $background_image) . "');";
                } else {
                    echo "background-image: url('" . asset('images/backgrounds/corporate-real.jpg') . "');";
                }
            @endphp
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: saturate(1.1) contrast(1.1);
            z-index: 1;
        }

        /* Asegurarse que la imagen de fondo sea visible */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                rgba(26, 35, 126, 0.85) 0%,
                rgba(0, 0, 0, 0.75) 100%
            );
            z-index: 2;
        }

        .login-panel {
            position: relative;
            z-index: 3;
            width: 100%;
            max-width: 450px;
            margin: auto;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .company-logo {
            max-width: 120px;
            max-height: 80px;
            margin-bottom: 1rem;
            object-fit: contain;
        }

        .company-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .company-subtitle {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin: 0.5rem 0 0 0;
            font-weight: 400;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 0.5rem 0;
            text-align: center;
        }

        .form-subtitle {
            color: #7f8c8d;
            font-size: 0.95rem;
            text-align: center;
            margin: 0 0 2rem 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: #34495e;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: #95a5a6;
            z-index: 2;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .form-control.is-invalid {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
            padding: 0;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: block;
        }

        .form-options {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 1.5rem 0 2rem 0;
            font-size: 0.9rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
        }

        .custom-checkbox {
            margin-right: 0.5rem;
            width: 16px;
            height: 16px;
            accent-color: #667eea;
        }

        .checkbox-label {
            color: #34495e;
            font-weight: 400;
            margin: 0;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-icon {
            transition: transform 0.3s ease;
        }

        .btn-login:hover .btn-icon {
            transform: translateX(3px);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .login-footer p {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 1024px) and (max-height: 768px) {
            .login-container {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }

            .login-panel {
                padding: 0.5rem;
                min-height: auto;
                align-items: flex-start;
                padding-top: 1rem;
            }

            .login-content {
                padding: 1.5rem 1.25rem;
                margin: 0 auto;
                max-width: 380px;
            }

            .login-header {
                margin-bottom: 1.5rem;
            }

            .company-logo {
                max-width: 80px;
                max-height: 50px;
                margin-bottom: 0.5rem;
            }

            .company-name {
                font-size: 1.6rem;
                margin-bottom: 0.25rem;
            }

            .company-subtitle {
                font-size: 0.8rem;
                margin-bottom: 0;
            }

            .form-title {
                font-size: 1.3rem;
                margin-bottom: 0.25rem;
            }

            .form-subtitle {
                font-size: 0.85rem;
                margin-bottom: 1.5rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-options {
                margin: 1rem 0 1.5rem 0;
            }

            .login-footer {
                margin-top: 1rem;
                padding-top: 1rem;
            }
        }

        @media (max-width: 800px) and (max-height: 600px) {
            .login-container {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
                padding: 0.5rem 0;
            }

            .login-panel {
                padding: 0.25rem;
                min-height: auto;
                align-items: flex-start;
                justify-content: flex-start;
                padding-top: 0.5rem;
            }

            .login-content {
                padding: 1rem 1rem;
                margin: 0 auto;
                max-width: 350px;
                border-radius: 12px;
            }

            .login-header {
                margin-bottom: 1rem;
            }

            .company-logo {
                max-width: 60px;
                max-height: 40px;
                margin-bottom: 0.3rem;
            }

            .company-name {
                font-size: 1.4rem;
                margin-bottom: 0.1rem;
            }

            .company-subtitle {
                font-size: 0.75rem;
                margin: 0;
            }

            .form-title {
                font-size: 1.2rem;
                margin-bottom: 0.1rem;
            }

            .form-subtitle {
                font-size: 0.8rem;
                margin-bottom: 1rem;
            }

            .form-group {
                margin-bottom: 0.8rem;
            }

            .form-label {
                font-size: 0.8rem;
                margin-bottom: 0.3rem;
            }

            .form-control {
                padding: 0.6rem 0.8rem 0.6rem 2.2rem;
                font-size: 0.9rem;
            }

            .input-icon {
                font-size: 0.8rem;
                left: 0.7rem;
            }

            .form-options {
                margin: 0.8rem 0 1rem 0;
                font-size: 0.8rem;
            }

            .btn-login {
                padding: 0.7rem 1rem;
                font-size: 0.9rem;
            }

            .login-footer {
                margin-top: 0.8rem;
                padding-top: 0.8rem;
            }

            .login-footer p {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                height: auto;
                min-height: 100vh;
                padding: 1rem 0;
            }

            .login-panel {
                padding: 1rem;
                max-width: 100%;
                width: 100%;
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .login-content {
                padding: 2rem 1.5rem;
                border-radius: 15px;
                margin: auto;
                max-width: 400px;
                width: 100%;
            }

            .company-name {
                font-size: 1.8rem;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .login-background {
                background-attachment: scroll;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                overflow-y: auto;
                height: auto;
                min-height: 100vh;
            }

            .login-panel {
                padding: 0.5rem;
                min-height: 100vh;
                max-width: 100%;
            }

            .login-content {
                padding: 1.5rem 1rem;
                max-width: 100%;
                margin: 1rem auto;
                min-height: auto;
            }

            .company-name {
                font-size: 1.6rem;
                line-height: 1.2;
            }

            .company-subtitle {
                font-size: 0.85rem;
            }

            .form-title {
                font-size: 1.3rem;
            }

            .form-subtitle {
                font-size: 0.85rem;
            }

            .form-control {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                font-size: 0.95rem;
            }

            .input-icon {
                font-size: 0.85rem;
                left: 0.875rem;
            }

            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .btn-login {
                padding: 0.875rem 1.25rem;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 360px) {
            .login-content {
                padding: 1rem 0.75rem;
                margin: 0.5rem auto;
            }

            .company-name {
                font-size: 1.4rem;
            }

            .form-title {
                font-size: 1.2rem;
            }
        }

        /* Asegurar que no haya scroll horizontal */
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
            }

            .login-container {
                overflow-x: hidden;
                width: 100%;
            }
        }

        /* Mejorar altura mínima en dispositivos móviles */
        @media (max-height: 600px) and (max-width: 768px) {
            .login-panel {
                min-height: auto;
                padding: 0.5rem;
            }

            .login-content {
                padding: 1.5rem 1rem;
            }

            .login-header {
                margin-bottom: 1.5rem;
            }

            .form-options {
                margin: 1rem 0 1.5rem 0;
            }
        }

        /* Ajuste específico para resoluciones problemáticas */
        @media (min-width: 800px) and (max-width: 1024px) and (max-height: 768px) {
            .login-panel {
                align-items: flex-start;
                padding-top: 2rem;
            }

            .login-content {
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        /* Animaciones de entrada */
        .login-content {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Efecto de typing para el placeholder */
        .form-control::placeholder {
            color: #bdc3c7;
            transition: opacity 0.3s ease;
        }

        .form-control:focus::placeholder {
            opacity: 0.7;
        }
    </style>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Agregar efectos de foco para mejorar la experiencia
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
@endsection
