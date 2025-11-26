<?php
// api/api_reportes.php - v4.0

session_start();
include 'db.php';

// Limpiar buffer
while (ob_get_level()) ob_end_clean();

// Verificar librería
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    header('Content-Type: text/html; charset=utf-8');
    die("<h2 style='color:red; text-align:center; font-family:sans-serif; margin-top:50px;'>Error Crítico: Librería Excel no encontrada en:<br><small>$vendorPath</small></h2>");
}
require $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Seguridad
if (!isset($_SESSION['usuario']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header('Content-Type: text/html; charset=utf-8');
    die("Acceso denegado.");
}

$reporte = $_GET['reporte'] ?? '';
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];

function autoSizeColumns($sheet) {
    foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }
}

try {
    $spreadsheet = new Spreadsheet();
    $filename = "Reporte_TG_" . date('Y-m-d_Hi') . ".xlsx";

    // VALIDACIÓN PREVIA: Verificar que hay datos (v5.0)
    $hayDatos = false;
    
    if ($reporte === 'inventario_hoy' || $reporte === 'consolidado') {
        $countProductos = $conexion->query("SELECT COUNT(*) as c FROM productos WHERE activo = 1")->fetch()['c'];
        if ($countProductos == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No hay productos en el inventario para exportar']);
            exit;
        }
        $hayDatos = true;
    }
    
    if ($reporte === 'ventas_periodo') {
        $desde = $_GET['desde'] ?? date('Y-m-d');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $countVentas = $conexion->prepare("SELECT COUNT(*) as c FROM ventas WHERE date(fecha) BETWEEN ? AND ?");
        $countVentas->execute([$desde, $hasta]);
        if ($countVentas->fetch()['c'] == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No hay ventas en el período seleccionado']);
            exit;
        }
        $hayDatos = true;
    }

    // --- GENERACIÓN DE DATOS ---
    if ($reporte === 'inventario_hoy' || $reporte === 'consolidado') {
        
        // 1. INVENTARIO
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');
        $sheet->setCellValue('A1', 'ID')->setCellValue('B1', 'Nombre')->setCellValue('C1', 'Código')->setCellValue('D1', 'Precio')->setCellValue('E1', 'Stock');
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        $i = 2;
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet->setCellValue('A'.$i, $r['id']);
            $sheet->setCellValue('B'.$i, $r['nombre']);
            $sheet->setCellValueExplicit('C'.$i, $r['codigo_barras'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D'.$i, $r['precio_venta']);
            $sheet->setCellValue('E'.$i, $r['stock']);
            $i++;
        }
        autoSizeColumns($sheet);

        if ($reporte === 'consolidado') {
            // 2. VENTAS
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Ventas');
            $sheet2->setCellValue('A1', 'Fecha')->setCellValue('B1', 'Vendedor')->setCellValue('C1', 'Producto')->setCellValue('D1', 'Total')->setCellValue('E1', 'Tipo');
            $sheet2->getStyle('A1:E1')->applyFromArray($headerStyle);
            
            $stmtV = $conexion->query("SELECT v.fecha, v.vendedor, p.nombre, v.total, v.tipo_pago FROM ventas v LEFT JOIN productos p ON v.producto_id=p.id ORDER BY v.fecha DESC");
            $i=2;
            while($r=$stmtV->fetch(PDO::FETCH_ASSOC)){
                $sheet2->setCellValue('A'.$i, $r['fecha']);
                $sheet2->setCellValue('B'.$i, $r['vendedor']);
                $sheet2->setCellValue('C'.$i, $r['nombre'] ?? '--');
                $sheet2->setCellValue('D'.$i, $r['total']);
                $sheet2->setCellValue('E'.$i, $r['tipo_pago']);
                $i++;
            }
            autoSizeColumns($sheet2);

            // 3. DEUDAS
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Deudores');
            $sheet3->setCellValue('A1', 'Nombre')->setCellValue('B1', 'Deuda Total');
            $sheet3->getStyle('A1:B1')->applyFromArray($headerStyle);
            
            $stmtD = $conexion->query("SELECT nombre_fiado, SUM(total) as d FROM ventas WHERE tipo_pago='fiado' AND fiado_pagado=0 GROUP BY nombre_fiado");
            $i=2;
            while($r=$stmtD->fetch(PDO::FETCH_ASSOC)){
                $sheet3->setCellValue('A'.$i, $r['nombre_fiado']);
                $sheet3->setCellValue('B'.$i, $r['d']);
                $i++;
            }
            autoSizeColumns($sheet3);
            
            // 4. SÉPTIMAS (omitido en edición regional)
            // El módulo de Séptimas está deshabilitado en la edición 'Tienda Regional'.
        }
    }

    // --- GUARDADO EN WINDOWS (FIX RUTAS) ---
    $userProfile = getenv('USERPROFILE');
    $downloadsDir = $userProfile . '\Downloads';
    
    // Fallback si no encuentra Descargas
    if (!is_dir($downloadsDir)) {
        $downloadsDir = __DIR__ . '/../../'; // Guardar en raíz del proyecto si falla
    }
    
    $savePath = $downloadsDir . DIRECTORY_SEPARATOR . $filename;

    $writer = new Xlsx($spreadsheet);
    $writer->save($savePath);

    // --- RESPUESTA VISUAL (HTML) ---
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reporte Exitoso</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'>
        <style>
            body { 
                font-family: 'Segoe UI', system-ui, sans-serif; 
                background-color: #f0f2f5; 
                display: flex; justify-content: center; align-items: center; 
                height: 100vh; margin: 0; 
            }
            .card { 
                background: white; padding: 3rem; border-radius: 20px; 
                box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; 
                max-width: 600px; width: 90%; animation: slideUp 0.5s ease-out;
            }
            h1 { color: #2e7d32; margin-top: 1rem; font-size: 2rem; }
            .icon-box { 
                font-size: 5rem; color: #2e7d32; margin-bottom: 1rem; 
                animation: bounce 2s infinite;
            }
            .path-box { 
                background: #e8f5e9; padding: 1.5rem; border-radius: 12px; 
                border: 2px dashed #a5d6a7; color: #1b5e20; word-break: break-all; 
                margin: 2rem 0; font-family: monospace; font-size: 1rem;
            }
            .btn { 
                background: #0d47a1; color: white; padding: 1rem 2.5rem; 
                text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 1.1rem;
                transition: transform 0.2s, box-shadow 0.2s; display: inline-flex; align-items: center; gap: 10px;
                box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
            }
            .btn:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(13, 71, 161, 0.4); }
            
            @keyframes slideUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
            @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-20px);} 60% {transform: translateY(-10px);} }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='icon-box'><i class='fas fa-check-circle'></i></div>
            <h1>¡Reporte Generado!</h1>
            <p style="color:#666; font-size:1.1rem;">El archivo Excel ha sido creado correctamente con todas las pestañas.</p>
            
            <div class='path-box'>
                <strong>Guardado en:</strong><br>
                <?php echo $savePath; ?>
            </div>
            
            <a href='../dashboard.php' class='btn'>
                <i class='fas fa-arrow-left'></i> Volver al Sistema
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<body style='background:#ffebee; display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif;'>
            <div style='background:white; padding:40px; border-radius:15px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.1); max-width:500px;'>
                <h1 style='color:#c62828; font-size:4rem; margin:0;'>⚠️</h1>
                <h2 style='color:#c62828;'>Error al generar reporte</h2>
                <p style='color:#555; background:#f5f5f5; padding:15px; border-radius:8px; font-family:monospace;'>" . $e->getMessage() . "</p>
                <a href='javascript:history.back()' style='display:inline-block; margin-top:20px; padding:12px 30px; background:#c62828; color:white; text-decoration:none; border-radius:50px; font-weight:bold;'>Intentar de nuevo</a>
            </div>
          </body>";
    exit;
}
?>