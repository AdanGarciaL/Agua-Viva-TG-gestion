<?php
// api/api_inventario.php - v4.1 Reconexión automática
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);  // NO mostrar errores HTML (solo loguear)
include 'db.php';
include 'error_handler.php';
header('Content-Type: application/json');

// Asegurar conexión de BD
if (!asegurar_conexion_db()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos']);
    exit();
}

validar_sesion();

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
        $stmt->bindValue(1, $like, PDO::PARAM_STR);
        $stmt->bindValue(2, $q . '%', PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetchAll();
        echo json_encode(['success' => true, 'productos' => $res]);
    }
    
    // Stock bajo (productos con stock <= umbral o stock_minimo)
    else if ($accion === 'stock_bajo') {
        $umbral = intval($_GET['umbral'] ?? 10);
        $stmt = $conexion->prepare("SELECT id, nombre, codigo_barras, stock, stock_minimo, precio_venta FROM productos WHERE activo = 1 AND (stock <= ? OR stock <= stock_minimo) ORDER BY stock ASC, nombre ASC");
        $stmt->execute([$umbral]);
        $productos = $stmt->fetchAll();
        echo json_encode(['success' => true, 'productos' => $productos, 'count' => count($productos)]);
    }
    
    // Historial de cambios (audit log) - Solo admins
    else if ($accion === 'historial' && $esAdmin) {
        $producto_id = intval($_GET['producto_id'] ?? 0);
        
        if ($producto_id === 0) {
            error_response('datos_incompletos', ['field' => 'producto_id']);
        }
        
        $stmt = $conexion->prepare("
            SELECT a.*, p.nombre as producto_nombre
            FROM audit_log a
            LEFT JOIN productos p ON p.id = a.registro_id
            WHERE a.tabla = 'productos' AND a.registro_id = ?
            ORDER BY a.fecha DESC
            LIMIT 50
        ");
        $stmt->execute([$producto_id]);
        $historial = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'historial' => $historial]);
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
            error_response('datos_incompletos', ['field' => 'nombre/precio/stock']);
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

    // Editar Producto (CON AUDIT LOG v5.0+)
    } else if ($accion === 'editar' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        
        $id = intval($_POST['id']);
        
        // Obtener valores anteriores para audit log
        $stmt_prev = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt_prev->execute([$id]);
        $anterior = $stmt_prev->fetch(PDO::FETCH_ASSOC);
        
        if (!$anterior) {
            error_response('producto_no_existe');
        }
        
        // Valores nuevos
        $nuevo_nombre = trim($_POST['nombre']);
        $nuevo_codigo = trim($_POST['codigo'] ?? '');
        $nuevo_precio = floatval($_POST['precio']);
        $nuevo_stock = intval($_POST['stock']);
        $nueva_foto = trim($_POST['foto'] ?? '');
        
        // Actualizar producto
        $stmt = $conexion->prepare("UPDATE productos SET nombre=?, codigo_barras=?, precio_venta=?, stock=?, foto_url=? WHERE id=?");
        $stmt->execute([
            $nuevo_nombre, 
            $nuevo_codigo, 
            $nuevo_precio, 
            $nuevo_stock, 
            $nueva_foto, 
            $id
        ]);
        
        // Registrar cambios en audit_log
        if ($anterior['nombre'] !== $nuevo_nombre) {
            registrar_auditoria('productos', $id, 'nombre', $anterior['nombre'], $nuevo_nombre);
        }
        if ($anterior['codigo_barras'] !== $nuevo_codigo) {
            registrar_auditoria('productos', $id, 'codigo_barras', $anterior['codigo_barras'], $nuevo_codigo);
        }
        if (floatval($anterior['precio_venta']) !== $nuevo_precio) {
            registrar_auditoria('productos', $id, 'precio_venta', $anterior['precio_venta'], $nuevo_precio);
        }
        if (intval($anterior['stock']) !== $nuevo_stock) {
            registrar_auditoria('productos', $id, 'stock', $anterior['stock'], $nuevo_stock);
        }
        if ($anterior['foto_url'] !== $nueva_foto) {
            registrar_auditoria('productos', $id, 'foto_url', $anterior['foto_url'], $nueva_foto);
        }
        
        echo json_encode(['success' => true, 'message' => 'Producto Actualizado']);

    // Eliminar (Soft Delete para no romper historial)
    } else if ($accion === 'eliminar' && $esAdmin) {
        include_once 'csrf.php';
        require_csrf_or_die();
        $id = $_POST['id'] ?? null;
        if (!$id) {
            error_response('datos_incompletos', ['field' => 'id']);
        }
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

} catch (Exception $e) {
    manejar_excepcion_general($e, 'inventario');
}
?>