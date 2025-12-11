<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. IMPORTAR ARCHIVOS NECESARIOS (Sin esto, PHP no encuentra las clases)
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenCrearModelo.php';class ordenCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        // 2. CORRECCIÓN: Usamos la clase 'Conexion' (no 'db')
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        // 3. Instanciamos el modelo pasándole la conexión activa
        $this->modelo = new ordenCrearModels($this->db);
    }
    // AGREGA ESTA FUNCIÓN AQUÍ 👇
    // Sirve de puente: si el router busca "index", lo manda a "cargarVista"
    public function index()
    {
        $this->cargarVista();
    }

    public function cargarVista()
    {
        $clientes   = $this->modelo->obtenerClientes();
        $tiposManto = $this->modelo->obtenerTiposMantenimiento();
        $tecnicos   = $this->modelo->obtenerTecnicos();

        // --- NUEVO: TRAER LISTAS DINÁMICAS ---
        $estados    = $this->modelo->obtenerEstadosMaquina();
        $califs     = $this->modelo->obtenerCalificaciones();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();

        $titulo = "Reporte de Servicios";
        $vistaContenido = "app/views/orden/ordenCrearVista.php";
        include "app/views/plantillaVista.php";
    }

    public function ajaxPuntos()
    {
        // CRÍTICO: Limpiar TODO el buffer de salida
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Iniciar captura limpia
        ob_start();

        header('Content-Type: application/json; charset=utf-8');

        try {
            if (isset($_POST['id_cliente']) && !empty($_POST['id_cliente'])) {
                $id = intval($_POST['id_cliente']);
                $puntos = $this->modelo->obtenerPuntosPorCliente($id);

                echo json_encode($puntos, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['error' => 'ID Cliente no recibido']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        ob_end_flush();
        exit;
    }

    public function ajaxMaquinas()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (isset($_POST['id_punto']) && !empty($_POST['id_punto'])) {
                $id = intval($_POST['id_punto']);
                $maquinas = $this->modelo->obtenerMaquinasPorPunto($id);

                echo json_encode($maquinas, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['error' => 'ID Punto no recibido']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        ob_end_flush();
        exit;
    }

    public function ajaxCalcularPrecio()
    {
        // Limpiamos buffer de salida para evitar errores de JSON
        while (ob_get_level()) {
            ob_end_clean();
        }

        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            // CAMBIO: Ahora validamos 'id_modalidad' en vez de 'id_zona'
            if (isset($_POST['id_maquina_tipo']) && isset($_POST['id_manto']) && isset($_POST['id_modalidad'])) {
                
                $precio = $this->modelo->consultarTarifa(
                    intval($_POST['id_maquina_tipo']),
                    intval($_POST['id_manto']),
                    intval($_POST['id_modalidad']) // <--- AQUÍ ESTÁ EL CAMBIO CLAVE
                );
                
                echo json_encode(['precio' => $precio]);
            } else {
                echo json_encode(['precio' => 0, 'error' => 'Parámetros incompletos (Falta modalidad)']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        ob_end_flush();
        exit;
    }
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $fechaReporte = $_POST['fecha_reporte'];
            $filas = $_POST['filas'] ?? [];

            $guardados = 0;
            $errores = 0;

            foreach ($filas as $fila) {
                if (!empty($fila['id_maquina'])) {

                    // --- LIMPIEZA DEL PRECIO ---
                    // Quitamos '$', espacios y puntos para que quede solo el número
                    // Ej: "$ 150.000" -> "150000"
                    $valorLimpio = str_replace(['$', '.', ' '], '', $fila['valor']);
                    // Si viene vacío, ponemos 0
                    $valorFinal = is_numeric($valorLimpio) ? $valorLimpio : 0;

                    $datosParaModelo = [
                        'remision'      => $fila['remision'],
                        'id_cliente'    => $fila['id_cliente'],    // <--- ¡AGREGA ESTO!
                        'id_punto'      => $fila['id_punto'],
                        'id_modalidad'  => $fila['id_modalidad'],
                        'fecha'         => $fechaReporte,
                        'id_maquina'    => $fila['id_maquina'],
                        'id_tecnico'    => $fila['id_tecnico'],
                        'tipo_servicio' => $fila['tipo_servicio'],
                        'valor'         => $valorFinal,        // <--- ENVIAMOS EL LIMPIO
                        'hora_entrada'  => $fila['hora_in'],
                        'hora_salida'   => $fila['hora_out'],
                        'estado'        => $fila['estado'],
                        'calif'         => $fila['calif'],
                        'obs'           => $fila['obs'],
                        'json_repuestos' => $fila['json_repuestos']
                    ];

                    if ($this->modelo->guardarOrden($datosParaModelo)) {
                        $guardados++;
                    } else {
                        $errores++;
                    }
                }
            }

            // Redireccionar al final
            echo "<script>
                alert('Proceso terminado. Guardados: $guardados. Errores: $errores');
                window.location.href = 'index.php?pagina=ordenCrear';
            </script>";
        }
    }
}
