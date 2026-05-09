<?php
// api/api_reportes_mejorado.php - v6.0 PROFESIONAL PARA JUNTA HACIENDA
// Con logo, encabezados profesionales, validaciones y análisis financiero completo

error_reporting(0);
ob_implicit_flush(0);

session_start();

$bufferLevel = ob_get_level();

include 'db.php';

while (ob_get_level() > $bufferLevel) {
    ob_end_clean();
}

$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Librería Excel no encontrada']);
    exit;
}

require $vendorPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// ==============================================
// FUNCIONES DE UTILIDAD MEJORADAS
// ==============================================

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

function agregarLogoEncabezado($sheet, $titulo, $subtitulo = '') {
    // Encabezado profesional SIN logo (para evitar problemas de compatibilidad)
    // Logo será manual en futuros ajustes de compatibilidad
    $sheet->mergeCells('B1:H1');
    $sheet->setCellValue('B1', 'Tienda Regional - ' . $titulo);
    $sheet->getStyle('B1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(50);
    
    // Subtítulo
    $sheet->mergeCells('B2:H2');
    $sheet->setCellValue('B2', $subtitulo ?: 'Reporte Financiero - ' . date('d/m/Y H:i'));
    $sheet->getStyle('B2')->applyFromArray([
        'font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    $sheet->getRowDimension(2)->setRowHeight(25);
    
    return 3; // Siguiente fila disponible
}

function estilosEncabezado() {
    return [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
    ];
}

function estilosFilaAlterna($esOscura = false) {
    return [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $esOscura ? 'E8EAF6' : 'FFFFFF']],
        'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
    ];
}

function autoSizeColumns($sheet) {
    foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }
}

function setSheetTabColor($sheet, $colorHex) {
    $sheet->getTabColor()->setRGB($colorHex);
}

// ==============================================
// VALIDACIONES INICIALES
// ==============================================

