<?php
// api_login.php
session_start();
include 'db.php';

header('Content-Type: application/json');

$modo = $_POST['modo'] ?? 'admin';

try {
    if ($modo === 'vendedor') {
        // --- Lógica de Vendedor (Tiendita) MODIFICADA ---
        $vendedor_nombre = $_POST['vendedor_nombre'] ?? '';
        $vendedor_estigma = $_POST['vendedor_estigma'] ?? ''; // (1) CAMBIO: Capturamos estigma
        $vendedor_padrino = $_POST['vendedor_padrino'] ?? '';
        
        // (2) CAMBIO: Ya no se pide PIN, se pide estigma
        if (empty($vendedor_nombre) || empty($vendedor_estigma) || empty($vendedor_padrino)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
            exit();
        }
        
        // Buscamos al usuario "tienda"
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE username = 'tienda' AND role = 'vendedor'");
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // (3) CAMBIO: Eliminamos la verificación de contraseña (password_verify)
        // Ahora solo comprobamos que el usuario "tienda" exista.
        if ($usuario) {
            session_regenerate_id(true); 
            $_SESSION['usuario'] = $usuario['username']; // Usuario interno 'tienda'
            $_SESSION['role'] = $usuario['role'];
            
            // Guardamos los datos extra en la sesión
            $_SESSION['vendedor_nombre'] = $vendedor_nombre;
            $_SESSION['vendedor_padrino'] = $vendedor_padrino;
            $_SESSION['vendedor_estigma'] = $vendedor_estigma; // (4) CAMBIO: Guardamos el estigma
            
            echo json_encode(['success' => true]);
        } else {
            // (5) CAMBIO: Mensaje de error actualizado
            logError("Intento de login VENDEDOR fallido (Usuario 'tienda' no encontrado)", $conexion);
            echo json_encode(['success' => false, 'message' => 'Error de configuración: usuario "tienda" no existe.']);
        }

    } else {
        // --- Lógica de Admin / Superadmin (SIN CAMBIOS) ---
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos.']);
            exit();
        }

        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE username = ? AND (role = 'admin' OR role = 'superadmin')");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            session_regenerate_id(true); 
            $_SESSION['usuario'] = $usuario['username'];
            $_SESSION['role'] = $usuario['role'];
            echo json_encode(['success' => true]);
        } else {
            logError("Intento de login ADMIN/SUPER fallido para: $username", $conexion);
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
        }
    }

} catch (PDOException $e) {
    logError("Error en API Login: " . $e->getMessage(), $conexion);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
?>