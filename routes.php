<?php
/**
 * routes.php - Índice de todas las rutas disponibles
 * Acceso: http://localhost:8080/routes.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutas Disponibles - TG Gestión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #0a0a0a;
            border: 2px solid #00ff00;
            padding: 20px;
        }
        h1 {
            color: #00ff00;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h2 {
            color: #ffff00;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .route {
            background: #111;
            border-left: 3px solid #00ff00;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        .url {
            color: #00ff00;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .method {
            display: inline-block;
            width: 60px;
            text-align: center;
            background: #333;
            padding: 3px;
            border-radius: 3px;
            color: #ffff00;
        }
        .description {
            color: #aaffaa;
            margin-top: 5px;
            font-size: 0.9em;
        }
        a {
            color: #00ff00;
            text-decoration: none;
            cursor: pointer;
        }
        a:hover { text-decoration: underline; }
        .category-title {
            color: #00ffff;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #00ffff;
            padding-bottom: 5px;
        }
        .status {
            display: inline-block;
            color: #00ff00;
            font-size: 0.8em;
        }
        .note {
            background: #1a1a3f;
            border-left: 3px solid #00ffff;
            padding: 10px;
            margin: 10px 0;
            color: #aaffff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗺️  Rutas Disponibles - TG Gestión v10 Beta</h1>
        
        <div class="note">
            💡 Usa estas rutas en el navegador escribiendo:
            <strong>http://localhost:8080/ruta</strong>
        </div>
        
        <h2>📱 ACCESO A APLICACIÓN</h2>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span>/</span>
            </div>
            <div class="description">Página de inicio (login)</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="index.php">index.php</a></span>
            </div>
            <div class="description">Login - Acceso al sistema</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="dashboard.php">dashboard.php</a></span>
            </div>
            <div class="description">Aplicación principal (requiere autenticación)</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="quickstart.php">quickstart.php</a></span>
            </div>
            <div class="description">Guía de inicio rápido</div>
        </div>
        
        <h2>🔧 DIAGNÓSTICO Y MANTENIMIENTO</h2>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="health-check.php">health-check.php</a></span>
            </div>
            <div class="description">Verificación completa del sistema (JSON)</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="diagnostic.html">diagnostic.html</a></span>
            </div>
            <div class="description">Panel de diagnóstico visual interactivo</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="monitor.html">monitor.html</a></span>
            </div>
            <div class="description">Monitor de logs en tiempo real (terminal-style)</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="ping.php">ping.php</a></span>
            </div>
            <div class="description">Health check rápido (JSON simple)</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="reset-db.php">reset-db.php</a></span>
            </div>
            <div class="description">Reinicio completo de la base de datos</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="launcher.php">launcher.php</a></span>
            </div>
            <div class="description">Inicialización automática (ejecutada al inicio)</div>
        </div>
        
        <h2>📊 APIs INTERNAS</h2>
        
        <div class="route">
            <div class="url">
                <span class="method">POST</span>
                <span>/api/api_login.php</span>
            </div>
            <div class="description">Autenticación de usuario</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="diagnostic-api.php">diagnostic-api.php</a></span>
            </div>
            <div class="description">API interna para recopilación de datos (JSON)</div>
        </div>
        
        <h2>📚 DOCUMENTACIÓN</h2>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="LEEME.txt">LEEME.txt</a></span>
            </div>
            <div class="description">Guía completa de usuario (muy recomendado leer primero)</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="CAMBIOS.txt">CAMBIOS.txt</a></span>
            </div>
            <div class="description">Documento técnico de cambios realizados</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="STATUS.txt">STATUS.txt</a></span>
            </div>
            <div class="description">Resumen de configuración y estado actual</div>
        </div>
        
        <div class="route">
            <div class="url">
                <span class="method">GET</span>
                <span><a href="routes.php">routes.php</a></span>
            </div>
            <div class="description">Este archivo - Índice de rutas</div>
        </div>
        
        <h2>🎯 RECOMENDACIONES DE USO</h2>
        
        <div class="note">
            <strong>PRIMER USO:</strong>
            <br>1. Abre <a href="quickstart.php">quickstart.php</a>
            <br>2. Lee <a href="LEEME.txt">LEEME.txt</a>
            <br>3. Accede al <a href="index.php">login</a>
            <br>4. Usa AdanGL / Agl252002
        </div>
        
        <div class="note">
            <strong>SI ALGO NO FUNCIONA:</strong>
            <br>1. Abre <a href="diagnostic.html">diagnostic.html</a> (visual)
            <br>2. O <a href="health-check.php">health-check.php</a> (técnico)
            <br>3. Lee los logs en <a href="monitor.html">monitor.html</a>
            <br>4. Si todo falla: usa <a href="reset-db.php">reset-db.php</a>
        </div>
        
        <div class="note">
            <strong>MANTENIMIENTO REGULAR:</strong>
            <br>• Revisitar <a href="health-check.php">health-check.php</a> periódicamente
            <br>• Monitorear <a href="monitor.html">monitor.html</a> en caso de errores
            <br>• Hacer respaldos manuales de <code>C:\Users\[USER]\AppData\Local\TG_Gestion\</code>
        </div>
        
        <h2>📍 BASE DE DATOS</h2>
        
        <div class="note">
            <strong>Ubicación:</strong> <code>C:\Users\[TU_USUARIO]\AppData\Local\TG_Gestion\database.sqlite</code>
            <br><strong>Motor:</strong> SQLite 3
            <br><strong>Respaldos:</strong> Se crean automáticamente en <code>data/backups/</code>
        </div>
        
        <h2>✨ ESTADO DEL SISTEMA</h2>
        
        <div style="background: #0a3a0a; border: 2px solid #00ff00; padding: 10px; margin-top: 20px;">
            <p><span class="status">✓</span> 100% Offline</p>
            <p><span class="status">✓</span> SQLite Local</p>
            <p><span class="status">✓</span> Auto-inicialización</p>
            <p><span class="status">✓</span> Diagnóstico disponible</p>
            <p><span class="status">✓</span> Logging completo</p>
            <p><span class="status">✓</span> Listo para producción</p>
        </div>
    </div>
</body>
</html>
