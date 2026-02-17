<?php
/**
 * test-login-direct.php
 * Prueba directa de login sin JavaScript
 */

// Limpiar cualquier output buffer
if (ob_get_level()) ob_end_clean();

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    try {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'db.php';
        
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        
        if (empty($user) || empty($pass)) {
            $message = 'Por favor completa ambos campos';
        } else {
            $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
            $stmt->execute([$user]);
            $u = $stmt->fetch();
            
            if ($u && password_verify($pass, $u['password'])) {
                session_regenerate_id(true);
                $_SESSION['usuario'] = $u['username'];
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['role'] = $u['role'];
                
                $success = true;
                $message = '¡Login exitoso! Redirigiendo...';
                
                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $message = 'Usuario o contraseña incorrectos';
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Directo - TG Gestión</title>
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
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 0.9em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .links {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.85em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Login Directo</h1>
        <p class="subtitle">Prueba sin JavaScript</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>ℹ️ Credenciales por defecto:</strong><br>
            Usuario: <code>AdanGL</code><br>
            Contraseña: <code>Agl252002</code>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" value="AdanGL" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Entrar al Sistema</button>
        </form>
        
        <div class="links">
            <a href="restore-system.php">🔧 Restaurar Sistema</a>
            <a href="index.php">← Login Normal</a>
        </div>
    </div>
</body>
</html>
