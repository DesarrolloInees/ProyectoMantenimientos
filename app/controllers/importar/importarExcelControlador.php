<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. IMPORTAR ARCHIVOS NECESARIOS
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/importar/importarExcelModelo.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class importarExcelControlador
{
    private $modelo;
    private $db;

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

    public function procesar()
    {
        // Configuración de recursos para archivos grandes
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {

            if ($_FILES['archivo_excel']['error'] == 0) {

                $rutaArchivo = $_FILES['archivo_excel']['tmp_name'];

                try {
                    // 1. CARGA OPTIMIZADA DEL EXCEL
                    $reader = IOFactory::createReaderForFile($rutaArchivo);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($rutaArchivo);

                    // Buscar hoja específica o usar la primera
                    $nombreHojaObjetivo = 'INSTALADAS CT';
                    $hoja = $spreadsheet->getSheetByName($nombreHojaObjetivo);
                    if ($hoja === null) {
                        $hoja = $spreadsheet->getActiveSheet();
                    }

                    $filas = $hoja->toArray();

                    // 2. MARCAR HORA DE INICIO (El sello de tiempo para la limpieza)
                    $fechaInicio = date('Y-m-d H:i:s');
                    sleep(1); // Pequeña pausa para evitar conflictos de milisegundos

                    // Variables de reporte
                    $stats = ['insertados' => 0, 'omitidos' => 0, 'errores' => 0];
                    $detallesInsertados = [];
                    $mapaTipos = ['MINI MEI' => 'Mini Mei', 'SDM-10' => 'SDM 10', 'JH-600' => 'JH 600'];

                    // 3. RECORRER FILAS (i=1 para saltar encabezado)
                    for ($i = 1; $i < count($filas); $i++) {
                        $fila = $filas[$i];

                        // Mapeo de columnas (Ajustado a tu CSV INSTALADAS CT)
                        $codClienteStr = $fila[0];  // A
                        $nombreCliente = $fila[1];  // B
                        $deviceId      = $fila[2] ?? ''; // C
                        $cod1          = $fila[3];  // D
                        $cod2          = $fila[4];  // E
                        $nombrePunto   = $fila[5];  // F
                        $delegacionTxt = $fila[8];  // I
                        $tipoMaquina   = $fila[10]; // K
                        $direccion     = $fila[36]; // AK (Verificar columna dirección)

                        if (empty($deviceId)) continue;

                        // =========================================================
                        // CASO A: YA EXISTE LA MÁQUINA
                        // =========================================================
                        if ($this->modelo->existeDeviceId($deviceId)) {

                            // 1. "Tocamos" la máquina para salvarla
                            $this->modelo->tocarMaquina($deviceId);

                            // 2. BUSCAMOS SU PUNTO REAL EN LA BASE DE DATOS
                            // No creamos uno nuevo basado en el nombre del Excel (evita duplicados por typos)
                            $idPuntoActual = $this->modelo->obtenerIdPuntoPorDevice($deviceId);

                            if ($idPuntoActual) {
                                // "Tocamos" el punto donde vive la máquina actualmente
                                $this->modelo->tocarPunto($idPuntoActual);
                            } else {
                                // Caso raro: La máquina existe pero no tiene punto (húerfana).
                                // Aquí sí podríamos intentar crearle uno o asignarlo, 
                                // pero por seguridad mejor no hacemos nada para no ensuciar.
                            }

                            $stats['omitidos']++;
                            continue;
                        }

                        // =========================================================
                        // CASO B: ES UNA MÁQUINA NUEVA
                        // =========================================================
                        try {
                            $this->db->beginTransaction();

                            // 1. Gestionar Cliente
                            $idCliente = $this->modelo->gestionarCliente($nombreCliente, $codClienteStr);

                            // 2. Gestionar Punto
                            $idDelegacion = $this->modelo->obtenerIdDelegacion($delegacionTxt);
                            $idPunto = $this->modelo->gestionarPunto(
                                $nombrePunto,
                                $idCliente,
                                $cod1,
                                $cod2,
                                $idDelegacion,
                                $direccion
                            );

                            // Marcamos el punto nuevo como activo hoy
                            $this->modelo->tocarPunto($idPunto);

                            // 3. Gestionar Tipo y Máquina
                            $tipoFinal = isset($mapaTipos[trim($tipoMaquina)]) ? $mapaTipos[trim($tipoMaquina)] : $tipoMaquina;
                            $idTipo = $this->modelo->obtenerIdTipoMaquina($tipoFinal);

                            $datosMaq = [
                                'device_id' => trim($deviceId),
                                'id_punto' => $idPunto,
                                'id_tipo_maquina' => $idTipo
                            ];

                            if ($this->modelo->insertarMaquina($datosMaq)) {

                                // Marcamos la máquina nueva como activa hoy
                                $this->modelo->tocarMaquina($deviceId);

                                $stats['insertados']++;
                                $detallesInsertados[] = [
                                    'device_id' => $deviceId,
                                    'cliente'   => $nombreCliente,
                                    'punto'     => $nombrePunto,
                                    'ciudad'    => $delegacionTxt
                                ];
                                $this->db->commit();
                            } else {
                                $this->db->rollBack();
                                $stats['errores']++;
                            }
                        } catch (Exception $e) {
                            $this->db->rollBack();
                            $stats['errores']++;
                        }
                    }

                    // 4. LIMPIEZA FINAL (APAGAR FANTASMAS)
                    // Todo lo que tenga fecha_actualizacion menor a $fechaInicio se desactiva
                    $bajas = $this->modelo->desactivarFantasmas($fechaInicio);

                    // 5. PREPARAR DATOS PARA LA VISTA
                    $resultados = $stats;
                    $resultados['bajas_maquinas'] = $bajas['maquinas'];
                    $resultados['bajas_puntos']   = $bajas['puntos'];
                    $listaNuevos = $detallesInsertados;

                    // Cargar vista con los datos
                    $vistaContenido = "app/views/importar/importarExcelVista.php";
                    include "app/views/plantillaVista.php";
                    return;
                } catch (Exception $e) {
                    echo "<script>alert('Error crítico: " . $e->getMessage() . "'); window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Error al subir el archivo.'); window.history.back();</script>";
            }
        }
    }
}
