<?php
// api/api_reportes.php - v4.3 - COMPATIBLE PHP DESKTOP

// CRÍTICO: NO output antes de headers
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer ANTES de cualquier cosa
ob_start();
ob_implicit_flush(0);

session_start();

// Guardar el nivel de buffer actual
$bufferLevel = ob_get_level();

// Log para debug
$logFile = (getenv('USERPROFILE') ?: '') . '\\AppData\\Local\\TG_Gestion\\reportes_log.txt';
function logReporte($msg) {
    global $logFile;
    if (empty($logFile)) return;
    $dir = dirname($logFile);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

include 'db.php';

// Limpiar cualquier output de db.php
while (ob_get_level() > $bufferLevel) {
    ob_end_clean();
}

// Verificar librería
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Librería Excel no encontrada']);
    exit;
}
require $vendorPath;

if (!class_exists('ZipArchive')) {
    while (ob_get_level()) ob_end_clean();
    logReporte('ERROR: Falta extension ZIP (php_zip.dll)');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ERROR|Falta la extensión ZIP de PHP. Reinicia la app después de habilitar php_zip.dll';
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

function resolveUserHome() {
    $candidates = [
        getenv('USERPROFILE') ?: '',
        (getenv('HOMEDRIVE') && getenv('HOMEPATH')) ? getenv('HOMEDRIVE') . getenv('HOMEPATH') : '',
        $_SERVER['USERPROFILE'] ?? '',
        (($_SERVER['HOMEDRIVE'] ?? '') . ($_SERVER['HOMEPATH'] ?? '')),
    ];

    foreach ($candidates as $path) {
        if (!empty($path) && is_dir($path)) {
            return rtrim($path, "\\/");
        }
    }

    return '';
}

function resolveDownloadsPath() {
    $paths = [];

    $home = resolveUserHome();
    if (!empty($home)) {
        $paths[] = $home . DIRECTORY_SEPARATOR . 'Downloads';
    }

    $username = getenv('USERNAME') ?: ($_SERVER['USERNAME'] ?? '');
    if (!empty($username)) {
        $paths[] = 'C:\\Users\\' . $username . '\\Downloads';
    }

    $paths[] = 'C:\\Users\\Public\\Downloads';

    foreach ($paths as $path) {
        if (is_dir($path)) {
            return $path;
        }
        if (@mkdir($path, 0777, true)) {
            return $path;
        }
    }

    return sys_get_temp_dir();
}

// Seguridad
if (!isset($_SESSION['usuario']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header('Content-Type: text/html; charset=utf-8');
    die("Acceso denegado.");
}

/** @var \PDO $conexion */

// Detectar acción (CSV, JSON o Excel)
$accion = $_GET['accion'] ?? '';
$formato = $_GET['formato'] ?? '';
$isMysql = (defined('DB_DRIVER') && DB_DRIVER === 'mysql');
$last30 = $isMysql ? "DATE_SUB(CURDATE(), INTERVAL 30 DAY)" : "date('now', '-30 days')";

// ========================================
// EXPORTACIÓN CSV/JSON
// ========================================
if ($accion === 'exportar') {
    try {
        if ($formato === 'csv') {
            // Validar que hay ventas
            $countStmt = $conexion->query("SELECT COUNT(*) as c FROM ventas");
            $count = $countStmt->fetch()['c'];
            
            if ($count == 0) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No hay ventas registradas para exportar']);
                exit;
            }
            
            // Obtener ventas del último mes
            $stmt = $conexion->query("
                SELECT v.id, v.fecha, v.total, v.tipo_pago, 
                       v.vendedor as vendedor, 
                       v.cantidad as items
                FROM ventas v
                WHERE v.fecha >= $last30
                ORDER BY v.fecha DESC
            ");
            $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($ventas)) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No hay ventas en el período de los últimos 30 días']);
                exit;
            }
            
            // Limpiar buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Guardar en Downloads
            $downloadsPath = resolveDownloadsPath();
            $filename = 'ventas_' . date('Y-m-d_His') . '.csv';
            $fullPath = $downloadsPath . DIRECTORY_SEPARATOR . $filename;
            
            $output = fopen($fullPath, 'w');
            
            // BOM para UTF-8 (Excel compatibility)
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados
            fputcsv($output, ['ID', 'Fecha', 'Total', 'Tipo Pago', 'Vendedor', 'Productos']);
            
            // Datos
            foreach ($ventas as $venta) {
                fputcsv($output, [
                    $venta['id'],
                    $venta['fecha'],
                    '$' . number_format($venta['total'], 2),
                    ucfirst($venta['tipo_pago']),
                    $venta['vendedor'] ?? 'N/A',
                    $venta['items']
                ]);
            }
            
            fclose($output);
            
            // Mostrar mensaje de éxito
            header('Content-Type: text/html; charset=utf-8');
            echo '<html><body style="background:#e8f5e9;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;">
                <div style="background:white;padding:40px;border-radius:12px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
                    <h1 style="color:#2e7d32;font-size:3em;">✅</h1>
                    <h2>Archivo CSV Guardado</h2>
                    <p style="background:#f5f5f5;padding:15px;border-radius:8px;margin:20px 0;font-family:monospace;word-break:break-all;">' . htmlspecialchars($fullPath) . '</p>
                    <a href="javascript:history.back()" style="display:inline-block;padding:12px 30px;background:#4caf50;color:white;text-decoration:none;border-radius:6px;font-weight:600;">Volver</a>
                </div></body></html>';
            exit;
            
        } elseif ($formato === 'json') {
            // Validar que hay datos
            $countProductos = $conexion->query("SELECT COUNT(*) as c FROM productos WHERE activo = 1")->fetch()['c'];
            $countVentas = $conexion->query("SELECT COUNT(*) as c FROM ventas")->fetch()['c'];
            
            if ($countProductos == 0 && $countVentas == 0) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No hay datos disponibles para exportar. Crea productos y ventas primero.']);
                exit;
            }
            
            // Exportar datos completos en JSON
            $datos = [];
            
            // Productos
            $stmt = $conexion->query("
                SELECT id, nombre, tipo_producto, codigo_barras, precio_venta, stock, activo
                FROM productos
                WHERE activo = 1
                ORDER BY nombre
            ");
            $datos['productos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ventas últimos 30 días
            $stmt = $conexion->query("
                SELECT v.id, v.fecha, v.total, v.tipo_pago, v.vendedor as vendedor
                FROM ventas v
                WHERE v.fecha >= $last30
                ORDER BY v.fecha DESC
            ");
            $datos['ventas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Usuarios
            $stmt = $conexion->query("SELECT id, username, role FROM usuarios ORDER BY username");
            $datos['usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Metadata
            $datos['metadata'] = [
                'exportado_el' => date('Y-m-d H:i:s'),
                'version' => '5.0',
                'registros' => [
                    'productos' => count($datos['productos']),
                    'ventas' => count($datos['ventas']),
                    'usuarios' => count($datos['usuarios'])
                ]
            ];
            
            // Limpiar buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Guardar en Downloads
            $downloadsPath = resolveDownloadsPath();
            $filename = 'datos_' . date('Y-m-d_His') . '.json';
            $fullPath = $downloadsPath . DIRECTORY_SEPARATOR . $filename;
            
            file_put_contents($fullPath, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Mostrar mensaje de éxito
            header('Content-Type: text/html; charset=utf-8');
            echo '<html><body style="background:#e3f2fd;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;">
                <div style="background:white;padding:40px;border-radius:12px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
                    <h1 style="color:#1976d2;font-size:3em;">✅</h1>
                    <h2>Archivo JSON Guardado</h2>
                    <p style="background:#f5f5f5;padding:15px;border-radius:8px;margin:20px 0;font-family:monospace;word-break:break-all;">' . htmlspecialchars($fullPath) . '</p>
                    <a href="javascript:history.back()" style="display:inline-block;padding:12px 30px;background:#2196f3;color:white;text-decoration:none;border-radius:6px;font-weight:600;">Volver</a>
                </div></body></html>';
            exit;
            
        } else {
            throw new Exception('Formato no válido');
        }
        
    } catch (Throwable $e) {
        logReporte('ERROR exportación: ' . $e->getMessage());
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// ========================================
// REPORTES EXCEL MEJORADOS (v5.2)
// ========================================
$reporte = $_GET['reporte'] ?? '';

// Estilos de encabezado
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12], 
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']], 
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
];

// Estilos alternos para filas
$rowStyleDark = [
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EAF6']],
    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
];

$rowStyleLight = [
    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
];

// Colores para pestañas por sección
$tabColors = [
    'Inventario' => 'FFC107',      // Amarillo
    'Resumen' => '3F51B5',         // Azul profundo
    'Ventas' => 'F44336',          // Rojo
    'Registros' => '9C27B0',       // Púrpura
    'Septimas' => '00BCD4',        // Cian
    'Arcas Servicios' => '4CAF50'  // Verde
];

function autoSizeColumns($sheet) {
    foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }
}

function applyRowStyle($sheet, $row, $style) {
    $cellIterator = $sheet->getRowIterator($row, $row)->current()->getCellIterator();
    foreach ($cellIterator as $cell) {
        $sheet->getStyle($cell->getCoordinate())->applyFromArray($style);
    }
}

function setTabColor($sheet, $colorHex) {
    $sheet->getTabColor()->setRGB($colorHex);
}

try {
    $spreadsheet = new Spreadsheet();
    
    // Nombre de archivo según tipo de reporte
    $filename = match($reporte) {
        'inventario_hoy' => 'Inventario_' . date('Y-m-d_His') . '.xlsx',
        'consolidado' => 'Consolidado_' . date('Y-m-d_His') . '.xlsx',
        'ventas_periodo' => 'Ventas_' . date('Y-m-d_His') . '.xlsx',
        default => 'Reporte_' . date('Y-m-d_His') . '.xlsx'
    };

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
        
        // 1. INVENTARIO (ACTUALIZADO: separar Productos y Preparados)
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario-Productos');
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->setCellValue('C1', 'Tipo');
        $sheet->setCellValue('D1', 'Código Barras');
        $sheet->setCellValue('E1', 'Precio');
        $sheet->setCellValue('F1', 'Stock');
        $sheet->setCellValue('G1', 'Stock Mínimo');
        
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);
        
        $stmt = $conexion->query("SELECT id, nombre, codigo_barras, precio_venta, stock, stock_minimo FROM productos WHERE activo = 1 AND (LOWER(tipo_producto) = 'producto' OR tipo_producto IS NULL) ORDER BY nombre ASC");
        $i = 2;
        $rowNum = 2;
        $totalProductos = 0;
        $totalStockProductos = 0;
        $stockBajoProductos = 0;
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet->setCellValue('A'.$i, $r['id']);
            $sheet->setCellValue('B'.$i, $r['nombre']);
            $sheet->setCellValue('C'.$i, 'Producto');
            $sheet->setCellValueExplicit('D'.$i, $r['codigo_barras'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E'.$i, $r['precio_venta']);
            $sheet->setCellValue('F'.$i, $r['stock']);
            $sheet->setCellValue('G'.$i, $r['stock_minimo'] ?? 10);
            
            // Colorear en rojo si stock bajo
            $stockBajo = $r['stock'] <= ($r['stock_minimo'] ?? 10);
            if ($stockBajo) {
                $style = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFCDD2']],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'C62828']],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ];
            } else {
                $style = ($rowNum % 2 == 0) ? $rowStyleDark : $rowStyleLight;
            }
            $sheet->getStyle('A'.$i.':G'.$i)->applyFromArray($style);

            $totalProductos++;
            $totalStockProductos += (int)$r['stock'];
            if ($stockBajo) {
                $stockBajoProductos++;
            }
            
            $i++;
            $rowNum++;
        }
        $sheet->setCellValue('A'.$i, 'TOTAL INVENTARIO');
        $sheet->setCellValue('B'.$i, $totalProductos);
        $sheet->setCellValue('F'.$i, $totalStockProductos);
        $sheet->setCellValue('G'.$i, 'Stock bajo: ' . $stockBajoProductos);
        $sheet->getStyle('A'.$i.':G'.$i)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D4037']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        autoSizeColumns($sheet);
        setTabColor($sheet, $tabColors['Inventario']);
        
        // 1B. INVENTARIO PREPARADOS (tortas, etc)
        $sheet1b = $spreadsheet->createSheet();
        $sheet1b->setTitle('Inventario-Preparados');
        $sheet1b->setCellValue('A1', 'ID');
        $sheet1b->setCellValue('B1', 'Nombre Preparado');
        $sheet1b->setCellValue('C1', 'Tipo');
        $sheet1b->setCellValue('D1', 'Código Barras');
        $sheet1b->setCellValue('E1', 'Precio');
        $sheet1b->setCellValue('F1', 'Stock');
        $sheet1b->setCellValue('G1', 'Stock Mínimo');
        
        $sheet1b->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet1b->getRowDimension(1)->setRowHeight(25);
        
        $stmtPrep = $conexion->query("SELECT id, nombre, codigo_barras, precio_venta, stock, stock_minimo FROM productos WHERE activo = 1 AND LOWER(tipo_producto) = 'preparado' ORDER BY nombre ASC");
        $i = 2;
        $rowNum = 2;
        $totalPreparados = 0;
        $totalStockPreparados = 0;
        $stockBajoPreparados = 0;
        while ($r = $stmtPrep->fetch(PDO::FETCH_ASSOC)) {
            $sheet1b->setCellValue('A'.$i, $r['id']);
            $sheet1b->setCellValue('B'.$i, $r['nombre']);
            $sheet1b->setCellValue('C'.$i, 'Preparado');
            $sheet1b->setCellValueExplicit('D'.$i, $r['codigo_barras'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet1b->setCellValue('E'.$i, $r['precio_venta']);
            $sheet1b->setCellValue('F'.$i, $r['stock']);
            $sheet1b->setCellValue('G'.$i, $r['stock_minimo'] ?? 10);
            
            $stockBajo = $r['stock'] <= ($r['stock_minimo'] ?? 10);
            if ($stockBajo) {
                $style = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3E5F5']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '6A1B9A']],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ];
            } else {
                $style = ($rowNum % 2 == 0) ? $rowStyleDark : $rowStyleLight;
            }
            $sheet1b->getStyle('A'.$i.':G'.$i)->applyFromArray($style);

            $totalPreparados++;
            $totalStockPreparados += (int)$r['stock'];
            if ($stockBajo) {
                $stockBajoPreparados++;
            }
            
            $i++;
            $rowNum++;
        }
        $sheet1b->setCellValue('A'.$i, 'TOTAL PREPARADOS');
        $sheet1b->setCellValue('B'.$i, $totalPreparados);
        $sheet1b->setCellValue('F'.$i, $totalStockPreparados);
        $sheet1b->setCellValue('G'.$i, 'Stock bajo: ' . $stockBajoPreparados);
        $sheet1b->getStyle('A'.$i.':G'.$i)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6A1B9A']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        autoSizeColumns($sheet1b);
        setTabColor($sheet1b, '9C27B0');

        if ($reporte === 'consolidado') {
            // RESUMEN GENERAL
            $summary = $spreadsheet->getActiveSheet();
            $summary->setTitle('Resumen');
            $summary->setShowGridLines(false);
            $summary->mergeCells('A1:B1');
            $summary->setCellValue('A1', 'Reporte Consolidado');
            $summary->mergeCells('A2:B2');
            $summary->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
            $summary->mergeCells('A3:B3');
            $summary->setCellValue('A3', 'Las cuentas concentran también el adeudo pendiente para evitar duplicidad en el reporte.');

            $summary->getStyle('A1:B1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 16],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A237E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $summary->getStyle('A2:B2')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3949AB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $summary->getStyle('A3:B3')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => 'E8EAF6']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C6BC0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
            ]);

            $ventasProdResumen = $conexion->query("SELECT COUNT(*) AS registros, COALESCE(SUM(v.cantidad),0) AS unidades, COALESCE(SUM(v.total),0) AS total, COALESCE(SUM(CASE WHEN v.tipo_pago='fiado' AND COALESCE(v.fiado_pagado,0)=0 THEN 1 ELSE 0 END),0) AS fiados_pendientes, COALESCE(SUM(CASE WHEN v.tipo_pago='fiado' AND COALESCE(v.fiado_pagado,0)=0 THEN v.total ELSE 0 END),0) AS monto_fiado FROM ventas v LEFT JOIN productos p ON v.producto_id = p.id WHERE LOWER(COALESCE(p.tipo_producto,'')) = 'producto' OR (p.tipo_producto IS NULL AND v.id IS NOT NULL)")->fetch(PDO::FETCH_ASSOC) ?: [];
            $ventasPrepResumen = $conexion->query("SELECT COUNT(*) AS registros, COALESCE(SUM(v.cantidad),0) AS unidades, COALESCE(SUM(v.total),0) AS total, COALESCE(SUM(CASE WHEN v.tipo_pago='fiado' AND COALESCE(v.fiado_pagado,0)=0 THEN 1 ELSE 0 END),0) AS fiados_pendientes, COALESCE(SUM(CASE WHEN v.tipo_pago='fiado' AND COALESCE(v.fiado_pagado,0)=0 THEN v.total ELSE 0 END),0) AS monto_fiado FROM ventas v LEFT JOIN productos p ON v.producto_id = p.id WHERE LOWER(COALESCE(p.tipo_producto,'')) = 'preparado'")->fetch(PDO::FETCH_ASSOC) ?: [];
            $cuentasResumen = $conexion->query("SELECT COUNT(*) AS total_cuentas, COALESCE(SUM(CASE WHEN estado_cuenta='activo' THEN 1 ELSE 0 END),0) AS activas, COALESCE(SUM(CASE WHEN estado_cuenta='inactivo' THEN 1 ELSE 0 END),0) AS inactivas, COALESCE(SUM(CASE WHEN estado_cuenta='bloqueado' THEN 1 ELSE 0 END),0) AS bloqueadas, COALESCE(SUM(saldo_total),0) AS saldo_total FROM cuentas")->fetch(PDO::FETCH_ASSOC) ?: [];
            $adeudosResumen = $conexion->query("SELECT COUNT(*) AS ventas_pendientes, COALESCE(SUM(total),0) AS adeudo_pendiente FROM ventas WHERE tipo_pago='fiado' AND COALESCE(fiado_pagado,0)=0")->fetch(PDO::FETCH_ASSOC) ?: [];
            $cortesResumen = $conexion->query("SELECT COUNT(*) AS total_cortes, COALESCE(SUM(CASE WHEN estado='abierto' THEN 1 ELSE 0 END),0) AS abiertos, COALESCE(SUM(CASE WHEN estado='cerrado' THEN 1 ELSE 0 END),0) AS cerrados, COALESCE(SUM(COALESCE(saldo_inicial,0)),0) AS saldo_inicial, COALESCE(SUM(COALESCE(ingresos_efectivo,0) + COALESCE(ingresos_tarjeta,0) + COALESCE(ingresos_transferencia,0)),0) AS ingresos, COALESCE(SUM(COALESCE(egresos,0)),0) AS egresos, COALESCE(SUM(COALESCE(saldo_final,0)),0) AS saldo_final, COALESCE(SUM(COALESCE(diferencia,0)),0) AS diferencia_total FROM cortes_caja")->fetch(PDO::FETCH_ASSOC) ?: [];
            $registrosResumen = $conexion->query("SELECT COUNT(*) AS total_registros, COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END),0) AS ingresos, COALESCE(SUM(CASE WHEN tipo='egreso' THEN monto ELSE 0 END),0) AS egresos, COALESCE(SUM(CASE WHEN tipo='arca_ingreso' THEN monto ELSE 0 END),0) AS arca_ingresos, COALESCE(SUM(CASE WHEN tipo IN ('arca_egreso','arca_gasto','arca_merma') THEN monto ELSE 0 END),0) AS arca_salidas FROM registros")->fetch(PDO::FETCH_ASSOC) ?: [];
            $septimasResumen = $conexion->query("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN pagado=1 THEN 1 ELSE 0 END),0) AS pagadas, COALESCE(SUM(CASE WHEN pagado=0 THEN 1 ELSE 0 END),0) AS pendientes, COALESCE(SUM(monto),0) AS monto_total FROM septimas")->fetch(PDO::FETCH_ASSOC) ?: [];
            $arcasResumen = $conexion->query("SELECT COUNT(DISTINCT servicio) AS servicios, COALESCE(SUM(CASE WHEN tipo='arca_ingreso' THEN monto ELSE 0 END),0) AS ingresos, COALESCE(SUM(CASE WHEN tipo='arca_egreso' THEN monto ELSE 0 END),0) AS egresos, COALESCE(SUM(CASE WHEN tipo='arca_gasto' THEN monto ELSE 0 END),0) AS gastos, COALESCE(SUM(CASE WHEN tipo='arca_merma' THEN monto ELSE 0 END),0) AS mermas FROM registros WHERE categoria = 'arca'")->fetch(PDO::FETCH_ASSOC) ?: [];
            $inventarioResumen = $conexion->query("SELECT COALESCE(SUM(CASE WHEN LOWER(tipo_producto)='producto' THEN 1 ELSE 0 END),0) AS productos, COALESCE(SUM(CASE WHEN LOWER(tipo_producto)='preparado' THEN 1 ELSE 0 END),0) AS preparados, COALESCE(SUM(CASE WHEN stock <= COALESCE(stock_minimo,10) THEN 1 ELSE 0 END),0) AS stock_bajo FROM productos WHERE activo = 1")->fetch(PDO::FETCH_ASSOC) ?: [];

            $summarySections = [
                [
                    'title' => 'Inventario',
                    'items' => [
                        ['label' => 'Productos activos', 'value' => $inventarioResumen['productos'] ?? 0],
                        ['label' => 'Preparados activos', 'value' => $inventarioResumen['preparados'] ?? 0],
                        ['label' => 'Artículos con stock bajo', 'value' => $inventarioResumen['stock_bajo'] ?? 0],
                    ]
                ],
                [
                    'title' => 'Ventas - Productos',
                    'items' => [
                        ['label' => 'Registros', 'value' => $ventasProdResumen['registros'] ?? 0],
                        ['label' => 'Unidades', 'value' => $ventasProdResumen['unidades'] ?? 0],
                        ['label' => 'Importe total', 'value' => '$' . number_format((float)($ventasProdResumen['total'] ?? 0), 2)],
                        ['label' => 'Fiados pendientes', 'value' => $ventasProdResumen['fiados_pendientes'] ?? 0],
                        ['label' => 'Monto en fiado', 'value' => '$' . number_format((float)($ventasProdResumen['monto_fiado'] ?? 0), 2)],
                    ]
                ],
                [
                    'title' => 'Ventas - Preparados',
                    'items' => [
                        ['label' => 'Registros', 'value' => $ventasPrepResumen['registros'] ?? 0],
                        ['label' => 'Unidades', 'value' => $ventasPrepResumen['unidades'] ?? 0],
                        ['label' => 'Importe total', 'value' => '$' . number_format((float)($ventasPrepResumen['total'] ?? 0), 2)],
                        ['label' => 'Fiados pendientes', 'value' => $ventasPrepResumen['fiados_pendientes'] ?? 0],
                        ['label' => 'Monto en fiado', 'value' => '$' . number_format((float)($ventasPrepResumen['monto_fiado'] ?? 0), 2)],
                    ]
                ],
                [
                    'title' => 'Cuentas',
                    'items' => [
                        ['label' => 'Total de cuentas', 'value' => $cuentasResumen['total_cuentas'] ?? 0],
                        ['label' => 'Activas', 'value' => $cuentasResumen['activas'] ?? 0],
                        ['label' => 'Inactivas', 'value' => $cuentasResumen['inactivas'] ?? 0],
                        ['label' => 'Bloqueadas', 'value' => $cuentasResumen['bloqueadas'] ?? 0],
                        ['label' => 'Saldo total', 'value' => '$' . number_format((float)($cuentasResumen['saldo_total'] ?? 0), 2)],
                        ['label' => 'Adeudo pendiente', 'value' => '$' . number_format((float)($adeudosResumen['adeudo_pendiente'] ?? 0), 2)],
                    ]
                ],
                [
                    'title' => 'Cortes de Caja',
                    'items' => [
                        ['label' => 'Cortes totales', 'value' => $cortesResumen['total_cortes'] ?? 0],
                        ['label' => 'Abiertos', 'value' => $cortesResumen['abiertos'] ?? 0],
                        ['label' => 'Cerrados', 'value' => $cortesResumen['cerrados'] ?? 0],
                        ['label' => 'Saldo inicial acumulado', 'value' => '$' . number_format((float)($cortesResumen['saldo_inicial'] ?? 0), 2)],
                        ['label' => 'Ingresos acumulados', 'value' => '$' . number_format((float)($cortesResumen['ingresos'] ?? 0), 2)],
                        ['label' => 'Egresos acumulados', 'value' => '$' . number_format((float)($cortesResumen['egresos'] ?? 0), 2)],
                        ['label' => 'Saldo final acumulado', 'value' => '$' . number_format((float)($cortesResumen['saldo_final'] ?? 0), 2)],
                        ['label' => 'Diferencia acumulada', 'value' => '$' . number_format((float)($cortesResumen['diferencia_total'] ?? 0), 2)],
                    ]
                ],
                [
                    'title' => 'Registros',
                    'items' => [
                        ['label' => 'Registros totales', 'value' => $registrosResumen['total_registros'] ?? 0],
                        ['label' => 'Ingresos', 'value' => '$' . number_format((float)($registrosResumen['ingresos'] ?? 0), 2)],
                        ['label' => 'Egresos', 'value' => '$' . number_format((float)($registrosResumen['egresos'] ?? 0), 2)],
                        ['label' => 'Arca ingresos', 'value' => '$' . number_format((float)($registrosResumen['arca_ingresos'] ?? 0), 2)],
                        ['label' => 'Arca salidas', 'value' => '$' . number_format((float)($registrosResumen['arca_salidas'] ?? 0), 2)],
                    ]
                ],
                [
                    'title' => 'Séptimas',
                    'items' => [
                        ['label' => 'Registros totales', 'value' => $septimasResumen['total'] ?? 0],
                        ['label' => 'Pagadas', 'value' => $septimasResumen['pagadas'] ?? 0],
                        ['label' => 'Pendientes', 'value' => $septimasResumen['pendientes'] ?? 0],
                        ['label' => 'Monto total', 'value' => '$' . number_format((float)($septimasResumen['monto_total'] ?? 0), 2)],
                    ]
                ],
                [
                    'title' => 'Arcas Servicios',
                    'items' => [
                        ['label' => 'Servicios con movimiento', 'value' => $arcasResumen['servicios'] ?? 0],
                        ['label' => 'Ingresos', 'value' => '$' . number_format((float)($arcasResumen['ingresos'] ?? 0), 2)],
                        ['label' => 'Egresos', 'value' => '$' . number_format((float)($arcasResumen['egresos'] ?? 0), 2)],
                        ['label' => 'Gastos', 'value' => '$' . number_format((float)($arcasResumen['gastos'] ?? 0), 2)],
                        ['label' => 'Mermas', 'value' => '$' . number_format((float)($arcasResumen['mermas'] ?? 0), 2)],
                        ['label' => 'Neto', 'value' => '$' . number_format((float)(($arcasResumen['ingresos'] ?? 0) - ($arcasResumen['egresos'] ?? 0) - ($arcasResumen['gastos'] ?? 0) - ($arcasResumen['mermas'] ?? 0)), 2)],
                    ]
                ]
            ];

            $summaryRow = 4;
            foreach ($summarySections as $section) {
                $summary->mergeCells('A' . $summaryRow . ':B' . $summaryRow);
                $summary->setCellValue('A' . $summaryRow, $section['title']);
                $summary->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3949AB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1A237E']]]
                ]);
                $summaryRow++;

                foreach ($section['items'] as $item) {
                    $summary->setCellValue('A' . $summaryRow, $item['label']);
                    $summary->setCellValue('B' . $summaryRow, $item['value']);
                    $summary->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray([
                        'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D7E2']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
                    ]);
                    $summary->getStyle('A' . $summaryRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '263238']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECEFF1']]
                    ]);
                    $summary->getStyle('B' . $summaryRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0D47A1']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']]
                    ]);
                    $summaryRow++;
                }

                $summaryRow++;
            }

            $summary->getColumnDimension('A')->setWidth(36);
            $summary->getColumnDimension('B')->setWidth(22);
            setTabColor($summary, $tabColors['Resumen']);

            // 2. VENTAS PRODUCTOS
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Ventas-Productos');
            $sheet2->setCellValue('A1', 'Fecha');
            $sheet2->setCellValue('B1', 'Vendedor');
            $sheet2->setCellValue('C1', 'Producto');
            $sheet2->setCellValue('D1', 'Cantidad');
            $sheet2->setCellValue('E1', 'Total');
            $sheet2->setCellValue('F1', 'Tipo Pago');
            $sheet2->setCellValue('G1', 'Cuenta');
            $sheet2->setCellValue('H1', 'Grupo/Región');
            $sheet2->setCellValue('I1', 'Número Cuenta');
            $sheet2->setCellValue('J1', 'Estado Cuenta');
            
            $sheet2->getStyle('A1:J1')->applyFromArray($headerStyle);
            $sheet2->getRowDimension(1)->setRowHeight(25);
            
            $stmtV = $conexion->query("
                SELECT v.id, v.fecha, v.vendedor, p.nombre, v.cantidad, v.total, v.tipo_pago, v.nombre_fiado, v.grupo_fiado, v.celular_fiado, v.fiado_pagado
                FROM ventas v 
                LEFT JOIN productos p ON v.producto_id=p.id 
                WHERE LOWER(p.tipo_producto) = 'producto' OR (p.tipo_producto IS NULL AND v.id IS NOT NULL)
                ORDER BY v.fecha DESC
            ");
            $i=2;
            $rowNum=2;
            $totalCantidadVentas = 0;
            $totalMontoVentas = 0;
            $totalFiadosPendientesVentas = 0;
            while($r=$stmtV->fetch(PDO::FETCH_ASSOC)){
                $sheet2->setCellValue('A'.$i, $r['fecha']);
                $sheet2->setCellValue('B'.$i, $r['vendedor'] ?? 'N/A');
                $sheet2->setCellValue('C'.$i, $r['nombre'] ?? '--');
                $sheet2->setCellValue('D'.$i, $r['cantidad'] ?? 1);
                $sheet2->setCellValue('E'.$i, $r['total']);
                $sheet2->setCellValue('F'.$i, ucfirst($r['tipo_pago']));
                $sheet2->setCellValue('G'.$i, $r['nombre_fiado'] ?? '');
                $sheet2->setCellValue('H'.$i, $r['grupo_fiado'] ?? '');
                $sheet2->setCellValue('I'.$i, $r['celular_fiado'] ?? '');
                $sheet2->setCellValue('J'.$i, (($r['tipo_pago'] ?? '') === 'fiado') ? ((intval($r['fiado_pagado'] ?? 0) === 1) ? 'Pagado' : 'Pendiente') : 'N/A');

                $totalCantidadVentas += (int)($r['cantidad'] ?? 0);
                $totalMontoVentas += (float)($r['total'] ?? 0);
                if (($r['tipo_pago'] ?? '') === 'fiado' && intval($r['fiado_pagado'] ?? 0) === 0) {
                    $totalFiadosPendientesVentas++;
                }
                
                $style = ($rowNum % 2 == 0) ? $rowStyleDark : $rowStyleLight;
                $sheet2->getStyle('A'.$i.':J'.$i)->applyFromArray($style);
                
                $i++;
                $rowNum++;
            }
            $sheet2->setCellValue('A'.$i, 'TOTAL VENTAS PRODUCTOS');
            $sheet2->setCellValue('B'.$i, max(0, $i - 2));
            $sheet2->setCellValue('D'.$i, $totalCantidadVentas);
            $sheet2->setCellValue('E'.$i, $totalMontoVentas);
            $sheet2->setCellValue('J'.$i, 'Fiados pendientes: ' . $totalFiadosPendientesVentas);
            $sheet2->getStyle('A'.$i.':J'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A237E']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            autoSizeColumns($sheet2);
            setTabColor($sheet2, $tabColors['Ventas']);
            
            // 2B. VENTAS PREPARADOS
            $sheet2b = $spreadsheet->createSheet();
            $sheet2b->setTitle('Ventas-Preparados');
            $sheet2b->setCellValue('A1', 'Fecha');
            $sheet2b->setCellValue('B1', 'Vendedor');
            $sheet2b->setCellValue('C1', 'Preparado');
            $sheet2b->setCellValue('D1', 'Cantidad');
            $sheet2b->setCellValue('E1', 'Total');
            $sheet2b->setCellValue('F1', 'Tipo Pago');
            $sheet2b->setCellValue('G1', 'Cuenta');
            $sheet2b->setCellValue('H1', 'Grupo/Región');
            $sheet2b->setCellValue('I1', 'Número Cuenta');
            $sheet2b->setCellValue('J1', 'Estado Cuenta');
            
            $sheet2b->getStyle('A1:J1')->applyFromArray($headerStyle);
            $sheet2b->getRowDimension(1)->setRowHeight(25);
            
            $stmtVp = $conexion->query("
                SELECT v.id, v.fecha, v.vendedor, p.nombre, v.cantidad, v.total, v.tipo_pago, v.nombre_fiado, v.grupo_fiado, v.celular_fiado, v.fiado_pagado
                FROM ventas v 
                LEFT JOIN productos p ON v.producto_id=p.id 
                WHERE LOWER(p.tipo_producto) = 'preparado'
                ORDER BY v.fecha DESC
            ");
            $i=2;
            $rowNum=2;
            $totalCantidadPreparados = 0;
            $totalMontoPreparados = 0;
            $totalFiadosPendientesPreparados = 0;
            while($r=$stmtVp->fetch(PDO::FETCH_ASSOC)){
                $sheet2b->setCellValue('A'.$i, $r['fecha']);
                $sheet2b->setCellValue('B'.$i, $r['vendedor'] ?? 'N/A');
                $sheet2b->setCellValue('C'.$i, $r['nombre'] ?? '--');
                $sheet2b->setCellValue('D'.$i, $r['cantidad'] ?? 1);
                $sheet2b->setCellValue('E'.$i, $r['total']);
                $sheet2b->setCellValue('F'.$i, ucfirst($r['tipo_pago']));
                $sheet2b->setCellValue('G'.$i, $r['nombre_fiado'] ?? '');
                $sheet2b->setCellValue('H'.$i, $r['grupo_fiado'] ?? '');
                $sheet2b->setCellValue('I'.$i, $r['celular_fiado'] ?? '');
                $sheet2b->setCellValue('J'.$i, (($r['tipo_pago'] ?? '') === 'fiado') ? ((intval($r['fiado_pagado'] ?? 0) === 1) ? 'Pagado' : 'Pendiente') : 'N/A');

                $totalCantidadPreparados += (int)($r['cantidad'] ?? 0);
                $totalMontoPreparados += (float)($r['total'] ?? 0);
                if (($r['tipo_pago'] ?? '') === 'fiado' && intval($r['fiado_pagado'] ?? 0) === 0) {
                    $totalFiadosPendientesPreparados++;
                }
                
                $style = ($rowNum % 2 == 0) ? $rowStyleDark : $rowStyleLight;
                $sheet2b->getStyle('A'.$i.':J'.$i)->applyFromArray($style);
                
                $i++;
                $rowNum++;
            }
            $sheet2b->setCellValue('A'.$i, 'TOTAL VENTAS PREPARADOS');
            $sheet2b->setCellValue('B'.$i, max(0, $i - 2));
            $sheet2b->setCellValue('D'.$i, $totalCantidadPreparados);
            $sheet2b->setCellValue('E'.$i, $totalMontoPreparados);
            $sheet2b->setCellValue('J'.$i, 'Fiados pendientes: ' . $totalFiadosPendientesPreparados);
            $sheet2b->getStyle('A'.$i.':J'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A237E']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            autoSizeColumns($sheet2b);
            setTabColor($sheet2b, 'E91E63');

            // 3. REGISTROS (ingresos/egresos/merma/arca)
            $sheet4 = $spreadsheet->createSheet();
            $sheet4->setTitle('Registros');
            $sheet4->setCellValue('A1','Fecha');
            $sheet4->setCellValue('B1','Tipo');
            $sheet4->setCellValue('C1','Categoría');
            $sheet4->setCellValue('D1','Servicio');
            $sheet4->setCellValue('E1','Concepto');
            $sheet4->setCellValue('F1','Monto');
            $sheet4->setCellValue('G1','Usuario');
            $sheet4->setCellValue('H1','ID');
            
            $sheet4->getStyle('A1:H1')->applyFromArray($headerStyle);
            $sheet4->getRowDimension(1)->setRowHeight(25);
            
            $stmtR = $conexion->query("SELECT id, fecha, tipo, categoria, servicio, concepto, monto, usuario FROM registros ORDER BY fecha DESC");
            $i=2;
            $rowNum=2;
            $colorMap = [
                'arca_ingreso' => 'C8E6C9',  // Verde claro
                'arca_egreso' => 'FFCCBC',   // Naranja claro
                'arca_gasto' => 'FFE0B2',    // Naranja más claro
                'arca_merma' => 'F8BBD0',    // Rosa
                'ingreso' => 'E1F5FE',       // Azul claro
                'egreso' => 'FCE4EC'         // Rosa claro
            ];
            $totalRegistros = 0;
            $totalMontoRegistros = 0;
            
            while($r=$stmtR->fetch(PDO::FETCH_ASSOC)){
                $sheet4->setCellValue('A'.$i, $r['fecha']);
                $sheet4->setCellValue('B'.$i, $r['tipo']);
                $sheet4->setCellValue('C'.$i, $r['categoria']);
                $sheet4->setCellValue('D'.$i, $r['servicio'] ?? 'N/A');
                $sheet4->setCellValue('E'.$i, $r['concepto']);
                $sheet4->setCellValue('F'.$i, $r['monto']);
                $sheet4->setCellValue('G'.$i, $r['usuario']);
                $sheet4->setCellValue('H'.$i, $r['id']);

                $totalRegistros++;
                $totalMontoRegistros += (float)($r['monto'] ?? 0);
                
                // Color según tipo
                $color = $colorMap[$r['tipo']] ?? 'FFFFFF';
                $sheet4->getStyle('A'.$i.':H'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
                $rowNum++;
            }
            $sheet4->setCellValue('A'.$i, 'TOTAL REGISTROS');
            $sheet4->setCellValue('F'.$i, $totalMontoRegistros);
            $sheet4->setCellValue('H'.$i, $totalRegistros);
            $sheet4->getStyle('A'.$i.':H'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7B1FA2']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            autoSizeColumns($sheet4);
            setTabColor($sheet4, $tabColors['Registros']);

            // 5. SÉPTIMAS
            $sheet5 = $spreadsheet->createSheet();
            $sheet5->setTitle('Septimas');
            $sheet5->setCellValue('A1','Fecha');
            $sheet5->setCellValue('B1','Padrino/Origen');
            $sheet5->setCellValue('C1','Monto');
            $sheet5->setCellValue('D1','Tipo');
            $sheet5->setCellValue('E1','Servicio');
            $sheet5->setCellValue('F1','Estado');
            $sheet5->setCellValue('G1','Días Registrada');
            $sheet5->setCellValue('H1','ID');
            
            $sheet5->getStyle('A1:H1')->applyFromArray($headerStyle);
            $sheet5->getRowDimension(1)->setRowHeight(25);
            
            $stmtS = $conexion->query("SELECT id, fecha, nombre_padrino, monto, tipo, servicio, pagado FROM septimas ORDER BY fecha DESC");
            $i=2;
            $rowNum=2;
            $totalSeptimas = 0;
            $septimasPagadas = 0;
            $septimasPendientes = 0;
            while($r=$stmtS->fetch(PDO::FETCH_ASSOC)){
                $dias = floor((time() - strtotime($r['fecha'])) / 86400);
                $estado = $r['pagado'] ? 'Pagada' : 'Pendiente';
                $color = $r['pagado'] ? 'C8E6C9' : 'FFE0B2';
                
                $sheet5->setCellValue('A'.$i, $r['fecha']);
                $sheet5->setCellValue('B'.$i, $r['nombre_padrino']);
                $sheet5->setCellValue('C'.$i, $r['monto']);
                $sheet5->setCellValue('D'.$i, $r['tipo'] ?? 'normal');
                $sheet5->setCellValue('E'.$i, $r['servicio'] ?? 'N/A');
                $sheet5->setCellValue('F'.$i, $estado);
                $sheet5->setCellValue('G'.$i, $dias . ' días');
                $sheet5->setCellValue('H'.$i, $r['id']);

                $totalSeptimas += (float)($r['monto'] ?? 0);
                if (!empty($r['pagado'])) {
                    $septimasPagadas++;
                } else {
                    $septimasPendientes++;
                }
                
                $sheet5->getStyle('A'.$i.':H'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
                $rowNum++;
            }
            $sheet5->setCellValue('A'.$i, 'TOTAL SÉPTIMAS');
            $sheet5->setCellValue('C'.$i, $totalSeptimas);
            $sheet5->setCellValue('F'.$i, 'Pagadas: ' . $septimasPagadas . ' / Pendientes: ' . $septimasPendientes);
            $sheet5->getStyle('A'.$i.':H'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            autoSizeColumns($sheet5);
            setTabColor($sheet5, $tabColors['Septimas']);

            // 6. ARCAS DE SERVICIO (resumen detallado)
            $sheet6 = $spreadsheet->createSheet();
            $sheet6->setTitle('Arcas Servicios');
            $sheet6->setCellValue('A1','Servicio');
            $sheet6->setCellValue('B1','Ingresos');
            $sheet6->setCellValue('C1','Egresos');
            $sheet6->setCellValue('D1','Gastos');
            $sheet6->setCellValue('E1','Merma');
            $sheet6->setCellValue('F1','Concepto');
            $sheet6->setCellValue('G1','Subtotal');
            
            $sheet6->getStyle('A1:G1')->applyFromArray($headerStyle);
            $sheet6->getRowDimension(1)->setRowHeight(25);
            
            $stmtA = $conexion->query("SELECT servicio, tipo, concepto, SUM(monto) as total FROM registros WHERE categoria = 'arca' GROUP BY servicio, tipo, concepto ORDER BY servicio, tipo");
            $agregado = [];
            while($r=$stmtA->fetch(PDO::FETCH_ASSOC)){
                $srv = $r['servicio'] ?: 'Sin Servicio';
                if (!isset($agregado[$srv])) {
                    $agregado[$srv] = ['ingresos'=>0,'egresos'=>0,'gastos'=>0,'merma'=>0, 'detalles'=>[]];
                }
                switch($r['tipo']){
                    case 'arca_ingreso': 
                        $agregado[$srv]['ingresos'] += $r['total'];
                        $agregado[$srv]['detalles'][] = "Ingreso: " . ($r['concepto'] ?: 'S/concepto') . " ($" . $r['total'] . ")";
                        break;
                    case 'arca_egreso': 
                        $agregado[$srv]['egresos'] += $r['total'];
                        $agregado[$srv]['detalles'][] = "Egreso: " . ($r['concepto'] ?: 'S/concepto') . " ($" . $r['total'] . ")";
                        break;
                    case 'arca_gasto': 
                        $agregado[$srv]['gastos'] += $r['total'];
                        $agregado[$srv]['detalles'][] = "Gasto: " . ($r['concepto'] ?: 'S/concepto') . " ($" . $r['total'] . ")";
                        break;
                    case 'arca_merma': 
                        $agregado[$srv]['merma'] += $r['total'];
                        $agregado[$srv]['detalles'][] = "Merma: " . ($r['concepto'] ?: 'S/concepto') . " ($" . $r['total'] . ")";
                        break;
                }
            }
            
            $totalGeneral = 0;
            $i=2;
            foreach ($agregado as $srv => $vals) {
                $total = ($vals['ingresos'] - $vals['egresos'] - $vals['gastos'] - $vals['merma']);
                $totalGeneral += $total;
                $conceptoDetalle = implode(" | ", array_slice($vals['detalles'], 0, 2));
                if (count($vals['detalles']) > 2) {
                    $conceptoDetalle .= " (+".( count($vals['detalles']) - 2).")";
                }
                
                $sheet6->setCellValue('A'.$i, $srv);
                $sheet6->setCellValue('B'.$i, $vals['ingresos']);
                $sheet6->setCellValue('C'.$i, $vals['egresos']);
                $sheet6->setCellValue('D'.$i, $vals['gastos']);
                $sheet6->setCellValue('E'.$i, $vals['merma']);
                $sheet6->setCellValue('F'.$i, $conceptoDetalle);
                $sheet6->setCellValue('G'.$i, $total);
                
                // Color según profitabilidad
                $pct = $vals['ingresos'] > 0 ? ($total / $vals['ingresos']) : 0;
                if ($pct > 0.5) $color = 'C8E6C9';  // Verde
                elseif ($pct > 0) $color = 'FFF9C4'; // Amarillo
                else $color = 'FFCCBC';             // Naranja
                
                $sheet6->getStyle('A'.$i.':G'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
            }
            
            // Fila total
            $sheet6->setCellValue('A'.$i, 'TOTAL GENERAL');
            $sheet6->setCellValue('G'.$i, $totalGeneral);
            $sheet6->getStyle('A'.$i.':G'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            
            autoSizeColumns($sheet6);
            setTabColor($sheet6, $tabColors['Arcas Servicios']);
            
            // 7. CORTES DE CAJA
            $sheet7 = $spreadsheet->createSheet();
            $sheet7->setTitle('Cortes Caja');
            $sheet7->setCellValue('A1','ID Corte');
            $sheet7->setCellValue('B1','Apertura');
            $sheet7->setCellValue('C1','Cierre');
            $sheet7->setCellValue('D1','Admin Apertura');
            $sheet7->setCellValue('E1','Admin Cierre');
            $sheet7->setCellValue('F1','Saldo Inicial');
            $sheet7->setCellValue('G1','Ingresos Efectivo');
            $sheet7->setCellValue('H1','Ingresos Tarjeta');
            $sheet7->setCellValue('I1','Ingresos Transferencia');
            $sheet7->setCellValue('J1','Egresos');
            $sheet7->setCellValue('K1','Saldo Final');
            $sheet7->setCellValue('L1','Diferencia');
            $sheet7->setCellValue('M1','Estado');
            $sheet7->setCellValue('N1','Notas');
            
            $sheet7->getStyle('A1:N1')->applyFromArray($headerStyle);
            $sheet7->getRowDimension(1)->setRowHeight(25);
            
            $stmtC = $conexion->query("SELECT * FROM cortes_caja ORDER BY fecha_apertura DESC");
            $i = 2;
            $totalCortes = 0;
            $totalSaldoInicial = 0;
            $totalIngresosCorte = 0;
            $totalEgresosCorte = 0;
            $totalSaldoFinal = 0;
            $totalDiferencia = 0;
            while ($r = $stmtC->fetch(PDO::FETCH_ASSOC)) {
                $sheet7->setCellValue('A'.$i, $r['id']);
                $sheet7->setCellValue('B'.$i, $r['fecha_apertura']);
                $sheet7->setCellValue('C'.$i, $r['fecha_cierre'] ?? 'Sin cerrar');
                $sheet7->setCellValue('D'.$i, $r['usuario_apertura']);
                $sheet7->setCellValue('E'.$i, $r['usuario_cierre'] ?? '-');
                $sheet7->setCellValue('F'.$i, $r['saldo_inicial']);
                $sheet7->setCellValue('G'.$i, $r['ingresos_efectivo']);
                $sheet7->setCellValue('H'.$i, $r['ingresos_tarjeta']);
                $sheet7->setCellValue('I'.$i, $r['ingresos_transferencia']);
                $sheet7->setCellValue('J'.$i, $r['egresos']);
                $sheet7->setCellValue('K'.$i, $r['saldo_final']);
                $sheet7->setCellValue('L'.$i, $r['diferencia'] ?? 0);
                $sheet7->setCellValue('M'.$i, ucfirst($r['estado']));
                $sheet7->setCellValue('N'.$i, $r['notas'] ?? '');

                $totalCortes++;
                $totalSaldoInicial += (float)($r['saldo_inicial'] ?? 0);
                $totalIngresosCorte += (float)($r['ingresos_efectivo'] ?? 0) + (float)($r['ingresos_tarjeta'] ?? 0) + (float)($r['ingresos_transferencia'] ?? 0);
                $totalEgresosCorte += (float)($r['egresos'] ?? 0);
                $totalSaldoFinal += (float)($r['saldo_final'] ?? 0);
                $totalDiferencia += (float)($r['diferencia'] ?? 0);
                
                $color = $r['estado'] === 'cerrado' ? 'C8E6C9' : 'FFF9C4';
                $sheet7->getStyle('A'.$i.':N'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
            }
            $sheet7->setCellValue('A'.$i, 'TOTAL CORTES');
            $sheet7->setCellValue('F'.$i, $totalSaldoInicial);
            $sheet7->setCellValue('G'.$i, $totalIngresosCorte);
            $sheet7->setCellValue('J'.$i, $totalEgresosCorte);
            $sheet7->setCellValue('K'.$i, $totalSaldoFinal);
            $sheet7->setCellValue('L'.$i, $totalDiferencia);
            $sheet7->setCellValue('M'.$i, $totalCortes);
            $sheet7->getStyle('A'.$i.':N'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            autoSizeColumns($sheet7);
            setTabColor($sheet7, '4CAF50');
            
            // 8. CUENTAS
            $sheet8 = $spreadsheet->createSheet();
            $sheet8->setTitle('Cuentas');
            $sheet8->setCellValue('A1','ID Cuenta');
            $sheet8->setCellValue('B1','Nombre Cuenta');
            $sheet8->setCellValue('C1','Celular');
            $sheet8->setCellValue('D1','Grupo/Región');
            $sheet8->setCellValue('E1','Estado Cuenta');
            $sheet8->setCellValue('F1','Saldo Cuenta');
            $sheet8->setCellValue('G1','Adeudo Pendiente (Ventas)');
            $sheet8->setCellValue('H1','Primera Compra');
            $sheet8->setCellValue('I1','Última Compra');
            $sheet8->setCellValue('J1','Días de Actividad');
            $sheet8->setCellValue('K1','Notas');
            
            $sheet8->getStyle('A1:K1')->applyFromArray($headerStyle);
            $sheet8->getRowDimension(1)->setRowHeight(25);
            
            $stmtCt = $conexion->query("SELECT c.*, (SELECT COALESCE(SUM(v.total), 0) FROM ventas v WHERE v.tipo_pago='fiado' AND v.fiado_pagado=0 AND REPLACE(REPLACE(LOWER(TRIM(v.nombre_fiado)), '.', ''), ',', '') = REPLACE(REPLACE(LOWER(TRIM(c.nombre_cuenta)), '.', ''), ',', '')) as adeudo_pendiente FROM cuentas c ORDER BY c.fecha_ultimo_compra DESC NULLS LAST");
            $i = 2;
            $totalCuentas = 0;
            $totalSaldoCuentas = 0;
            $totalAdeudoCuentas = 0;
            while ($r = $stmtCt->fetch(PDO::FETCH_ASSOC)) {
                $dias = $r['fecha_primer_compra'] ? floor((time() - strtotime($r['fecha_primer_compra'])) / 86400) : 0;
                $region = '';
                if (!empty($r['notas']) && preg_match('/(?:REGION|REGIÓN|GRUPO)\s*:\s*([^\n\|;]+)/i', $r['notas'], $mRegion)) {
                    $region = trim($mRegion[1]);
                }
                
                $sheet8->setCellValue('A'.$i, $r['id']);
                $sheet8->setCellValue('B'.$i, $r['nombre_cuenta']);
                $sheet8->setCellValue('C'.$i, $r['celular'] ?? '-');
                $sheet8->setCellValue('D'.$i, $region);
                $sheet8->setCellValue('E'.$i, ucfirst($r['estado_cuenta']));
                $sheet8->setCellValue('F'.$i, $r['saldo_total']);
                $sheet8->setCellValue('G'.$i, $r['adeudo_pendiente']);
                $sheet8->setCellValue('H'.$i, $r['fecha_primer_compra'] ?? '-');
                $sheet8->setCellValue('I'.$i, $r['fecha_ultimo_compra'] ?? '-');
                $sheet8->setCellValue('J'.$i, $dias . ' días');
                $sheet8->setCellValue('K'.$i, $r['notas'] ?? '');

                $totalCuentas++;
                $totalSaldoCuentas += (float)($r['saldo_total'] ?? 0);
                $totalAdeudoCuentas += (float)($r['adeudo_pendiente'] ?? 0);
                
                $colorMap2 = [
                    'activo' => 'C8E6C9',
                    'inactivo' => 'F5F5F5',
                    'bloqueado' => 'FFEBEE'
                ];
                $color = $colorMap2[$r['estado_cuenta']] ?? 'FFFFFF';
                
                $sheet8->getStyle('A'.$i.':K'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
            }
            $sheet8->setCellValue('A'.$i, 'TOTAL CUENTAS');
            $sheet8->setCellValue('E'.$i, $totalCuentas);
            $sheet8->setCellValue('F'.$i, $totalSaldoCuentas);
            $sheet8->setCellValue('G'.$i, $totalAdeudoCuentas);
            $sheet8->getStyle('A'.$i.':K'.$i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EF6C00']],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
            ]);
            autoSizeColumns($sheet8);
            setTabColor($sheet8, 'FF9800');
        }
    }

    // --- DESCARGAR ARCHIVO (RESPUESTA SIMPLE DE TEXTO) ---
    
    // Limpiar TODO el output buffer antes de guardar
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Crear carpeta de descargas en el directorio del usuario
    $downloadsPath = resolveDownloadsPath();
    
    // Asegurar nombre de archivo único
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    $fullPath = $downloadsPath . DIRECTORY_SEPARATOR . $safeName;
    
    // Si el archivo existe, agregar número
    $counter = 1;
    $pathInfo = pathinfo($fullPath);
    while (file_exists($fullPath)) {
        $fullPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 
                    $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
        $counter++;
    }
    
    try {
        // Guardar directamente en Downloads
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
        
        // Verificar que se guardó correctamente
        if (!file_exists($fullPath) || filesize($fullPath) == 0) {
            throw new Exception('No se pudo guardar el archivo');
        }
        
        $fileSize = filesize($fullPath);
        $fileName = basename($fullPath);
        
        // Liberar memoria
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        
        // Retornar texto simple separado por pipes
        header('Content-Type: text/plain; charset=utf-8');
        echo 'SUCCESS|' . $fileName . '|' . str_replace('\\', '/', $downloadsPath) . '|' . $fileSize;
        exit;
        
    } catch (Exception $saveError) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'ERROR|' . $saveError->getMessage();
        exit;
    }

} catch (Throwable $e) {
    logReporte('ERROR reporte: ' . $e->getMessage());
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ERROR|' . $e->getMessage();
    exit;
}
?>