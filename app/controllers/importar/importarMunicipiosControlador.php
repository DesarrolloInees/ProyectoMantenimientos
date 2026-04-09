<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/importar/importarMunicipiosModelo.php'; 
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class importarMunicipiosControlador
{
    private $modelo;
    private $db;
    private $rutaTemporal = __DIR__ . '/../../uploads/temp_municipios_preview.xlsx'; 

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new importarMunicipiosModelo($this->db);
    }

    public function index()
    {
        $titulo = "Actualización Masiva de Municipios";
        $vistaContenido = "app/views/importar/importarMunicipiosVista.php";
        include "app/views/plantillaVista.php";
    }

    // FASE 1: SUBIR Y GENERAR VISTA PREVIA (SIMULACIÓN)
    public function generarSimulacion()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
            if (!is_dir(dirname($this->rutaTemporal))) mkdir(dirname($this->rutaTemporal), 0777, true);

            if (move_uploaded_file($_FILES['archivo_excel']['tmp_name'], $this->rutaTemporal)) {
                try {
                    ini_set('memory_limit', '-1');
                    $reader = IOFactory::createReaderForFile($this->rutaTemporal);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($this->rutaTemporal);
                    $hoja = $spreadsheet->getActiveSheet();
                    $filas = $hoja->toArray(null, true, true, true);

                    $simulacion = [];
                    $stats = ['leidos' => 0, 'con_cambios' => 0, 'no_encontrados' => 0];

                    foreach ($filas as $numFila => $fila) {
                        if ($numFila == 1) continue; // Saltar cabecera

                        $deviceId   = trim($fila['C'] ?? ''); 
                        $nuevaZona  = trim($fila['F'] ?? ''); // Asumiendo que Col E trae la zona
                        $delegacion = trim($fila['E'] ?? ''); 

                        if (empty($deviceId)) continue;
                        $stats['leidos']++;

                        // Filtro antibasura
                        $caracteresBasura = ['.', '-', '_', '*', 'N/A', 'na', '?'];
                        if (empty($nuevaZona) || in_array($nuevaZona, $caracteresBasura) || strlen($nuevaZona) < 2) {
                            $nuevaZona = $delegacion;
                        }

                        if (empty($nuevaZona)) continue;

                        // Buscamos cómo está el punto actualmente
                        $datosActuales = $this->modelo->obtenerDatosPuntoPorDevice($deviceId);

                        if ($datosActuales) {
                            $zonaActual = $datosActuales['zona_actual'] ?? 'SIN ZONA';
                            $nuevaZonaUpper = mb_strtoupper($nuevaZona, 'UTF-8');

                            // Lo agregamos a la simulación
                            $simulacion[] = [
                                'id_punto'     => $datosActuales['id_punto'],
                                'device_id'    => $deviceId,
                                'nombre_punto' => $datosActuales['nombre_punto'],
                                'zona_actual'  => $zonaActual,
                                'zona_nueva'   => $nuevaZonaUpper,
                                // Marcamos si es idéntica para facilitar la vista
                                'es_diferente' => ($zonaActual !== $nuevaZonaUpper)
                            ];
                            $stats['con_cambios']++;
                        } else {
                            $stats['no_encontrados']++;
                        }
                    }

                    echo json_encode(['exito' => true, 'datos' => $simulacion, 'stats' => $stats]);
                } catch (Exception $e) {
                    echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['exito' => false, 'error' => 'Error al mover archivo temporal.']);
            }
        }
        exit;
    }

    // FASE 2: GUARDAR LOS SELECCIONADOS POR EL USUARIO
    public function ejecutarActualizacion()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $datosJson = $_POST['datos_seleccionados'] ?? '[]';
        $puntosActualizar = json_decode($datosJson, true);

        if (empty($puntosActualizar)) {
            echo json_encode(['exito' => false, 'error' => 'No se recibieron datos para actualizar.']);
            exit;
        }

        $exitosos = 0;
        $errores = 0;

        foreach ($puntosActualizar as $item) {
            $idPunto = $item['id_punto'] ?? 0;
            $zona = $item['zona_nueva'] ?? '';

            if ($idPunto > 0) {
                if ($this->modelo->actualizarSoloZona($idPunto, $zona)) {
                    $exitosos++;
                } else {
                    $errores++;
                }
            }
        }

        echo json_encode([
            'exito' => true,
            'mensaje' => "Proceso completado. Actualizados: $exitosos, Errores: $errores."
        ]);
        exit;
    }
}
?>