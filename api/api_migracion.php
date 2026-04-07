<?php
// api/api_migracion.php - Migración de datos entre BDs (v10 Beta)
// Permite cargar datos de una BD antigua a la nueva

session_start();
header('Content-Type: application/json; charset=utf-8');

// Validar que el usuario sea superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol superadmin']);
    exit();
}

include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', '0');

$accion = $_REQUEST['accion'] ?? '';

try {
    // --- 1. OBTENER INFORMACIÓN DE BD ACTUAL ---
    if ($accion === 'estado_bd_actual') {
        $stmtU = $conexion->query("SELECT COUNT(*) as cnt FROM usuarios");
        $stmtP = $conexion->query("SELECT COUNT(*) as cnt FROM productos WHERE activo=1");
        $stmtV = $conexion->query("SELECT COUNT(*) as cnt FROM ventas");
        $stmtC = $conexion->query("SELECT COUNT(*) as cnt FROM cuentas");
        $stmtR = $conexion->query("SELECT COUNT(*) as cnt FROM registros");
        
        echo json_encode([
            'success' => true,
            'bd_actual' => [
                'usuarios' => $stmtU->fetch()['cnt'],
                'productos' => $stmtP->fetch()['cnt'],
                'ventas' => $stmtV->fetch()['cnt'],
                'cuentas' => $stmtC->fetch()['cnt'],
                'registros' => $stmtR->fetch()['cnt']
            ]
        ]);
        exit();
    }
    
    // --- 2. CARGAR BD ANTIGUA (subida de archivo) ---
    if ($accion === 'cargar_bd_antigua') {
        if (!isset($_FILES['archivo_bd'])) {
            echo json_encode(['success' => false, 'message' => 'No se subió archivo']);
            exit();
        }
        
        $file = $_FILES['archivo_bd']['tmp_name'];
        $fname = $_FILES['archivo_bd']['name'];
        
        if (!file_exists($file)) {
            echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
            exit();
        }
        
        // Validar que sea un archivo SQLite
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);
        
        if ($mime !== 'application/octet-stream' && !preg_match('/sqlite|octet/', $mime)) {
            echo json_encode(['success' => false, 'message' => 'El archivo no parece ser una BD SQLite válida']);
            exit();
        }
        
        // Guardar en directorio temporal conocido
        $tempDir = dirname(DB_PATH) . DIRECTORY_SEPARATOR . 'temp_migration';
        if (!is_dir($tempDir)) @mkdir($tempDir, 0777, true);
        
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'bd_antigua_' . time() . '.db';
        if (!move_uploaded_file($file, $tempFile)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo temporal']);
            exit();
        }
        
        // Intentar conectar a la BD antigua
        try {
            $bdAntigua = new PDO('sqlite:' . $tempFile, '', '', [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $bdAntigua->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Contar registros en BD antigua
            $stmtA_U = $bdAntigua->query("SELECT COUNT(*) as cnt FROM usuarios");
            $stmtA_P = $bdAntigua->query("SELECT COUNT(*) as cnt FROM productos WHERE activo=1");
            $stmtA_V = $bdAntigua->query("SELECT COUNT(*) as cnt FROM ventas");
            
            $uCount = $stmtA_U->fetch()['cnt'] ?? 0;
            $pCount = $stmtA_P->fetch()['cnt'] ?? 0;
            $vCount = $stmtA_V->fetch()['cnt'] ?? 0;
            
            // Guardar referencia en sesión para siguiente paso
            $_SESSION['temp_bd_path'] = $tempFile;
            
            echo json_encode([
                'success' => true,
                'message' => 'BD cargada correctamente',
                'bd_antigua' => [
                    'usuarios' => $uCount,
                    'productos' => $pCount,
                    'ventas' => $vCount
                ]
            ]);
        } catch (Exception $e) {
            @unlink($tempFile);
            echo json_encode(['success' => false, 'message' => 'Error leyendo BD: ' . $e->getMessage()]);
        }
        exit();
    }
    
    // --- 3. EJECUTAR MIGRACIÓN (importar datos) ---
    if ($accion === 'ejecutar_migracion') {
        if (!isset($_SESSION['temp_bd_path']) || !file_exists($_SESSION['temp_bd_path'])) {
            echo json_encode(['success' => false, 'message' => 'No hay BD cargada. Sube el archivo primero']);
            exit();
        }
        
        $tempFile = $_SESSION['temp_bd_path'];
        
        $conexion->beginTransaction();
        
        try {
            $bdAntigua = new PDO('sqlite:' . $tempFile, '', '', [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $bdAntigua->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $importados = [
                'usuarios' => 0,
                'productos' => 0,
                'ventas' => 0,
                'registros' => 0,
                'cuentas' => 0,
                'errores' => []
            ];
            
            // Importar Productos
            try {
                $stmt = $bdAntigua->query("SELECT * FROM productos WHERE activo=1");
                if ($stmt) {
                    while ($r = $stmt->fetch()) {
                        $stmtIns = $conexion->prepare("
                            INSERT OR IGNORE INTO productos (nombre, codigo_barras, precio_venta, stock, stock_minimo, tipo_producto, activo)
                            VALUES (?, ?, ?, ?, ?, ?, 1)
                        ");
                        $stmtIns->execute([
                            $r['nombre'],
                            $r['codigo_barras'] ?? null,
                            floatval($r['precio_venta'] ?? 0),
                            intval($r['stock'] ?? 0),
                            intval($r['stock_minimo'] ?? 10),
                            $r['tipo_producto'] ?? 'producto'
                        ]);
                        $importados['productos']++;
                    }
                }
            } catch (Exception $e) {
                $importados['errores'][] = "Productos: " . $e->getMessage();
            }
            
            // Importar Ventas
            try {
                $stmt = $bdAntigua->query("SELECT * FROM ventas ORDER BY id");
                if ($stmt) {
                    while ($r = $stmt->fetch()) {
                        $stmtIns = $conexion->prepare("
                            INSERT OR IGNORE INTO ventas 
                            (producto_id, cantidad, total, fecha, vendedor, tipo_pago, nombre_fiado, fiado_pagado, celular_fiado, estado_cuenta, metodo_pago)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtIns->execute([
                            $r['producto_id'] ?? null,
                            intval($r['cantidad'] ?? 1),
                            floatval($r['total'] ?? 0),
                            $r['fecha'] ?? date('Y-m-d H:i:s'),
                            $r['vendedor'] ?? 'importado',
                            $r['tipo_pago'] ?? 'efectivo',
                            $r['nombre_fiado'] ?? null,
                            intval($r['fiado_pagado'] ?? 0),
                            $r['celular_fiado'] ?? null,
                            $r['estado_cuenta'] ?? 'pendiente',
                            $r['metodo_pago'] ?? null
                        ]);
                        $importados['ventas']++;
                    }
                }
            } catch (Exception $e) {
                $importados['errores'][] = "Ventas: " . $e->getMessage();
            }
            
            // Importar Registros (ingresos/egresos)
            try {
                $stmt = $bdAntigua->query("SELECT * FROM registros ORDER BY id");
                if ($stmt) {
                    while ($r = $stmt->fetch()) {
                        $stmtIns = $conexion->prepare("
                            INSERT INTO registros (fecha, tipo, concepto, monto, usuario, categoria, servicio)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtIns->execute([
                            $r['fecha'] ?? date('Y-m-d H:i:s'),
                            $r['tipo'] ?? 'ingreso',
                            $r['concepto'] ?? 'Importado',
                            floatval($r['monto'] ?? 0),
                            $r['usuario'] ?? 'importado',
                            $r['categoria'] ?? null,
                            $r['servicio'] ?? null
                        ]);
                        $importados['registros']++;
                    }
                }
            } catch (Exception $e) {
                $importados['errores'][] = "Registros: " . $e->getMessage();
            }
            
            // Importar Cuentas (si existen en BD antigua)
            try {
                $stmt = $bdAntigua->query("SELECT * FROM cuentas");
                if ($stmt) {
                    while ($r = $stmt->fetch()) {
                        $stmtIns = $conexion->prepare("
                            INSERT OR IGNORE INTO cuentas 
                            (nombre_cuenta, celular, estado_cuenta, saldo_total, fecha_primer_compra, fecha_ultimo_compra, notas)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtIns->execute([
                            $r['nombre_cuenta'],
                            $r['celular'] ?? null,
                            $r['estado_cuenta'] ?? 'activo',
                            floatval($r['saldo_total'] ?? 0),
                            $r['fecha_primer_compra'] ?? null,
                            $r['fecha_ultimo_compra'] ?? null,
                            $r['notas'] ?? null
                        ]);
                        $importados['cuentas']++;
                    }
                }
            } catch (Exception $e) {
                // Tabla cuentas podría no existir en BD antigua
                $importados['errores'][] = "Cuentas: " . $e->getMessage();
            }
            
            $conexion->commit();
            
            // Limpiar archivo temporal
            @unlink($tempFile);
            unset($_SESSION['temp_bd_path']);
            
            // Registrar en auditoría
            registrar_auditoria('sistema', 0, 'migracion_datos', 'anterior', json_encode($importados));
            
            echo json_encode([
                'success' => true,
                'message' => 'Migración completada',
                'importados' => $importados
            ]);
        } catch (Exception $e) {
            $conexion->rollBack();
            @unlink($tempFile);
            unset($_SESSION['temp_bd_path']);
            echo json_encode(['success' => false, 'message' => 'Error en migración: ' . $e->getMessage()]);
        }
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
