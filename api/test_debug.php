<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$_SESSION['usuario'] = 'test';
$_SESSION['role'] = 'superadmin';

$accion = 'salud_sistema';

if ($accion === 'salud_sistema') {
    try {
        $config_path = dirname(dirname(__FILE__)) . '/config.php';
        
        echo json_encode([
            'debug' => true,
            'config_path' => $config_path,
            'file_exists' => file_exists($config_path),
            'cwd' => getcwd(),
            '__FILE__' => __FILE__,
            '__DIR__' => __DIR__
        ]);
        exit();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
