<?php
class ordenDetalleControlador
{
    private $modelo;

    public function __construct()
    {
        $database = new Database();
        $this->modelo = new ordenDetalleModelo($database->getConnection());
    }

    public function procesarAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

            if ($_POST['accion'] === 'ajaxObtenerPuntos') {
                $this->ajaxObtenerPuntos();
            }

            if ($_POST['accion'] === 'ajaxObtenerMaquinas') {
                $this->ajaxObtenerMaquinas();
            }

            if ($_POST['accion'] === 'ajaxObtenerDelegacion') {
                $this->ajaxObtenerDelegacion();
            }

            // ⭐ NUEVO: AJAX para obtener precio dinámico
            if ($_POST['accion'] === 'ajaxObtenerPrecio') {
                $this->ajaxObtenerPrecio();
            }
        }
    }

    // ==========================================
    // 1. CARGA LA VISTA NORMAL
    // ==========================================
    public function cargarVista()
    {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $servicios = $this->modelo->obtenerServiciosPorFecha($fecha);
        $listaClientes = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos   = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();
        $listaEstados  = $this->modelo->obtenerEstados();
        $listaCalifs   = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades(); // ⭐ NUEVO

        $titulo = "Edición Total: " . $fecha;
        $vistaContenido = "app/views/orden/ordenDetalleVista.php";
        include "app/views/plantillaVista.php";
    }

    // ==========================================
    // 2. AJAX METHODS
    // ==========================================
    public function ajaxObtenerPuntos()
    {
        ob_clean();
        $id_cliente = $_POST['id_cliente'] ?? 0;
        $puntos = $this->modelo->obtenerPuntosPorCliente($id_cliente);
        header('Content-Type: application/json');
        echo json_encode($puntos);
        exit;
    }

    public function ajaxObtenerMaquinas()
    {
        ob_clean();
        $id_punto = $_POST['id_punto'] ?? 0;
        $maquinas = $this->modelo->obtenerMaquinasPorPunto($id_punto);
        header('Content-Type: application/json');
        echo json_encode($maquinas);
        exit;
    }

    public function ajaxObtenerDelegacion()
    {
        ob_clean();
        $id_punto = $_POST['id_punto'] ?? 0;
        $delegacion = $this->modelo->obtenerDelegacionPorPunto($id_punto);
        header('Content-Type: application/json');
        echo json_encode(['delegacion' => $delegacion]);
        exit;
    }

    // ⭐ NUEVO: Obtener precio dinámico
    public function ajaxObtenerPrecio()
    {
        ob_clean();
        $id_tipo_maquina = $_POST['id_tipo_maquina'] ?? 0;
        $id_tipo_mantenimiento = $_POST['id_tipo_mantenimiento'] ?? 0;
        $id_modalidad = $_POST['id_modalidad'] ?? 1;

        $precio = $this->modelo->obtenerPrecioTarifa($id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad);

        header('Content-Type: application/json');
        echo json_encode(['precio' => $precio]);
        exit;
    }

    // ==========================================
    // 3. GUARDAR CAMBIOS
    // ==========================================
    public function guardarCambios()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $servicios = $_POST['servicios'] ?? [];
            $fechaOrigen = $_POST['fecha_origen'];

            foreach ($servicios as $id => $datos) {
                // Calcular Tiempo
                $tiempoCalc = "00:00";
                if (!empty($datos['entrada']) && !empty($datos['salida'])) {
                    $d1 = new DateTime($datos['entrada']);
                    $d2 = new DateTime($datos['salida']);
                    if ($d2 < $d1) $d2->modify('+1 day');
                    $tiempoCalc = $d1->diff($d2)->format('%H:%I');
                }

                // Limpiar precio
                $valorLimpio = str_replace('.', '', $datos['valor']);
                $valorLimpio = str_replace(',', '.', $valorLimpio);

                $this->modelo->actualizarOrdenFull($id, [
                    'id_cliente' => $datos['id_cliente'],
                    'id_punto'   => $datos['id_punto'],
                    'id_maquina' => $datos['id_maquina'],
                    'id_modalidad' => $datos['id_modalidad'],
                    'remision'   => $datos['remision'],
                    'id_tecnico' => $datos['id_tecnico'],
                    'id_manto'   => $datos['id_manto'],
                    'id_estado'  => $datos['id_estado'],
                    'id_calif'   => $datos['id_calif'],
                    'entrada'    => $datos['entrada'],
                    'salida'     => $datos['salida'],
                    'tiempo'     => $tiempoCalc,
                    'valor'      => $valorLimpio,
                    'obs'        => $datos['obs'],
                    'tiene_novedad' => $datos['tiene_novedad'] ?? 0,
                    'fecha_individual' => $datos['fecha_individual'],
                    
                    // 👇 ¡ESTA ES LA LÍNEA QUE FALTABA! 👇
                    // Sin esto, el modelo recibía NULL y no guardaba nada
                    'json_repuestos' => $datos['json_repuestos'] ?? '[]'
                ]);
            }

            echo "<script>
                alert('¡Todo actualizado correctamente! Repuestos guardados.');
                window.location.href = 'index.php?pagina=ordenDetalle&fecha=$fechaOrigen';
            </script>";
        }
    }
}