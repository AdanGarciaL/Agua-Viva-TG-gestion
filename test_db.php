<?php
/**
 * test_db.php - Test de base de datos
 * Compatible con SQLite y MySQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Test de Base de Datos</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0d47a1; border-bottom: 2px solid #0d47a1; padding-bottom: 10px; }
        .status-ok { color: #4caf50; font-weight: bold; }
        .status-error { color: #f44336; font-weight: bold; }
        .status-warning { color: #ff9800; font-weight: bold; }
        .info { background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #0d47a1; }
        .error { background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #f44336; }
        .success { background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #4caf50; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔍 Test de Configuración - TG Gestión v10 Beta</h1>";

// 1. Información del Sistema
echo "<h2>📊 Información del Sistema</h2>";
echo "<table>
        <tr><th>Propiedad</th><th>Valor</th></tr>
        <tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>
        <tr><td>Sistema Operativo</td><td>" . PHP_OS . "</td></tr>
        <tr><td>SAPI</td><td>" . php_sapi_name() . "</td></tr>
        <tr><td>PHP Desktop</td><td>" . ($config['phpdesktop'] ? '<span class=\"status-ok\">✓ Sí</span>' : 'No') . "</td></tr>
    </table>";

// 2. Configuración de BD
echo "<h2>💾 Configuración de Base de Datos</h2>";
echo "<table>
        <tr><th>Parámetro</th><th>Valor</th></tr>
        <tr><td>Driver</td><td>" . strtoupper($config['db']['driver']) . "</td></tr>";

if (strtolower($config['db']['driver']) === 'sqlite') {
    echo "<tr><td>Ruta de BD</td><td>" . DB_PATH . "</td></tr>";
    echo "<tr><td>BD Existe</td><td>" . (file_exists(DB_PATH) ? '<span class=\"status-ok\">✓ Sí</span>' : 'No (se creará)') . "</td></tr>";
} else {
    echo "<tr><td>Host</td><td>" . $config['db']['host'] . "</td></tr>";
    echo "<tr><td>Base de Datos</td><td>" . $config['db']['name'] . "</td></tr>";
    echo "<tr><td>Usuario</td><td>" . $config['db']['user'] . "</td></tr>";
}
echo "</table>";

// 3. Test de Conexión
echo "<h2>🔗 Test de Conexión</h2>";
try {
    require_once 'api/db.php';
    
    if ($conexion) {
        echo "<div class='success'>✓ <span class='status-ok'>Conexión establecida correctamente</span></div>";
        
        // Ejecutar query de prueba
        try {
            $result = $conexion->query("SELECT 1 as test");
            $row = $result->fetch();
            echo "<div class='success'>✓ <span class='status-ok'>Query de prueba exitoso</span></div>";
        } catch (Exception $e) {
            echo "<div class='error'>✗ <span class='status-error'>Error en query: " . htmlspecialchars($e->getMessage()) . "</span></div>";
        }
        
        // Verificar tablas
        if ($db_driver === 'sqlite') {
            $tables = $conexion->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $tables = $conexion->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        }
        
        echo "<div class='info'>";
        echo "<strong>Tablas encontradas (" . count($tables) . "):</strong><br>";
        if (!empty($tables)) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "No hay tablas. La BD será inicializada en el primer acceso.";
        }
        echo "</div>";
    } else {
        echo "<div class='error'>✗ <span class='status-error'>No se pudo establecer conexión</span></div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ <span class='status-error'>Error: " . htmlspecialchars($e->getMessage()) . "</span></div>";
}

// 4. Verificar Directorios
echo "<h2>📁 Directorios Críticos</h2>";
$dirs = [
    'data' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data',
    'api' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api',
    'assets' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets',
];

if (strtolower($config['db']['driver']) === 'sqlite') {
    $dirs['BD Storage'] = dirname(DB_PATH);
}

echo "<table>
        <tr><th>Directorio</th><th>Existe</th><th>Escribible</th></tr>";
foreach ($dirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td>" . ($exists ? '<span class=\"status-ok\">✓</span>' : '<span class=\"status-error\">✗</span>') . "</td>";
    echo "<td>" . ($writable ? '<span class=\"status-ok\">✓</span>' : '<span class=\"status-warning\">✗</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Extensiones PHP
echo "<h2>🔧 Extensiones PHP Requeridas</h2>";
$extensions = ['pdo', 'pdo_sqlite', 'pdo_mysql', 'mbstring', 'curl'];
echo "<table>
        <tr><th>Extensión</th><th>Estado</th></tr>";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<tr>";
    echo "<td>$ext</td>";
    echo "<td>" . ($loaded ? '<span class=\"status-ok\">✓ Cargada</span>' : '<span class=\"status-warning\">✗ No cargada</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "</div>
    </body>
</html>";
        echo "<h2 style='color:green'>✓ Conexión exitosa con: $host</h2>";
        break;
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
        echo "<p style='color:orange'>✗ Falló con $host: {$e->getMessage()}</p>";
    }
}

if ($pdo) {
    try {
    
    // Ver tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tablas encontradas (" . count($tables) . "):</h3>";
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No hay tablas. Necesitas ejecutar setup_db.php</p>";
    }
    
    } catch (Exception $e) {
        echo "<h2 style='color:red'>✗ Error consultando tablas</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2 style='color:red'>✗ No se pudo conectar con ningún host</h2>";
    echo "<p><strong>Último error:</strong> $lastError</p>";
}

echo "<hr>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PDO MySQL disponible: " . (extension_loaded('pdo_mysql') ? 'Sí' : 'No') . "</p>";
?>
