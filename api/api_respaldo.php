<?php
// api/api_respaldo.php
// VERSIÓN FINAL: Descarga Segura de SQLite

session_start();
include 'db.php'; // Incluye la conexión para asegurar que la ruta es correcta

// 1. Seguridad: Solo Superadmin puede descargar la base de datos completa
if (!isset($_SESSION['usuario']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    die("⛔ Acceso Denegado: Solo el Superadmin puede descargar la base de datos.");
}

$db_file = defined('DB_FILE') ? DB_FILE : (__DIR__ . '/database.sqlite');
$fecha = date('Y-m-d_H-i');
$nombre_descarga = "Respaldo_TG_Gestion_$fecha.sqlite";

// 3. Verificar existencia
if (file_exists($db_file)) {
    // 4. Limpiar buffer de salida (Vital para no corromper el archivo)
    if (ob_get_level()) ob_end_clean();

    // 5. Headers para forzar descarga binaria
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($db_file));
    
    // 6. Enviar archivo
    readfile($db_file);
    exit;
} else {
    die("❌ Error Crítico: No se encuentra el archivo 'database.sqlite' en el servidor.");
}
?>