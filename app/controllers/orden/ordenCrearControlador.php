<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. IMPORTAR ARCHIVOS NECESARIOS (Sin esto, PHP no encuentra las clases)
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenCrearModelo.php';
class ordenCrearControlador
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
        $listaFestivos = $this->modelo->obtenerFestivos();

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
        // Limpiamos buffer
        while (ob_get_level()) ob_end_clean();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Validamos que lleguen los datos básicos
            if (isset($_POST['id_maquina_tipo']) && isset($_POST['id_manto']) && isset($_POST['id_modalidad'])) {

                // 🔥 1. RECIBIMOS LA FECHA (Si no llega, usamos la de hoy)
                $fechaVisita = $_POST['fecha_visita'] ?? date('Y-m-d');

                $precio = $this->modelo->consultarTarifa(
                    intval($_POST['id_maquina_tipo']),
                    intval($_POST['id_manto']),
                    intval($_POST['id_modalidad']),
                    $fechaVisita // 🔥 2. SE LA PASAMOS AL MODELO (Argumento #4)
                );

                echo json_encode(['precio' => $precio]);
            } else {
                echo json_encode(['precio' => 0, 'error' => 'Faltan datos']);
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
            // Validación: Asegurarse que 'filas' existe y es array
            $filas = $_POST['filas'] ?? [];

            $guardados = 0;
            $errores = 0;
            $detallesError = ""; // Para saber qué falló

            foreach ($filas as $index => $fila) {

                // 🔥 CLAVE: Validar que venga la máquina seleccionada
                // Si la fila 2 no tiene máquina seleccionada, el sistema la ignora
                if (!empty($fila['id_maquina'])) {

                    $valorLimpio = str_replace(['$', '.', ' '], '', $fila['valor']);
                    $valorFinal = is_numeric($valorLimpio) ? $valorLimpio : 0;

                    $datosParaModelo = [
                        'remision'      => $fila['remision'],
                        'id_cliente'    => $fila['id_cliente'] ?? null, // Usar null coalescing
                        'id_punto'      => $fila['id_punto'] ?? null,
                        'id_modalidad'  => $fila['id_modalidad'] ?? 1,
                        'fecha'         => $fechaReporte,
                        'id_maquina'    => $fila['id_maquina'],
                        'id_tecnico'    => $fila['id_tecnico'],
                        'tipo_servicio' => $fila['tipo_servicio'],
                        'valor'         => $valorFinal,
                        'hora_entrada'  => $fila['hora_in'],
                        'hora_salida'   => $fila['hora_out'],
                        'estado'        => $fila['estado'],
                        'calif'         => $fila['calif'],
                        'obs'           => $fila['obs'],
                        'json_repuestos' => $fila['json_repuestos']
                    ];

                    // Intentar guardar
                    if ($this->modelo->guardarOrden($datosParaModelo)) {
                        $guardados++;
                    } else {
                        $errores++;
                        $detallesError .= "Fila #" . ($index + 1) . " falló en BD. ";
                    }
                } else {
                    // Si entra aquí es porque la fila no tenía máquina seleccionada
                    // Esto es común si agregas filas vacías y le das guardar sin llenarlas
                }
            }

            // Feedback detallado
            if ($guardados > 0 && $errores == 0) {
                echo "<script>
                alert('✅ ÉXITO TOTAL: Se guardaron $guardados servicios correctamente.');
                window.location.href = 'index.php?pagina=ordenCrear';
            </script>";
            } else {
                echo "<script>
                alert('⚠️ ATENCIÓN: Se guardaron $guardados servicios, pero hubo $errores errores.\\nDetalles: $detallesError');
                window.location.href = 'index.php?pagina=ordenCrear';
            </script>";
            }
        }
    }
    public function ajaxRemisiones()
    {
        // Limpieza de buffer por seguridad
        while (ob_get_level()) ob_end_clean();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (isset($_POST['id_tecnico']) && !empty($_POST['id_tecnico'])) {
                $id = intval($_POST['id_tecnico']);
                // Llamamos a la función que ya tienes en el modelo
                $remisiones = $this->modelo->obtenerRemisionesDisponibles($id);
                echo json_encode($remisiones);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        ob_end_flush();
        exit;
    }
}
