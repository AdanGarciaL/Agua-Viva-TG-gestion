<?php
// api/api_septimas.php - v5.1 habilitado

session_start();
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
	echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
	exit();
}

$esAdmin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
if (!$esAdmin) {
	echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
	exit();
}

$accion = $_REQUEST['accion'] ?? '';
$nowExpr = (defined('DB_DRIVER') && DB_DRIVER === 'mysql') ? 'NOW()' : "datetime('now', 'localtime')";

try {
	// LISTAR
	if ($accion === 'listar') {
		$stmt = $conexion->query("SELECT * FROM septimas ORDER BY id DESC LIMIT 200");
		echo json_encode(['success' => true, 'septimas' => $stmt->fetchAll()]);
		exit();
	}

	// CREAR
	if ($accion === 'crear') {
		include_once 'csrf.php';
		require_csrf_or_die();
		$nombre = trim($_POST['nombre_padrino'] ?? '');
		$monto = floatval($_POST['monto'] ?? 0);
		$tipo = $_POST['tipo'] ?? 'normal'; // normal | especial
		$servicio = $_POST['servicio'] ?? null; // opcional

		if ($nombre === '' || $monto <= 0) {
			echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
			exit();
		}

		$stmt = $conexion->prepare("INSERT INTO septimas (fecha, nombre_padrino, monto, usuario_registro, pagado, tipo, servicio) VALUES ($nowExpr, ?, ?, ?, 0, ?, ?)");
		$stmt->execute([$nombre, $monto, $_SESSION['usuario'], $tipo, $servicio]);
		echo json_encode(['success' => true]);
		exit();
	}

	// MARCAR PAGADO
	if ($accion === 'pagar') {
		include_once 'csrf.php';
		require_csrf_or_die();
		$id = $_POST['id'] ?? null;
		if (!$id) { echo json_encode(['success'=>false,'message'=>'Falta id']); exit(); }
		$stmt = $conexion->prepare("UPDATE septimas SET pagado = 1 WHERE id = ?");
		$stmt->execute([$id]);
		echo json_encode(['success' => true]);
		exit();
	}

	// ELIMINAR
	if ($accion === 'eliminar') {
		include_once 'csrf.php';
		require_csrf_or_die();
		$id = $_POST['id'] ?? null;
		if (!$id) { echo json_encode(['success'=>false,'message'=>'Falta id']); exit(); }
		$stmt = $conexion->prepare("DELETE FROM septimas WHERE id = ?");
		$stmt->execute([$id]);
		echo json_encode(['success' => true]);
		exit();
	}

	echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Error septimas: ' . $e->getMessage()]);
}
?>