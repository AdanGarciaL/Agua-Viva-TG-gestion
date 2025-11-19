<?php
// api/api_respaldo.php
// VERSIÓN FINAL: Producción
// Descarga directa del archivo de base de datos

session_start();
include 'db.php';

// Seguridad: Solo Superadmin
if (!isset($_SESSION['usuario']) || $_SESSION['role'] !== 'superadmin') {
    die("Acceso denegado.");
}

// Archivo a descargar
$db_file = __DIR__ . '/database.sqlite';
$fecha = date('Y-m-d_H-i');
$nombre_descarga = "Respaldo_AguaViva_$fecha.sqlite";

if (file_exists($db_file)) {
    // Limpiar cualquier basura en el buffer para no corromper el archivo
    while (ob_get_level()) ob_end_clean();

    // Forzar la descarga
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($db_file));
    
    // Enviar el archivo
    readfile($db_file);
    exit;
} else {
    die("Error Crítico: No se encuentra el archivo de base de datos.");
}
?>