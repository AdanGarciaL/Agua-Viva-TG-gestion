<?php
/**
 * launcher.php - Punto de entrada para PHP Desktop
 * 
 * Este script es el punto de entrada para la aplicación cuando se ejecuta 
 * como un ejecutable de PHP Desktop. Se encarga de:
 * 1. Redirigir a index.php o dashboard.php dependiendo de la sesión
 * 2. Configurar headers para seguridad
 * 3. Servir assets estáticos
 */

// Permitir acceso a archivos estáticos (CSS, JS, imágenes)
$requestUri = $_SERVER['REQUEST_URI'];
$scriptPath = dirname(__FILE__) . '/../';

// Normalizar la ruta
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = str_replace('/packaging/launcher.php', '', $requestPath);

// Servir archivos estáticos directamente
if (file_exists($scriptPath . ltrim($requestPath, '/'))) {
    $filePath = $scriptPath . ltrim($requestPath, '/');
    
    // Validar que no sea un archivo PHP (excepto en casos específicos)
    if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
        // Determinar MIME type
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'html' => 'text/html',
            'txt'  => 'text/plain',
        ];
        
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
        }
        
        // Caché for static assets (30 days)
        if (in_array($ext, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf'])) {
            header('Cache-Control: public, max-age=2592000');
            header('Expires: ' . date('r', strtotime('+30 days')));
        } else {
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
        
        readfile($filePath);
        exit;
    }
}

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Redirigir solicitudes a los scripts principales
if (strpos($requestPath, '/api/') === 0) {
    // Las requests a /api/ se procesan directamente
    $_GET['path'] = $requestPath;
    require $scriptPath . 'index.php';
} else if (empty($requestPath) || $requestPath === '/') {
    // Raíz redirige a login o dashboard
    require $scriptPath . 'index.php';
} else {
    // Cualquier otra ruta va a index.php que maneja la lógica
    require $scriptPath . 'index.php';
}
