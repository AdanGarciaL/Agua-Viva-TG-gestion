<?php
// api/api_ventas.php
// VERSIÓN v4.1 - Reconexión automática

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'error_handler.php';
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

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
            SELECT
                nombre_fiado,
                COALESCE(MAX(grupo_fiado), '') as grupo_fiado,
                COALESCE(MAX(celular_fiado), '') as celular_fiado,
                SUM(total) as total_deuda,
                COUNT(*) as cantidad_ventas
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
        $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
        $referencia_pago = trim($_POST['referencia_pago'] ?? '');

        if (!in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia'])) {
            $metodo_pago = 'efectivo';
        }

        if (!$monto || $monto <= 0) {
            error_response('datos_incompletos', ['field' => 'monto_pagado']);
        }

        $conexion->beginTransaction();
        
        try {
            // Marcar ventas como pagadas (buscando con LOWER para insensibilidad a mayúsculas)
            $stmt = $conexion->prepare("
                UPDATE ventas 
                SET fiado_pagado = 1 
                WHERE REPLACE(REPLACE(LOWER(TRIM(nombre_fiado)), '.', ''), ',', '') = REPLACE(REPLACE(LOWER(TRIM(?)), '.', ''), ',', '') AND tipo_pago = 'fiado' AND fiado_pagado = 0
            ");
            $stmt->execute([$nombre]);
            
            $filas_actualizadas = $stmt->rowCount();
            
            if ($filas_actualizadas === 0) {
                $conexion->rollBack();
                log_error('deuda_no_encontrada', "No se encontraron deudas a nombre de: {$nombre}", $usuario);
                error_response('datos_incompletos', ['message' => 'No hay deudas registradas a este nombre'], 404);
            }

            // Registrar ingreso de dinero en caja
            $conceptoPago = "Pago de cuenta: {$nombre} ({$metodo_pago}";
            if (!empty($referencia_pago)) {
                $conceptoPago .= ", ref: {$referencia_pago}";
            }
            $conceptoPago .= ")";
            $stmtReg = $conexion->prepare(
                "INSERT INTO registros (tipo, concepto, monto, usuario, fecha) 
                 VALUES ('ingreso', ?, ?, ?, $nowExpr)"
            );
            $stmtReg->execute([$conceptoPago, $monto, $vendedor_nombre]);

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
        $celularFiado = null;
        if ($tipo === 'fiado') {
            $nombre_fiado_input = $input['nombre_fiado'] ?? null;
            $grupoFiado = $input['grupo_fiado'] ?? null;
            $celularFiado = trim((string)($input['celular_fiado'] ?? ''));
            
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
        $stmtVenta = $conexion->prepare("INSERT INTO ventas (producto_id, cantidad, total, vendedor, tipo_pago, nombre_fiado, grupo_fiado, celular_fiado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, $nowExpr)");
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
            $stmtVenta->execute([$productoId, $cantidad, $total, $vendedor_nombre, $tipo, $fiadoA, $grupoFiado, $celularFiado]);
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

    // --- 6. GESTIÓN DE CUENTAS (NEW) ---
    
    // Listar todas las cuentas con búsqueda
    if ($accion === 'listar_cuentas') {
        $buscar = $_GET['buscar'] ?? '';
        $estado = $_GET['estado'] ?? 'activo';
        
        $sql = "SELECT * FROM cuentas WHERE estado_cuenta = ?";
        $params = [$estado];
        
        if (!empty($buscar)) {
            $sql .= " AND (LOWER(nombre_cuenta) LIKE ? OR celular LIKE ?)";
            $buscarLike = '%' . strtolower($buscar) . '%';
            $params[] = $buscarLike;
            $params[] = '%' . $buscar . '%';
        }
        
        $sql .= " ORDER BY fecha_ultimo_compra DESC NULLS LAST";
        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'cuentas' => $stmt->fetchAll()]);
        exit();
    }
    
    // Obtener detalles de una cuenta con historial
    if ($accion === 'obtener_cuenta') {
        $nombre_cuenta = $_GET['nombre_cuenta'] ?? '';
        if (empty($nombre_cuenta)) {
            echo json_encode(['success' => false, 'message' => 'Nombre de cuenta requerido']);
            exit();
        }
        
        // Obtener datos de la cuenta
        $stmt = $conexion->prepare("
            SELECT * FROM cuentas 
            WHERE REPLACE(REPLACE(LOWER(TRIM(nombre_cuenta)), '.', ''), ',', '') = REPLACE(REPLACE(LOWER(TRIM(?)), '.', ''), ',', '')
        ");
        $stmt->execute([$nombre_cuenta]);
        $cuenta = $stmt->fetch();
        
        if (!$cuenta) {
            echo json_encode(['success' => false, 'message' => 'Cuenta no encontrada']);
            exit();
        }
        
        // Obtener historial de compras de esta cuenta
        $stmt2 = $conexion->prepare("
            SELECT v.id, v.fecha, v.total, v.cantidad, v.tipo_pago, v.fiado_pagado, 
                   v.metodo_pago, p.nombre as producto_nombre
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            WHERE REPLACE(REPLACE(LOWER(TRIM(v.nombre_fiado)), '.', ''), ',', '') = REPLACE(REPLACE(LOWER(TRIM(?)), '.', ''), ',', '')
            ORDER BY v.fecha DESC
        ");
        $stmt2->execute([$nombre_cuenta]);
        $historial = $stmt2->fetchAll();
        
        // Obtener historial de cambios de la cuenta (auditoría)
        $stmt3 = $conexion->prepare("
            SELECT * FROM audit_log 
            WHERE tabla = 'cuentas' AND registro_id = ?
            ORDER BY fecha DESC
        ");
        $stmt3->execute([$cuenta['id']]);
        $cambios = $stmt3->fetchAll();
        
        echo json_encode([
            'success' => true,
            'cuenta' => $cuenta,
            'historial_compras' => $historial,
            'historial_cambios' => $cambios
        ]);
        exit();
    }
    
    // Crear o actualizar cuenta (detectar duplicados)
    if ($accion === 'crear_cuenta' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $nombre_cuenta = trim($_POST['nombre_cuenta'] ?? '');
        $celular = trim($_POST['celular'] ?? '');
        
        if (empty($nombre_cuenta) || empty($celular) || empty(trim($_POST['notas'] ?? ''))) {
            error_response('datos_incompletos', ['field' => 'nombre_cuenta']);
        }
        
        // Buscar si ya existe (case-insensitive)
        $stmt = $conexion->prepare("
            SELECT id FROM cuentas 
            WHERE REPLACE(REPLACE(LOWER(TRIM(nombre_cuenta)), '.', ''), ',', '') = REPLACE(REPLACE(LOWER(TRIM(?)), '.', ''), ',', '')
        ");
        $stmt->execute([$nombre_cuenta]);
        $existe = $stmt->fetch();
        
        if ($existe) {
                // Si ya existe, actualizar y reactivar por si estaba inactiva.
            $stmt = $conexion->prepare("
                UPDATE cuentas 
                SET celular = ?, notas = ?
                WHERE id = ?
            ");
            $stmt->execute([$celular ?: null, trim($_POST['notas'] ?? ''), $existe['id']]);

                $stmtNombreEstado = $conexion->prepare("
                    UPDATE cuentas
                    SET nombre_cuenta = ?, estado_cuenta = 'activo'
                    WHERE id = ?
                ");
                $stmtNombreEstado->execute([$nombre_cuenta, $existe['id']]);
            
            // Registrar en auditoría
            registrar_auditoria('cuentas', $existe['id'], 'actualización', 'anterior', 'actualizado');
            
                echo json_encode(['success' => true, 'message' => 'Cuenta actualizada/reactivada', 'cuenta_id' => $existe['id']]);
        } else {
            // Crear nueva
            $stmt = $conexion->prepare("
                INSERT INTO cuentas (nombre_cuenta, celular, fecha_creacion, estado_cuenta)
                VALUES (?, ?, datetime('now', 'localtime'), 'activo')
            ");
            $stmt->execute([$nombre_cuenta, $celular ?: null]);
            $newId = $conexion->lastInsertId();
            
            // Registrar en auditoría
            registrar_auditoria('cuentas', $newId, 'creación', 'nueva', $nombre_cuenta);
            
            echo json_encode(['success' => true, 'message' => 'Cuenta creada', 'cuenta_id' => $newId]);
        }
        exit();
    }

    // Editar cuenta existente
    if ($accion === 'editar_cuenta' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();

        $id_cuenta = intval($_POST['id_cuenta'] ?? 0);
        $nombre_cuenta = trim($_POST['nombre_cuenta'] ?? '');
        $celular = trim($_POST['celular'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        if (!$id_cuenta || empty($nombre_cuenta) || empty($celular) || empty($notas)) {
            error_response('datos_incompletos', ['field' => !$id_cuenta ? 'id_cuenta' : 'nombre_cuenta']);
        }

        $stmt = $conexion->prepare("SELECT id FROM cuentas WHERE id = ?");
        $stmt->execute([$id_cuenta]);
        if (!$stmt->fetch()) {
            error_response('registro_no_encontrado', ['tabla' => 'cuentas']);
        }

        $stmtDup = $conexion->prepare("SELECT id FROM cuentas WHERE REPLACE(REPLACE(LOWER(TRIM(nombre_cuenta)), '.', ''), ',', '') = REPLACE(REPLACE(LOWER(TRIM(?)), '.', ''), ',', '') AND id <> ?");
        $stmtDup->execute([$nombre_cuenta, $id_cuenta]);
        if ($stmtDup->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra cuenta con ese nombre']);
            exit();
        }

        $stmtUpd = $conexion->prepare("UPDATE cuentas SET nombre_cuenta = ?, celular = ?, notas = ? WHERE id = ?");
        $stmtUpd->execute([$nombre_cuenta, $celular ?: null, $notas, $id_cuenta]);

        registrar_auditoria('cuentas', $id_cuenta, 'edicion', null, 'Cuenta editada');
        echo json_encode(['success' => true, 'message' => 'Cuenta actualizada']);
        exit();
    }

    // Eliminar (desactivar) cuenta
    if ($accion === 'eliminar_cuenta' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();

        $id_cuenta = intval($_POST['id_cuenta'] ?? 0);
        if (!$id_cuenta) {
            error_response('datos_incompletos', ['field' => 'id_cuenta']);
        }

        $stmt = $conexion->prepare("SELECT saldo_total, nombre_cuenta FROM cuentas WHERE id = ?");
        $stmt->execute([$id_cuenta]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) {
            error_response('registro_no_encontrado', ['tabla' => 'cuentas']);
        }

        if (floatval($cuenta['saldo_total']) > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar: la cuenta tiene saldo pendiente']);
            exit();
        }

        $stmtDel = $conexion->prepare("UPDATE cuentas SET estado_cuenta = 'inactivo' WHERE id = ?");
        $stmtDel->execute([$id_cuenta]);

        registrar_auditoria('cuentas', $id_cuenta, 'eliminacion_logica', 'activo', 'inactivo');
        echo json_encode(['success' => true, 'message' => 'Cuenta eliminada']);
        exit();
    }
    
    // Registrar pago a cuenta
    if ($accion === 'pagar_cuenta' && !empty($_POST['id_cuenta'])) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $id_cuenta = intval($_POST['id_cuenta']);
        $monto_pagado = floatval($_POST['monto_pagado'] ?? 0);
        $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
        $referencia_pago = trim($_POST['referencia_pago'] ?? '');

        if (!in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia'])) {
            $metodo_pago = 'efectivo';
        }
        
        if ($monto_pagado <= 0) {
            error_response('datos_incompletos', ['field' => 'monto_pagado']);
        }
        
        $conexion->beginTransaction();
        
        try {
            // Marcar ventas como pagadas
            $stmt = $conexion->prepare("
                UPDATE ventas 
                SET fiado_pagado = 1 
                WHERE nombre_fiado = (SELECT nombre_cuenta FROM cuentas WHERE id = ?) 
                  AND tipo_pago = 'fiado' 
                  AND fiado_pagado = 0
                LIMIT ?
            ");
            // SQLite no soporta LIMIT en UPDATE directamente, así que lo hacemos en dos pasos
            $stmt = $conexion->prepare("
                SELECT id FROM ventas 
                WHERE nombre_fiado = (SELECT nombre_cuenta FROM cuentas WHERE id = ?) 
                  AND tipo_pago = 'fiado' 
                  AND fiado_pagado = 0
            ");
            $stmt->execute([$id_cuenta]);
            $ventas = $stmt->fetchAll();
            
            $monto_acumulado = 0;
            foreach ($ventas as $venta) {
                if ($monto_acumulado + floatval($venta['total']) <= $monto_pagado) {
                    $stmtUpdate = $conexion->prepare("UPDATE ventas SET fiado_pagado = 1 WHERE id = ?");
                    $stmtUpdate->execute([$venta['id']]);
                    $monto_acumulado += floatval($venta['total']);
                }
            }
            
            // Actualizar saldo de la cuenta
            $stmt = $conexion->prepare("
                UPDATE cuentas 
                SET saldo_total = saldo_total - ?
                WHERE id = ?
            ");
            $stmt->execute([$monto_pagado, $id_cuenta]);
            
            // Registrar en auditoría
            registrar_auditoria('cuentas', $id_cuenta, 'pago', 'anterior', 'pago de $' . $monto_pagado);
            
            // Registrar en registros (ingresos)
            $conceptoPago = 'Pago de cuenta';
            if (!empty($metodo_pago)) {
                $conceptoPago .= " ({$metodo_pago}";
                if (!empty($referencia_pago)) {
                    $conceptoPago .= ", ref: {$referencia_pago}";
                }
                $conceptoPago .= ')';
            }
            $stmt = $conexion->prepare("
                INSERT INTO registros (tipo, concepto, monto, usuario, categoria, fecha)
                VALUES ('ingreso', ?, ?, ?, 'pagos_deuda', datetime('now', 'localtime'))
            ");
            $stmt->execute([$conceptoPago, $monto_pagado, $usuario]);
            
            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Pago registrado', 'monto_pagado' => $monto_pagado]);
        } catch (Exception $e) {
            $conexion->rollBack();
            error_response('error_pago', ['detalle' => $e->getMessage()]);
        }
        exit();
    }
    
    // Agregar monto a cuenta (nueva compra a crédito)
    if ($accion === 'agregar_a_cuenta' && !empty($_POST['id_cuenta'])) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $id_cuenta = intval($_POST['id_cuenta']);
        $monto = floatval($_POST['monto'] ?? 0);
        
        if ($monto <= 0) {
            error_response('datos_incompletos', ['field' => 'monto']);
        }
        
        $conexion->beginTransaction();
        
        try {
            // Actualizar saldo
            $stmt = $conexion->prepare("
                UPDATE cuentas 
                SET saldo_total = saldo_total + ?,
                    fecha_ultimo_compra = datetime('now', 'localtime')
                WHERE id = ?
            ");
            $stmt->execute([$monto, $id_cuenta]);
            
            // Registrar en auditoría
            registrar_auditoria('cuentas', $id_cuenta, 'compra_agregada', null, 'Monto: $' . $monto);
            
            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Monto agregado a la cuenta']);
        } catch (Exception $e) {
            $conexion->rollBack();
            error_response('error_actualizar', ['detalle' => $e->getMessage()]);
        }
        exit();
    }

    // --- 7. GESTIÓN DE MÉTODOS DE PAGO (NEW) ---
    
    // Listar confirmaciones de pagos pendientes (para admin)
    if ($accion === 'listar_confirmaciones' && $esAdmin) {
        $estado = $_GET['estado'] ?? 'pendiente';
        
        $sql = "
            SELECT cp.*, v.total as monto_venta, v.nombre_fiado
            FROM confirmacion_pagos cp
            JOIN ventas v ON cp.venta_id = v.id
            WHERE cp.estado = ?
            ORDER BY cp.fecha_solicitud DESC
        ";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$estado]);
        echo json_encode(['success' => true, 'confirmaciones' => $stmt->fetchAll()]);
        exit();
    }
    
    // Confirmar pago (solo admin)
    if ($accion === 'confirmar_pago' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $id_confirmacion = intval($_POST['id_confirmacion'] ?? 0);
        $aceptar = boolval($_POST['aceptar'] ?? false);
        $notas = $_POST['notas'] ?? '';
        
        if (!$id_confirmacion) {
            error_response('datos_incompletos', ['field' => 'id_confirmacion']);
        }
        
        $conexion->beginTransaction();
        
        try {
            $estado = $aceptar ? 'confirmado' : 'rechazado';
            
            $stmt = $conexion->prepare("
                UPDATE confirmacion_pagos 
                SET estado = ?, fecha_confirmacion = datetime('now', 'localtime'), usuario_confirmo = ?, notas = ?
                WHERE id = ?
            ");
            $stmt->execute([$estado, $usuario, $notas, $id_confirmacion]);
            
            if ($aceptar) {
                // Obtener la venta asociada
                $stmt2 = $conexion->prepare("
                    SELECT venta_id FROM confirmacion_pagos WHERE id = ?
                ");
                $stmt2->execute([$id_confirmacion]);
                $conf = $stmt2->fetch();
                
                if ($conf) {
                    // Marcar venta como pagada
                    $stmt3 = $conexion->prepare("UPDATE ventas SET fiado_pagado = 1 WHERE id = ?");
                    $stmt3->execute([$conf['venta_id']]);
                    
                    // Registrar en auditoría
                    registrar_auditoria('confirmacion_pagos', $id_confirmacion, 'confirmacion_pagada', $aceptar ? 'no' : 'sí', 'confirmado');
                }
            }
            
            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Pago ' . $estado]);
        } catch (Exception $e) {
            $conexion->rollBack();
            error_response('error_confirmacion', ['detalle' => $e->getMessage()]);
        }
        exit();
    }
    
    // Crear solicitud de confirmación de pago (tarjeta o transferencia)
    if ($accion === 'solicitar_confirmacion_pago') {
        $venta_id = intval($_POST['venta_id'] ?? 0);
        $metodo_pago = $_POST['metodo_pago'] ?? 'tarjeta'; // 'tarjeta' o 'transferencia'
        $comprobante_referencia = $_POST['comprobante_referencia'] ?? ''; // número de referencia o transacción
        
        if (!$venta_id || !in_array($metodo_pago, ['tarjeta', 'transferencia'])) {
            error_response('datos_incompletos', ['field' => $venta_id ? 'metodo_pago' : 'venta_id']);
        }
        
        if (empty($comprobante_referencia)) {
            error_response('datos_incompletos', ['field' => 'comprobante_referencia']);
        }
        
        try {
            // Verificar que la venta existe
            $stmt = $conexion->prepare("SELECT id FROM ventas WHERE id = ?");
            $stmt->execute([$venta_id]);
            if (!$stmt->fetch()) {
                error_response('venta_no_existe');
            }
            
            // Crear solicitud de confirmación
            $stmt = $conexion->prepare("
                INSERT INTO confirmacion_pagos (venta_id, metodo_pago, comprobante_referencia, estado)
                VALUES (?, ?, ?, 'pendiente')
            ");
            $stmt->execute([$venta_id, $metodo_pago, $comprobante_referencia]);
            
            $id_solicitud = $conexion->lastInsertId();
            
            // Registrar en auditoría
            registrar_auditoria('confirmacion_pagos', $id_solicitud, 'solicitud_creada', null, "Método: $metodo_pago, Ref: $comprobante_referencia");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Solicitud de confirmación creada',
                'id_confirmacion' => $id_solicitud,
                'metodo_pago' => $metodo_pago
            ]);
        } catch (Exception $e) {
            error_response('error_solicitud', ['detalle' => $e->getMessage()]);
        }
        exit();
    }

} catch (Exception $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    manejar_excepcion_general($e, 'ventas');
}
?>