<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            text-align: center;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h3>{{ $mensaje }}</h3>
    <p>Fecha: {{ $date_of_issue }} - {{ $created_at }}</p>
    <p>Sucursal: {{ $sucursal->description ?? '' }}</p>
</body>
</html>
