<?php
// api/migrate_db.php
// MIGRACIÓN: Agregar campos para mejoras de UX

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
include 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Migración de Base de Datos</h1>";

if (!asegurar_conexion_db()) {
    die("<p style='color:red;'>❌ Error de conexión a base de datos</p>");
}

$isMysql = (defined('DB_DRIVER') && DB_DRIVER === 'mysql');

try {
    echo "<h2>Aplicando migraciones...</h2>";
    
    // 1. Agregar stock_minimo a productos (para alertas)
    try {
        if ($isMysql) {
            $conexion->exec("ALTER TABLE productos ADD COLUMN stock_minimo INT DEFAULT 10");
        } else {
            $conexion->exec("ALTER TABLE productos ADD COLUMN stock_minimo INTEGER DEFAULT 10");
        }
        echo "<p>✅ Campo <code>stock_minimo</code> agregado a productos</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'duplicate column') !== false) {
            echo "<p>⚠️ Campo <code>stock_minimo</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ stock_minimo: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Agregar grupo a ventas (para fiados con grupo)
    try {
        if ($isMysql) {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN grupo_fiado VARCHAR(100)");
        } else {
            $conexion->exec("ALTER TABLE ventas ADD COLUMN grupo_fiado TEXT");
        }
        echo "<p>✅ Campo <code>grupo_fiado</code> agregado a ventas</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'duplicate column') !== false) {
            echo "<p>⚠️ Campo <code>grupo_fiado</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ grupo_fiado: " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. Agregar teléfono a ventas (para fiados con teléfono) - OMITIDO POR SOLICITUD DEL USUARIO
    // NO SE AGREGA telefono_fiado
    
    // 4. Agregar grupo a usuarios (para vendedores)
    try {
        if ($isMysql) {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN grupo VARCHAR(100)");
        } else {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN grupo TEXT");
        }
        echo "<p>✅ Campo <code>grupo</code> agregado a usuarios</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'duplicate column') !== false) {
            echo "<p>⚠️ Campo <code>grupo</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ grupo usuarios: " . $e->getMessage() . "</p>";
        }
    }
    
    // 5. Agregar padrino a usuarios (para vendedores)
    try {
        if ($isMysql) {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN padrino VARCHAR(255)");
        } else {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN padrino TEXT");
        }
        echo "<p>✅ Campo <code>padrino</code> agregado a usuarios</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'duplicate column') !== false) {
            echo "<p>⚠️ Campo <code>padrino</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ padrino: " . $e->getMessage() . "</p>";
        }
    }
    
    // 6. Agregar inicial a usuarios (para vendedores)
    try {
        if ($isMysql) {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN inicial VARCHAR(10)");
        } else {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN inicial TEXT");
        }
        echo "<p>✅ Campo <code>inicial</code> agregado a usuarios</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'duplicate column') !== false) {
            echo "<p>⚠️ Campo <code>inicial</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ inicial: " . $e->getMessage() . "</p>";
        }
    }
    
    // 7. Crear índices para búsqueda rápida
    try {
        if ($isMysql) {
            $conexion->exec("CREATE INDEX idx_productos_nombre ON productos(nombre)");
            echo "<p>✅ Índice <code>idx_productos_nombre</code> creado</p>";
        } else {
            $conexion->exec("CREATE INDEX IF NOT EXISTS idx_productos_nombre ON productos(nombre)");
            echo "<p>✅ Índice <code>idx_productos_nombre</code> creado</p>";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<p>⚠️ Índice <code>idx_productos_nombre</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ idx_productos_nombre: " . $e->getMessage() . "</p>";
        }
    }
    
    try {
        if ($isMysql) {
            $conexion->exec("CREATE INDEX idx_productos_codigo ON productos(codigo_barras)");
            echo "<p>✅ Índice <code>idx_productos_codigo</code> creado</p>";
        } else {
            $conexion->exec("CREATE INDEX IF NOT EXISTS idx_productos_codigo ON productos(codigo_barras)");
            echo "<p>✅ Índice <code>idx_productos_codigo</code> creado</p>";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<p>⚠️ Índice <code>idx_productos_codigo</code> ya existe</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ idx_productos_codigo: " . $e->getMessage() . "</p>";
        }
    }
    
    // 8. Actualizar productos existentes con stock_minimo si es NULL
    try {
        $conexion->exec("UPDATE productos SET stock_minimo = 10 WHERE stock_minimo IS NULL");
        echo "<p>✅ Stock mínimo actualizado para productos existentes</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠️ update stock_minimo: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2 style='color:green;'>✅ Migración completada</h2>";
    echo "<p><a href='../dashboard.php' style='padding:10px 20px; background:#0d47a1; color:white; text-decoration:none; border-radius:6px;'>← Volver al Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error general: " . $e->getMessage() . "</p>";
}
?>
