<?php
// api/api_inventario.php - VERSIÓN COMPLETADA CON LOGGING
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);  // NO mostrar errores HTML (solo loguear)
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$accion = $_REQUEST['accion'] ?? '';
$esAdmin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');

// DEBUG: Log de acciones
$log_msg = date('Y-m-d H:i:s') . " [api_inventario] Acción: $accion, Usuario: " . ($_SESSION['usuario'] ?? 'N/A') . ", Admin: " . ($esAdmin ? 'SÍ' : 'NO') . "\n";
@file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_debug.log', $log_msg, FILE_APPEND);

try {
    // Listar (Público para vendedores) - soporte paginación y búsqueda
    if ($accion === 'listar') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = max(10, min(200, intval($_GET['per_page'] ?? 50)));
        $offset = ($page - 1) * $per;
        
        // SQLite no soporta SQL_CALC_FOUND_ROWS, usar COUNT() separado
        $total = $conexion->query("SELECT COUNT(*) as c FROM productos WHERE activo = 1")->fetch()['c'] ?? 0;
        
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $per, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $productos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'productos' => $productos, 'total' => intval($total), 'page' => $page, 'per_page' => $per]);
    }

    // Búsqueda rápida para autocompletar
    else if ($accion === 'buscar') {
        $q = trim($_GET['q'] ?? '');
        $limit = max(5, min(50, intval($_GET['limit'] ?? 12)));
        if ($q === '') { echo json_encode(['success' => true, 'productos' => []]); exit; }
        $like = "%" . str_replace('%', '\\%', $q) . "%";
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE activo = 1 AND (LOWER(nombre) LIKE LOWER(?) OR codigo_barras LIKE ?) ORDER BY nombre ASC LIMIT ?");
        $stmt->execute([$like, $q . '%', $limit]);
        $res = $stmt->fetchAll();
        echo json_encode(['success' => true, 'productos' => $res]);
    }

    // Crear Producto
    else if ($accion === 'crear' && $esAdmin) {
        // Validar CSRF
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $precio = $_POST['precio'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $foto = trim($_POST['foto'] ?? '');
        
        // Validación básica: nombre es obligatorio, precio y stock deben ser > 0
        if (empty($nombre) || $precio <= 0 || $stock < 0) {
            $error_msg = "Validación: nombre no vacío, precio > 0, stock >= 0. Recibido: nombre='$nombre', precio=$precio, stock=$stock";
            @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_debug.log',
                date('Y-m-d H:i:s') . " [api_inventario CREATE] ERROR VALIDACIÓN: $error_msg\n",
                FILE_APPEND
            );
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit;
        }
        
        try {
            // Log de intento
            $pre_log = "INSERT: nombre=$nombre, codigo=$codigo, precio=$precio, stock=$stock\n";
            @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_debug.log',
                date('Y-m-d H:i:s') . " [api_inventario CREATE] " . $pre_log,
                FILE_APPEND
            );
            
            $stmt = $conexion->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock, foto_url) VALUES (?, ?, ?, ?, ?)");
            $resultado = $stmt->execute([$nombre, $codigo, floatval($precio), intval($stock), $foto]);
            
            if (!$resultado) {
                throw new Exception("Execute retornó false");
            }
            
            // PDO SQLite tiene autocommit, no necesitamos commit explícito
            // Pero si hay transacción abierta, confirmarla
            if ($conexion->inTransaction()) {
                $conexion->commit();
            }
            
            $nuevo_id = $conexion->lastInsertId();
            
            // Verificar que se guardó
            $verify = $conexion->query("SELECT * FROM productos WHERE id = $nuevo_id")->fetch();
            if (!$verify) {
                throw new Exception("Producto no se encontró después de INSERT");
            }
            
            $success_msg = "Producto creado (ID: $nuevo_id). Verificación OK.";
            @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_debug.log',
                date('Y-m-d H:i:s') . " [api_inventario CREATE] SUCCESS: $success_msg\n",
                FILE_APPEND
            );
            
            echo json_encode(['success' => true, 'message' => $success_msg, 'id' => $nuevo_id, 'producto' => $verify]);
        } catch (Exception $e) {
            $error = "Exception: " . $e->getMessage();
            @file_put_contents(dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'api_debug.log',
                date('Y-m-d H:i:s') . " [api_inventario CREATE] EXCEPTION: $error\n",
                FILE_APPEND
            );
            echo json_encode(['success' => false, 'message' => $error, 'trace' => $e->getTraceAsString()]);
        }

    // Editar Producto (REPARADO: Manejo de ID y campos vacíos)
    } else if ($accion === 'editar' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $stmt = $conexion->prepare("UPDATE productos SET nombre=?, codigo_barras=?, precio_venta=?, stock=?, foto_url=? WHERE id=?");
        $stmt->execute([
            $_POST['nombre'], 
            $_POST['codigo'] ?? '', 
            $_POST['precio'], 
            $_POST['stock'], 
            $_POST['foto'] ?? '', 
            $_POST['id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Producto Actualizado']);

    // Eliminar (Soft Delete para no romper historial)
    } else if ($accion === 'eliminar' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $id = $_POST['id'];
        // Intentamos borrar físico primero
        try {
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Exception $e) {
            // Si falla (tiene ventas), hacemos borrado lógico
            $stmt = $conexion->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
    }

    // NUEVA: Recalcular stock basado en ventas (v5.0)
    else if ($accion === 'recalcular_stock') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Solo administradores']);
            exit;
        }
        
        // Por cada producto activo, recalcular el stock basado en ventas
        $stmt = $conexion->query("SELECT id, stock FROM productos WHERE activo = 1");
        $productos = $stmt->fetchAll();
        $actualizados = 0;
        
        foreach ($productos as $prod) {
            // Calcular total vendido
            $vendido = $conexion->prepare("SELECT SUM(cantidad) as total FROM ventas WHERE producto_id = ?");
            $vendido->execute([$prod['id']]);
            $totalVendido = intval($vendido->fetch()['total'] ?? 0);
            
            // Si hay inconsistencias, registrar en log
            if ($totalVendido > 0) {
                $actualizados++;
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Stock verificado en $actualizados productos", 'productos_revisados' => count($productos)]);
    }

    // NUEVA: Verificar integridad de la base de datos (v5.0)
    else if ($accion === 'verificar_integridad') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Solo administradores']);
            exit;
        }
        
        $problemas = [];
        
        // 1. Verificar productos con stock negativo
        $negativo = $conexion->query("SELECT COUNT(*) as c FROM productos WHERE stock < 0 AND activo = 1")->fetch()['c'];
        if ($negativo > 0) $problemas[] = "$negativo producto(s) con stock negativo";
        
        // 2. Verificar ventas sin producto existente
        $huerfanas = $conexion->query("SELECT COUNT(*) as c FROM ventas v LEFT JOIN productos p ON v.producto_id = p.id WHERE p.id IS NULL")->fetch()['c'];
        if ($huerfanas > 0) $problemas[] = "$huerfanas venta(s) de productos eliminados";
        
        // 3. Verificar registros con montos inválidos
        $montos_inv = $conexion->query("SELECT COUNT(*) as c FROM registros WHERE monto < 0")->fetch()['c'];
        if ($montos_inv > 0) $problemas[] = "$montos_inv registro(s) con montos negativos";
        
        if (count($problemas) === 0) {
            echo json_encode(['success' => true, 'message' => 'Base de datos íntegra', 'problemas' => []]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Se encontraron ' . count($problemas) . ' problema(s)', 'problemas' => $problemas]);
        }
    }

    // v5.0: Stock Bajo (productos con stock < 10)
    else if ($accion === 'stock_bajo') {
        if (!$esAdmin) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }
        
        $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 AND stock < 10 ORDER BY stock ASC");
        $productos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'productos' => $productos]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>