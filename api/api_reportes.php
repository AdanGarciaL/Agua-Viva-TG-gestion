<?php
// api_reportes.php
if (!file_exists('../vendor/autoload.php')) {
    die('Error: No se encuentra "../vendor/autoload.php".');
}
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();
include 'db.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    logError("Intento no autorizado de acceso a reportes.", $conexion);
    die('No autorizado');
}

$reporte = $_GET['reporte'] ?? '';
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];

function autoSizeColumns($sheet) {
    foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }
}

// --- REPORTE 1: SOLO INVENTARIO ---
if ($reporte === 'inventario_hoy') {
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');
        
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->setCellValue('C1', 'Código Barras');
        $sheet->setCellValue('D1', 'Precio');
        $sheet->setCellValue('E1', 'Stock Actual');
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        // FILTRO: Solo activos (activo = 1) o si no existe columna, todo.
        try {
             $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        } catch (PDOException $e) {
             $stmt = $conexion->query("SELECT * FROM productos ORDER BY nombre ASC");
        }

        $fila = 2;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet->setCellValue('A' . $fila, $row['id']);
            $sheet->setCellValue('B' . $fila, $row['nombre']);
            $sheet->setCellValue('C' . $fila, $row['codigo_barras']);
            $sheet->setCellValue('D' . $fila, $row['precio_venta']);
            $sheet->setCellValue('E' . $fila, $row['stock']);
            $sheet->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $fila++;
        }
        autoSizeColumns($sheet);
        
        $fecha = date('Y-m-d');
        $filename = "Reporte_Inventario_$fecha.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();

    } catch (PDOException $e) {
        die("Error BD: " . $e->getMessage());
    }
}

