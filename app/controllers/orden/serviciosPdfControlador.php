<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/serviciosPdfModelo.php';
require_once __DIR__ . '/../../helpers/servicioPdfGenerator.php';

class serviciosPdfControlador
{
    const MAX_SERVICIOS_ZIP = 1000;
    const LOTE_PDF_DEFAULT = 3;
    const TTL_TRABAJO_SEGUNDOS = 7200;

    private $modelo;
    private $db;
    private $pdfGenerator;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new serviciosPdfModelo($this->db);
        $this->pdfGenerator = new ServicioPdfGenerator();
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        $titulo = "Generador de PDFs";
        $opciones = $this->modelo->obtenerOpcionesFiltro();
        $vistaContenido = "app/views/orden/serviciosPdfVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxListar()
    {
        $this->iniciarJson();

        [$idRol, $mesesRestriccion] = $this->obtenerRestriccion();
        $filtros = $this->leerFiltros();
        $datos = $this->modelo->listarServiciosParaPdf($idRol, $mesesRestriccion, $filtros, self::MAX_SERVICIOS_ZIP);

        echo json_encode(['data' => $datos], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function ajaxPuntos()
    {
        $this->iniciarJson();

        $idCliente = null;
        if (isset($_POST['id_cliente']) && $_POST['id_cliente'] !== '') {
            $idCliente = (int) $_POST['id_cliente'];
        } elseif (isset($_GET['id_cliente']) && $_GET['id_cliente'] !== '') {
            $idCliente = (int) $_GET['id_cliente'];
        }

        echo json_encode(['data' => $this->modelo->obtenerPuntos($idCliente)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function crearTrabajoZip()
    {
        $this->iniciarJson();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(['status' => 'error', 'msg' => 'Método no permitido.']);
        }

        [$idRol, $mesesRestriccion] = $this->obtenerRestriccion();
        $filtros = $this->leerFiltros();
        $ids = $this->leerIdsSeleccionados();
        $modo = !empty($ids) ? 'ids' : 'filtros';
        $advertencia = null;

        if ($modo === 'ids') {
            $servicios = $this->modelo->obtenerServiciosPorIds($ids, $idRol, $mesesRestriccion);
            if (count($servicios) > self::MAX_SERVICIOS_ZIP) {
                $servicios = array_slice($servicios, 0, self::MAX_SERVICIOS_ZIP);
                $advertencia = 'Se limitó la descarga a ' . self::MAX_SERVICIOS_ZIP . ' servicios.';
            }
        } else {
            $servicios = $this->modelo->listarServiciosParaPdf(
                $idRol,
                $mesesRestriccion,
                $filtros,
                self::MAX_SERVICIOS_ZIP + 1
            );

            if (count($servicios) > self::MAX_SERVICIOS_ZIP) {
                $servicios = array_slice($servicios, 0, self::MAX_SERVICIOS_ZIP);
                $advertencia = 'La selección supera el máximo permitido y se limitó a ' . self::MAX_SERVICIOS_ZIP . ' servicios.';
            }
        }

        if (empty($servicios)) {
            $this->responderJson(['status' => 'error', 'msg' => 'No se encontraron servicios con los filtros seleccionados.']);
        }

        $token = bin2hex(random_bytes(16));
        $base = $this->rutaBaseTrabajos();
        $this->asegurarDirectorio($base);
        $this->limpiarTrabajosViejos();

        $this->asegurarDirectorio($this->rutaDirectorioTrabajo($token));

        $trabajo = [
            'token' => $token,
            'usuario_id' => isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null,
            'creado' => time(),
            'modo' => $modo,
            'filtros' => $filtros,
            'ids' => array_map(fn($s) => (int) $s['id_ordenes_servicio'], $servicios),
            'offset' => 0,
            'total' => count($servicios),
            'errores' => [],
            'incluir_delegacion' => empty($filtros['id_delegacion']),
            'incluir_tecnico' => empty($filtros['id_tecnico']),
            'completado' => false
        ];

        $this->guardarTrabajo($trabajo);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        echo json_encode([
            'status' => 'ok',
            'token' => $token,
            'total' => $trabajo['total'],
            'advertencia' => $advertencia
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function procesarLoteZip()
    {
        $this->iniciarJson();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(['status' => 'error', 'msg' => 'Método no permitido.']);
        }

        $token = $_POST['token'] ?? '';
        $trabajo = $this->leerTrabajo($token);

        if (!$trabajo) {
            $this->responderJson(['status' => 'error', 'msg' => 'El trabajo de descarga no existe o expiró.']);
        }

        if (!empty($trabajo['completado'])) {
            $this->responderJson([
                'status' => 'ok',
                'procesados' => (int) $trabajo['total'],
                'total' => (int) $trabajo['total'],
                'completado' => true
            ]);
        }

        $lote = (int) ($_POST['lote'] ?? self::LOTE_PDF_DEFAULT);
        $lote = max(1, min(10, $lote));
        $offset = (int) $trabajo['offset'];
        $total = (int) $trabajo['total'];

        if ($offset >= $total) {
            $trabajo['completado'] = true;
            $this->guardarTrabajo($trabajo);
            $this->responderJson(['status' => 'ok', 'procesados' => $total, 'total' => $total, 'completado' => true]);
        }

        $idsLote = array_slice($trabajo['ids'], $offset, $lote);
        $directorio = $this->rutaDirectorioTrabajo($token);
        $this->asegurarDirectorio($directorio);

        set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $generados = 0;
        $errores = [];
        $actual = null;

        foreach ($idsLote as $idOrden) {
            $idOrden = (int) $idOrden;
            $datosOrden = $this->modelo->obtenerDatosCompletosOrden($idOrden);

            if (!$datosOrden) {
                $errores[] = 'Orden ' . $idOrden . ': no encontrada o sin datos.';
                continue;
            }

            $resultado = $this->pdfGenerator->generarPdf($this->modelo, $idOrden, $datosOrden);

            if (!$resultado['ok']) {
                $errores[] = 'Orden ' . $idOrden . ': ' . $resultado['error'];
                continue;
            }

            $nombreArchivo = $resultado['nombreArchivo'];
            $rutaRelativa = $this->pdfGenerator->construirRutaRelativa(
                $datosOrden,
                $nombreArchivo,
                (bool) $trabajo['incluir_delegacion'],
                (bool) $trabajo['incluir_tecnico']
            );

            $rutaRelativa = $this->resolverNombreUnico($directorio, $rutaRelativa, $idOrden);
            $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
            $this->asegurarDirectorio(dirname($rutaAbsoluta));

            if (@file_put_contents($rutaAbsoluta, $resultado['contenido']) === false) {
                $errores[] = 'Orden ' . $idOrden . ': no se pudo guardar el PDF.';
                continue;
            }

            $generados++;
            $actual = basename($rutaRelativa);
        }

        $trabajo = $this->leerTrabajo($token);

        if (!$trabajo) {
            $this->responderJson(['status' => 'error', 'msg' => 'El trabajo de descarga expiró mientras se generaban los PDFs.']);
        }

        $trabajo['offset'] = $offset + count($idsLote);

        if (!empty($errores)) {
            $trabajo['errores'] = array_merge($trabajo['errores'], $errores);
        }

        $trabajo['completado'] = $trabajo['offset'] >= (int) $trabajo['total'];
        $this->guardarTrabajo($trabajo);

        echo json_encode([
            'status' => 'ok',
            'procesados' => (int) $trabajo['offset'],
            'total' => (int) $trabajo['total'],
            'generados' => $generados,
            'errores' => count($errores),
            'actual' => $actual,
            'completado' => (bool) $trabajo['completado']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function descargarZipTrabajo()
    {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        $trabajo = $this->leerTrabajo($token);

        if (!$trabajo) {
            die('El trabajo de descarga no existe o expiró.');
        }

        if (empty($trabajo['completado'])) {
            die('El ZIP todavía no está listo. Vuelve a intentarlo cuando finalice la generación.');
        }

        $directorio = $this->rutaDirectorioTrabajo($token);

        if (!is_dir($directorio)) {
            die('No se encontraron los archivos generados.');
        }

        if (!class_exists('ZipArchive')) {
            die('La extensión ZIP de PHP no está habilitada en el servidor.');
        }

        $rutaZip = $this->rutaBaseTrabajos() . DIRECTORY_SEPARATOR . $token . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            die('No se pudo crear el archivo ZIP.');
        }

        $archivos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS)
        );

        $agregados = 0;
        foreach ($archivos as $archivo) {
            if (!$archivo->isFile()) {
                continue;
            }

            $rutaAbsoluta = $archivo->getPathname();
            $rutaRelativa = substr($rutaAbsoluta, strlen($directorio) + 1);
            $rutaRelativa = str_replace('\\', '/', $rutaRelativa);

            if ($zip->addFile($rutaAbsoluta, $rutaRelativa)) {
                $agregados++;
            }
        }

        if (!empty($trabajo['errores'])) {
            $zip->addFromString('errores.txt', implode(PHP_EOL, $trabajo['errores']));
        }

        $zip->close();

        if ($agregados === 0) {
            $detalle = !empty($trabajo['errores'])
                ? ' Detalle: ' . htmlspecialchars(implode(' | ', array_slice($trabajo['errores'], 0, 3)), ENT_QUOTES, 'UTF-8')
                : '';

            $this->eliminarTrabajo($token);
            die('No se generó ningún PDF. Revisa los filtros e intenta de nuevo.' . $detalle);
        }

        $nombreZip = 'Servicios_' . date('Ymd_His') . '.zip';

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $nombreZip . '"');
        header('Content-Length: ' . filesize($rutaZip));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($rutaZip);
        $this->eliminarTrabajo($token);
        exit;
    }

    public function cancelarTrabajoZip()
    {
        $this->iniciarJson();

        $token = $_POST['token'] ?? $_GET['token'] ?? '';
        $trabajo = $this->leerTrabajo($token);

        if ($trabajo) {
            $this->eliminarTrabajo($token);
        }

        $this->responderJson(['status' => 'ok']);
    }

    private function iniciarJson()
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json');
    }

    private function responderJson($datos)
    {
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function obtenerRestriccion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idRol = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : null;
        $mesesRestriccion = 0;

        if ($idRol === 4) {
            $paramValor = $this->modelo->obtenerParametro('meses_restriccion_prosegur');
            $mesesRestriccion = $paramValor ? (int) $paramValor : 1;
        }

        return [$idRol, $mesesRestriccion];
    }

    private function leerFiltros()
    {
        $fuente = !empty($_POST) ? $_POST : $_GET;
        $filtros = [];

        $fechaInicio = trim((string) ($fuente['fecha_inicio'] ?? ''));
        $fechaFin = trim((string) ($fuente['fecha_fin'] ?? ''));

        if ($this->esFechaValida($fechaInicio)) {
            $filtros['fecha_inicio'] = $fechaInicio;
        }

        if ($this->esFechaValida($fechaFin)) {
            $filtros['fecha_fin'] = $fechaFin;
        }

        foreach (['id_cliente', 'id_punto', 'id_tecnico', 'id_delegacion', 'id_tipo_mantenimiento'] as $campo) {
            if (isset($fuente[$campo]) && $fuente[$campo] !== '') {
                $valor = (int) $fuente[$campo];
                if ($valor > 0) {
                    $filtros[$campo] = $valor;
                }
            }
        }

        return $filtros;
    }

    private function esFechaValida($fecha)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $fecha)) {
            return false;
        }

        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);

        return $fechaObj && $fechaObj->format('Y-m-d') === $fecha;
    }

