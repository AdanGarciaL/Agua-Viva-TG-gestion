<?php
// api/api_ventas.php
// VERSIÓN v4.1 - Reconexión automática

session_start();
include 'db.php';
include 'error_handler.php';
header('Content-Type: application/json');

// Logging pero sin mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Asegurar conexión de BD
if (!asegurar_conexion_db()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos. Reintentando...']);
    exit();
}

// DEBUG: Log de operaciones de ventas
@file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_ventas.log',
    date('Y-m-d H:i:s') . " [VENTAS] Accion: " . ($_REQUEST['accion'] ?? 'unknown') . " | Data: " . json_encode($_REQUEST) . "\n",
    FILE_APPEND
);

validar_sesion();

$usuario = $_SESSION['usuario'];
$role = $_SESSION['role'];
$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($role === 'admin' || $role === 'superadmin');
$vendedor_nombre = $_SESSION['vendedor_nombre'] ?? $usuario;
$nowExpr = (defined('DB_DRIVER') && DB_DRIVER === 'mysql') ? 'NOW()' : "datetime('now', 'localtime')";

try {
    // --- 1. LISTAR VENTAS (Últimas 100 para velocidad) ---
    if ($accion === 'listar_ventas') {
        $stmt = $conexion->query("
            SELECT v.*, p.nombre as producto_nombre 
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            ORDER BY v.fecha DESC
            LIMIT 100
        ");
        echo json_encode(['success' => true, 'ventas' => $stmt->fetchAll()]);
        exit();
    }

    // --- 2. LISTAR DEUDORES (Normalizado) ---
    if ($accion === 'listar_fiados') {
        // Obtener deudores con nombre normalizado para agrupar correctamente
        $stmt = $conexion->query("
            SELECT nombre_fiado, SUM(total) as total_deuda, COUNT(*) as cantidad_ventas
            FROM ventas
            WHERE tipo_pago = 'fiado' AND fiado_pagado = 0
            GROUP BY LOWER(TRIM(nombre_fiado))
            HAVING total_deuda > 0
            ORDER BY total_deuda DESC
        ");
        
        $deudores = $stmt->fetchAll();
        
        // Normalizar nombres en la respuesta
        foreach ($deudores as &$deudor) {
            $deudor['nombre_fiado'] = normalizar_nombre($deudor['nombre_fiado']);
        }
        
        echo json_encode(['success' => true, 'deudores' => $deudores]);
        exit;
    }

    // --- 3. PAGAR DEUDA (Normalizado) ---
    if ($accion === 'pagar_fiado' && !empty($_POST['nombre_fiado'])) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        // Normalizar nombre del deudor
        $nombre_original = $_POST['nombre_fiado'];
        if (!validar_nombre($nombre_original)) {
            error_response('nombre_invalido', [], 400);
        }
        
        $nombre = normalizar_nombre($nombre_original);
        $monto = $_POST['monto_pagado'] ?? 0;

        if (!$monto || $monto <= 0) {
            error_response('datos_incompletos', ['field' => 'monto_pagado']);
        }

        $conexion->beginTransaction();
        
        try {
            // Marcar ventas como pagadas (buscando con LOWER para insensibilidad a mayúsculas)
            $stmt = $conexion->prepare("
                UPDATE ventas 
                SET fiado_pagado = 1 
                WHERE LOWER(TRIM(nombre_fiado)) = LOWER(TRIM(?)) AND tipo_pago = 'fiado' AND fiado_pagado = 0
            ");
            $stmt->execute([$nombre]);
            
            $filas_actualizadas = $stmt->rowCount();
            
            if ($filas_actualizadas === 0) {
                $conexion->rollBack();
                log_error('deuda_no_encontrada', "No se encontraron deudas a nombre de: {$nombre}", $usuario);
                error_response('datos_incompletos', ['message' => 'No hay deudas registradas a este nombre'], 404);
            }

            // Registrar ingreso de dinero en caja
            $stmtReg = $conexion->prepare(
                "INSERT INTO registros (tipo, concepto, monto, usuario, fecha) 
                 VALUES ('ingreso', ?, ?, ?, $nowExpr)"
            );
            $stmtReg->execute(["Pago de fiado: {$nombre}", $monto, $vendedor_nombre]);

            $conexion->commit();
            
            log_error('pago_deuda', "Pagado: {$nombre}, Monto: {$monto}, Ventas marcadas: {$filas_actualizadas}", $usuario);
            echo json_encode(['success' => true, 'message' => "Deuda de {$nombre} pagada", 'filas_actualizadas' => $filas_actualizadas]);
        } catch (Exception $e) {
            $conexion->rollBack();
            log_error('error_pago_deuda', $e->getMessage(), $usuario);
            error_response('base_datos_error', [], 500);
        }
        exit();
    }

    // --- 4. CANCELAR / ELIMINAR VENTA (Solo Admin/Superadmin) ---
    if (($accion === 'cancelar_venta' || $accion === 'eliminar_venta') && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $id = $_POST['id'] ?? null;

        if (!$id) {
            error_response('datos_incompletos', ['field' => 'id']);
        }

        $conexion->beginTransaction();

        // Obtener venta para devolver stock y validar existencia
        $stmtGet = $conexion->prepare("SELECT producto_id, cantidad FROM ventas WHERE id = ?");
        $stmtGet->execute([$id]);
        $venta = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            $conexion->rollBack();
            error_response('datos_incompletos', ['field' => 'venta']);
        }

        // Devolver stock del producto vendido
        $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
        $stmtStock->execute([$venta['cantidad'], $venta['producto_id']]);

        // Borrar venta (mantenemos delete físico)
        $stmtDel = $conexion->prepare("DELETE FROM ventas WHERE id = ?");
        $stmtDel->execute([$id]);

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // --- 5. NUEVA VENTA (Procesar Carrito - BLINDADO) ---
    include_once 'csrf.php';
    // Leer una sola vez el raw input (cached en csrf)
    $raw_input = get_cached_raw_input();
    $input = $raw_input ? json_decode($raw_input, true) : null;
    if ($input) {
        require_csrf_or_die();
        $carrito = $input['carrito'] ?? [];
        if (empty($carrito)) {
            error_response('datos_incompletos', ['field' => 'carrito']);
        }

        $tipo = $input['tipo_pago'] ?? 'pagado';
        // Normalizar: "pagado" → "efectivo", "fiado" → "fiado"
        if ($tipo === 'pagado') $tipo = 'efectivo';
        
        // Validar y normalizar nombre del deudor si es fiado
        $fiadoA = null;
        $grupoFiado = null;
        if ($tipo === 'fiado') {
            $nombre_fiado_input = $input['nombre_fiado'] ?? null;
            $grupoFiado = $input['grupo_fiado'] ?? null;
            
            if (empty($nombre_fiado_input)) {
                error_response('nombre_vacio', [], 400);
            }
            
            if (!validar_nombre($nombre_fiado_input)) {
                error_response('nombre_invalido', [], 400);
            }
            
            $fiadoA = normalizar_nombre($nombre_fiado_input);
        }

        $conexion->beginTransaction();

        // Consultas preparadas
        $stmtCheckStock = $conexion->prepare("SELECT nombre, stock FROM productos WHERE id = ?");
        $stmtVenta = $conexion->prepare("INSERT INTO ventas (producto_id, cantidad, total, vendedor, foto_referencia, tipo_pago, nombre_fiado, grupo_fiado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, $nowExpr)");
        $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

        foreach ($carrito as $item) {
            $productoId = $item['id'] ?? null;
            $cantidad = isset($item['cantidad']) ? intval($item['cantidad']) : 0;
            if (!$productoId || $cantidad <= 0) {
                error_response('datos_incompletos', ['field' => 'producto/cantidad']);
            }

            // 1. BLINDAJE DE STOCK: Verificar antes de insertar
            $stmtCheckStock->execute([$productoId]);
            $prodReal = $stmtCheckStock->fetch(PDO::FETCH_ASSOC);

            if (!$prodReal) {
                error_response('producto_no_existe');
            }
            
            if ($prodReal['stock'] < $cantidad) {
                error_response('stock_insuficiente', ['producto' => $prodReal['nombre']]);
            }

            // 2. Si hay stock, procedemos
            // Soportar tanto 'precio' como 'precio_venta' del JS
            $precio = $item['precio'] ?? $item['precio_venta'] ?? 0;
            $total = floatval($precio) * $cantidad;
            $stmtVenta->execute([$productoId, $cantidad, $total, $vendedor_nombre, $item['foto'] ?? null, $tipo, $fiadoA, $grupoFiado]);
            $stmtStock->execute([$cantidad, $productoId]);
        }

        // 3. REGISTRAR MOVIMIENTO EN CAJA (automático después de venta exitosa)
        $totalCarrito = array_reduce($carrito, function($sum, $item) {
            $precio = $item['precio'] ?? $item['precio_venta'] ?? 0;
            return $sum + (floatval($precio) * intval($item['cantidad']));
        }, 0);
        
        $stmtReg = $conexion->prepare("INSERT INTO registros (tipo, concepto, monto, usuario, fecha) VALUES (?, ?, ?, ?, $nowExpr)");
        if ($tipo === 'fiado') {
            $stmtReg->execute(['fiado', 'Fiado a: ' . ($fiadoA ?? 'Sin nombre'), $totalCarrito, $vendedor_nombre]);
            // Registrar deudor en log para auditoría
            log_error('deudor_registrado', "Nuevo fiado registrado: $fiadoA | Total: $totalCarrito | Vendedor: $vendedor_nombre", 'info');
        } else {
            $stmtReg->execute(['efectivo', 'Venta Efectivo', $totalCarrito, $vendedor_nombre]);
        }

        $conexion->commit();
        echo json_encode(['success' => true]);
        exit();
    }

    // v5.0: ÚLTIMAS VENTAS (para historial)
    if ($accion === 'ultimas_ventas') {
        $limit = min(20, max(1, intval($_GET['limit'] ?? 5)));
        $stmt = $conexion->prepare("
            SELECT id, total, tipo_pago, fecha
            FROM ventas
            ORDER BY fecha DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        echo json_encode(['success' => true, 'ventas' => $stmt->fetchAll()]);
        exit();
    }
    
    // v5.1: HISTORIAL DE CLIENTE (para fiados)
    if ($accion === 'historial_cliente') {
        $nombre = $_GET['nombre'] ?? '';
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
            exit();
        }
        
        $stmt = $conexion->prepare("
            SELECT v.id, v.fecha, v.total, v.cantidad, v.tipo_pago, v.fiado_pagado, 
                   p.nombre as producto_nombre
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            WHERE LOWER(TRIM(v.nombre_fiado)) = LOWER(TRIM(?))
            ORDER BY v.fecha DESC
            LIMIT 50
        ");
        $stmt->execute([$nombre]);
        echo json_encode(['success' => true, 'ventas' => $stmt->fetchAll()]);
        exit();
    }

} catch (Exception $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    manejar_excepcion_general($e, 'ventas');
}
?>