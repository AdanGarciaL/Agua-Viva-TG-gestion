<?php
/**
 * setup.php - Asistente de instalación (primera vez)
 * Acceso: http://localhost:8080/setup.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Inicial - TG Gestión</title>
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
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content {
            padding: 40px 30px;
        }
        .step {
            margin-bottom: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .step h3 { color: #333; margin-bottom: 10px; }
        .step p { color: #666; font-size: 0.95em; }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            margin-top: 10px;
        }
        .status.ok { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.pending { background: #fff3cd; color: #856404; }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
            width: 100%;
        }
        button:hover {
            background: #5568d3;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 10px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .footer {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            color: #666;
            font-size: 0.85em;
            border-top: 1px solid #eee;
        }
        .success-msg {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Configuración Inicial</h1>
            <p>TG Gestión v10 Beta - Primera Instalación</p>
        </div>
        
        <div class="content">
            <div class="success-msg" id="successMsg">
                <strong>✓ ¡Éxito!</strong><br>
                La base de datos se ha creado correctamente.<br>
                Redirigiendo al login en 3 segundos...
            </div>
            
            <div class="step">
                <h3>Paso 1: Verificar Sistema</h3>
                <p>Comprobando si la aplicación está lista...</p>
                <div id="status1" class="status pending">Verificando...</div>
            </div>
            
            <div class="step">
                <h3>Paso 2: Crear Base de Datos</h3>
                <p>Se creará la base de datos con usuario admin por defecto.</p>
                <p><strong>Usuario:</strong> AdanGL<br><strong>Contraseña:</strong> Agl252002</p>
                <p style="color: #d32f2f; font-size: 0.9em; margin-top: 10px;">⚠️ Cambia la contraseña después de iniciar sesión</p>
                <button id="createBtn" onclick="createDatabase()" disabled>
                    Crear Base de Datos
                </button>
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Creando base de datos...</p>
                </div>
                <div id="status2" class="status pending" style="display: none;">Esperando acción</div>
            </div>
            
            <div class="step">
                <h3>Paso 3: Iniciar Sesión</h3>
                <p>Una vez creada la BD, podrás iniciar sesión con las credenciales de admin.</p>
                <button onclick="goToLogin()" id="loginBtn" style="display: none; background: #28a745;">
                    Ir al Login →
                </button>
            </div>
        </div>
        
        <div class="footer">
            <p>Por favor, espera a que se complete cada paso antes de continuar.</p>
        </div>
    </div>
    
    <script>
        let bdCreated = false;
        
        async function checkSystem() {
            try {
                const res = await fetch('ping.php?t=' + Date.now());
                const data = await res.json();
                
                const status1 = document.getElementById('status1');
                
                if (data.ok) {
                    status1.className = 'status ok';
                    status1.textContent = '✓ Sistema listo';
                    document.getElementById('createBtn').disabled = false;
                } else {
                    status1.className = 'status pending';
                    status1.textContent = '⏳ Preparando...';
                    document.getElementById('createBtn').disabled = false; // Permitir crear aunque no esté listo
                }
            } catch (err) {
                console.error('Error:', err);
                document.getElementById('createBtn').disabled = false; // Permitir de todas formas
            }
        }
        
        async function createDatabase() {
            if (bdCreated) return;
            
            const btn = document.getElementById('createBtn');
            const loading = document.getElementById('loading');
            const status2 = document.getElementById('status2');
            
            btn.style.display = 'none';
            loading.style.display = 'block';
            
            try {
                const res = await fetch('verify-and-fix.php');
                const data = await res.json();
                
                loading.style.display = 'none';
                
                if (data.final_status === 'SUCCESS ✓') {
                    bdCreated = true;
                    status2.style.display = 'block';
                    status2.className = 'status ok';
                    status2.textContent = '✓ Base de datos creada';
                    
                    document.getElementById('successMsg').style.display = 'block';
                    document.getElementById('loginBtn').style.display = 'block';
                    
                    // Redirigir automáticamente después de 3 segundos
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 3000);
                } else {
                    status2.style.display = 'block';
                    status2.className = 'status error';
                    status2.textContent = '✗ Error: ' + data.final_status;
                    btn.style.display = 'block';
                }
            } catch (err) {
                console.error('Error:', err);
                loading.style.display = 'none';
                status2.style.display = 'block';
                status2.className = 'status error';
                status2.textContent = '✗ Error de conexión';
                btn.style.display = 'block';
            }
        }
        
        function goToLogin() {
            window.location.href = 'index.php';
        }
        
        // Verificar sistema al cargar
        window.addEventListener('load', checkSystem);
        // Verificar cada 2 segundos
        setInterval(checkSystem, 2000);
    </script>
</body>
</html>
