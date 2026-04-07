<?php

header('Content-Type: application/json; charset=utf-8');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

$root = dirname(dirname(__FILE__));
$config_path = $root . '/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
}
require_once $root . '/api/csrf.php';
require_once $root . '/api/db.php';

$accion = $_REQUEST['accion'] ?? '';
$usuario = $_SESSION['usuario'] ?? null;
$role = $_SESSION['role'] ?? '';
$es_admin = ($role === 'admin' || $role === 'superadmin');

$log_dir = dirname($root) . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}
$log_file = $log_dir . DIRECTORY_SEPARATOR . 'app.log';
$color_file = $log_dir . DIRECTORY_SEPARATOR . 'color_tema.txt';

function to_log_string($value) {
    if (is_null($value)) return '';
    if (is_scalar($value)) return (string)$value;
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $encoded !== false ? $encoded : '[valor-no-serializable]';
}

function read_log_entries($file, $limit = 200) {
    if (!file_exists($file)) return [];
    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return [];
    $slice = array_slice($lines, -1 * $limit);
    $out = [];
    foreach ($slice as $line) {
        if (preg_match('/^\[(.*?)\]\s\[(.*?)\]\s(.*)$/', $line, $m)) {
            $out[] = ['fecha' => $m[1], 'error' => $m[2] . ' | ' . $m[3]];
        } else {
            $out[] = ['fecha' => '', 'error' => $line];
        }
    }
    return array_reverse($out);
}

try {
    // --- 0. AUTENTICAR ADMIN (Para operaciones sensibles desde frontend) ---
    if ($accion === 'autenticar_admin') {
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($usuario) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incompletas']);
            exit();
        }
        
        // Verificar que sea admin o superadmin
        $stmt = $conexion->prepare("SELECT id, password, role FROM usuarios WHERE username = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'superadmin')) {
            echo json_encode(['success' => false, 'message' => 'Usuario no válido o sin permisos']);
            exit();
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
            exit();
        }
        
        echo json_encode(['success' => true, 'message' => 'Autenticado', 'role' => $user['role']]);
        exit();
    }

    // --- Registrar errores del frontend ---
    if ($accion === 'log_error') {
        if (!$usuario) {
            echo json_encode(['success' => true, 'message' => 'Sin sesión']);
            exit();
        }
        $raw = get_cached_raw_input();
        $data = json_decode($raw, true) ?: [];
        $tipo = to_log_string($data['tipo'] ?? 'Error');
        $mensaje = to_log_string($data['mensaje'] ?? 'Sin mensaje');
        $detalles = to_log_string($data['detalles'] ?? '');
        $url = to_log_string($data['url'] ?? '');
        $ua = to_log_string($data['userAgent'] ?? '');

        $msg = "$tipo: $mensaje";
        if ($detalles) $msg .= " | $detalles";
        if ($url) $msg .= " | url=$url";
        if ($ua) $msg .= " | ua=$ua";

        if (function_exists('app_log')) {
            app_log('error', $msg);
        } else {
            @file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . "] [error] $msg\n", FILE_APPEND);
        }

        echo json_encode(['success' => true]);
        exit();
    }

    // --- Ver errores (solo admin/superadmin) ---
    if ($accion === 'ver_errores') {
        if (!$es_admin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit();
        }
        $errores = read_log_entries($log_file, 200);
        echo json_encode(['success' => true, 'errores' => $errores]);
        exit();
    }

    // --- Limpiar errores (solo admin/superadmin) ---
    if ($accion === 'limpiar_errores') {
        if (!$es_admin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit();
        }
        require_csrf_or_die();
        @file_put_contents($log_file, '');
        echo json_encode(['success' => true]);
        exit();
    }

    // --- Obtener color ---
    if ($accion === 'get_color') {
        $color = '#0d47a1';
        if (file_exists($color_file)) {
            $c = trim(@file_get_contents($color_file));
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) $color = $c;
        }
        echo json_encode(['success' => true, 'color' => $color]);
        exit();
    }

    // --- Guardar color (solo admin/superadmin) ---
    if ($accion === 'save_color') {
        if (!$es_admin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit();
        }
        require_csrf_or_die();
        $nuevo = $_POST['color'] ?? '';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $nuevo)) {
            echo json_encode(['success' => false, 'message' => 'Color inválido']);
            exit();
        }
        @file_put_contents($color_file, $nuevo);
        echo json_encode(['success' => true]);
        exit();
    }

    // --- Salud del sistema (admin+) ---
    if ($accion === 'salud_sistema') {
        if (!$es_admin) {
            throw new Exception('Usuario no tiene permisos de admin');
        }

        if (!defined('DB_PATH')) {
            throw new Exception('DB_PATH no definido en config.php');
        }

        $db_path = DB_PATH;
        if (!file_exists($db_path)) {
            throw new Exception('Base de datos no existe: ' . $db_path);
        }

        $conexion = new PDO('sqlite:' . $db_path, '', '', [
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $db_size = round(filesize($db_path) / 1024 / 1024, 2);
        $stmt = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios");
        $usuarios = intval($stmt->fetch()['cnt'] ?? 0);
        $stmt = $conexion->query("SELECT COUNT(*) as cnt FROM productos WHERE activo = 1");
        $productos = intval($stmt->fetch()['cnt'] ?? 0);
        $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas");
        $ventas = floatval($stmt->fetch()['total'] ?? 0);
        $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE tipo_pago = 'fiado' AND fiado_pagado = 0");
        $fiados = floatval($stmt->fetch()['total'] ?? 0);
        $disk_free = @disk_free_space(dirname($db_path));
        $disk_gb = $disk_free ? round($disk_free / 1024 / 1024 / 1024, 2) : 0;

        echo json_encode([
            'success' => true,
            'db_size_mb' => $db_size,
            'usuarios_total' => $usuarios,
            'productos_activos' => $productos,
            'ventas_total' => $ventas,
            'fiados_pendientes' => $fiados,
            'disk_free_gb' => $disk_gb
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Accion invalida: ' . $accion]);
} catch (Exception $e) {
    ob_clean();
    if (function_exists('app_log')) {
        app_log('error', 'api_admin.php: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}
?>
