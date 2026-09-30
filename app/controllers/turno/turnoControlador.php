<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/turno/turnoModelo.php';

/**
 * Marcación de entrada del técnico.
 * - index()     : si abren /turno directo, los manda al inicio (aquí solo viven las acciones AJAX).
 * - guardar()   : guarda o corrige la entrada del día (POST -> JSON).
 * - estadoHoy() : devuelve el estado del día del técnico logueado (GET -> JSON).
 */
class turnoControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new turnoModelo($this->db);
    }

    public function index()
    {
        header("Location: " . BASE_URL . "inicio");
        exit;
    }

    /** POST: guarda (o corrige) la marcación del día. */
    public function guardar()
    {
        $this->responderJSON();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'msj' => 'Método no permitido.']);
        }

        $datosTecnico = $this->tecnicoDeLaSesion();

        // ---- FECHA ----
        $fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
            $this->json(['ok' => false, 'msj' => 'La fecha no es válida.']);
        }
        if ($fecha > date('Y-m-d')) {
            $this->json(['ok' => false, 'msj' => 'No puedes registrar una fecha futura.']);
        }

        // ---- HORA DE ENTRADA ----
        $horaEntrada = $this->normalizarHora(isset($_POST['hora_entrada']) ? $_POST['hora_entrada'] : '');
        if ($horaEntrada === null) {
            $this->json(['ok' => false, 'msj' => 'La hora de entrada no es válida (usa el formato HH:MM).']);
        }

        // ---- NOVEDAD (opcional, texto libre) ----
        $novedad = isset($_POST['novedad']) ? trim($_POST['novedad']) : '';
        if (mb_strlen($novedad, 'UTF-8') > 1000) {
            $novedad = mb_substr($novedad, 0, 1000, 'UTF-8');
        }

        // ---- UBICACIÓN (opcional: si el técnico niega el permiso, queda vacía) ----
        $latitud  = $this->normalizarCoordenada(isset($_POST['latitud']) ? $_POST['latitud'] : null, 90);
        $longitud = $this->normalizarCoordenada(isset($_POST['longitud']) ? $_POST['longitud'] : null, 180);

        $idTecnico = (int) $datosTecnico['id_tecnico'];
        $existia = (bool) $this->modelo->obtenerTurnoPorDia($idTecnico, $fecha);

        $ok = $this->modelo->guardarEntrada([
            'id_tecnico'   => $idTecnico,
            'fecha'        => $fecha,
            'hora_entrada' => $horaEntrada,
            'novedad'      => ($novedad === '') ? null : $novedad,
            'latitud'      => $latitud,
            'longitud'     => $longitud
        ]);

        if (!$ok) {
            $this->json(['ok' => false, 'msj' => 'No se pudo guardar la marcación. Intenta de nuevo.']);
        }

        $turno = $this->modelo->obtenerTurnoPorDia($idTecnico, $fecha);
        $fechaTexto = date('d/m/Y', strtotime($fecha));
        $horaTexto = substr($horaEntrada, 0, 5);

        $this->json([
            'ok'    => true,
            'msj'   => $existia
                ? 'Se actualizó tu entrada del ' . $fechaTexto . ' a las ' . $horaTexto . '.'
                : '¡Entrada registrada! ' . $fechaTexto . ' a las ' . $horaTexto . '.',
            'turno' => $turno ? $this->formatearTurno($turno) : null
        ]);
    }

    /** GET: estado de la marcación del día. */
    public function estadoHoy()
    {
        $this->responderJSON();
        $datosTecnico = $this->tecnicoDeLaSesion();

        $fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $turno = $this->modelo->obtenerTurnoPorDia((int) $datosTecnico['id_tecnico'], $fecha);

        $this->json([
            'ok'    => true,
            'turno' => $turno ? $this->formatearTurno($turno) : null
        ]);
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    /** Devuelve el técnico de la sesión o corta con error en JSON. */
    private function tecnicoDeLaSesion()
    {
        $idUsuarioLogueado = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

        if ($idUsuarioLogueado === 0) {
            $this->json(['ok' => false, 'msj' => 'Tu sesión expiró. Vuelve a iniciar sesión.']);
        }

        $datosTecnico = $this->modelo->obtenerDatosTecnicoPorUsuario($idUsuarioLogueado);
        if (!$datosTecnico || empty($datosTecnico['id_tecnico'])) {
            $this->json(['ok' => false, 'msj' => 'Tu usuario no está vinculado a un perfil de técnico.']);
        }

        return $datosTecnico;
    }

    /** Normaliza "7:5", "07:05" o "07:05:00" a "HH:MM:SS". Devuelve null si no sirve. */
    private function normalizarHora($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $valor, $m)) {
            return null;
        }

        $h = (int) $m[1];
        $i = (int) $m[2];
        $s = isset($m[3]) ? (int) $m[3] : 0;

        if ($h > 23 || $i > 59 || $s > 59) {
            return null;
        }

        return sprintf('%02d:%02d:%02d', $h, $i, $s);
    }

    /** Valida que la coordenada sea un número real dentro del rango; si no, devuelve null. */
    private function normalizarCoordenada($valor, $limite)
    {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return null;
        }
        $num = (float) $valor;
        if ($num < -$limite || $num > $limite) {
            return null;
        }
        return $num;
    }

    /** Datos listos para pintar en el modal y en la tarjeta de inicio. */
    private function formatearTurno($turno)
    {
        return [
            'id_turno'     => (int) $turno['id_turno'],
            'fecha'        => $turno['fecha'],
            'fecha_texto'  => date('d/m/Y', strtotime($turno['fecha'])),
            'hora_entrada' => substr($turno['hora_entrada'], 0, 5),
            'novedad'      => (string) $turno['novedad'],
            'latitud'      => $turno['latitud'],
            'longitud'     => $turno['longitud']
        ];
    }

    private function responderJSON()
    {
        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
    }

    private function json($data)
    {
        echo json_encode($data);
        exit;
    }
}
