<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/importar/importarEstadoMaquinaModelo.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class importarEstadoMaquinaControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new importarEstadoMaquinaModelo($this->db);
    }

    public function index()
    {
        $titulo = "Importar Estado de Maquinas";
        $vistaContenido = "app/views/importar/importarEstadoMaquinaVista.php";
        include "app/views/plantillaVista.php";
    }

    // ========================================================================
    // FASE 1: SUBIR EL ARCHIVO, LEERLO Y GUARDAR DEVICE IDS EN SESION
    // ========================================================================
    public function subirArchivo()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {

            if ($_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['exito' => false, 'error' => 'Error al subir el archivo (codigo ' . $_FILES['archivo_excel']['error'] . ').']);
                exit;
            }

            $archivoSubido = $_FILES['archivo_excel']['tmp_name'];

            try {
                $reader = IOFactory::createReaderForFile($archivoSubido);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($archivoSubido);
                $hoja = $spreadsheet->getActiveSheet();
                $filas = $hoja->toArray(null, true, true, true);

                // Extraer device_ids de la columna A (desde fila 2)
                $deviceIds = [];
                foreach ($filas as $numFila => $fila) {
                    if ($numFila == 1) continue;
                    $deviceId = trim($fila['A'] ?? '');
                    if ($deviceId !== '') {
                        $deviceIds[] = $deviceId;
                    }
                }

                if (count($deviceIds) === 0) {
                    echo json_encode(['exito' => false, 'error' => 'El archivo no contiene Device ID en la columna A.']);
                    exit;
                }

                // Guardar en sesion
                $_SESSION['lista_device_ids_estado'] = $deviceIds;
                $_SESSION['total_device_ids_estado'] = count($deviceIds);

                echo json_encode([
                    'exito' => true,
                    'total_filas' => count($deviceIds),
                    'mensaje' => 'Archivo cargado con ' . count($deviceIds) . ' Device IDs. Iniciando analisis...'
                ]);

            } catch (Exception $e) {
                echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
            }
        }
        exit;
    }

    // ========================================================================
    // FASE 2: PROCESAR UN LOTE (SIMULACION O IMPORTACION REAL) DESDE SESION
    // ========================================================================
    public function procesarLote()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        ini_set('memory_limit', '-1');
        set_time_limit(300);

        $inicio = intval($_POST['inicio'] ?? 2);
        $cantidad = intval($_POST['cantidad'] ?? 200);
        $modo = $_POST['modo'] ?? 'simular';
        $aprobados = isset($_POST['aprobados']) ? json_decode($_POST['aprobados'], true) : [];

        $deviceIds = $_SESSION['lista_device_ids_estado'] ?? [];

        if (empty($deviceIds)) {
            echo json_encode(['exito' => false, 'error' => 'No hay datos cargados. Vuelve a subir el archivo.']);
            exit;
        }

        // Los device_ids estan en el array indexado desde 0 (fila 2 -> indice 0)
        $idxInicio = $inicio - 2; // fila 2 = indice 0
        $lote = array_slice($deviceIds, $idxInicio, $cantidad);

        $stats = ['marcados' => 0, 'errores' => 0, 'no_encontrados' => 0];
        $detallesLote = [];
        $filasConDatosEnEsteLote = 0;

        try {
            foreach ($lote as $deviceId) {
                $deviceId = trim($deviceId);
                if (empty($deviceId)) continue;

                $filasConDatosEnEsteLote++;

                if ($modo === 'simular') {
                    $datosMaquina = $this->modelo->buscarMaquinaPorDevice($deviceId);

                    if ($datosMaquina) {
                        $yaInactiva = ($datosMaquina['activo_operativo'] == 0);
                        $accion = $yaInactiva ? 'YA INACTIVA' : 'FUERA DE SERVICIO';
                        $badge = $yaInactiva ? 'badge-secondary' : 'badge-danger';

                        $detallesLote[] = [
                            'device' => $deviceId,
                            'cliente' => $datosMaquina['nombre_cliente'],
                            'punto' => $datosMaquina['nombre_punto'],
                            'tipo_maquina' => $datosMaquina['nombre_tipo_maquina'],
                            'zona' => $datosMaquina['zona'] ?? '',
                            'estado_actual' => $datosMaquina['activo_operativo'] == 1 ? 'Operativo' : 'Inactivo',
                            'accion' => "<span class='badge {$badge}'>{$accion}</span>",
                            'estado' => $yaInactiva ? 'YA_INACTIVA' : 'MARCAR_INACTIVO'
                        ];
                    } else {
                        $detallesLote[] = [
                            'device' => $deviceId,
                            'cliente' => '---',
                            'punto' => '---',
                            'tipo_maquina' => '---',
                            'zona' => '',
                            'estado_actual' => '---',
                            'accion' => "<span class='badge badge-warning'>NO ENCONTRADO</span>",
                            'estado' => 'NO_ENCONTRADO'
                        ];
                    }
                    continue;
                }

                // --- MODO IMPORTAR REAL ---
                if (in_array($deviceId, $aprobados)) {
                    $resultado = $this->modelo->marcarFueraDeServicio($deviceId);
                    if ($resultado) {
                        $stats['marcados']++;
                    } else {
                        $stats['errores']++;
                    }
                }
            }

            // Detener cuando no quedan mas filas
            $indiceFinal = $idxInicio + count($lote);
            $forzarDetencion = ($indiceFinal >= count($deviceIds));

            echo json_encode([
                'exito' => true,
                'procesados' => count($lote),
                'stats' => $stats,
                'detalles' => $detallesLote,
                'detener' => $forzarDetencion
            ]);

        } catch (Exception $e) {
            echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ========================================================================
    // FASE 3: LIMPIEZA DE SESION
    // ========================================================================
    public function finalizarImportacion()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        unset($_SESSION['lista_device_ids_estado'], $_SESSION['total_device_ids_estado']);

        echo json_encode(['exito' => true, 'mensaje' => 'Importacion finalizada.']);
        exit;
    }
}
