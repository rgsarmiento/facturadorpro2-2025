<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - Factura Fácil</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .maintenance-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 90%;
            padding: 3rem;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .maintenance-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .maintenance-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .maintenance-subtitle {
            font-size: 1.2rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .maintenance-message {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.6;
            color: #495057;
        }

        .maintenance-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .info-item {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 10px;
            flex: 1;
            min-width: 150px;
        }

        .info-item i {
            color: #1976d2;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .info-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1976d2;
            margin-top: 0.2rem;
        }

        .loading-animation {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 2rem 0;
        }

        .loading-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            margin: 0 4px;
            animation: loading 1.4s ease-in-out infinite both;
        }

        .loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .loading-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes loading {
            0%, 80%, 100% {
                transform: scale(0);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .contact-info {
            background: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .contact-info h4 {
            color: #f57c00;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .contact-info p {
            color: #5d4037;
            margin: 0.3rem 0;
        }

        .contact-info a {
            color: #f57c00;
            text-decoration: none;
            font-weight: 500;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .refresh-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .refresh-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .refresh-button i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .maintenance-container {
                padding: 2rem;
                margin: 1rem;
            }

            .maintenance-title {
                font-size: 2rem;
            }

            .maintenance-info {
                flex-direction: column;
            }

            .info-item {
                min-width: auto;
            }
        }

        .auto-refresh {
            font-size: 0.9rem;
            color: #666;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <h1 class="maintenance-title">Mantenimiento Programado</h1>
        <p class="maintenance-subtitle">Estamos mejorando nuestro sistema para servirle mejor</p>
        
        <div class="maintenance-message">
            {{ $message }}
        </div>

        <div class="maintenance-info">
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div class="info-label">Inicio del mantenimiento</div>
                <div class="info-value">{{ $started_at ?: 'En curso' }}</div>
            </div>
            <div class="info-item">
                <i class="fas fa-chart-line"></i>
                <div class="info-label">Estado del sistema</div>
                <div class="info-value">Mantenimiento</div>
            </div>
        </div>

        <div class="loading-animation">
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
        </div>

        <button class="refresh-button" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i>
            Actualizar página
        </button>

        <div class="auto-refresh">
            <i class="fas fa-info-circle"></i>
            Esta página se actualizará automáticamente cada 30 segundos
        </div>

        <div class="contact-info">
            <h4><i class="fas fa-headset"></i> ¿Necesita ayuda urgente?</h4>
            <p><strong>Email:</strong> <a href="mailto:soporte@facturafacil.com">soporte@factufacil.com</a></p>
            <p><strong>Teléfono:</strong> <a href="tel:+57018000123">+57 323 7952172</a></p>
            <p><strong>Horario:</strong> Lunes a Viernes, 8:00 AM - 6:00 PM</p>
        </div>
    </div>

    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Efecto de rotación en el icono de refresh
        document.querySelector('.refresh-button').addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.style.animation = 'spin 1s linear';
            setTimeout(() => {
                icon.style.animation = '';
            }, 1000);
        });

        // Animación de rotación
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>