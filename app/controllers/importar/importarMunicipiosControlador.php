<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/importar/importarMunicipiosModelo.php'; // <--- OJO CON LA RUTA
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class importarMunicipiosControlador
{
    private $modelo;
    private $db;
    private $rutaTemporal = __DIR__ . '/../../uploads/temp_municipios.xlsx'; // Nombre distinto para no chocar

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new importarMunicipiosModelo($this->db);
    }

    public function index()
    {
        // Carga la vista que te pondré abajo
        $titulo = "Importación de Zonas Geográficas";
        $vistaContenido = "app/views/importar/importarMunicipiosVista.php";
        include "app/views/plantillaVista.php";
    }

    // FASE 1: SUBIR (Igual al anterior)
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
                    $spreadsheet = $reader->load($this->rutaTemporal);
                    $totalFilas = $spreadsheet->getActiveSheet()->getHighestRow();

                    echo json_encode(['exito' => true, 'total_filas' => $totalFilas]);
                } catch (Exception $e) {
                    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['exito' => false, 'error' => 'Error al mover archivo temporal.']);
            }
        }
        exit;
    }

    // FASE 2: PROCESAR LOTE
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
            
            $chunkFilter = new ChunkReadFilterMunicipios(); 
            $chunkFilter->setRows($inicio, $cantidad);
            $reader->setReadFilter($chunkFilter);

            $spreadsheet = $reader->load($this->rutaTemporal);
            $hoja = $spreadsheet->getActiveSheet();
            $filas = $hoja->toArray(null, true, true, true); 

            $stats = ['actualizados' => 0, 'no_encontrados' => 0, 'delegacion_error' => 0]; 
            $nuevos = [];
            $filasProcesadasRealmente = 0;

            foreach ($filas as $numFila => $fila) {
                if ($numFila == 1) continue; 
                if ($numFila < $inicio) continue;

                // --- MAPEO DE COLUMNAS ---
                $deviceId         = trim($fila['C'] ?? ''); 
                $nombreMunicipio  = trim($fila['E'] ?? ''); 
                $nombreDelegacion = trim($fila['F'] ?? ''); 

                if (empty($deviceId)) continue; 

                // ============================================================
                // 🛑 FILTRO ANTI-BASURA (NUEVO)
                // ============================================================
                
                // Lista negra de caracteres que no queremos como zona
                $caracteresBasura = ['.', '-', '_', '*', 'N/A', 'na', '?'];

                // Verificamos:
                // 1. Si está vacío
                // 2. O si es exactamente un caracter basura (ej: ".")
                // 3. O si tiene menos de 2 letras (ej: "A" o "1") -> Opcional, pero recomendado
                if (empty($nombreMunicipio) || in_array($nombreMunicipio, $caracteresBasura) || strlen($nombreMunicipio) < 2) {
                    
                    // Si detectamos basura, FORZAMOS que tome el nombre de la Delegación
                    // Ej: Si dice "." pasa a ser "BARRANQUILLA"
                    $nombreMunicipio = $nombreDelegacion;
                }
                // ============================================================

                // Si aun después del salvavidas no hay nombre (porque delegación también estaba vacía), saltamos
                if (empty($nombreMunicipio)) continue;

                $filasProcesadasRealmente++;

                try {
                    // 1. BUSCAR PUNTO POR DEVICE ID
                    $idPunto = $this->modelo->obtenerPuntoPorDevice($deviceId);

                    if ($idPunto) {
                        // 2. BUSCAR ID DELEGACIÓN (DINÁMICO)
                        // Aquí estaba el cambio: Ya no forzamos a BOGOTA.
                        $idDelegacion = $this->modelo->obtenerIdDelegacion($nombreDelegacion);

                        if ($idDelegacion) {
                            // 3. GESTIONAR ID MUNICIPIO/ZONA
                            // Ej: Crea "NORTE" asociado a "MEDELLIN"
                            $idMunicipio = $this->modelo->gestionarMunicipio($nombreMunicipio, $idDelegacion);

                            if ($idMunicipio) {
                                // 4. ACTUALIZAR PUNTO Y ZONA TEXTUAL
                                // Guarda id_municipio (Numérico) y zona (Texto: "NORTE")
                                $this->modelo->actualizarUbicacionPunto($idPunto, $idMunicipio, $nombreMunicipio);
                                
                                $nuevos[] = [
                                    'device' => $deviceId,
                                    'municipio' => $nombreMunicipio . ' (' . $nombreDelegacion . ')',
                                    'punto_id' => $idPunto
                                ];
                                $stats['actualizados']++;
                            }
                        } else {
                            // Si en el Excel dice "MEDELLIN" pero en tu BD no existe esa delegación
                            $stats['delegacion_error']++;
                        }
                    } else {
                        $stats['no_encontrados']++;
                    }

                } catch (Exception $e) {
                    $stats['no_encontrados']++;
                }
            }
            
            $detener = (count($filas) < $cantidad && $filasProcesadasRealmente === 0);

            echo json_encode([
                'exito' => true,
                'stats' => $stats,
                'nuevos' => $nuevos,
                'detener' => $detener
            ]);

        } catch (Exception $e) {
            echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Clase auxiliar necesaria para leer por trozos
class ChunkReadFilterMunicipios implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize;
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return true;
        }
        return false;
    }
}
