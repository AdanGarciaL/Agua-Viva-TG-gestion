<?php
// api/api_reportes.php
// VERSIÓN FINAL: Guarda localmente y renderiza HTML correctamente

session_start();
include 'db.php';

// 1. LIMPIEZA DE BUFFER
while (ob_get_level()) ob_end_clean();

// 2. CARGA DE LIBRERÍA
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    header('Content-Type: text/html; charset=utf-8');
    die("<h2 style='color:red; font-family:sans-serif; text-align:center; margin-top:50px;'>ERROR CRÍTICO: No se encuentra la librería Excel.</h2><p style='text-align:center;'>Buscada en: " . realpath($vendorPath) . "</p>");
}
require $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Seguridad
if (!isset($_SESSION['usuario']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header('Content-Type: text/html; charset=utf-8');
    die('<h2 style="text-align:center; font-family:sans-serif;">Acceso denegado.</h2>');
}

$reporte = $_GET['reporte'] ?? '';

// Estilos Excel
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

try {
    $spreadsheet = new Spreadsheet();
    $filename = "";

    // ==========================================
    // GENERACIÓN DE DATOS
    // ==========================================
    if ($reporte === 'inventario_hoy') {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');
        $sheet->setCellValue('A1', 'ID')->setCellValue('B1', 'Nombre')->setCellValue('C1', 'Código')->setCellValue('D1', 'Precio')->setCellValue('E1', 'Stock');
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
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
        $filename = "Inventario_TG_" . date('Y-m-d_H-i') . ".xlsx";

    } else if ($reporte === 'consolidado') {
        // Pestaña 1: Inventario
        $sheetInv = $spreadsheet->getActiveSheet();
        $sheetInv->setTitle('Inventario');
        $sheetInv->setCellValue('A1', 'ID')->setCellValue('B1', 'Nombre')->setCellValue('C1', 'Precio')->setCellValue('D1', 'Stock');
        $sheetInv->getStyle('A1:D1')->applyFromArray($headerStyle);
        $stmtInv = $conexion->query("SELECT * FROM productos WHERE activo = 1");
        $fila = 2;
        while ($r = $stmtInv->fetch(PDO::FETCH_ASSOC)) {
            $sheetInv->setCellValue('A'.$fila, $r['id'])->setCellValue('B'.$fila, $r['nombre'])->setCellValue('C'.$fila, $r['precio_venta'])->setCellValue('D'.$fila, $r['stock']);
            $fila++;
        }
        autoSizeColumns($sheetInv);

        // Pestaña 2: Ventas
        $sheetVentas = $spreadsheet->createSheet();
        $sheetVentas->setTitle('Ventas');
        $sheetVentas->setCellValue('A1', 'Fecha')->setCellValue('B1', 'Vendedor')->setCellValue('C1', 'Producto')->setCellValue('D1', 'Total');
        $sheetVentas->getStyle('A1:D1')->applyFromArray($headerStyle);
        $stmtV = $conexion->query("SELECT v.*, p.nombre as n FROM ventas v LEFT JOIN productos p ON v.producto_id=p.id ORDER BY v.fecha DESC");
        $fila = 2;
        while ($r = $stmtV->fetch(PDO::FETCH_ASSOC)) {
            $sheetVentas->setCellValue('A'.$fila, $r['fecha'])->setCellValue('B'.$fila, $r['vendedor'])->setCellValue('C'.$fila, $r['n']??'--')->setCellValue('D'.$fila, $r['total']);
            $fila++;
        }
        autoSizeColumns($sheetVentas);

        // Pestaña 3: Deudores
        $sheetFiad = $spreadsheet->createSheet();
        $sheetFiad->setTitle('Deudores');
        $sheetFiad->setCellValue('A1', 'Nombre')->setCellValue('B1', 'Deuda');
        $sheetFiad->getStyle('A1:B1')->applyFromArray($headerStyle);
        $stmtF = $conexion->query("SELECT nombre_fiado, SUM(total) as d FROM ventas WHERE tipo_pago='fiado' AND fiado_pagado=0 GROUP BY nombre_fiado");
        $fila = 2;
        while ($r = $stmtF->fetch(PDO::FETCH_ASSOC)) {
            $sheetFiad->setCellValue('A'.$fila, $r['nombre_fiado'])->setCellValue('B'.$fila, $r['d']);
            $fila++;
        }
        autoSizeColumns($sheetFiad);

        // Pestaña 4: Registros
        $sheetReg = $spreadsheet->createSheet();
        $sheetReg->setTitle('Caja');
        $sheetReg->setCellValue('A1', 'Fecha')->setCellValue('B1', 'Tipo')->setCellValue('C1', 'Concepto')->setCellValue('D1', 'Monto');
        $sheetReg->getStyle('A1:D1')->applyFromArray($headerStyle);
        $stmtR = $conexion->query("SELECT * FROM registros ORDER BY fecha DESC");
        $fila = 2;
        while ($r = $stmtR->fetch(PDO::FETCH_ASSOC)) {
            $sheetReg->setCellValue('A'.$fila, $r['fecha'])->setCellValue('B'.$fila, $r['tipo'])->setCellValue('C'.$fila, $r['concepto'])->setCellValue('D'.$fila, $r['monto']);
            $fila++;
        }
        autoSizeColumns($sheetReg);

        // Pestaña 5: Séptimas
        $sheetSep = $spreadsheet->createSheet();
        $sheetSep->setTitle('Séptimas');
        $sheetSep->setCellValue('A1', 'Fecha')->setCellValue('B1', 'Nombre')->setCellValue('C1', 'Monto')->setCellValue('D1', 'Estado');
        $sheetSep->getStyle('A1:D1')->applyFromArray($headerStyle);
        $stmtS = $conexion->query("SELECT * FROM septimas ORDER BY fecha DESC");
        $fila = 2;
        while ($r = $stmtS->fetch(PDO::FETCH_ASSOC)) {
            $sheetSep->setCellValue('A'.$fila, $r['fecha'])->setCellValue('B'.$fila, $r['nombre_padrino'])->setCellValue('C'.$fila, $r['monto'])->setCellValue('D'.$fila, $r['pagado']?'Pagado':'Pendiente');
            $fila++;
        }
        autoSizeColumns($sheetSep);

        $spreadsheet->setActiveSheetIndex(0);
        $filename = "Reporte_Completo_TG_" . date('Y-m-d_H-i') . ".xlsx";
    }

    // --- GUARDAR ARCHIVO ---
    $baseDir = getenv('USERPROFILE');
    $savePath = $baseDir . '/Downloads/' . $filename;
    if (!is_dir($baseDir . '/Downloads')) $savePath = $baseDir . '/Documents/' . $filename;

    $writer = new Xlsx($spreadsheet);
    $writer->save($savePath);

    // --- AVISO HTML (HEADER AÑADIDO AQUÍ) ---
    header('Content-Type: text/html; charset=utf-8'); // <--- ESTA LÍNEA ARREGLA TU PROBLEMA
    
    echo "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Reporte Listo</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'>
        <style>
            body { font-family: 'Segoe UI', sans-serif; text-align: center; padding-top: 50px; background: #f0f2f5; margin: 0; }
            .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: inline-block; max-width: 600px; width: 90%; }
            h1 { color: #2e7d32; margin-bottom: 10px; }
            .path { background: #e8f5e9; padding: 15px; border: 1px solid #c8e6c9; border-radius: 5px; font-family: monospace; margin: 20px 0; word-break: break-all; color: #1b5e20; }
            .btn { background: #0d47a1; color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; box-shadow: 0 4px 10px rgba(13, 71, 161, 0.3); transition: 0.3s; }
            .btn:hover { background: #1565c0; transform: translateY(-2px); }
            .icon-large { font-size: 60px; color: #2e7d32; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <i class='fas fa-file-excel icon-large'></i>
            <h1>¡Reporte Completo Generado!</h1>
            <p>Se han exportado 5 pestañas correctamente.</p>
            
            <div class='path'>
                <strong>Guardado en:</strong><br>
                $savePath
            </div>
            
            <p>Revisa tu carpeta de <b>Descargas</b>.</p>
            <a href='javascript:history.back()' class='btn'>Volver al Sistema</a>
        </div>
    </body>
    </html>
    ";
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    die("<div style='color:red; text-align:center; padding:50px; font-family:sans-serif;'><h2>Error al generar reporte</h2><p>" . $e->getMessage() . "</p></div>");
}
?>