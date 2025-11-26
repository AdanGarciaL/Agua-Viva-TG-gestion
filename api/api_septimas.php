<?php
// api/api_septimas.php - DEPRECATED
// Esta API se mantiene por compatibilidad pero está desactivada en la edición 'Tienda Regional'.
session_start();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'message' => 'Módulo Séptimas deshabilitado en esta versión.']);
exit();
?>