if (!isset($_SESSION['usuario']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$reporte = $_GET['reporte'] ?? 'consolidado';
$validReports = ['inventario_hoy', 'utilidad_productos', 'consolidado'];

if (!in_array($reporte, $validReports)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de reporte inválido']);
    exit;
}

try {
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()
        ->setCreator('Tienda Regional TG Gestión')
        ->setTitle('Reporte - ' . ucfirst($reporte))
        ->setSubject('Reporte Financiero')
        ->setDescription('Generado automáticamente por TG Gestión Establos V10.8');

    // ==============================================
    // REPORTE CONSOLIDADO (JUNTA HACIENDA)
    // ==============================================
    if ($reporte === 'consolidado') {
        
        // ====== HOJA 0: RESUMEN EJECUTIVO ======
        $sheetResumen = $spreadsheet->getActiveSheet();
        $fila = agregarLogoEncabezado($sheetResumen, 'RESUMEN EJECUTIVO', 'Estado Financiero Integral');
        
        $sheetResumen->setTitle('ResumenEjecutivo');
        setSheetTabColor($sheetResumen, '1E3A8A');
        
        // Obtener datos de negocio
        $totalVentas = (float)($conexion->query("SELECT COALESCE(SUM(total), 0) as monto FROM ventas")->fetch(PDO::FETCH_ASSOC)['monto'] ?? 0);
        $totalGastos = (float)($conexion->query("SELECT COALESCE(SUM(monto), 0) as monto FROM registros WHERE tipo IN ('egreso', 'arca_gasto')")->fetch(PDO::FETCH_ASSOC)['monto'] ?? 0);
        $totalFiados = (float)($conexion->query("SELECT COALESCE(SUM(total), 0) as monto FROM ventas WHERE tipo_pago='fiado' AND fiado_pagado=0")->fetch(PDO::FETCH_ASSOC)['monto'] ?? 0);
        $totalInventario = (float)($conexion->query("SELECT COALESCE(SUM(precio_venta * stock), 0) as monto FROM productos WHERE activo=1")->fetch(PDO::FETCH_ASSOC)['monto'] ?? 0);
        $inversionStock = (float)($conexion->query("SELECT COALESCE(SUM(precio_compra * stock), 0) as monto FROM productos WHERE activo=1")->fetch(PDO::FETCH_ASSOC)['monto'] ?? 0);
        $utilidadPotencial = $totalInventario - $inversionStock;
        $rentabilidad = $totalVentas > 0 ? (($totalVentas - $totalGastos) / $totalVentas) * 100 : 0;
        $margineBruto = $totalVentas > 0 ? ($utilidadPotencial / $totalVentas) * 100 : 0;

        $ventasProductos = $conexion->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as monto, COALESCE(SUM(cantidad),0) as unidades FROM ventas v LEFT JOIN productos p ON v.producto_id = p.id WHERE LOWER(COALESCE(p.tipo_producto,'')) IN ('producto','')")->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'monto' => 0, 'unidades' => 0];
        $ventasPreparados = $conexion->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as monto, COALESCE(SUM(cantidad),0) as unidades FROM ventas v LEFT JOIN productos p ON v.producto_id = p.id WHERE LOWER(COALESCE(p.tipo_producto,'')) = 'preparado'")->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'monto' => 0, 'unidades' => 0];
        $inventarioResumen = $conexion->query("SELECT COALESCE(SUM(CASE WHEN LOWER(tipo_producto)='producto' THEN 1 ELSE 0 END),0) AS productos, COALESCE(SUM(CASE WHEN LOWER(tipo_producto)='preparado' THEN 1 ELSE 0 END),0) AS preparados, COALESCE(SUM(CASE WHEN stock <= COALESCE(stock_minimo,10) THEN 1 ELSE 0 END),0) AS stock_bajo, COALESCE(SUM(COALESCE(precio_compra,0) * COALESCE(stock,0)),0) AS inversion_stock, COALESCE(SUM(COALESCE(precio_venta,0) * COALESCE(stock,0)),0) AS ingreso_potencial, COALESCE(SUM((COALESCE(precio_venta,0) - COALESCE(precio_compra,0)) * COALESCE(stock,0)),0) AS utilidad_potencial, COALESCE(SUM(COALESCE(stock,0)),0) AS unidades_total FROM productos WHERE activo = 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $cuentasResumen = $conexion->query("SELECT COUNT(*) AS total_cuentas, COALESCE(SUM(CASE WHEN estado_cuenta='activo' THEN 1 ELSE 0 END),0) AS activas, COALESCE(SUM(CASE WHEN estado_cuenta='inactivo' THEN 1 ELSE 0 END),0) AS inactivas, COALESCE(SUM(CASE WHEN estado_cuenta='bloqueado' THEN 1 ELSE 0 END),0) AS bloqueadas, COALESCE(SUM(saldo_total),0) AS saldo_total FROM cuentas")->fetch(PDO::FETCH_ASSOC) ?: [];
        $adeudosResumen = $conexion->query("SELECT COUNT(*) AS ventas_pendientes, COALESCE(SUM(total),0) AS adeudo_pendiente FROM ventas WHERE tipo_pago='fiado' AND COALESCE(fiado_pagado,0)=0")->fetch(PDO::FETCH_ASSOC) ?: [];
        $cortesResumen = $conexion->query("SELECT COUNT(*) AS total_cortes, COALESCE(SUM(CASE WHEN estado='abierto' THEN 1 ELSE 0 END),0) AS abiertos, COALESCE(SUM(CASE WHEN estado='cerrado' THEN 1 ELSE 0 END),0) AS cerrados, COALESCE(SUM(COALESCE(saldo_inicial,0)),0) AS saldo_inicial, COALESCE(SUM(COALESCE(ingresos_efectivo,0) + COALESCE(ingresos_tarjeta,0) + COALESCE(ingresos_transferencia,0)),0) AS ingresos, COALESCE(SUM(COALESCE(egresos,0)),0) AS egresos, COALESCE(SUM(COALESCE(saldo_final,0)),0) AS saldo_final, COALESCE(SUM(COALESCE(diferencia,0)),0) AS diferencia_total FROM cortes_caja")->fetch(PDO::FETCH_ASSOC) ?: [];
        $registrosResumen = $conexion->query("SELECT COUNT(*) AS total_registros, COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END),0) AS ingresos, COALESCE(SUM(CASE WHEN tipo='egreso' THEN monto ELSE 0 END),0) AS egresos, COALESCE(SUM(CASE WHEN tipo='arca_ingreso' THEN monto ELSE 0 END),0) AS arca_ingresos, COALESCE(SUM(CASE WHEN tipo IN ('arca_egreso','arca_gasto','arca_merma') THEN monto ELSE 0 END),0) AS arca_salidas FROM registros")->fetch(PDO::FETCH_ASSOC) ?: [];
        $septimasResumen = $conexion->query("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN pagado=1 THEN 1 ELSE 0 END),0) AS pagadas, COALESCE(SUM(CASE WHEN pagado=0 THEN 1 ELSE 0 END),0) AS pendientes, COALESCE(SUM(monto),0) AS monto_total FROM septimas")->fetch(PDO::FETCH_ASSOC) ?: [];
        $arcasResumen = $conexion->query("SELECT COUNT(DISTINCT servicio) AS servicios, COALESCE(SUM(CASE WHEN tipo='arca_ingreso' THEN monto ELSE 0 END),0) AS ingresos, COALESCE(SUM(CASE WHEN tipo='arca_egreso' THEN monto ELSE 0 END),0) AS egresos, COALESCE(SUM(CASE WHEN tipo='arca_gasto' THEN monto ELSE 0 END),0) AS gastos, COALESCE(SUM(CASE WHEN tipo='arca_merma' THEN monto ELSE 0 END),0) AS mermas FROM registros WHERE categoria = 'arca'")->fetch(PDO::FETCH_ASSOC) ?: [];

        $utilidadReal = $totalVentas - $totalGastos;
        $utilidadPotencialInventario = (float)($inventarioResumen['utilidad_potencial'] ?? 0);
        $estadoGeneral = 'Estable';
        if (($inventarioResumen['stock_bajo'] ?? 0) > 0 || ($adeudosResumen['ventas_pendientes'] ?? 0) > 0) {
            $estadoGeneral = 'Atención';
        }
        if ($rentabilidad < 0) {
            $estadoGeneral = 'Crítico';
        }

        $summaryBlocks = [
            [
                'title' => 'Lectura Ejecutiva',
                'color' => '1E3A8A',
                'rows' => [
                    ['label' => 'Estado general', 'value' => $estadoGeneral],
                    ['label' => 'Ventas totales', 'value' => '$' . number_format($totalVentas, 2)],
                    ['label' => 'Gastos totales', 'value' => '$' . number_format($totalGastos, 2)],
                    ['label' => 'Utilidad real', 'value' => '$' . number_format($utilidadReal, 2)],
                    ['label' => 'Rentabilidad sobre ventas', 'value' => number_format($rentabilidad, 2) . '%'],
                ]
            ],
            [
                'title' => 'Inventario',
                'color' => '2E7D32',
                'rows' => [
                    ['label' => 'Productos activos', 'value' => (int)($inventarioResumen['productos'] ?? 0)],
                    ['label' => 'Preparados activos', 'value' => (int)($inventarioResumen['preparados'] ?? 0)],
                    ['label' => 'Unidades en stock', 'value' => (int)($inventarioResumen['unidades_total'] ?? 0)],
                    ['label' => 'Artículos con stock bajo', 'value' => (int)($inventarioResumen['stock_bajo'] ?? 0)],
                    ['label' => 'Inversión en inventario', 'value' => '$' . number_format((float)($inventarioResumen['inversion_stock'] ?? 0), 2)],
                    ['label' => 'Valor de venta del inventario', 'value' => '$' . number_format((float)($inventarioResumen['ingreso_potencial'] ?? 0), 2)],
                    ['label' => 'Utilidad potencial inventario', 'value' => '$' . number_format($utilidadPotencialInventario, 2)],
                ]
            ],
            [
                'title' => 'Ventas',
                'color' => '1565C0',
                'rows' => [
                    ['label' => 'Ventas productos', 'value' => (int)($ventasProductos['cnt'] ?? 0)],
                    ['label' => 'Importe ventas productos', 'value' => '$' . number_format((float)($ventasProductos['monto'] ?? 0), 2)],
                    ['label' => 'Unidades productos', 'value' => (int)($ventasProductos['unidades'] ?? 0)],
                    ['label' => 'Ventas preparados', 'value' => (int)($ventasPreparados['cnt'] ?? 0)],
                    ['label' => 'Importe ventas preparados', 'value' => '$' . number_format((float)($ventasPreparados['monto'] ?? 0), 2)],
                    ['label' => 'Fiados pendientes', 'value' => '$' . number_format($totalFiados, 2)],
                ]
            ],
            [
                'title' => 'Caja, Cuentas y Arca',
                'color' => '6A1B9A',
                'rows' => [
                    ['label' => 'Cuentas activas', 'value' => (int)($cuentasResumen['activas'] ?? 0)],
                    ['label' => 'Saldo de cuentas', 'value' => '$' . number_format((float)($cuentasResumen['saldo_total'] ?? 0), 2)],
                    ['label' => 'Adeudo pendiente', 'value' => '$' . number_format((float)($adeudosResumen['adeudo_pendiente'] ?? 0), 2)],
                    ['label' => 'Cortes abiertos', 'value' => (int)($cortesResumen['abiertos'] ?? 0)],
                    ['label' => 'Ingresos en cortes', 'value' => '$' . number_format((float)($cortesResumen['ingresos'] ?? 0), 2)],
                    ['label' => 'Egresos en cortes', 'value' => '$' . number_format((float)($cortesResumen['egresos'] ?? 0), 2)],
                    ['label' => 'Arca neto', 'value' => '$' . number_format((float)(($arcasResumen['ingresos'] ?? 0) - ($arcasResumen['egresos'] ?? 0) - ($arcasResumen['gastos'] ?? 0) - ($arcasResumen['mermas'] ?? 0)), 2)],
                ]
            ],
            [
                'title' => 'Séptimas y Registros',
                'color' => '00897B',
                'rows' => [
                    ['label' => 'Séptimas registradas', 'value' => (int)($septimasResumen['total'] ?? 0)],
                    ['label' => 'Séptimas pagadas', 'value' => (int)($septimasResumen['pagadas'] ?? 0)],
                    ['label' => 'Séptimas pendientes', 'value' => (int)($septimasResumen['pendientes'] ?? 0)],
                    ['label' => 'Monto total séptimas', 'value' => '$' . number_format((float)($septimasResumen['monto_total'] ?? 0), 2)],
                    ['label' => 'Registros totales', 'value' => (int)($registrosResumen['total_registros'] ?? 0)],
                    ['label' => 'Ingresos registrados', 'value' => '$' . number_format((float)($registrosResumen['ingresos'] ?? 0), 2)],
                    ['label' => 'Egresos registrados', 'value' => '$' . number_format((float)($registrosResumen['egresos'] ?? 0), 2)],
                ]
            ],
            [
                'title' => 'Observaciones',
                'color' => '5D4037',
                'rows' => [
                    ['label' => 'Alertas de inventario', 'value' => (int)($inventarioResumen['stock_bajo'] ?? 0) . ' productos'],
                    ['label' => 'Fiados por cobrar', 'value' => (int)($adeudosResumen['ventas_pendientes'] ?? 0) . ' ventas'],
                    ['label' => 'Diferencia cortes', 'value' => '$' . number_format((float)($cortesResumen['diferencia_total'] ?? 0), 2)],
                    ['label' => 'Rentabilidad potencial inventario', 'value' => number_format(($totalVentas > 0 ? ($utilidadPotencialInventario / $totalVentas) * 100 : 0), 2) . '%'],
                    ['label' => 'Nota', 'value' => ($totalFiados > 0 ? 'Hay saldo pendiente que requiere seguimiento.' : 'Sin fiados pendientes al momento.')],
                ]
            ],
        ];

        $sheetResumen->mergeCells('A4:B4');
        $sheetResumen->setCellValue('A4', 'Resumen por secciones');
        $sheetResumen->getStyle('A4:B4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '37474F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '263238']]]
        ]);

        $summaryRow = 5;
        foreach ($summaryBlocks as $block) {
            $sheetResumen->mergeCells('A' . $summaryRow . ':B' . $summaryRow);
            $sheetResumen->setCellValue('A' . $summaryRow, $block['title']);
            $sheetResumen->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $block['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1B1B1B']]]
            ]);
            $summaryRow++;

            foreach ($block['rows'] as $row) {
                $sheetResumen->setCellValue('A' . $summaryRow, $row['label']);
                $sheetResumen->setCellValue('B' . $summaryRow, $row['value']);
                $sheetResumen->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray([
                    'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D7E2']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]
                ]);
                $sheetResumen->getStyle('A' . $summaryRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '263238']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7F9FC']]
                ]);
                $sheetResumen->getStyle('B' . $summaryRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0D47A1']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']]
                ]);
                $summaryRow++;
            }

            $summaryRow++;
        }

        $sheetResumen->setCellValue('A' . $summaryRow, 'Lectura rápida');
        $sheetResumen->mergeCells('A' . $summaryRow . ':B' . $summaryRow);
        $sheetResumen->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1B1B1B']]]
        ]);
        $summaryRow++;
        $sheetResumen->mergeCells('A' . $summaryRow . ':B' . ($summaryRow + 1));
        $sheetResumen->setCellValue('A' . $summaryRow, 'Este resumen agrupa ventas, gastos, inventario, cuentas, cortes y séptimas en lenguaje operativo. Las secciones con valor en rojo o amarillo requieren seguimiento inmediato.');
        $sheetResumen->getStyle('A' . $summaryRow . ':B' . ($summaryRow + 1))->applyFromArray([
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
            'font' => ['italic' => true, 'color' => ['rgb' => '455A64']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F7FA']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D7E2']]]
        ]);

        $sheetResumen->getColumnDimension('A')->setWidth(34);
        $sheetResumen->getColumnDimension('B')->setWidth(26);
        $sheetResumen->freezePane('A5');
        $sheetResumen->setAutoFilter('A4:B' . $summaryRow);
        autoSizeColumns($sheetResumen);

        // ====== HOJA 1: INVENTARIO ======
        $sheetInv = $spreadsheet->createSheet();
        $fila = agregarLogoEncabezado($sheetInv, 'INVENTARIO DE PRODUCTOS', 'Listado completo con precios');
        $sheetInv->setTitle('Inventario');
        setSheetTabColor($sheetInv, '2E7D32');
        
        // Headers
        $headers = ['ID', 'Nombre', 'Tipo', 'Código', 'Costo', 'Precio Venta', 'Stock (Unidades)', 'Inversión', 'Valor Venta', 'Margen'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($cols as $i => $col) {
            $sheetInv->setCellValue($col . $fila, $headers[$i]);
        }
        $sheetInv->getStyle('A' . $fila . ':J' . $fila)->applyFromArray(estilosEncabezado());
        $sheetInv->freezePane('A4');
        $sheetInv->setAutoFilter('A3:J3');
        
        $fila++;
        $productos = $conexion->query("SELECT id, nombre, tipo_producto, codigo_barras, precio_compra, precio_venta, stock FROM productos WHERE activo=1 ORDER BY nombre");
        $totalInversionCompleto = 0;
        $totalValorVenta = 0;
        $totalUnidadesStock = 0;
        $filaNum = 0;
        
        while ($p = $productos->fetch(PDO::FETCH_ASSOC)) {
            $inversion = ($p['precio_compra'] ?? 0) * ($p['stock'] ?? 0);
            $valorVenta = ($p['precio_venta'] ?? 0) * ($p['stock'] ?? 0);
            $margen = $valorVenta - $inversion;
            
            $sheetInv->setCellValue('A' . $fila, $p['id']);
            $sheetInv->setCellValue('B' . $fila, $p['nombre']);
            $sheetInv->setCellValue('C' . $fila, ucfirst($p['tipo_producto'] ?? 'Producto'));
            $sheetInv->setCellValueExplicit('D' . $fila, $p['codigo_barras'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheetInv->setCellValue('E' . $fila, $p['precio_compra'] ?? 0);
            $sheetInv->setCellValue('F' . $fila, $p['precio_venta']);
            $sheetInv->setCellValue('G' . $fila, $p['stock']);
            $sheetInv->setCellValue('H' . $fila, $inversion);
            $sheetInv->setCellValue('I' . $fila, $valorVenta);
            $sheetInv->setCellValue('J' . $fila, $margen);
            
            $totalInversionCompleto += $inversion;
            $totalValorVenta += $valorVenta;
            $totalUnidadesStock += (int)($p['stock'] ?? 0);
            
            $sheetInv->getStyle('A' . $fila . ':J' . $fila)->applyFromArray(estilosFilaAlterna($filaNum % 2 == 0));
            $fila++;
            $filaNum++;
        }

        $sheetInv->getStyle('E4:F' . $fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheetInv->getStyle('H4:J' . $fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheetInv->getStyle('G4:G' . $fila)->getNumberFormat()->setFormatCode('#,##0');
        $sheetInv->getStyle('A4:A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheetInv->getStyle('G4:G' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Total
        $sheetInv->setCellValue('A' . $fila, 'TOTAL INVENTARIO');
        $sheetInv->setCellValue('G' . $fila, $totalUnidadesStock);
        $sheetInv->setCellValue('H' . $fila, $totalInversionCompleto);
        $sheetInv->setCellValue('I' . $fila, $totalValorVenta);
        $sheetInv->setCellValue('J' . $fila, $totalValorVenta - $totalInversionCompleto);
        $sheetInv->getStyle('A' . $fila . ':J' . $fila)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5D4037']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        
        autoSizeColumns($sheetInv);

        // ====== HOJA 2: VENTAS ======
        $sheetVentas = $spreadsheet->createSheet();
        $fila = agregarLogoEncabezado($sheetVentas, 'REGISTRO DE VENTAS', 'Detalle de transacciones');
        $sheetVentas->setTitle('Ventas');
        setSheetTabColor($sheetVentas, '1565C0');
        
        $headers = ['Fecha', 'Vendedor', 'Producto', 'Cantidad', 'Precio Unit.', 'Total', 'Tipo Pago', 'Cuenta', 'Estado'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($cols as $i => $col) {
            $sheetVentas->setCellValue($col . $fila, $headers[$i]);
        }
        $sheetVentas->getStyle('A' . $fila . ':I' . $fila)->applyFromArray(estilosEncabezado());
        $sheetVentas->freezePane('A4');
        $sheetVentas->setAutoFilter('A3:I3');
        
        $fila++;
        $ventas = $conexion->query("
                 SELECT v.id, v.fecha, v.vendedor, p.nombre, v.cantidad,
                     CASE WHEN COALESCE(v.cantidad, 0) > 0 THEN (v.total * 1.0 / v.cantidad) ELSE 0 END as precio_unit,
                     v.total, v.tipo_pago, v.nombre_fiado, v.fiado_pagado
            FROM ventas v
            LEFT JOIN productos p ON v.producto_id = p.id
            ORDER BY v.fecha DESC
        ");
        
        $totalVentasSH = 0;
        $filaNum = 0;
        
        while ($v = $ventas->fetch(PDO::FETCH_ASSOC)) {
            $sheetVentas->setCellValue('A' . $fila, substr($v['fecha'], 0, 16));
            $sheetVentas->setCellValue('B' . $fila, $v['vendedor'] ?? 'N/A');
            $sheetVentas->setCellValue('C' . $fila, $v['nombre'] ?? '--');
            $sheetVentas->setCellValue('D' . $fila, $v['cantidad']);
            $sheetVentas->setCellValue('E' . $fila, (float)($v['precio_unit'] ?? 0));
            $sheetVentas->setCellValue('F' . $fila, $v['total']);
            $sheetVentas->setCellValue('G' . $fila, ucfirst($v['tipo_pago']));
            $sheetVentas->setCellValue('H' . $fila, $v['nombre_fiado'] ?? '');
            $sheetVentas->setCellValue('I' . $fila, ($v['tipo_pago'] === 'fiado') ? (($v['fiado_pagado'] == 1) ? 'Pagado' : 'Pendiente') : 'N/A');
            
            $totalVentasSH += $v['total'];
            
            $sheetVentas->getStyle('A' . $fila . ':I' . $fila)->applyFromArray(estilosFilaAlterna($filaNum % 2 == 0));
            $fila++;
            $filaNum++;
        }

        $sheetVentas->getStyle('E4:F' . $fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        // Total
        $sheetVentas->setCellValue('A' . $fila, 'TOTAL VENTAS');
        $sheetVentas->setCellValue('F' . $fila, $totalVentasSH);
        $sheetVentas->getStyle('A' . $fila . ':I' . $fila)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        
        autoSizeColumns($sheetVentas);

        // ====== HOJA 3: GASTOS Y REGISTROS ======
        $sheetGastos = $spreadsheet->createSheet();
        $fila = agregarLogoEncabezado($sheetGastos, 'GASTOS Y REGISTROS', 'Movimientos financieros');
        $sheetGastos->setTitle('Gastos');
        setSheetTabColor($sheetGastos, 'C62828');
        
        $headers = ['Fecha', 'Tipo', 'Concepto', 'Categoría', 'Monto', 'Usuario'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($cols as $i => $col) {
            $sheetGastos->setCellValue($col . $fila, $headers[$i]);
        }
        $sheetGastos->getStyle('A' . $fila . ':F' . $fila)->applyFromArray(estilosEncabezado());
        $sheetGastos->freezePane('A4');
        $sheetGastos->setAutoFilter('A3:F3');
        
        $fila++;
        $gastos = $conexion->query("SELECT fecha, tipo, concepto, categoria, monto, usuario FROM registros ORDER BY fecha DESC");
        
        $totalIngresosReg = 0;
        $totalEgresosReg = 0;
        $filaNum = 0;
        
        while ($g = $gastos->fetch(PDO::FETCH_ASSOC)) {
            $sheetGastos->setCellValue('A' . $fila, substr($g['fecha'], 0, 16));
            $sheetGastos->setCellValue('B' . $fila, $g['tipo']);
            $sheetGastos->setCellValue('C' . $fila, $g['concepto']);
            $sheetGastos->setCellValue('D' . $fila, $g['categoria'] ?? 'N/A');
            $sheetGastos->setCellValue('E' . $fila, $g['monto']);
            $sheetGastos->setCellValue('F' . $fila, $g['usuario']);
            
            if (strpos($g['tipo'], 'ingreso') !== false) {
                $totalIngresosReg += $g['monto'];
            } else {
                $totalEgresosReg += $g['monto'];
            }
            
            $sheetGastos->getStyle('A' . $fila . ':F' . $fila)->applyFromArray(estilosFilaAlterna($filaNum % 2 == 0));
            $fila++;
            $filaNum++;
        }

        $sheetGastos->getStyle('E4:E' . $fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        // Totales
        $sheetGastos->setCellValue('A' . $fila, 'TOTALES');
        $sheetGastos->setCellValue('B' . $fila, 'INGRESOS');
        $sheetGastos->setCellValue('E' . $fila, $totalIngresosReg);
        $fila++;
        $sheetGastos->setCellValue('B' . $fila, 'EGRESOS');
        $sheetGastos->setCellValue('E' . $fila, $totalEgresosReg);
        
        autoSizeColumns($sheetGastos);

        // ====== HOJA 4: CUENTAS (DEUDORES) ======
        $sheetCuentas = $spreadsheet->createSheet();
        $fila = agregarLogoEncabezado($sheetCuentas, 'CUENTAS Y DEUDORES', 'Estado de créditos');
        $sheetCuentas->setTitle('Cuentas');
        setSheetTabColor($sheetCuentas, '6A1B9A');
        
        $headers = ['Nombre', 'Celular', 'Grupo', 'Estado', 'Saldo Actual', 'Adeudo', 'Última Compra'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($cols as $i => $col) {
            $sheetCuentas->setCellValue($col . $fila, $headers[$i]);
        }
        $sheetCuentas->getStyle('A' . $fila . ':G' . $fila)->applyFromArray(estilosEncabezado());
        $sheetCuentas->freezePane('A4');
        $sheetCuentas->setAutoFilter('A3:G3');
        
        $fila++;
        $cuentas = $conexion->query("SELECT * FROM cuentas ORDER BY nombre_cuenta");
        
        $totalAdeudoCuentas = 0;
        $filaNum = 0;
        
        while ($c = $cuentas->fetch(PDO::FETCH_ASSOC)) {
            $adeudo = $conexion->prepare(
                "SELECT COALESCE(SUM(total), 0) as monto FROM ventas WHERE tipo_pago='fiado' AND fiado_pagado=0 AND LOWER(REPLACE(nombre_fiado, ' ', '')) = LOWER(REPLACE(?, ' ', ''))"
            );
            $adeudo->execute([$c['nombre_cuenta']]);
            $adeudoMonto = $adeudo->fetch(PDO::FETCH_ASSOC)['monto'];
            
            $sheetCuentas->setCellValue('A' . $fila, $c['nombre_cuenta']);
            $sheetCuentas->setCellValue('B' . $fila, $c['celular'] ?? '-');
            $sheetCuentas->setCellValue('C' . $fila, isset($c['grupo']) ? $c['grupo'] : 'N/A');
            $sheetCuentas->setCellValue('D' . $fila, ucfirst($c['estado_cuenta']));
            $sheetCuentas->setCellValue('E' . $fila, $c['saldo_total']);
            $sheetCuentas->setCellValue('F' . $fila, $adeudoMonto);
            $sheetCuentas->setCellValue('G' . $fila, $c['fecha_ultimo_compra'] ?? '-');
            
            $totalAdeudoCuentas += $adeudoMonto;
            
            $sheetCuentas->getStyle('A' . $fila . ':G' . $fila)->applyFromArray(estilosFilaAlterna($filaNum % 2 == 0));
            $fila++;
            $filaNum++;
        }

        $sheetCuentas->getStyle('E4:F' . $fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        // Total
        $sheetCuentas->setCellValue('A' . $fila, 'TOTAL ADEUDO');
        $sheetCuentas->setCellValue('F' . $fila, $totalAdeudoCuentas);
        $sheetCuentas->getStyle('A' . $fila . ':G' . $fila)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F44336']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        
        autoSizeColumns($sheetCuentas);

        // ====== HOJA 5: SÉPTIMAS ======
        $sheetSeptimas = $spreadsheet->createSheet();
        $fila = agregarLogoEncabezado($sheetSeptimas, 'SÉPTIMAS', 'Registro de contribuciones y estado');
        $sheetSeptimas->setTitle('Septimas');
        setSheetTabColor($sheetSeptimas, '00897B');
        
        $headers = ['Fecha', 'Padrino', 'Monto', 'Tipo', 'Servicio', 'Estado', 'Días Registro'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($cols as $i => $col) {
            $sheetSeptimas->setCellValue($col . $fila, $headers[$i]);
        }
        $sheetSeptimas->getStyle('A' . $fila . ':G' . $fila)->applyFromArray(estilosEncabezado());
        $sheetSeptimas->freezePane('A4');
        $sheetSeptimas->setAutoFilter('A3:G3');
        
        $fila++;
        $septimas = $conexion->query("SELECT fecha, nombre_padrino, monto, tipo, servicio, pagado FROM septimas ORDER BY fecha DESC");
        
        $totalSeptimas = 0;
        $filaNum = 0;
        
        while ($s = $septimas->fetch(PDO::FETCH_ASSOC)) {
            $dias = floor((time() - strtotime($s['fecha'])) / 86400);
            
            $sheetSeptimas->setCellValue('A' . $fila, $s['fecha']);
            $sheetSeptimas->setCellValue('B' . $fila, $s['nombre_padrino']);
            $sheetSeptimas->setCellValue('C' . $fila, $s['monto']);
            $sheetSeptimas->setCellValue('D' . $fila, $s['tipo'] ?? 'Normal');
            $sheetSeptimas->setCellValue('E' . $fila, $s['servicio'] ?? 'N/A');
            $sheetSeptimas->setCellValue('F' . $fila, $s['pagado'] ? 'Pagada' : 'Pendiente');
            $sheetSeptimas->setCellValue('G' . $fila, $dias . ' días');
            
            $totalSeptimas += $s['monto'];
            
            $sheetSeptimas->getStyle('A' . $fila . ':G' . $fila)->applyFromArray(estilosFilaAlterna($filaNum % 2 == 0));
            $fila++;
            $filaNum++;
        }

        $sheetSeptimas->getStyle('C4:C' . $fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        // Total
        $sheetSeptimas->setCellValue('A' . $fila, 'TOTAL SÉPTIMAS');
        $sheetSeptimas->setCellValue('C' . $fila, $totalSeptimas);
        $sheetSeptimas->getStyle('A' . $fila . ':G' . $fila)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'border' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ]);
        
        autoSizeColumns($sheetSeptimas);
    }

    // --- GUARDAR ARCHIVO ---
    while (ob_get_level()) {
        ob_end_clean();
    }

    $downloadsPath = resolveDownloadsPath();
    $filename = 'Reporte_' . ucfirst($reporte) . '_' . date('Y-m-d_His') . '.xlsx';
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    $fullPath = $downloadsPath . DIRECTORY_SEPARATOR . $safeName;

    $counter = 1;
    $pathInfo = pathinfo($fullPath);
    while (file_exists($fullPath)) {
        $fullPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 
                    $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
        $counter++;
    }

    try {
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        if (!file_exists($fullPath) || filesize($fullPath) == 0) {
            throw new Exception('No se pudo guardar el archivo');
        }

        $fileSize = filesize($fullPath);
        $fileName = basename($fullPath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        header('Content-Type: text/plain; charset=utf-8');
        echo 'SUCCESS|' . $fileName . '|' . str_replace('\\', '/', $downloadsPath) . '|' . $fileSize;
        exit;

    } catch (Exception $saveError) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'ERROR|' . $saveError->getMessage();
        exit;
    }

} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ERROR|' . $e->getMessage();
    exit;
}
?>
