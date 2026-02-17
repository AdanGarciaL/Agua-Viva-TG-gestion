<?php
// api/error_handler.php - Manejador centralizado de errores v5.2
// Este archivo maneja TODOS los errores de forma consistente

// Configuración
$ERROR_MESSAGES = [
    'producto_no_existe' => '❌ El producto no existe o no está disponible',
    'stock_insuficiente' => '❌ No hay stock suficiente de este producto',
    'cantidad_invalida' => '❌ La cantidad debe ser mayor a 0',
    'precio_invalido' => '❌ El precio ingresado no es válido',
    'usuario_no_existe' => '❌ El usuario no existe',
    'clave_incorrecta' => '❌ Usuario o contraseña incorrectos',
    'datos_incompletos' => '❌ Faltan datos para completar la operación',
    'campo_requerido' => '❌ El campo "{field}" es obligatorio',
    'base_datos_error' => '❌ Error al procesar en la base de datos (contacte al administrador)',
    'acceso_denegado' => '❌ No tienes permiso para esta acción',
    'sesion_expirada' => '❌ Tu sesión expiró, por favor inicia sesión nuevamente',
    'nombre_vacio' => '❌ El nombre de la persona no puede estar vacío',
    'nombre_invalido' => '❌ El nombre contiene caracteres inválidos',
    'default' => '❌ Algo salió mal. Por favor intenta de nuevo o contacta al administrador'
];

/**
 * Normalizar nombres (uppercase, trim, espacios múltiples)
 * Convierte "juan PÉREZ" -> "Juan Pérez"
 */
function normalizar_nombre($nombre) {
    // Trim y convertir a minúsculas primero
    $nombre = trim(strtolower($nombre));
    
    // Eliminar espacios múltiples
    $nombre = preg_replace('/\s+/', ' ', $nombre);
    
    // Capitalizar primera letra de cada palabra
    $nombre = ucwords($nombre);
    
    return $nombre;
}

/**
 * Normalizar para búsqueda (todo en minúsculas, sin acentos, espacios normalizados)
 * Útil para comparaciones insensibles a mayúsculas
 */
function normalizar_para_busqueda($texto) {
    $texto = trim(strtolower($texto));
    $texto = preg_replace('/\s+/', ' ', $texto);
    
    // Remover acentos
    $busca = array('á','é','í','ó','ú','ü','ñ','à','è','ì','ò','ù');
    $reemplaza = array('a','e','i','o','u','u','n','a','e','i','o','u');
    $texto = str_replace($busca, $reemplaza, $texto);
    
    return $texto;
}

/**
 * Validar nombre (solo letras, espacios, y algunos caracteres especiales)
 */
function validar_nombre($nombre) {
    // Permitir letras, números, espacios, guiones y acentos
    if (!preg_match('/^[a-záéíóúüñ\s\-\'\.0-9]+$/i', $nombre)) {
        return false;
    }
    return true;
}

/**
 * Registrar error en archivo de log v5.2
 */
function log_error($tipo, $mensaje, $usuario = 'desconocido', $detalles_extra = []) {
    $log_dir = dirname(DB_PATH) ?: dirname(__DIR__) . '/data';
    @mkdir($log_dir, 0777, true);
    
    $log_file = $log_dir . '/errores.log';
    
    // Construir entrada de log
    $timestamp = date('Y-m-d H:i:s');
    $user_info = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : $usuario;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocido';
    $url = $_SERVER['REQUEST_URI'] ?? 'desconocida';
    
    $entry = "[{$timestamp}] TIPO: {$tipo} | USUARIO: {$user_info} | IP: {$ip} | URL: {$url}\n";
    $entry .= "  MENSAJE: {$mensaje}\n";
    
    if (!empty($detalles_extra)) {
        $entry .= "  DETALLES: " . json_encode($detalles_extra) . "\n";
    }
    
    $entry .= "---\n";
    
    // Escribir en log
    @file_put_contents($log_file, $entry, FILE_APPEND);
    
    // Si el log es muy grande (>50MB), crear uno nuevo y archivarlo
    if (file_exists($log_file) && filesize($log_file) > 50 * 1024 * 1024) {
        $backup = $log_file . '.' . date('YmdHis');
        @rename($log_file, $backup);
        // Opcional: comprimir backups antiguos
        limpiar_logs_antiguos($log_dir);
    }
}

/**
 * Devolver error al usuario de forma amigable
 */
function error_response($clave_error, $detalles = [], $http_code = 400) {
    global $ERROR_MESSAGES;
    
    // Obtener mensaje amigable
    $mensaje = $ERROR_MESSAGES[$clave_error] ?? $ERROR_MESSAGES['default'];
    
    // Reemplazar placeholders como {field}
    foreach ($detalles as $key => $value) {
        $mensaje = str_replace("{{$key}}", $value, $mensaje);
    }
    
    // Registrar el error
    log_error($clave_error, $mensaje, 'desconocido', $detalles);
    
    // Devolver respuesta
    http_response_code($http_code);
    echo json_encode([
        'success' => false,
        'message' => $mensaje,
        'code' => $clave_error
    ]);
    exit;
}