    private function leerIdsSeleccionados()
    {
        $ids = $_POST['ids'] ?? $_GET['ids'] ?? [];

        if (is_string($ids)) {
            $ids = strpos($ids, ',') !== false ? explode(',', $ids) : [$ids];
        }

        $ids = array_map('intval', (array) $ids);
        $ids = array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));

        return $ids;
    }

    private function rutaBaseTrabajos()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inees_zip_jobs';
    }

    private function rutaDirectorioTrabajo($token)
    {
        return $this->rutaBaseTrabajos() . DIRECTORY_SEPARATOR . $token;
    }

    private function rutaEstadoTrabajo($token)
    {
        return $this->rutaBaseTrabajos() . DIRECTORY_SEPARATOR . $token . '.json';
    }

    private function asegurarDirectorio($directorio)
    {
        if (!is_dir($directorio)) {
            @mkdir($directorio, 0777, true);
        }
    }

    private function guardarTrabajo($trabajo)
    {
        $ruta = $this->rutaEstadoTrabajo($trabajo['token']);
        @file_put_contents($ruta, json_encode($trabajo, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function leerTrabajo($token)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', (string) $token)) {
            return null;
        }

        $ruta = $this->rutaEstadoTrabajo($token);

        if (!is_file($ruta)) {
            return null;
        }

        $trabajo = json_decode((string) file_get_contents($ruta), true);

        if (!is_array($trabajo)) {
            return null;
        }

        $usuarioActual = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
        $usuarioTrabajo = isset($trabajo['usuario_id']) ? (int) $trabajo['usuario_id'] : null;

        if ($usuarioActual !== null && $usuarioTrabajo !== $usuarioActual) {
            return null;
        }

        return $trabajo;
    }

    private function resolverNombreUnico($directorio, $rutaRelativa, $idOrden)
    {
        $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);

        if (!file_exists($rutaAbsoluta)) {
            return $rutaRelativa;
        }

        $info = pathinfo($rutaRelativa);
        $prefijo = (isset($info['dirname']) && $info['dirname'] !== '.') ? $info['dirname'] . '/' : '';

        return $prefijo . $info['filename'] . '_' . (int) $idOrden . '.pdf';
    }

    private function limpiarTrabajosViejos()
    {
        $base = $this->rutaBaseTrabajos();

        if (!is_dir($base)) {
            return;
        }

        $archivos = glob($base . DIRECTORY_SEPARATOR . '*.json');

        if (!$archivos) {
            return;
        }

        foreach ($archivos as $archivo) {
            $trabajo = json_decode((string) @file_get_contents($archivo), true);
            $creado = is_array($trabajo) ? (int) ($trabajo['creado'] ?? 0) : 0;

            if ($creado === 0 || (time() - $creado) > self::TTL_TRABAJO_SEGUNDOS) {
                $token = basename($archivo, '.json');
                $this->eliminarTrabajo($token);
            }
        }
    }

    private function eliminarTrabajo($token)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', (string) $token)) {
            return;
        }

        $this->eliminarDirectorio($this->rutaDirectorioTrabajo($token));

        $estado = $this->rutaEstadoTrabajo($token);
        if (is_file($estado)) {
            @unlink($estado);
        }

        $zip = $this->rutaBaseTrabajos() . DIRECTORY_SEPARATOR . $token . '.zip';
        if (is_file($zip)) {
            @unlink($zip);
        }
    }

    private function eliminarDirectorio($directorio)
    {
        if (!is_dir($directorio)) {
            return;
        }

        $archivos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($archivos as $archivo) {
            if ($archivo->isDir()) {
                @rmdir($archivo->getPathname());
            } else {
                @unlink($archivo->getPathname());
            }
        }

        @rmdir($directorio);
    }
}
