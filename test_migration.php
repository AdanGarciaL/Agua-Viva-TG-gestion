<?php
/**
 * TEST: Verificar que la columna stock_minimo existe en la tabla productos
 * Ejecuta: http://localhost/test_migration.php
 */

session_start();
require_once 'config.php';
require_once 'api/db.php';

echo "<h2>Test de Migración: stock_minimo en productos</h2>";
echo "<pre>";

try {
    // Obtener información de la tabla productos
    if (DB_DRIVER === 'mysql') {
        $result = $conexion->query("SHOW COLUMNS FROM `productos`")->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_map(function($c){ return $c['Field']; }, $result);
        echo "Columnas en productos (MySQL):\n";
        print_r($columns);
        
        if (in_array('stock_minimo', $columns)) {
            echo "\n✅ SUCCESS: Columna 'stock_minimo' existe en la tabla 'productos'\n";
        } else {
            echo "\n❌ ERROR: Columna 'stock_minimo' NO existe. Intentando agregar...\n";
            $conexion->exec("ALTER TABLE productos ADD COLUMN stock_minimo INT DEFAULT 10");
            echo "✅ Columna agregada correctamente\n";
        }
    } else {
        $result = $conexion->query("PRAGMA table_info(productos)")->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_map(function($c){ return $c['name']; }, $result);
        echo "Columnas en productos (SQLite):\n";
        print_r($columns);
        
        if (in_array('stock_minimo', $columns)) {
            echo "\n✅ SUCCESS: Columna 'stock_minimo' existe en la tabla 'productos'\n";
        } else {
            echo "\n❌ ERROR: Columna 'stock_minimo' NO existe. Intentando agregar...\n";
            $conexion->exec("ALTER TABLE productos ADD COLUMN stock_minimo INTEGER DEFAULT 10");
            echo "✅ Columna agregada correctamente\n";
        }
    }
    
    // Test de la consulta stock_bajo
    echo "\n\n--- Test de consulta stock_bajo ---\n";
    $stmt = $conexion->prepare("SELECT id, nombre, codigo_barras, stock, stock_minimo, precio_venta FROM productos WHERE activo = 1 AND (stock <= ? OR stock <= stock_minimo) ORDER BY stock ASC, nombre ASC");
    $stmt->execute([10]);
    $productos = $stmt->fetchAll();
    echo "Productos con stock bajo: " . count($productos) . "\n";
    print_r($productos);
    
    echo "\n✅ Test completado exitosamente\n";
    
} catch (Exception $e) {
    echo "\n❌ Error durante el test:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
