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
                SELECT id, nombre, codigo_barras, precio_venta, stock, foto_url, activo
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
    'Ventas' => 'F44336',          // Rojo
    'Deudores' => 'FF9800',        // Naranja
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
        
        // 1. INVENTARIO
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->setCellValue('C1', 'Código Barras');
        $sheet->setCellValue('D1', 'Precio');
        $sheet->setCellValue('E1', 'Stock');
        $sheet->setCellValue('F1', 'Stock Mínimo');
        
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);
        
        $stmt = $conexion->query("SELECT id, nombre, codigo_barras, precio_venta, stock, stock_minimo FROM productos WHERE activo = 1 ORDER BY nombre ASC");
        $i = 2;
        $rowNum = 2;
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sheet->setCellValue('A'.$i, $r['id']);
            $sheet->setCellValue('B'.$i, $r['nombre']);
            $sheet->setCellValueExplicit('C'.$i, $r['codigo_barras'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D'.$i, $r['precio_venta']);
            $sheet->setCellValue('E'.$i, $r['stock']);
            $sheet->setCellValue('F'.$i, $r['stock_minimo'] ?? 10);
            
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
            $sheet->getStyle('A'.$i.':F'.$i)->applyFromArray($style);
            
            $i++;
            $rowNum++;
        }
        autoSizeColumns($sheet);
        setTabColor($sheet, $tabColors['Inventario']);

        if ($reporte === 'consolidado') {
            // 2. VENTAS
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Ventas');
            $sheet2->setCellValue('A1', 'Fecha');
            $sheet2->setCellValue('B1', 'Vendedor');
            $sheet2->setCellValue('C1', 'Producto');
            $sheet2->setCellValue('D1', 'Cantidad');
            $sheet2->setCellValue('E1', 'Total');
            $sheet2->setCellValue('F1', 'Tipo Pago');
            $sheet2->setCellValue('G1', 'Nombre Fiado');
            
            $sheet2->getStyle('A1:G1')->applyFromArray($headerStyle);
            $sheet2->getRowDimension(1)->setRowHeight(25);
            
            $stmtV = $conexion->query("SELECT v.id, v.fecha, v.vendedor, p.nombre, v.cantidad, v.total, v.tipo_pago, v.nombre_fiado FROM ventas v LEFT JOIN productos p ON v.producto_id=p.id ORDER BY v.fecha DESC");
            $i=2;
            $rowNum=2;
            while($r=$stmtV->fetch(PDO::FETCH_ASSOC)){
                $sheet2->setCellValue('A'.$i, $r['fecha']);
                $sheet2->setCellValue('B'.$i, $r['vendedor'] ?? 'N/A');
                $sheet2->setCellValue('C'.$i, $r['nombre'] ?? '--');
                $sheet2->setCellValue('D'.$i, $r['cantidad'] ?? 1);
                $sheet2->setCellValue('E'.$i, $r['total']);
                $sheet2->setCellValue('F'.$i, ucfirst($r['tipo_pago']));
                $sheet2->setCellValue('G'.$i, $r['nombre_fiado'] ?? '');
                
                // Estilos alternados
                $style = ($rowNum % 2 == 0) ? $rowStyleDark : $rowStyleLight;
                $sheet2->getStyle('A'.$i.':G'.$i)->applyFromArray($style);
                
                $i++;
                $rowNum++;
            }
            autoSizeColumns($sheet2);
            setTabColor($sheet2, $tabColors['Ventas']);

            // 3. DEUDORES
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Deudores');
            $sheet3->setCellValue('A1', 'Nombre Normalizado');
            $sheet3->setCellValue('B1', 'Deuda Total');
            $sheet3->setCellValue('C1', 'Transacciones');
            $sheet3->setCellValue('D1', 'Última Transacción');
            $sheet3->setCellValue('E1', 'Hora');
            $sheet3->setCellValue('F1', 'Días de Deuda');
            
            $sheet3->getStyle('A1:F1')->applyFromArray($headerStyle);
            $sheet3->getRowDimension(1)->setRowHeight(25);
            
            $stmtD = $conexion->query("SELECT nombre_fiado, SUM(total) as d, COUNT(*) as cnt, MAX(fecha) as ultima FROM ventas WHERE tipo_pago='fiado' AND fiado_pagado=0 GROUP BY nombre_fiado ORDER BY d DESC");
            $i=2;
            $rowNum=2;
            while($r=$stmtD->fetch(PDO::FETCH_ASSOC)){
                $ultimaFecha = strtotime($r['ultima']);
                $dias = floor((time() - $ultimaFecha) / 86400);
                $hora = date('H:i:s', $ultimaFecha);
                $fecha = date('Y-m-d', $ultimaFecha);
                
                $sheet3->setCellValue('A'.$i, $r['nombre_fiado']);
                $sheet3->setCellValue('B'.$i, $r['d']);
                $sheet3->setCellValue('C'.$i, $r['cnt']);
                $sheet3->setCellValue('D'.$i, $fecha);
                $sheet3->setCellValue('E'.$i, $hora);
                $sheet3->setCellValue('F'.$i, $dias . ' días');
                
                // Color rojo si deuda es muy vieja
                if ($dias > 30) {
                    $sheet3->getStyle('A'.$i.':F'.$i)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']],
                        'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                    ]);
                } else {
                    $style = ($rowNum % 2 == 0) ? $rowStyleDark : $rowStyleLight;
                    $sheet3->getStyle('A'.$i.':F'.$i)->applyFromArray($style);
                }
                
                $i++;
                $rowNum++;
            }
            autoSizeColumns($sheet3);
            setTabColor($sheet3, $tabColors['Deudores']);

            // 4. REGISTROS (ingresos/egresos/merma/arca)
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
            
            while($r=$stmtR->fetch(PDO::FETCH_ASSOC)){
                $sheet4->setCellValue('A'.$i, $r['fecha']);
                $sheet4->setCellValue('B'.$i, $r['tipo']);
                $sheet4->setCellValue('C'.$i, $r['categoria']);
                $sheet4->setCellValue('D'.$i, $r['servicio'] ?? 'N/A');
                $sheet4->setCellValue('E'.$i, $r['concepto']);
                $sheet4->setCellValue('F'.$i, $r['monto']);
                $sheet4->setCellValue('G'.$i, $r['usuario']);
                $sheet4->setCellValue('H'.$i, $r['id']);
                
                // Color según tipo
                $color = $colorMap[$r['tipo']] ?? 'FFFFFF';
                $sheet4->getStyle('A'.$i.':H'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
                $rowNum++;
            }
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
                
                $sheet5->getStyle('A'.$i.':H'.$i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
                ]);
                
                $i++;
                $rowNum++;
            }
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