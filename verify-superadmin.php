<?php
/**
 * verify-superadmin.php
 * Página de verificación del usuario superadmin
 * Acceso: http://localhost:8080/verify-superadmin.php
 */

// Cargar configuración
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

$db_file = DB_PATH;
$status = [];
$errors = [];

try {
    // 1. Verificar que existe el archivo de BD
    $status['db_file_exists'] = file_exists($db_file);
    $status['db_file_path'] = $db_file;
    
    if (!$status['db_file_exists']) {
        $errors[] = "Base de datos no existe en: $db_file";
    }
    
    // 2. Conectar a BD
    $conexion = new PDO("sqlite:$db_file", '', '', [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $status['db_connection'] = true;
    
    // 3. Verificar tabla usuarios
    $table_check = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios'")->fetch();
    $status['table_usuarios_exists'] = ($table_check !== false);
    
    if (!$status['table_usuarios_exists']) {
        $errors[] = "Tabla 'usuarios' no existe";
    }
    
    // 4. Contar usuarios
    $count_result = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios")->fetch();
    $status['total_users'] = $count_result['cnt'];
    
    // 5. Buscar usuario AdanGL
    $user_check = $conexion->query("SELECT id, username, role FROM usuarios WHERE username='AdanGL'")->fetch();
    $status['superadmin_exists'] = ($user_check !== false);
    
    if ($user_check) {
        $status['superadmin_data'] = [
            'id' => $user_check['id'],
            'username' => $user_check['username'],
            'role' => $user_check['role']
        ];
        
        // Verificar que es superadmin
        if ($user_check['role'] !== 'superadmin') {
            $errors[] = "Usuario AdanGL existe pero NO es superadmin (role: {$user_check['role']})";
        }
    } else {
        $errors[] = "Usuario AdanGL NO existe en la base de datos";
    }
    
    // 6. Listar todos los usuarios (solo username y role)
    $all_users = $conexion->query("SELECT id, username, role FROM usuarios")->fetchAll();
    $status['all_users'] = $all_users;
    
    // 7. Probar verificación de password
    if ($user_check) {
        $pass_check = $conexion->query("SELECT password FROM usuarios WHERE username='AdanGL'")->fetch();
        $test_password = password_verify("Agl252002", $pass_check['password']);
        $status['password_verify'] = $test_password;
        
        if (!$test_password) {
            $errors[] = "La contraseña 'Agl252002' NO coincide con el hash almacenado";
        }
    }
    
} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
    $status['exception'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

$has_errors = count($errors) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Superadmin - TG Gestión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: <?php echo $has_errors ? '#d32f2f' : '#2e7d32'; ?>;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { padding: 30px; }
        .status-box {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ccc;
        }
        .status-box.success { background: #e8f5e9; border-color: #4caf50; }
        .status-box.error { background: #ffebee; border-color: #f44336; }
        .status-box.warning { background: #fff3e0; border-color: #ff9800; }
        .status-box h3 { margin-bottom: 10px; color: #333; }
        .status-box p { margin: 5px 0; color: #666; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th, .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th { background: #f5f5f5; font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge.success { background: #4caf50; color: white; }
        .badge.error { background: #f44336; color: white; }
        .badge.info { background: #2196f3; color: white; }
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-success { background: #4caf50; color: white; }
        .btn-success:hover { background: #43a047; }
        .btn-danger { background: #f44336; color: white; }
        .btn-danger:hover { background: #e53935; }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $has_errors ? '⚠️ Problemas Detectados' : '✅ Sistema OK'; ?></h1>
            <p>Verificación del Usuario Superadmin AdanGL</p>
        </div>
        
        <div class="content">
            <?php if (count($errors) > 0): ?>
                <div class="status-box error">
                    <h3>❌ Errores Encontrados:</h3>
                    <?php foreach ($errors as $error): ?>
                        <p>• <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="status-box <?php echo $status['db_file_exists'] ? 'success' : 'error'; ?>">
                <h3>1. Base de Datos</h3>
                <p><strong>Archivo:</strong> <code><?php echo htmlspecialchars($status['db_file_path']); ?></code></p>
                <p><strong>Existe:</strong> <span class="badge <?php echo $status['db_file_exists'] ? 'success' : 'error'; ?>">
                    <?php echo $status['db_file_exists'] ? 'SÍ' : 'NO'; ?>
                </span></p>
                <p><strong>Conexión:</strong> <span class="badge <?php echo isset($status['db_connection']) && $status['db_connection'] ? 'success' : 'error'; ?>">
                    <?php echo isset($status['db_connection']) && $status['db_connection'] ? 'OK' : 'FAIL'; ?>
                </span></p>
            </div>
            
            <div class="status-box <?php echo isset($status['table_usuarios_exists']) && $status['table_usuarios_exists'] ? 'success' : 'error'; ?>">
                <h3>2. Tabla Usuarios</h3>
                <p><strong>Existe:</strong> <span class="badge <?php echo isset($status['table_usuarios_exists']) && $status['table_usuarios_exists'] ? 'success' : 'error'; ?>">
                    <?php echo isset($status['table_usuarios_exists']) && $status['table_usuarios_exists'] ? 'SÍ' : 'NO'; ?>
                </span></p>
                <p><strong>Total de usuarios:</strong> <?php echo $status['total_users'] ?? 0; ?></p>
            </div>
            
            <div class="status-box <?php echo isset($status['superadmin_exists']) && $status['superadmin_exists'] && isset($status['password_verify']) && $status['password_verify'] ? 'success' : 'error'; ?>">
                <h3>3. Usuario Superadmin AdanGL</h3>
                <p><strong>Existe:</strong> <span class="badge <?php echo isset($status['superadmin_exists']) && $status['superadmin_exists'] ? 'success' : 'error'; ?>">
                    <?php echo isset($status['superadmin_exists']) && $status['superadmin_exists'] ? 'SÍ' : 'NO'; ?>
                </span></p>
                
                <?php if (isset($status['superadmin_data'])): ?>
                    <p><strong>ID:</strong> <?php echo $status['superadmin_data']['id']; ?></p>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($status['superadmin_data']['username']); ?></p>
                    <p><strong>Role:</strong> <span class="badge <?php echo $status['superadmin_data']['role'] === 'superadmin' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($status['superadmin_data']['role']); ?>
                    </span></p>
                    <p><strong>Contraseña (Agl252002):</strong> <span class="badge <?php echo isset($status['password_verify']) && $status['password_verify'] ? 'success' : 'error'; ?>">
                        <?php echo isset($status['password_verify']) && $status['password_verify'] ? 'VÁLIDA ✓' : 'INVÁLIDA ✗'; ?>
                    </span></p>
                <?php endif; ?>
            </div>
            
            <?php if (isset($status['all_users']) && count($status['all_users']) > 0): ?>
                <div class="status-box success">
                    <h3>4. Todos los Usuarios en BD</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status['all_users'] as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><span class="badge info"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="actions">
                <?php if ($has_errors): ?>
                    <a href="api/ensure_superadmin.php" class="btn btn-danger">🔧 Reparar Ahora</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-primary">🏠 Ir al Login</a>
                <a href="verify-superadmin.php" class="btn btn-success">🔄 Verificar de Nuevo</a>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">📝 Credenciales de Acceso:</h4>
                <p><strong>Usuario:</strong> <code>AdanGL</code></p>
                <p><strong>Contraseña:</strong> <code>Agl252002</code></p>
            </div>
        </div>
    </div>
</body>
</html>
