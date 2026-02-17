<?php
/**
 * restore-system.php
 * Sistema completo de restauración y diagnóstico
 */

// Evitar cualquier output previo
ob_start();

// Cargar configuración
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

$action = $_GET['action'] ?? 'show';
$result = ['success' => false, 'message' => ''];

// Si es una acción POST, procesarla
if ($action === 'verify_db') {
    header('Content-Type: application/json');
    try {
        $db_file = DB_PATH;
        $exists = file_exists($db_file);
        
        if ($exists) {
            $conexion = new PDO("sqlite:$db_file", '', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Verificar usuario
            $user = $conexion->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL'")->fetch();
            
            echo json_encode([
                'success' => true,
                'db_exists' => true,
                'user_exists' => ($user !== false),
                'user_data' => $user ?: null,
                'db_path' => $db_file
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'db_exists' => false,
                'message' => 'Base de datos no existe'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'clear_sessions') {
    header('Content-Type: application/json');
    try {
        // Limpiar sesiones
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Limpiar cookies
        if (isset($_COOKIE['PHPSESSID'])) {
            setcookie('PHPSESSID', '', time() - 3600, '/');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Sesiones limpiadas'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'recreate_user') {
    header('Content-Type: application/json');
    try {
        $conexion = new PDO("sqlite:" . DB_PATH, '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Eliminar usuario existente
        $conexion->exec("DELETE FROM usuarios WHERE username='AdanGL'");
        
        // Crear nuevo usuario
        $pass_hash = password_hash("Agl252002", PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['AdanGL', $pass_hash, 'superadmin']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuario recreado exitosamente'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Sistema - TG Gestión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { padding: 30px; }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .section h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: auto;
        }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.success { background: #4caf50; color: white; }
        .status-badge.error { background: #f44336; color: white; }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover:not(:disabled) { background: #5568d3; }
        .btn-success { background: #4caf50; color: white; }
        .btn-success:hover:not(:disabled) { background: #43a047; }
        .btn-danger { background: #f44336; color: white; }
        .btn-danger:hover:not(:disabled) { background: #e53935; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-warning:hover:not(:disabled) { background: #f57c00; }
        .info-box {
            padding: 15px;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 4px;
            margin: 15px 0;
        }
        .info-box strong { color: #1976d2; }
        .result-message {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            display: none;
        }
        .result-message.show { display: block; }
        .result-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .result-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        pre {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Restaurar Sistema</h1>
            <p>Diagnóstico y Reparación de TG Gestión</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h3>
                    1. Estado del Sistema
                    <span class="status-badge pending" id="status-sistema">Verificando...</span>
                </h3>
                <div id="info-sistema">
                    <p>Verificando base de datos y usuario...</p>
                </div>
            </div>
            
            <div class="section">
                <h3>2. Opciones de Reparación</h3>
                
                <div style="margin: 15px 0;">
                    <button class="btn btn-warning" onclick="clearSessions()" id="btn-clear-sessions">
                        🗑️ Limpiar Sesiones
                    </button>
                    <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                        Limpia las sesiones corruptas que pueden estar causando el error.
                    </p>
                </div>
                
                <div style="margin: 15px 0;">
                    <button class="btn btn-danger" onclick="recreateUser()" id="btn-recreate-user">
                        👤 Recrear Usuario AdanGL
                    </button>
                    <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                        Elimina y recrea el usuario superadmin con contraseña fresca.
                    </p>
                </div>
                
                <div style="margin: 15px 0;">
                    <a href="force-init-db.php" class="btn btn-danger">
                        🔨 Recrear BD Completa
                    </a>
                    <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                        ⚠️ Elimina toda la BD y la recrea desde cero (perderás todos los datos).
                    </p>
                </div>
                
                <div id="result-message" class="result-message"></div>
            </div>
            
            <div class="section">
                <h3>3. Acciones Rápidas</h3>
                <a href="index.php" class="btn btn-success">🏠 Ir al Login</a>
                <a href="verify-superadmin.php" class="btn btn-primary">🔍 Verificación Detallada</a>
                <a href="quick-diagnostic.html" class="btn btn-primary">📊 Diagnóstico Completo</a>
            </div>
            
            <div class="info-box">
                <strong>ℹ️ Información:</strong><br>
                Usuario: <code>AdanGL</code><br>
                Contraseña: <code>Agl252002</code><br>
                Base de Datos: <code><?php echo htmlspecialchars(DB_PATH); ?></code>
            </div>
        </div>
    </div>
    
    <script>
        let systemStatus = null;
        
        async function checkSystem() {
            try {
                const response = await fetch('restore-system.php?action=verify_db&t=' + Date.now());
                const data = await response.json();
                systemStatus = data;
                
                const statusBadge = document.getElementById('status-sistema');
                const infoDiv = document.getElementById('info-sistema');
                
                if (data.success && data.db_exists && data.user_exists) {
                    statusBadge.className = 'status-badge success';
                    statusBadge.textContent = '✓ OK';
                    infoDiv.innerHTML = `
                        <p style="color: #4caf50;">✓ Base de datos existe</p>
                        <p style="color: #4caf50;">✓ Usuario AdanGL existe (ID: ${data.user_data.id})</p>
                        <p style="color: #4caf50;">✓ Role: ${data.user_data.role}</p>
                        <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                            Si aún no puedes entrar, prueba limpiar las sesiones.
                        </p>
                    `;
                } else {
                    statusBadge.className = 'status-badge error';
                    statusBadge.textContent = '✗ Error';
                    infoDiv.innerHTML = `
                        <p style="color: #f44336;">✗ Base de datos: ${data.db_exists ? 'OK' : 'NO EXISTE'}</p>
                        <p style="color: #f44336;">✗ Usuario: ${data.user_exists ? 'OK' : 'NO EXISTE'}</p>
                        <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                            Usa las opciones de reparación para solucionar los problemas.
                        </p>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                const statusBadge = document.getElementById('status-sistema');
                statusBadge.className = 'status-badge error';
                statusBadge.textContent = '✗ Error';
                document.getElementById('info-sistema').innerHTML = 
                    `<p style="color: #f44336;">Error al verificar: ${error.message}</p>`;
            }
        }
        
        async function clearSessions() {
            const btn = document.getElementById('btn-clear-sessions');
            const resultDiv = document.getElementById('result-message');
            
            btn.disabled = true;
            btn.innerHTML = '🗑️ Limpiando... <span class="spinner"></span>';
            
            try {
                const response = await fetch('restore-system.php?action=clear_sessions&t=' + Date.now());
                const data = await response.json();
                
                resultDiv.className = 'result-message show ' + (data.success ? 'success' : 'error');
                resultDiv.textContent = data.success ? 
                    '✓ Sesiones limpiadas. Intenta iniciar sesión nuevamente.' : 
                    '✗ Error: ' + (data.error || data.message);
                
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                }
            } catch (error) {
                resultDiv.className = 'result-message show error';
                resultDiv.textContent = '✗ Error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🗑️ Limpiar Sesiones';
            }
        }
        
        async function recreateUser() {
            if (!confirm('¿Estás seguro? Esto eliminará y recreará el usuario AdanGL.')) {
                return;
            }
            
            const btn = document.getElementById('btn-recreate-user');
            const resultDiv = document.getElementById('result-message');
            
            btn.disabled = true;
            btn.innerHTML = '👤 Recreando... <span class="spinner"></span>';
            
            try {
                const response = await fetch('restore-system.php?action=recreate_user&t=' + Date.now());
                const data = await response.json();
                
                resultDiv.className = 'result-message show ' + (data.success ? 'success' : 'error');
                resultDiv.textContent = data.success ? 
                    '✓ Usuario recreado exitosamente. Contraseña: Agl252002' : 
                    '✗ Error: ' + (data.error || data.message);
                
                if (data.success) {
                    await checkSystem();
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 3000);
                }
            } catch (error) {
                resultDiv.className = 'result-message show error';
                resultDiv.textContent = '✗ Error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '👤 Recrear Usuario AdanGL';
            }
        }
        
        // Verificar sistema al cargar
        document.addEventListener('DOMContentLoaded', () => {
            checkSystem();
        });
    </script>
</body>
</html>
