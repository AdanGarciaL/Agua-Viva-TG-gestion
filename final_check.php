<?php
// final_check.php - Verificación final del sistema

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: text/html; charset=utf-8');

$checks = [];

// 1. Verificar config
$config_file = 'config.php';
$checks['config'] = file_exists($config_file) ? 'OK' : 'FALTA';

// 2. Verificar api_admin.php
$admin_file = 'api/api_admin.php';
$checks['api_admin'] = file_exists($admin_file) ? 'OK' : 'FALTA';

// 3. Verificar db.php
$db_file = 'api/db.php';
$checks['db'] = file_exists($db_file) ? 'OK' : 'FALTA';

// 4. Intentar conexión a BD
try {
    require_once 'config.php';
    require_once 'api/db.php';
    
    if ($conexion) {
        $stmt = $conexion->query("SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table'");
        $row = $stmt->fetch();
        $checks['bd_conexion'] = 'OK (' . $row['cnt'] . ' tablas)';
    } else {
        $checks['bd_conexion'] = 'FALLO';
    }
} catch (Exception $e) {
    $checks['bd_conexion'] = 'ERROR: ' . $e->getMessage();
}

// 5. Verificar sintaxis de api_admin.php
$exec_output = [];
exec('php -l api/api_admin.php 2>&1', $exec_output);
$syntax_ok = implode(' ', $exec_output);
$checks['sintaxis_api_admin'] = strpos($syntax_ok, 'No syntax errors') !== false ? 'OK' : 'ERROR: ' . $syntax_ok;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación Final - TG Gestión</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #0d47a1; border-bottom: 2px solid #0d47a1; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: 600; width: 60%; }
        .ok { color: #4caf50; font-weight: 600; }
        .error { color: #f44336; font-weight: 600; }
        .falta { color: #ff9800; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <h1>✓ Verificación Final del Sistema</h1>
    
    <table>
        <?php foreach ($checks as $nombre => $estado): ?>
        <tr>
            <td><?php echo htmlspecialchars($nombre); ?></td>
            <td class="<?php echo strtolower(strpos($estado, 'OK') !== false ? 'ok' : (strpos($estado, 'FALTA') !== false ? 'falta' : 'error')); ?>">
                <?php echo htmlspecialchars($estado); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p style="margin-top: 20px; padding: 10px; background: #e3f2fd; border-radius: 4px;">
        <strong>ℹ️ Para probar el API:</strong><br>
        <a href="test_salud.html" target="_blank" style="color: #0d47a1; text-decoration: none;">Abre test_salud.html →</a>
    </p>
</div>
</body>
</html>
