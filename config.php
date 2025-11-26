<?php
// config.php - Centraliza rutas y configuración global
// Este archivo se carga PRIMERO en index.php y dashboard.php

// ===== CONFIGURACIÓN DE RUTAS =====
// La BD se almacenará en una ruta persistente para que no se pierda al empaquetar

$config = [
    'app_name' => 'Tienda Regional',
    'app_version' => 'v4.0 (Offline)',
    'debug' => true, // DEBUG: Cambiar a true para ver errores detallados EN CGI. PHP Desktop lo debe leer de logs
];

// ===== FUNCIÓN PARA LOGS DE APLICACIÓN =====
// DEBE estar ANTES de get_database_path() porque app_log se usa dentro
function app_log($level, $msg) {
    global $config;
    if (!$config['debug'] && $level === 'debug') return;

    $ts = date('Y-m-d H:i:s');
    $log_msg = "[$ts] [$level] $msg\n";
    
    // Determinar dónde escribir el log (sin usar DB_PATH si no está definido aún)
    $log_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    @mkdir($log_dir, 0777, true);
    $log_file = $log_dir . DIRECTORY_SEPARATOR . 'app.log';

    @file_put_contents($log_file, $log_msg, FILE_APPEND);

    if ($config['debug']) {
        error_log($log_msg);
    }
}

// ===== RESOLUCIÓN DE RUTA BD =====
// CRÍTICO: En modo CGI (phpdesktop) las variables de ambiente pueden no estar disponibles
// Por lo tanto: intentamos PRIMERO fallback seguro, LUEGO environment
// Orden:
// 1. APPDATA\TG_Gestion\ (si es Windows y variable disponible)
// 2. C:\Users\[usuario]\AppData\Roaming\TG_Gestion\ (fallback Windows puro)
// 3. Variable de entorno TG_DB_PATH (desarrollo/custom)
// 4. Carpeta 'data/' en raíz del proyecto (desarrollo local)

function get_database_path() {
    // DETECCIÓN DE WINDOWS
    $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if (!$is_windows) {
        // En sistemas no-Windows, intentar XDG o home
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        if ($home) {
            $tg_dir = $home . DIRECTORY_SEPARATOR . '.TG_Gestion';
            if (!is_dir($tg_dir)) @mkdir($tg_dir, 0777, true);
            return $tg_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
        }
    }
    
    // ===== INTENTO 1: APPDATA (Windows) =====
    $appData = getenv('APPDATA');
    if ($appData && $is_windows) {
        $tg_dir = $appData . DIRECTORY_SEPARATOR . 'TG_Gestion';
        if (!is_dir($tg_dir)) @mkdir($tg_dir, 0777, true);
        $db_path = $tg_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
        // Si existe, usarla (migración desde versión anterior)
        if (file_exists($db_path)) {
            app_log('debug', 'DB encontrada en APPDATA: ' . $db_path);
            return $db_path;
        }
    }
    
    // ===== INTENTO 2: USERPROFILE Fallback (para CGI sin APPDATA) =====
    // En Windows, construir manualmente C:\Users\[user]\AppData\Roaming
    if ($is_windows) {
        $userprofile = getenv('USERPROFILE');
        if ($userprofile && is_dir($userprofile)) {
            $roaming = $userprofile . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Roaming' . DIRECTORY_SEPARATOR . 'TG_Gestion';
            if (!is_dir($roaming)) @mkdir($roaming, 0777, true);
            $db_path = $roaming . DIRECTORY_SEPARATOR . 'database.sqlite';
            if (file_exists($db_path)) {
                app_log('debug', 'DB encontrada en USERPROFILE fallback: ' . $db_path);
                return $db_path;
            }
            // Preferir esta ruta para nuevas BDs en Windows
            app_log('debug', 'Usando ruta USERPROFILE para nueva BD: ' . $db_path);
            return $db_path;
        }
    }

    // ===== INTENTO 3: Variable de entorno TG_DB_PATH (custom) =====
    if (!empty(getenv('TG_DB_PATH'))) {
        $path = getenv('TG_DB_PATH');
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        app_log('debug', 'Usando BD personalizada via TG_DB_PATH: ' . $path);
        return $path;
    }

    // ===== INTENTO 4: Carpeta 'data' en raíz del proyecto (desarrollo) =====
    $root_data = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($root_data)) @mkdir($root_data, 0777, true);
    $db_path = $root_data . DIRECTORY_SEPARATOR . 'database.sqlite';
    if (file_exists($db_path)) {
        app_log('debug', 'BD encontrada en carpeta data (desarrollo): ' . $db_path);
        return $db_path;
    }

    // ===== FALLBACK FINAL: api/ folder (último recurso) =====
    $api_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api';
    if (!is_dir($api_dir)) @mkdir($api_dir, 0777, true);
    $db_path = $api_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
    app_log('debug', 'Usando fallback api/ para BD: ' . $db_path);
    return $db_path;
}

// Definir la ruta final
define('DB_PATH', get_database_path());
define('APP_ROOT', dirname(__FILE__));
define('API_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'api');

app_log('info', 'Configuración cargada. DB_PATH: ' . DB_PATH);

?>
