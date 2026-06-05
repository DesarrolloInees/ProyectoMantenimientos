<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/transporte/transporteCrearModelo.php';

class transporteCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new transporteCrearModelo($this->db);
    }

    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        $tecnicos = $this->modelo->obtenerTecnicos();
        $tiposMaquina = $this->modelo->obtenerTiposMaquina();
        $clientes = $this->modelo->obtenerClientes();

        $titulo = "Registro de Transportes";
        $vistaContenido = "app/views/transporte/transporteCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxPuntos()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!empty($_POST['id_cliente']) && is_numeric($_POST['id_cliente'])) {
                $puntos = $this->modelo->obtenerPuntosPorCliente(intval($_POST['id_cliente']));
                echo json_encode($puntos, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function ajaxRemisiones()
    {
        $this->limpiarBuffer();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!empty($_POST['id_tecnico'])) {
                $remisiones = $this->modelo->obtenerRemisionesDisponibles(intval($_POST['id_tecnico']));
                echo json_encode($remisiones, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?pagina=transporte');
            exit;
        }

        // Función auxiliar para parsear inputs dinámicos (ID vs Texto)
        $parseDinamico = function($input) {
            $val = trim($input ?? '');
            if ($val === '') return ['id' => null, 'texto' => null];
            if (is_numeric($val)) return ['id' => intval($val), 'texto' => null];
            return ['id' => null, 'texto' => $val];
        };

        $clienteOrigen = $parseDinamico($_POST['cliente_origen'] ?? '');
        $puntoOrigen = $parseDinamico($_POST['punto_origen'] ?? '');
        $clienteDestino = $parseDinamico($_POST['cliente_destino'] ?? '');
        $puntoDestino = $parseDinamico($_POST['punto_destino'] ?? '');

        // Determinar nombre del servicio basado en la categoría
        $categoria = $_POST['categoria_servicio'] ?? '';
        $tipoServicioNombre = null;
        if ($categoria === 'Inees') {
            $tipoServicioNombre = $_POST['tipo_servicio_inees'] ?? null;
        } else if ($categoria === 'Prosegur_Cobro') {
            $tipoServicioNombre = $_POST['tipo_servicio_cobro'] ?? null;
        } else if ($categoria === 'Prosegur_NoCobro') {
            $tipoServicioNombre = $_POST['tipo_servicio_nocobro'] ?? null;
        }

        // --- LÓGICA DE CARGA DE EVIDENCIAS ---
        $fotoRemisionPath = null;
        $fotoMaquinaPath = null;
        $fotoChazosPath = null;

        // Limpiamos el nombre de la remisión para que sea una carpeta válida en Windows/Linux
        $nombreRemision = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_POST['texto_remision'] ?? 'SIN_REMISION');
        if (empty(trim($nombreRemision))) $nombreRemision = 'SIN_REMISION';
        
        // Directorio: app/uploads/transporte/{numero_remision}/
        $directorioDestino = 'app/uploads/transporte/' . $nombreRemision . '/';

        // Crear carpeta si no existe
        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }

        // Función auxiliar para subir las fotos
        $subirFoto = function($inputName, $prefijo) use ($directorioDestino) {
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
                $nuevoNombre = $prefijo . '_' . uniqid() . '.' . $ext;
                $rutaFinal = $directorioDestino . $nuevoNombre;
                
                if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $rutaFinal)) {
                    return $rutaFinal;
                }
            }
            return null;
        };

        $fotoRemisionPath = $subirFoto('foto_remision', 'remision');
        $fotoMaquinaPath = $subirFoto('foto_maquina', 'maquina');
        $fotoChazosPath = $subirFoto('foto_chazos', 'chazos');
        // ------------------------------------

        $datos = [
            'id_tecnico'            => $_POST['id_tecnico'] ?? null,
            'id_control_remision'   => $_POST['id_control_remision'] ?? null,
            'fecha_instalacion'     => $_POST['fecha_instalacion'] ?? null,
            'categoria_servicio'    => $categoria,
            'tipo_servicio_nombre'  => $tipoServicioNombre,
            'notas'                 => trim($_POST['notas'] ?? ''),
            'descripcion_inees'     => trim($_POST['descripcion_inees'] ?? ''),
            
            'lugar_recogida'        => $_POST['lugar_recogida'] ?? null,
            'fecha_recogida'        => $_POST['fecha_recogida'] ?? null,
            
            'es_maquina'            => isset($_POST['es_maquina']) ? intval($_POST['es_maquina']) : 1,
            'id_tipo_maquina'       => $_POST['id_tipo_maquina'] ?? null,
            'serial_maquina'        => trim($_POST['serial_maquina'] ?? ''),
            'producto_otro'         => trim($_POST['producto_otro'] ?? ''),
            
            'id_cliente_origen'     => $clienteOrigen['id'],
            'cliente_origen_texto'  => $clienteOrigen['texto'],
            'id_punto_origen'       => $puntoOrigen['id'],
            'punto_origen_texto'    => $puntoOrigen['texto'],
            
            'id_cliente_destino'    => $clienteDestino['id'],
            'cliente_destino_texto' => $clienteDestino['texto'],
            'id_punto_destino'      => $puntoDestino['id'],
            'punto_destino_texto'   => $puntoDestino['texto'],
            
            'foto_remision'         => $fotoRemisionPath,
            'foto_maquina'          => $fotoMaquinaPath,
            'foto_chazos'           => $fotoChazosPath
        ];

        // Validaciones básicas
        if (empty($datos['categoria_servicio']) || empty($datos['id_tecnico']) || empty($datos['fecha_instalacion'])) {
            echo "<script>alert('⚠️ Faltan datos requeridos (Categoría, Técnico o Fecha).'); history.back();</script>";
            return;
        }

        $idInstalacion = $this->modelo->guardarInstalacion($datos);

        if ($idInstalacion) {
            $_SESSION['mensaje_exito'] = "✅ Registro #$idInstalacion guardado correctamente.";
            header("Location: index.php?pagina=transporteVer");
            exit;
        } else {
            $_SESSION['mensaje_error'] = "❌ Error al guardar el registro. Revisa los datos e intenta de nuevo.";
            header("Location: index.php?pagina=transporteCrear");
            exit;
        }
    }

    private function limpiarBuffer()
    {
        while (ob_get_level()) ob_end_clean();
        ob_start();
    }
}