<?php
// api/csrf.php - pequeñas helpers para verificar token CSRF en peticiones que modifican estado
// NOTA: NO llamar session_start() aquí - ya debe estar hecho en el archivo que incluye esto

function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_from_request() {
    // Primero checar cabecera
    $headers = [];
    if (function_exists('getallheaders')) $headers = getallheaders();
    $token = null;
    if (!empty($headers['X-CSRF-Token'])) $token = $headers['X-CSRF-Token'];
    if (!$token && !empty($_POST['csrf_token'])) $token = $_POST['csrf_token'];
    // Si la petición viene en JSON (php://input) intentar decodificar
    if (!$token) {
        static $cached_raw = null;
        if ($cached_raw === null) $cached_raw = file_get_contents('php://input');
        if ($cached_raw) {
            $json = json_decode($cached_raw, true);
            if (isset($json['csrf_token'])) $token = $json['csrf_token'];
        }
    }

    if (!$token) return false;
    if (!isset($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf_or_die() {
    if (!validate_csrf_from_request()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF token inválido o ausente']);
        exit();
    }
}

// Permite acceder al cuerpo raw leído por la validación para evitar dobles lecturas
function get_cached_raw_input() {
    static $cached_raw = null;
    if ($cached_raw === null) $cached_raw = file_get_contents('php://input');
    return $cached_raw;
}

?>