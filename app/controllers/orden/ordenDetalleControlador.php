<?php
// app/controllers/orden/ordenDetalleControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// 1. IMPORTAR ARCHIVOS NECESARIOS (Sin esto, PHP no encuentra las clases)
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenDetalleModelo.php';

class ordenDetalleControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        // 2. CORRECCIÓN: Usamos la clase 'Conexion' (no 'db')
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();

        // 3. Instanciamos el modelo pasándole la conexión activa
        $this->modelo = new ordenDetalleModelo($this->db);
    }

    // ==========================================
    // 0. PROCESAR AJAX (Ruteo interno)
    // ==========================================
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

            if ($_POST['accion'] === 'ajaxObtenerPrecio') {
                $this->ajaxObtenerPrecio();
            }
            if ($_POST['accion'] === 'ajaxObtenerStockTecnico') {
                $this->ajaxObtenerStockTecnico();
            }
            if ($_POST['accion'] === 'ajaxGestionarRepuestoRT') {
                $this->ajaxGestionarRepuestoRT();
            }
            if ($_POST['accion'] === 'ajaxGuardarNovedad') {
                $this->ajaxGuardarNovedad();
            }
        }
    }

    // ==========================================
    // 1. CARGA LA VISTA NORMAL
    // ==========================================
    public function cargarVista()
    {
        // Verificar sesión (Doble seguridad)
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        // Datos para la vista
        $servicios = $this->modelo->obtenerServiciosPorFecha($fecha);
        $listaClientes = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos   = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();
        $listaEstados  = $this->modelo->obtenerEstados();
        $listaCalifs   = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos = $this->modelo->obtenerFestivos(); // <--- ESTO ES VITAL
        $listaFestivos = $this->modelo->obtenerFestivos();
        $listaNovedades = $this->modelo->obtenerTiposNovedad();

        $titulo = "Edición Total: " . $fecha;

        // Rutas relativas desde index.php
        $vistaContenido = "app/views/orden/ordenDetalleVista.php";

        // Incluimos la plantilla maestra
        // Salimos de 'orden' (..), salimos de 'controllers' (..), entramos a 'views'
        require_once __DIR__ . '/../../views/plantillaVista.php';
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

    public function ajaxObtenerPrecio()
    {
        ob_clean();
        $id_tipo_maquina = $_POST['id_tipo_maquina'] ?? 0;
        $id_tipo_mantenimiento = $_POST['id_tipo_mantenimiento'] ?? 0;
        $id_modalidad = $_POST['id_modalidad'] ?? 1;

        // 🔥 1. RECIBIMOS LA FECHA DE LA FILA
        $fechaVisita = $_POST['fecha_visita'] ?? date('Y-m-d');

        // 🔥 2. CALCULAMOS EL AÑO (Ej: '2026-01-05' -> 2026)
        $anio = date('Y', strtotime($fechaVisita));

        // 🔥 3. LLAMAMOS AL MODELO CON EL AÑO
        $precio = $this->modelo->obtenerPrecioTarifa($id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad, $anio);

        header('Content-Type: application/json');
        echo json_encode(['precio' => $precio]);
        exit;
    }

    public function ajaxObtenerStockTecnico()
    {
        ob_clean(); // Limpiar buffers previos
        $idTecnico = $_POST['id_tecnico'] ?? 0;

        if ($idTecnico > 0) {
            $stock = $this->modelo->obtenerStockPorTecnico($idTecnico);
            header('Content-Type: application/json');
            echo json_encode($stock);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    public function ajaxGestionarRepuestoRT()
    {
        ob_clean();
        header('Content-Type: application/json');

        $tipo       = $_POST['tipo']; // 'agregar' o 'eliminar'
        $idOrden    = $_POST['id_orden'];
        $idRepuesto = $_POST['id_repuesto'];
        $origen     = $_POST['origen'];
        $idTecnico  = $_POST['id_tecnico'];

        if ($tipo === 'agregar') {
            $cantidad = $_POST['cantidad'];
            $res = $this->modelo->agregarRepuestoRealTime($idOrden, $idRepuesto, $cantidad, $origen, $idTecnico);
        } else {
            $res = $this->modelo->eliminarRepuestoRealTime($idOrden, $idRepuesto, $origen, $idTecnico);
        }

        echo json_encode($res);
        exit;
    }

    // ==========================================
    // 3. GUARDAR CAMBIOS (ADAPTADO AL INDEX.PHP ORIGINAL)
    // ==========================================
    public function guardarCambios()
    {
        // ---------------------------------------------------------
        // 🚫 ELIMINAMOS EL BLOQUE QUE DESVIABA A AJAX 🚫
        // (El index.php ya se encargó de llamarnos directamente)
        // ---------------------------------------------------------

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Recoger datos
            $servicios = $_POST['servicios'] ?? [];
            
            // Si no viene fecha de origen, usamos la actual
            $fechaOrigen = $_POST['fecha_origen'] ?? date('Y-m-d');
            
            // Detectar si venimos del buscador
            $esBusqueda = isset($_POST['es_busqueda']) && $_POST['es_busqueda'] == '1';

            $errores = 0;

            foreach ($servicios as $id => $datos) {

                // A. LIMPIEZA DE PRECIO
                if (isset($datos['valor'])) {
                    $valorLimpio = str_replace('.', '', $datos['valor']);
                    $datos['valor'] = str_replace(',', '.', $valorLimpio);
                }

                // B. CALCULAR TIEMPO
                if (!isset($datos['tiempo']) || empty($datos['tiempo'])) {
                    $datos['tiempo'] = '00:00';
                    if (!empty($datos['entrada']) && !empty($datos['salida'])) {
                        try {
                            $d1 = new DateTime($datos['entrada']);
                            $d2 = new DateTime($datos['salida']);
                            if ($d2 < $d1) $d2->modify('+1 day');
                            $datos['tiempo'] = $d1->diff($d2)->format('%H:%I');
                        } catch (Exception $e) {}
                    }
                }

                // C. FECHA INDIVIDUAL
                if (empty($datos['fecha_individual'])) {
                    $datos['fecha_individual'] = $fechaOrigen;
                }

                // D. GUARDAR EN BD
                // Usamos la función robusta que ya corregimos en el Modelo
                $resultado = $this->modelo->actualizarOrdenFull($id, $datos);
                
                if (!$resultado) {
                    $errores++;
                }
            }

            // 2. REDIRECCIÓN
            if ($esBusqueda) {
                $urlDestino = BASE_URL . "ordenDetalleBuscar";
            } else {
                $urlDestino = BASE_URL . "ordenDetalle/" . $fechaOrigen;
            }

            if ($errores > 0) {
                echo "<script>
                    alert('Se guardaron los cambios, pero hubo errores en $errores filas.');
                    window.location.href = '$urlDestino';
                </script>";
            } else {
                echo "<script>
                    alert('¡Cambios guardados correctamente!');
                    window.location.href = '$urlDestino';
                </script>";
            }
            exit; // Importante detener el script aquí
        } else {
            // Si intentan entrar directo por URL sin POST
            header('Location: ' . BASE_URL . 'ordenDetalle');
            exit;
        }
    }


    // A. Cargar la vista del buscador
    public function cargarVistaBusqueda()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // Listas necesarias para los selectores
        $listaClientes  = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos  = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos    = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();
        $listaEstados   = $this->modelo->obtenerEstados();
        $listaCalifs    = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos  = $this->modelo->obtenerFestivos();

        $vistaContenido = "app/views/orden/ordenBusquedaVista.php"; // <--- NUEVA VISTA
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    // B. Procesar la búsqueda AJAX
    public function ajaxBuscarOrdenes()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [
                'remision'   => $_POST['remision'] ?? '',
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'id_punto'   => $_POST['id_punto'] ?? '',
                // 🔥 AGREGAR ESTAS 3 LÍNEAS NUEVAS:
                'id_delegacion' => $_POST['id_delegacion'] ?? '',
                'fecha_inicio'  => $_POST['fecha_inicio'] ?? '',
                'fecha_fin'     => $_POST['fecha_fin'] ?? ''
            ];

            $servicios = $this->modelo->buscarOrdenesFiltros($filtros);

            // === CORRECCIÓN: CARGAR TODAS LAS LISTAS NECESARIAS PARA detalleFila.php ===
            $listaClientes    = $this->modelo->obtenerTodosLosClientes(); // <--- FALTABA ESTA
            $listaTecnicos    = $this->modelo->obtenerTodosLosTecnicos();
            $listaMantos      = $this->modelo->obtenerTiposMantenimiento();
            $listaEstados     = $this->modelo->obtenerEstados();
            $listaCalifs      = $this->modelo->obtenerCalificaciones();
            $listaModalidades = $this->modelo->obtenerModalidades();      // <--- Y ESTA

            // Renderizamos
            ob_start();
            if (empty($servicios)) {
                echo '<tr><td colspan="16" class="p-4 text-center text-red-500 font-bold">No se encontraron servicios con esos filtros.</td></tr>';
            } else {
                foreach ($servicios as $s) {
                    $idFila = $s['id_ordenes_servicio'];
                    // Ahora sí, detalleFila tendrá acceso a $listaClientes y demás
                    include __DIR__ . '/../../views/orden/partials/detalleFila.php';
                }
            }
            $html = ob_get_clean();

            echo $html;
            exit;
        }
    }

    // En ordenDetalleControlador.php -> function ajaxGuardarNovedad()

    public function ajaxGuardarNovedad()
    {
        ob_clean(); // Limpia cualquier basura anterior
        header('Content-Type: application/json');

        $idOrden = $_POST['id_orden'] ?? 0;
        $tipo    = $_POST['tipo'] ?? ''; // 'guardar' o 'eliminar'

        // Verificar que venga un ID válido
        if ($idOrden <= 0) {
            echo json_encode(['success' => false, 'msg' => 'ID inválido']);
            exit;
        }

        if ($tipo === 'eliminar') {
            // Llama a la función del modelo que acabamos de corregir
            $res = $this->modelo->eliminarNovedadOrden($idOrden);
        } else {
            $idTipoNovedad = $_POST['id_tipo_novedad'] ?? null;
            $res = $this->modelo->guardarNovedadOrden($idOrden, $idTipoNovedad);
        }

        echo json_encode(['success' => $res]);
        exit;
    }
}
