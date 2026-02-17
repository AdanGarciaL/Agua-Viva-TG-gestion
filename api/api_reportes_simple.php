<?php
// API REPORTES SIMPLE - VERSIÓN QUE SÍ FUNCIONA
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar todo
while (ob_get_level()) ob_end_clean();
ob_start();

session_start();

// Log para debug
$logFile = getenv('USERPROFILE') . '\\AppData\\Local\\TG_Gestion\\reportes_log.txt';
function logDebug($msg) {
    global $logFile;
    $dir = dirname($logFile);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

logDebug("=== INICIO REPORTE ===");

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

// Cargar DB
require_once __DIR__ . '/db.php';
logDebug("DB cargada");

// Cargar PhpSpreadsheet
$vendorPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($vendorPath)) {
    logDebug("ERROR: Librería no encontrada");
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ERROR|Librería Excel no encontrada';
    exit;
}
require_once $vendorPath;
logDebug("PhpSpreadsheet cargada");

if (!class_exists('ZipArchive')) {
    logDebug("ERROR: Falta extension ZIP (php_zip.dll)");
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ERROR|Falta la extensión ZIP de PHP. Reinicia la app después de habilitar php_zip.dll';
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Verificar sesión
    if (!isset($_SESSION['usuario'])) {
        throw new Exception('No hay sesión activa');
    }
    
    logDebug("Usuario: " . $_SESSION['usuario']);
    
    $reporte = $_GET['reporte'] ?? 'inventario_hoy';
    logDebug("Tipo reporte: $reporte");
    
    // Obtener datos de productos (la tabla correcta es "productos", no "inventario")
    $stmt = $conexion->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Productos obtenidos: " . count($productos));
    
    if (empty($productos)) {
        throw new Exception('No hay productos en el inventario');
    }
    
    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Inventario');
    
    // Encabezados
    $headers = ['Nombre', 'Código', 'Stock', 'Precio Venta', 'Stock Mínimo'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $col++;
    }
    
    // Datos
    $row = 2;
    foreach ($productos as $p) {
        $sheet->setCellValue('A' . $row, $p['nombre'] ?? '');
        $sheet->setCellValue('B' . $row, $p['codigo_barras'] ?? '');
        $sheet->setCellValue('C' . $row, $p['stock'] ?? 0);
        $sheet->setCellValue('D' . $row, $p['precio_venta'] ?? 0);
        $sheet->setCellValue('E' . $row, $p['stock_minimo'] ?? 0);
        $row++;
    }
    
    // Autoajustar columnas
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    logDebug("Excel creado");
    
    // Guardar en Downloads
    $downloadsPath = resolveDownloadsPath();
    logDebug("Downloads path: $downloadsPath");
    
    $filename = 'Inventario_' . date('Y-m-d_His') . '.xlsx';
    $fullPath = $downloadsPath . '\\' . $filename;
    
    logDebug("Guardando en: $fullPath");
    
    try {
        $writer = new Xlsx($spreadsheet);
        logDebug("Writer creado");
        
        $writer->save($fullPath);
        logDebug("Save ejecutado");
        
        if (!file_exists($fullPath)) {
            throw new Exception('El archivo no se guardó - no existe en disco');
        }
        
        $fileSize = filesize($fullPath);
        if ($fileSize == 0) {
            throw new Exception('El archivo se guardó pero está vacío');
        }
        
        logDebug("Archivo guardado. Tamaño: $fileSize bytes");
    } catch (Exception $saveEx) {
        logDebug("ERROR al guardar: " . $saveEx->getMessage());
        throw new Exception('Error al guardar archivo: ' . $saveEx->getMessage());
    }
    
    // Liberar memoria
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    
    // Limpiar output
    while (ob_get_level()) ob_end_clean();
    
    // Retornar respuesta
    header('Content-Type: text/plain; charset=utf-8');
    echo 'SUCCESS|' . $filename . '|' . str_replace('\\', '/', $downloadsPath) . '|' . $fileSize;
    logDebug("=== FIN EXITOSO ===");
    exit;
    
} catch (Throwable $e) {
    logDebug("ERROR: " . $e->getMessage());
    logDebug("=== FIN CON ERROR ===");
    
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ERROR|' . $e->getMessage();
    exit;
}
?>