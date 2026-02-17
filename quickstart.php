<?php
/**
 * quickstart.php - Guía rápida de inicio
 * Acceso: http://localhost:8080/quickstart.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio Rápido - TG Gestión</title>
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
            max-width: 600px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 0.95em; }
        .content {
            padding: 40px 30px;
        }
        .step {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .step-number {
            display: inline-block;
            width: 32px;
            height: 32px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: bold;
            margin-right: 10px;
        }
        .step h3 {
            color: #333;
            margin-bottom: 5px;
            display: inline-block;
        }
        .step p {
            color: #666;
            font-size: 0.95em;
            margin-top: 5px;
            margin-left: 42px;
        }
        .credentials {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: 'Monaco', monospace;
            margin-left: 42px;
        }
        .credentials div {
            margin: 3px 0;
        }
        .label { color: #2196f3; font-weight: bold; }
        .value { color: #1565c0; }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        a, button {
            flex: 1;
            min-width: 150px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
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
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        .btn-secondary:hover {
            background: #efefef;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border-left: 4px solid #1976d2;
        }
        .alert-warning {
            background: #fff3e0;
            color: #f57c00;
            border-left: 4px solid #f57c00;
        }
        .footer {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            color: #666;
            font-size: 0.85em;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Inicio Rápido</h1>
            <p>TG Gestión v10 Beta - Offline Edition</p>
        </div>
        
        <div class="content">
            <div class="alert alert-info">
                <strong>✓ Buenas noticias:</strong> Tu aplicación está completamente instalada y funcionando offline.
            </div>
            
            <div class="step">
                <span class="step-number">1</span>
                <h3>Primer Acceso</h3>
                <p>Usa estas credenciales para ingresar:</p>
                <div class="credentials">
                    <div><span class="label">Usuario:</span> <span class="value">AdanGL</span></div>
                    <div><span class="label">Contraseña:</span> <span class="value">Agl252002</span></div>
                </div>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <h3>Cambiar Credenciales</h3>
                <p>Después de ingresar, cambia tu contraseña desde el panel de admin. No compartas estas credenciales.</p>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <h3>Explora la Aplicación</h3>
                <p>La aplicación está completamente offline. Tus datos se guardan automáticamente en tu computadora.</p>
            </div>
            
            <div class="alert alert-warning">
                <strong>💡 Consejo:</strong> Si algo funciona mal, abre http://localhost:8080/diagnostic.html para ver el estado.
            </div>
            
            <div class="button-group">
                <a href="index.php" class="btn-primary">Ir a Login →</a>
                <a href="diagnostic.html" class="btn-secondary">Ver Diagnóstico</a>
            </div>
        </div>
        
        <div class="footer">
            <p>¿Primera vez? Lee <strong>LEEME.txt</strong> para más información.</p>
            <p style="margin-top: 10px; opacity: 0.7;">Todo funciona sin internet • Datos locales en AppData</p>
        </div>
    </div>
</body>
</html>