// --- REPORTE 2: REPORTE CONSOLIDADO ---
else if ($reporte === 'consolidado') {
    try {
        $spreadsheet = new Spreadsheet();
        
        // Pestaña 1: Inventario (Solo Activos)
        $sheetInv = $spreadsheet->getActiveSheet();
        $sheetInv->setTitle('Inventario');
        $sheetInv->setCellValue('A1', 'ID');
        $sheetInv->setCellValue('B1', 'Nombre');
        $sheetInv->setCellValue('C1', 'Código Barras');
        $sheetInv->setCellValue('D1', 'Precio');
        $sheetInv->setCellValue('E1', 'Stock Actual');
        $sheetInv->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        try {
             $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        } catch (PDOException $e) {
             $stmt = $conexion->query("SELECT * FROM productos ORDER BY nombre ASC");
        }

        $fila = 2;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheetInv->setCellValue('A' . $fila, $row['id']);
            $sheetInv->setCellValue('B' . $fila, $row['nombre']);
            $sheetInv->setCellValue('C' . $fila, $row['codigo_barras']);
            $sheetInv->setCellValue('D' . $fila, $row['precio_venta']);
            $sheetInv->setCellValue('E' . $fila, $row['stock']);
            $sheetInv->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $fila++;
        }
        autoSizeColumns($sheetInv);

        // Pestaña 2: Ventas (Histórico completo, incluyendo productos borrados)
        $sheetVentas = $spreadsheet->createSheet();
        $sheetVentas->setTitle('Ventas');
        $headers = ['ID', 'Fecha', 'Vendedor', 'Producto ID', 'Producto (Histórico)', 'Cantidad', 'Total', 'Tipo Pago', 'Fiado a', 'Pagado'];
        $sheetVentas->fromArray($headers, NULL, 'A1');
        $sheetVentas->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // JOIN para obtener el nombre del producto incluso si está borrado
        $stmt = $conexion->query("
            SELECT v.*, p.nombre as nombre_prod 
            FROM ventas v 
            LEFT JOIN productos p ON v.producto_id = p.id 
            ORDER BY v.fecha DESC
        ");

        $fila = 2;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheetVentas->setCellValue('A' . $fila, $row['id']);
            $sheetVentas->setCellValue('B' . $fila, $row['fecha']);
            $sheetVentas->setCellValue('C' . $fila, $row['vendedor']);
            $sheetVentas->setCellValue('D' . $fila, $row['producto_id']);
            $sheetVentas->setCellValue('E' . $fila, $row['nombre_prod'] ?? '(Eliminado)');
            $sheetVentas->setCellValue('F' . $fila, $row['cantidad']);
            $sheetVentas->setCellValue('G' . $fila, $row['total']);
            $sheetVentas->setCellValue('H' . $fila, $row['tipo_pago']);
            $sheetVentas->setCellValue('I' . $fila, $row['nombre_fiado']);
            $sheetVentas->setCellValue('J' . $fila, $row['fiado_pagado'] ? 'Sí' : 'No');
            $sheetVentas->getStyle('G' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $fila++;
        }
        autoSizeColumns($sheetVentas);
        
        // Pestaña 3: Deudores
        $sheetDeudores = $spreadsheet->createSheet();
        $sheetDeudores->setTitle('Deudores');
        $headers = ['Nombre Deudor', 'Total Deuda'];
        $sheetDeudores->fromArray($headers, NULL, 'A1');
        $sheetDeudores->getStyle('A1:B1')->applyFromArray($headerStyle);
        $stmt = $conexion->query("SELECT nombre_fiado, SUM(total) as total_deuda FROM ventas WHERE tipo_pago = 'fiado' AND fiado_pagado = 0 GROUP BY nombre_fiado HAVING total_deuda > 0 ORDER BY nombre_fiado ASC");
        $fila = 2;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheetDeudores->setCellValue('A' . $fila, $row['nombre_fiado']);
            $sheetDeudores->setCellValue('B' . $fila, $row['total_deuda']);
            $sheetDeudores->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $fila++;
        }
        autoSizeColumns($sheetDeudores);
        
        // Pestaña 4: Registros
        $sheetRegistros = $spreadsheet->createSheet();
        $sheetRegistros->setTitle('Registros');
        $headers = ['ID', 'Fecha', 'Tipo', 'Concepto', 'Monto', 'Usuario'];
        $sheetRegistros->fromArray($headers, NULL, 'A1');
        $sheetRegistros->getStyle('A1:F1')->applyFromArray($headerStyle);
        $stmt = $conexion->query("SELECT * FROM registros ORDER BY fecha DESC");
        $fila = 2;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheetRegistros->setCellValue('A' . $fila, $row['id']);
            $sheetRegistros->setCellValue('B' . $fila, $row['fecha']);
            $sheetRegistros->setCellValue('C' . $fila, $row['tipo']);
            $sheetRegistros->setCellValue('D' . $fila, $row['concepto']);
            $sheetRegistros->setCellValue('E' . $fila, $row['monto']);
            $sheetRegistros->setCellValue('F' . $fila, $row['usuario']);
            $sheetRegistros->getStyle('E' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $fila++;
        }
        autoSizeColumns($sheetRegistros);
        
        // Pestaña 5: Séptimas
        $sheetSeptimas = $spreadsheet->createSheet();
        $sheetSeptimas->setTitle('Séptimas');
        $headers = ['ID', 'Fecha', 'Padrino/Madrina', 'Monto', 'Registró', 'Estado'];
        $sheetSeptimas->fromArray($headers, NULL, 'A1');
        $sheetSeptimas->getStyle('A1:F1')->applyFromArray($headerStyle);
        $stmt = $conexion->query("SELECT * FROM septimas ORDER BY pagado ASC, fecha DESC");
        $fila = 2;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheetSeptimas->setCellValue('A' . $fila, $row['id']);
            $sheetSeptimas->setCellValue('B' . $fila, $row['fecha']);
            $sheetSeptimas->setCellValue('C' . $fila, $row['nombre_padrino']);
            $sheetSeptimas->setCellValue('D' . $fila, $row['monto']);
            $sheetSeptimas->setCellValue('E' . $fila, $row['usuario_registro']);
            $sheetSeptimas->setCellValue('F' . $fila, $row['pagado'] ? 'Pagado' : 'Pendiente');
            $sheetSeptimas->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('$#,##0.00');
            $fila++;
        }
        autoSizeColumns($sheetSeptimas);

        $spreadsheet->setActiveSheetIndex(0);
        $fecha = date('Y-m-d');
        $filename = "Reporte_Consolidado_AguaViva_$fecha.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();

    } catch (Exception $e) {
        die("Error Reporte: " . $e->getMessage());
    }
}

echo "Reporte no válido.";
?>