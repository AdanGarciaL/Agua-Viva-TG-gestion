<?php
// api/setup_db.php
// INSTALADOR AUTOMÁTICO (Con Ejemplos y Configuración)

$db_file = __DIR__ . '/database.sqlite';
$existe = file_exists($db_file);

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$existe) {
        echo "<h1>Iniciando Instalación...</h1>";

        // 1. TABLA USUARIOS
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        )");
        // Superadmin: AdanGL / Agl252002
        $passHash = password_hash("Agl252002", PASSWORD_DEFAULT);
        $pdo->exec("INSERT OR IGNORE INTO usuarios (username, password, role) VALUES ('AdanGL', '$passHash', 'superadmin')");
        echo "✅ Tabla Usuarios creada.<br>";

        // 2. TABLA PRODUCTOS
        $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            codigo_barras TEXT,
            precio_venta REAL NOT NULL,
            stock INTEGER NOT NULL,
            foto_url TEXT,
            activo INTEGER DEFAULT 1
        )");
        
        // --- AGREGANDO TUS EJEMPLOS ---
        $stmtProd = $pdo->prepare("INSERT INTO productos (nombre, codigo_barras, precio_venta, stock, foto_url) VALUES (?, ?, ?, ?, ?)");
        
        // Ejemplo 1: Coca-Cola
        $stmtProd->execute([
            'Coca-Cola Vidrio 355ml', 
            '7501055300075', 
            18.00, 
            48, 
            'https://cdn-icons-png.flaticon.com/512/2405/2405597.png' // Icono generico si hay internet
        ]);

        // Ejemplo 2: Marlboro
        $stmtProd->execute([
            'Marlboro Rojo (Cajetilla 20)', 
            '7501000000000', 
            85.00, 
            20, 
            'https://cdn-icons-png.flaticon.com/512/9557/9557864.png' // Icono generico
        ]);
        
        echo "✅ Productos de Ejemplo Agregados (Coca y Marlboro).<br>";

        // 3. TABLA VENTAS
        $pdo->exec("CREATE TABLE IF NOT EXISTS ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            producto_id INTEGER,
            cantidad INTEGER,
            total REAL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            vendedor TEXT,
            foto_referencia TEXT,
            tipo_pago TEXT,
            nombre_fiado TEXT,
            fiado_pagado INTEGER DEFAULT 0
        )");

        // 4. TABLA REGISTROS
        $pdo->exec("CREATE TABLE IF NOT EXISTS registros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            tipo TEXT,
            concepto TEXT,
            monto REAL,
            usuario TEXT
        )");

        // 5. TABLA SÉPTIMAS
        $pdo->exec("CREATE TABLE IF NOT EXISTS septimas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            nombre_padrino TEXT,
            monto REAL,
            usuario_registro TEXT,
            pagado INTEGER DEFAULT 0
        )");

        // 6. TABLA LOG ERRORES
        $pdo->exec("CREATE TABLE IF NOT EXISTS log_errores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            error TEXT
        )");

        // 7. NUEVA TABLA: CONFIGURACIÓN (Para guardar el color)
        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
            clave TEXT PRIMARY KEY,
            valor TEXT
        )");
        // Color por defecto (Azul Agua Viva)
        $pdo->exec("INSERT OR IGNORE INTO configuracion (clave, valor) VALUES ('color_tema', '#0d47a1')");
        
        echo "✅ Tabla Configuración creada.<br>";

        echo "<br><h2 style='color:green'>¡SISTEMA LISTO! Cierra esta ventana.</h2>";
        echo "<button onclick='window.close()'>Cerrar</button>";

    } else {
        echo "<h2>La base de datos ya existe.</h2>";
        echo "<p>Para reiniciar y ver los ejemplos, borra el archivo <b>database.sqlite</b> y recarga esta página.</p>";
    }

} catch (PDOException $e) {
    die("Error Fatal: " . $e->getMessage());
}
?>