<?php
// api/api_respaldo.php
session_start();
include 'db.php';

// 1. Seguridad: Solo Superadmin puede descargar la base de datos
if (!isset($_SESSION['usuario']) || $_SESSION['role'] !== 'superadmin') {
    die("Acceso denegado. Solo el Superadmin puede realizar respaldos.");
}

// 2. Configuración del archivo
$fecha = date('Y-m-d_H-i-s');
$nombre_archivo = "Respaldo_AguaViva_$fecha.sql";

// Forzar la descarga del archivo
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"$nombre_archivo\"");

// 3. Generación del contenido SQL
try {
    $tablas = array();
    // Obtener lista de tablas
    $resultado = $conexion->query("SHOW TABLES");
    while ($fila = $resultado->fetch(PDO::FETCH_NUM)) {
        $tablas[] = $fila[0];
    }

    $sql_dump = "-- Respaldo de Base de Datos Agua Viva POS\n";
    $sql_dump .= "-- Fecha: " . date('d-m-Y H:i:s') . "\n";
    $sql_dump .= "-- Generado por: " . $_SESSION['usuario'] . "\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tablas as $tabla) {
        // Obtener la estructura de creación de la tabla
        $row2 = $conexion->query("SHOW CREATE TABLE $tabla")->fetch(PDO::FETCH_NUM);
        $sql_dump .= "\n\n" . $row2[1] . ";\n\n";

        // Obtener los datos de la tabla
        $datos = $conexion->query("SELECT * FROM $tabla");
        while ($fila = $datos->fetch(PDO::FETCH_ASSOC)) {
            $sql_dump .= "INSERT INTO $tabla VALUES(";
            $valores = array();
            foreach ($fila as $valor) {
                if (is_null($valor)) {
                    $valores[] = "NULL";
                } else {
                    // Escapar comillas para evitar errores de SQL
                    $valor_escapado = addslashes($valor);
                    $valores[] = "'$valor_escapado'";
                }
            }
            $sql_dump .= implode(',', $valores);
            $sql_dump .= ");\n";
        }
    }
    
    $sql_dump .= "\nSET FOREIGN_KEY_CHECKS=1;";

    echo $sql_dump;

} catch (Exception $e) {
    echo "Error al generar respaldo: " . $e->getMessage();
}
?>