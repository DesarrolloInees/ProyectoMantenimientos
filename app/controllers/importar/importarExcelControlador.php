<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/importar/importarExcelModelo.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class importarExcelControlador
{
    private $modelo;
    private $db;
    private $rutaTemporal = __DIR__ . '/../../uploads/temp_import.xlsx';

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new importarExcelModels($this->db);
    }

    public function index()
    {
        $titulo = "Importación Masiva de Instalaciones";
        $vistaContenido = "app/views/importar/importarExcelVista.php";
        include "app/views/plantillaVista.php";
    }

    // ========================================================================
    // FASE 1: SUBIR EL ARCHIVO Y CONTAR FILAS
    // ========================================================================
    public function subirArchivo()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
            
            if (!is_dir(dirname($this->rutaTemporal))) mkdir(dirname($this->rutaTemporal), 0777, true);

            if (move_uploaded_file($_FILES['archivo_excel']['tmp_name'], $this->rutaTemporal)) {
                
                try {
                    $reader = IOFactory::createReaderForFile($this->rutaTemporal);
                    $reader->setReadDataOnly(true);
                    
                    // Intentamos leer información básica
                    try {
                        $info = $reader->listWorksheetInfo($this->rutaTemporal);
                        $totalFilas = $info[0]['totalRows'];
                        
                        // Buscamos la hoja correcta
                        foreach ($info as $hoja) {
                            if ($hoja['worksheetName'] == 'INSTALADAS CT') {
                                $totalFilas = $hoja['totalRows'];
                                break;
                            }
                        }
                    } catch (Exception $e) {
                        // Fallback si falla listWorksheetInfo
                        $spreadsheet = $reader->load($this->rutaTemporal);
                        $totalFilas = $spreadsheet->getActiveSheet()->getHighestRow();
                    }

                    echo json_encode([
                        'exito' => true, 
                        'total_filas' => $totalFilas,
                        'mensaje' => 'Archivo cargado. Iniciando procesamiento...'
                    ]);

                } catch (Exception $e) {
                    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['exito' => false, 'error' => 'No se pudo mover el archivo temporal.']);
            }
        }
        exit;
    }

    // ========================================================================
    // FASE 2: PROCESAR UN LOTE (CHUNK)
    // ========================================================================
   public function procesarLote()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        
        ini_set('memory_limit', '-1');
        set_time_limit(300); 

        $inicio = intval($_POST['inicio'] ?? 2); 
        $cantidad = intval($_POST['cantidad'] ?? 200);

        try {
            $reader = IOFactory::createReaderForFile($this->rutaTemporal);
            $reader->setReadDataOnly(true);
            
            $chunkFilter = new ChunkReadFilter(); 
            $chunkFilter->setRows($inicio, $cantidad);
            $reader->setReadFilter($chunkFilter);

            $spreadsheet = $reader->load($this->rutaTemporal);
            
            $nombreHoja = 'INSTALADAS CT';
            $hoja = $spreadsheet->getSheetByName($nombreHoja);
            if ($hoja === null) $hoja = $spreadsheet->getActiveSheet();
            
            $filas = $hoja->toArray(null, true, true, true); 

            $stats = ['insertados' => 0, 'actualizados' => 0, 'errores' => 0];
            $detallesNuevos = []; // <--- 1. ARRAY PARA GUARDAR LOS NUEVOS DE ESTE LOTE
            $mapaTipos = ['MINI MEI' => 'Mini Mei', 'SDM-10' => 'SDM 10', 'JH-600' => 'JH 600'];

            $filasConDatosEnEsteLote = 0;

            foreach ($filas as $numFila => $fila) {
                if ($numFila == 1) continue; 
                if ($numFila < $inicio) continue;

                $checkCliente  = trim($fila['A'] ?? '');
                $checkDeviceId = trim($fila['C'] ?? '');
                if (empty($checkCliente) && empty($checkDeviceId)) continue;
                
                $filasConDatosEnEsteLote++;

                $codClienteStr = $fila['A']; 
                $nombreCliente = $fila['B']; 
                $deviceId      = $fila['C'] ?? ''; 
                $cod1          = $fila['D']; 
                $cod2          = $fila['E']; 
                $nombrePunto   = $fila['F']; 
                $delegacionTxt = $fila['I']; 
                $tipoMaquina   = $fila['K']; 
                $direccion     = $fila['AK'] ?? ''; 

                if (empty($deviceId)) continue;

                try {
                    $this->db->beginTransaction();

                    $idCliente = $this->modelo->gestionarCliente($nombreCliente, $codClienteStr);
                    $idDelegacion = $this->modelo->obtenerIdDelegacion($delegacionTxt);
                    $idPuntoDestino = $this->modelo->gestionarPunto($nombrePunto, $idCliente, $cod1, $cod2, $idDelegacion, $direccion);
                    $this->modelo->tocarPunto($idPuntoDestino);

                    $tipoFinal = isset($mapaTipos[trim($tipoMaquina)]) ? $mapaTipos[trim($tipoMaquina)] : $tipoMaquina;
                    $idTipo = $this->modelo->obtenerIdTipoMaquina($tipoFinal);

                    if ($this->modelo->existeDeviceId($deviceId)) {
                        $this->modelo->actualizarMaquina($deviceId, $idPuntoDestino, $idTipo);
                        $this->modelo->tocarMaquina($deviceId);
                        $stats['actualizados']++;
                    } else {
                        $datosMaq = ['device_id' => trim($deviceId), 'id_punto' => $idPuntoDestino, 'id_tipo_maquina' => $idTipo];
                        if ($this->modelo->insertarMaquina($datosMaq)) {
                            $this->modelo->tocarMaquina($deviceId);
                            $stats['insertados']++;
                            
                            // <--- 2. GUARDAMOS EL DETALLE DEL NUEVO
                            $detallesNuevos[] = [
                                'device'  => $deviceId,
                                'cliente' => $nombreCliente,
                                'punto'   => $nombrePunto,
                                'ciudad'  => $delegacionTxt
                            ];
                        }
                    }
                    $this->db->commit();
                } catch (Exception $e) {
                    $this->db->rollBack();
                    $stats['errores']++;
                }
            }
            
            $forzarDetencion = ($filasConDatosEnEsteLote === 0 && count($filas) > 0);

            echo json_encode([
                'exito' => true,
                'procesados' => count($filas),
                'stats' => $stats,
                'nuevos' => $detallesNuevos, // <--- 3. ENVIAMOS LA LISTA AL JS
                'detener' => $forzarDetencion
            ]);

        } catch (Exception $e) {
            echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ========================================================================
    // FASE 3: LIMPIEZA FINAL
    // ========================================================================
    public function finalizarImportacion()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $fechaInicio = $_POST['fecha_inicio'];
        $bajas = $this->modelo->desactivarFantasmas($fechaInicio);
        
        if (file_exists($this->rutaTemporal)) unlink($this->rutaTemporal);

        echo json_encode(['exito' => true, 'bajas' => $bajas]);
        exit;
    }
}

// ============================================================================
// CLASE AUXILIAR CORREGIDA (Aquí estaba el error)
// ============================================================================
class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;

    public function setRows($startRow, $chunkSize) {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize;
    }

    // 🔥 CORRECCIÓN: Se agregaron los tipos string, int y :bool
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return true;
        }
        return false;
    }
}
?>