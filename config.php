<?php

$is_php_desktop = true;

$config = [
    'app_name' => 'Tienda Regional',
    'app_version' => 'v10 Beta (Offline)',
    'debug' => false,
    'phpdesktop' => true,
    'db' => [
        'driver' => 'sqlite',
    ],
];
function app_log($level, $msg) {
    global $config;
    if (!$config['debug'] && $level === 'debug') return;

    $ts = date('Y-m-d H:i:s');
    $log_msg = "[$ts] [$level] $msg\n";
    
    $log_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . DIRECTORY_SEPARATOR . 'app.log';

    @file_put_contents($log_file, $log_msg, FILE_APPEND);

    if ($config['debug']) {
        error_log($log_msg);
    }
}

set_error_handler(function($severity, $message, $file, $line) {
    $level = 'error';
    if (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED])) $level = 'notice';
    if (in_array($severity, [E_WARNING, E_USER_WARNING])) $level = 'warning';
    app_log($level, "PHP [$severity] $message | $file:$line");
    return false;
});

set_exception_handler(function($e) {
    $msg = $e->getMessage();
    $file = $e->getFile();
    $line = $e->getLine();
    app_log('error', "PHP EXCEPTION $msg | $file:$line");
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        app_log('fatal', "PHP FATAL {$err['message']} | {$err['file']}:{$err['line']}");
    }
});

function get_database_path() {
    global $config;
    $driver = isset($config['db']['driver']) ? strtolower($config['db']['driver']) : 'sqlite';
    $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($config['phpdesktop'] && $is_windows) {
        $userprofile = getenv('USERPROFILE');
        if ($userprofile && is_dir($userprofile)) {
            $tg_dir = $userprofile . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local' . DIRECTORY_SEPARATOR . 'TG_Gestion';
            if (!is_dir($tg_dir)) {
                @mkdir($tg_dir, 0777, true);
            }
            return $tg_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
        }
    }
    
    if ($driver === 'mysql') {
        $root_data = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($root_data)) {
            @mkdir($root_data, 0777, true);
        }
        return $root_data . DIRECTORY_SEPARATOR . 'database.sqlite';
    }
    if (!$is_windows) {
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        if ($home) {
            $tg_dir = $home . DIRECTORY_SEPARATOR . '.TG_Gestion';
            if (!is_dir($tg_dir)) {
                @mkdir($tg_dir, 0777, true);
            }
            return $tg_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
        }
    }
    
    if (!$is_windows) {
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        if ($home) {
            $tg_dir = $home . DIRECTORY_SEPARATOR . '.TG_Gestion';
            if (!is_dir($tg_dir)) @mkdir($tg_dir, 0777, true);
            return $tg_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
        }
    }
    
    $appData = getenv('APPDATA');
    if ($appData && $is_windows) {
        $tg_dir = $appData . DIRECTORY_SEPARATOR . 'TG_Gestion';
        if (!is_dir($tg_dir)) @mkdir($tg_dir, 0777, true);
        $db_path = $tg_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
        if (file_exists($db_path)) {
            return $db_path;
        }
    }
    
    if ($is_windows) {
        $userprofile = getenv('USERPROFILE');
        if ($userprofile && is_dir($userprofile)) {
            $roaming = $userprofile . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Roaming' . DIRECTORY_SEPARATOR . 'TG_Gestion';
            if (!is_dir($roaming)) @mkdir($roaming, 0777, true);
            $db_path = $roaming . DIRECTORY_SEPARATOR . 'database.sqlite';
            if (file_exists($db_path)) {
                return $db_path;
            }
            app_log('debug', 'Usando ruta USERPROFILE para nueva BD: ' . $db_path);
            return $db_path;
        }
    }

    if (!empty(getenv('TG_DB_PATH'))) {
        $path = getenv('TG_DB_PATH');
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        app_log('debug', 'Usando BD personalizada via TG_DB_PATH: ' . $path);
        return $path;
    }

    $root_data = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($root_data)) @mkdir($root_data, 0777, true);
    $db_path = $root_data . DIRECTORY_SEPARATOR . 'database.sqlite';
    if (file_exists($db_path)) {
        return $db_path;
    }
    $api_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api';
    if (!is_dir($api_dir)) @mkdir($api_dir, 0777, true);
    $db_path = $api_dir . DIRECTORY_SEPARATOR . 'database.sqlite';
    return $db_path;
}
define('DB_PATH', get_database_path());
define('APP_ROOT', dirname(__FILE__));
define('API_ROOT', APP_ROOT . DIRECTORY_SEPARATOR . 'api');