/**
 * Validar que existe un campo
 */
function validar_campo_requerido($nombre_campo, $valor) {
    if (!isset($valor) || $valor === '' || $valor === null) {
        error_response('campo_requerido', ['field' => $nombre_campo]);
    }
    return trim($valor);
}

/**
 * Validar cantidad
 */
function validar_cantidad($cantidad) {
    $cant = intval($cantidad ?? 0);
    if ($cant <= 0) {
        error_response('cantidad_invalida', [], 400);
    }
    return $cant;
}

/**
 * Validar precio
 */
function validar_precio($precio) {
    $prec = floatval($precio ?? 0);
    if ($prec < 0 || !is_numeric($precio)) {
        error_response('precio_invalido', [], 400);
    }
    return $prec;
}

/**
 * Validar producto existe
 */
function validar_producto_existe($conexion, $producto_id) {
    $stmt = $conexion->prepare("SELECT id, nombre, stock, precio_venta FROM productos WHERE id = ? AND activo = 1");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
    
    if (!$producto) {
        error_response('producto_no_existe', [], 400);
    }
    
    return $producto;
}

/**
 * Validar stock disponible
 */
function validar_stock($conexion, $producto_id, $cantidad_solicitada) {
    $producto = validar_producto_existe($conexion, $producto_id);
    
    if ($producto['stock'] < $cantidad_solicitada) {
        log_error('stock_insuficiente', 
            "Producto: {$producto['nombre']}, Solicitado: {$cantidad_solicitada}, Disponible: {$producto['stock']}", 
            $_SESSION['usuario'] ?? 'desconocido'
        );
        error_response('stock_insuficiente', ['producto' => $producto['nombre']], 400);
    }
    
    return $producto;
}

/**
 * Validar sesión de usuario
 */
function validar_sesion() {
    if (!isset($_SESSION['usuario'])) {
        http_response_code(401);
        log_error('sesion_expirada', 'Intento de acceso sin sesión válida', 'desconocido');
        echo json_encode([
            'success' => false,
            'message' => $GLOBALS['ERROR_MESSAGES']['sesion_expirada'],
            'code' => 'sesion_expirada'
        ]);
        exit;
    }
}

/**
 * Manejo de excepciones general
 */
function manejar_excepcion_general($exception, $contexto = 'general') {
    $mensaje = $exception->getMessage();
    $linea = $exception->getLine();
    $archivo = $exception->getFile();
    
    log_error(
        'excepcion_' . $contexto,
        "En {$archivo}:{$linea} - {$mensaje}",
        $_SESSION['usuario'] ?? 'desconocido',
        [
            'archivo' => $archivo,
            'linea' => $linea,
            'stack' => $exception->getTraceAsString()
        ]
    );
    
    // Respuesta amigable al usuario
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Algo salió mal al procesar tu solicitud. El administrador ha sido notificado.',
        'code' => 'error_interno'
    ]);
    exit;
}

/**
 * Limpiar logs antiguos (>7 días)
 */
function limpiar_logs_antiguos($log_dir, $dias = 7) {
    try {
        $archivos = glob($log_dir . '/errores.log.*');
        $fecha_limite = time() - ($dias * 24 * 60 * 60);
        
        foreach ($archivos as $archivo) {
            if (filemtime($archivo) < $fecha_limite) {
                @unlink($archivo);
            }
        }
    } catch (Exception $e) {
        // Silenciosamente fallar sin interrumpir operaciones
    }
}

/**
 * Obtener resumen de errores recientes
 */
function obtener_resumen_errores($log_dir, $ultimos = 50) {
    $log_file = $log_dir . '/errores.log';
    
    if (!file_exists($log_file)) {
        return [];
    }
    
    $lineas = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $errores = [];
    $error_actual = null;
    
    foreach ($lineas as $linea) {
        if (strpos($linea, '[') === 0) {
            if ($error_actual) {
                $errores[] = $error_actual;
            }
            $error_actual = ['raw' => $linea];
        } elseif ($error_actual && strpos($linea, 'TIPO:') !== false) {
            preg_match('/TIPO: (\w+)/', $linea, $matches);
            $error_actual['tipo'] = $matches[1] ?? 'desconocido';
            preg_match('/USUARIO: ([\w\-\.]+)/', $linea, $matches);
            $error_actual['usuario'] = $matches[1] ?? 'desconocido';
        }
    }
    
    if ($error_actual) {
        $errores[] = $error_actual;
    }
    
    return array_slice(array_reverse($errores), 0, $ultimos);
}

?>

