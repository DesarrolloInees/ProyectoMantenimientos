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
    // 0. PROCESAR AJAX
    // ==========================================
    public function procesarAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

            $accion = $_POST['accion'];

            if ($accion === 'ajaxObtenerPuntos')         $this->ajaxObtenerPuntos();
            if ($accion === 'ajaxObtenerMaquinas')        $this->ajaxObtenerMaquinas();
            if ($accion === 'ajaxObtenerDelegacion')      $this->ajaxObtenerDelegacion();
            if ($accion === 'ajaxObtenerPrecio')          $this->ajaxObtenerPrecio();
            if ($accion === 'ajaxObtenerStockTecnico')    $this->ajaxObtenerStockTecnico();
            if ($accion === 'ajaxGestionarRepuestoRT')    $this->ajaxGestionarRepuestoRT();
            if ($accion === 'ajaxGuardarNovedad')         $this->ajaxGuardarNovedad();

            // ✅ NUEVO: Remisiones disponibles del técnico
            if ($accion === 'ajaxObtenerRemisiones')      $this->ajaxObtenerRemisiones();
            if ($accion === 'ajaxExportarDetalle') $this->ajaxExportarDetalle();
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

        $servicios        = $this->modelo->obtenerServiciosPorFecha($fecha);
        $listaClientes    = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos    = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos      = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos   = $this->modelo->obtenerListaRepuestos();
        $listaEstados     = $this->modelo->obtenerEstados();
        $listaCalifs      = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos    = $this->modelo->obtenerFestivos();
        $listaNovedades   = $this->modelo->obtenerTiposNovedad();

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
        $id_tipo_maquina       = $_POST['id_tipo_maquina'] ?? 0;
        $id_tipo_mantenimiento = $_POST['id_tipo_mantenimiento'] ?? 0;
        $id_modalidad          = $_POST['id_modalidad'] ?? 1;
        $fechaVisita           = $_POST['fecha_visita'] ?? date('Y-m-d');
        $anio                  = date('Y', strtotime($fechaVisita));

        $precio = $this->modelo->obtenerPrecioTarifa($id_tipo_maquina, $id_tipo_mantenimiento, $id_modalidad, $anio);

        header('Content-Type: application/json');
        echo json_encode(['precio' => $precio]);
        exit;
    }

    public function ajaxObtenerStockTecnico()
    {
        ob_clean();
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

    // ✅ NUEVO: Remisiones disponibles para el técnico
    public function ajaxObtenerRemisiones()
    {
        ob_clean();
        header('Content-Type: application/json');

        $idTecnico      = intval($_POST['id_tecnico']      ?? 0);
        $remisionActual = trim($_POST['remision_actual']   ?? '');

        if ($idTecnico > 0) {
            $remisiones = $this->modelo->obtenerRemisionesDisponiblesPorTecnico(
                $idTecnico,
                $remisionActual ?: null
            );
            echo json_encode($remisiones);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    public function ajaxGestionarRepuestoRT()
    {
        ob_clean();
        header('Content-Type: application/json');

        $tipo       = $_POST['tipo'];
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
    // 3. GUARDAR CAMBIOS
    // ==========================================
    public function guardarCambios()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $servicios   = $_POST['servicios'] ?? [];
            $fechaOrigen = $_POST['fecha_origen'] ?? date('Y-m-d');
            $esBusqueda  = isset($_POST['es_busqueda']) && $_POST['es_busqueda'] == '1';

            $errores = 0;

            foreach ($servicios as $id => $datos) {

                // Limpiar precio
                if (isset($datos['valor'])) {
                    $valorLimpio   = str_replace('.', '', $datos['valor']);
                    $datos['valor'] = str_replace(',', '.', $valorLimpio);
                }

                // Calcular tiempo si no viene
                if (!isset($datos['tiempo']) || empty($datos['tiempo'])) {
                    $datos['tiempo'] = '00:00';
                    if (!empty($datos['entrada']) && !empty($datos['salida'])) {
                        try {
                            $d1 = new DateTime($datos['entrada']);
                            $d2 = new DateTime($datos['salida']);
                            if ($d2 < $d1) $d2->modify('+1 day');
                            $datos['tiempo'] = $d1->diff($d2)->format('%H:%I');
                        } catch (Exception $e) {
                        }
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

            $urlDestino = $esBusqueda
                ? BASE_URL . "ordenDetalleBuscar"
                : BASE_URL . "ordenDetalle/" . $fechaOrigen;

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

    // ==========================================
    // 4. BÚSQUEDA AVANZADA
    // ==========================================
    public function cargarVistaBusqueda()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $listaClientes    = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos    = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos      = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos   = $this->modelo->obtenerListaRepuestos();
        $listaEstados     = $this->modelo->obtenerEstados();
        $listaCalifs      = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos    = $this->modelo->obtenerFestivos();

        $vistaContenido = "app/views/orden/ordenBusquedaVista.php";
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    public function ajaxBuscarOrdenes()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [
                'remision'      => $_POST['remision']      ?? '',
                'id_cliente'    => $_POST['id_cliente']    ?? '',
                'id_punto'      => $_POST['id_punto']      ?? '',
                'id_delegacion' => $_POST['id_delegacion'] ?? '',
                'fecha_inicio'  => $_POST['fecha_inicio']  ?? '',
                'fecha_fin'     => $_POST['fecha_fin']     ?? ''
            ];

            $servicios        = $this->modelo->buscarOrdenesFiltros($filtros);
            $listaClientes    = $this->modelo->obtenerTodosLosClientes();
            $listaTecnicos    = $this->modelo->obtenerTodosLosTecnicos();
            $listaMantos      = $this->modelo->obtenerTiposMantenimiento();
            $listaEstados     = $this->modelo->obtenerEstados();
            $listaCalifs      = $this->modelo->obtenerCalificaciones();
            $listaModalidades = $this->modelo->obtenerModalidades();

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
        ob_clean();
        header('Content-Type: application/json');

        $idOrden = $_POST['id_orden'] ?? 0;
        // Ahora esperamos un arreglo de IDs desde el frontend
        $arrayNovedades = isset($_POST['novedades']) ? $_POST['novedades'] : [];

        // Verificar que venga un ID válido
        if ($idOrden <= 0) {
            echo json_encode(['success' => false, 'msg' => 'ID de orden inválido']);
            exit;
        }

        // Si mandaron un string vacío por accidente, lo convertimos a array vacío
        if (!is_array($arrayNovedades)) {
            $arrayNovedades = empty($arrayNovedades) ? [] : [$arrayNovedades];
        }

        $res = $this->modelo->guardarNovedadesOrden($idOrden, $arrayNovedades);

        echo json_encode(['success' => $res]);
        exit;
    }

    // PASO 2: Agregar este método a la clase:
    // ============================================================

    public function ajaxExportarDetalle()
    {
        ob_clean();
        header('Content-Type: application/json');

        $fecha = $_POST['fecha'] ?? date('Y-m-d');

        // Reutilizamos exactamente el mismo método que ya usa cargarVista()
        $servicios = $this->modelo->obtenerServiciosPorFecha($fecha);

        // Enriquecemos cada fila con el catálogo de novedades resuelto
        // para que el JS no tenga que resolver IDs
        $catalogoNovedades = $this->modelo->obtenerTiposNovedad();
        $mapaNov = [];
        foreach ($catalogoNovedades as $n) {
            $mapaNov[$n['id_tipo_novedad']] = $n['nombre_novedad'];
        }

        foreach ($servicios as &$s) {
            // Resolver IDs de novedades a nombres legibles
            $idsNov = $s['ids_novedades'] ?? '';
            if (!empty($idsNov)) {
                $ids = explode(',', $idsNov);
                $nombres = array_map(function ($id) use ($mapaNov) {
                    $id = trim($id);
                    return $mapaNov[$id] ?? "ID:$id";
                }, $ids);
                $s['nombres_novedades_resueltos'] = implode(', ', $nombres);
            } else {
                $s['nombres_novedades_resueltos'] = '';
            }
        }

        echo json_encode([
            'status' => 'ok',
            'datos'  => $servicios
        ]);
        exit;
    }
}
