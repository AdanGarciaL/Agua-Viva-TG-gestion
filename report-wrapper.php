<?php
/**
 * report-wrapper.php - Envoltorio para reportes con botón de retorno
 * Acceso: http://localhost:8080/report-wrapper.php?accion=...&formato=...
 */

session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

// Redirigir a api_reportes con parámetros
if (isset($_GET['accion']) && isset($_GET['formato'])) {
    header("Location: api/api_reportes.php?" . http_build_query($_GET));
    exit;
}

// Si llegamos aquí, mostrar página de descarga completada
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descarga Completada - TG Gestión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            text-align: center;
            padding: 40px 30px;
        }
        .icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8em;
        }
        p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button, a {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        .btn-secondary:hover {
            background: #e8e8e8;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            color: #1565c0;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📥</div>
        <h1>¡Descarga Completada!</h1>
        <p>Tu reporte ha sido descargado correctamente en la carpeta <strong>Descargas</strong>.</p>
        
        <div class="info">
            📂 <strong>Ubicación:</strong> C:\Users\[TuUsuario]\Downloads\
        </div>
        
        <div class="buttons">
            <a href="dashboard.php" class="btn-primary">← Volver a la Aplicación</a>
            <button class="btn-secondary" onclick="window.close()">Cerrar esta ventana</button>
        </div>
    </div>
</body>
</html>
