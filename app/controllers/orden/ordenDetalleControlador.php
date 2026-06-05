<?php
// app/controllers/orden/ordenDetalleControlador.php

if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenDetalleModelo.php';

class ordenDetalleControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ordenDetalleModelo($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function procesarAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

            $accion = $_POST['accion'];

            if ($accion === 'ajaxObtenerPuntos')
                $this->ajaxObtenerPuntos();
            if ($accion === 'ajaxObtenerMaquinas')
                $this->ajaxObtenerMaquinas();
            if ($accion === 'ajaxObtenerDelegacion')
                $this->ajaxObtenerDelegacion();
            if ($accion === 'ajaxObtenerPrecio')
                $this->ajaxObtenerPrecio();
            if ($accion === 'ajaxObtenerStockTecnico')
                $this->ajaxObtenerStockTecnico();
            if ($accion === 'ajaxGestionarRepuestoRT')
                $this->ajaxGestionarRepuestoRT();
            if ($accion === 'ajaxGuardarNovedad')
                $this->ajaxGuardarNovedad();
            if ($accion === 'ajaxObtenerRemisiones')
                $this->ajaxObtenerRemisiones();
            if ($accion === 'ajaxExportarDetalle')
                $this->ajaxExportarDetalle();
            if ($accion === 'ajaxMejorarTextoIA')
                $this->ajaxMejorarTextoIA();
            if ($accion === 'ajaxGuardarCambiosJSON')
                $this->ajaxGuardarCambiosJSON();
        }
    }

    public function ajaxExportarBusqueda()
    {
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [
                'remision' => $_POST['remision'] ?? '',
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'id_punto' => $_POST['id_punto'] ?? '',
                'id_delegacion' => $_POST['id_delegacion'] ?? '',
                'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                'fecha_fin' => $_POST['fecha_fin'] ?? ''
            ];

            $resultados = $this->modelo->buscarOrdenesFiltros($filtros);

            // 🔒 SEGURIDAD EXCEL: Limpiamos los precios si es rol 5
            $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;
            if ($rolUsuario === 5 && $resultados) {
                foreach ($resultados as &$r) {
                    $r['valor_servicio'] = 0;
                    $r['valor_viaticos'] = 0;
                }
            }

            if ($resultados && count($resultados) > 0) {
                echo json_encode(['status' => 'ok', 'datos' => $resultados]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'No hay datos']);
            }
            exit;
        }
    }

    public function cargarVista()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $servicios = $this->modelo->obtenerServiciosPorFecha($fecha);
        $listaClientes = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();
        $listaEstados = $this->modelo->obtenerEstados();
        $listaCalifs = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos = $this->modelo->obtenerFestivos();
        $listaNovedades = $this->modelo->obtenerTiposNovedad();
        $remisionesGlobales = $this->modelo->obtenerTodasRemisionesDisponibles();

        // 🔒 SEGURIDAD HTML F12: Limpiamos los precios antes de pasarlos a la Vista
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;
        if ($rolUsuario === 5 && $servicios) {
            foreach ($servicios as &$s) {
                $s['valor_servicio'] = 0;
                $s['valor_viaticos'] = 0;
            }
        }

        $titulo = "Edición Total: " . $fecha;
        $vistaContenido = "app/views/orden/ordenDetalleVista.php";
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

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

        // 🔒 SEGURIDAD RED AJAX: Devolvemos 0 al navegador para que la pestaña Red (F12) no filtre precios
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;
        if ($rolUsuario === 5) {
            header('Content-Type: application/json');
            echo json_encode(['precio' => 0]);
            exit;
        }

        $id_tipo_maquina = $_POST['id_tipo_maquina'] ?? 0;
        $id_tipo_mantenimiento = $_POST['id_tipo_mantenimiento'] ?? 0;
        $id_modalidad = $_POST['id_modalidad'] ?? 1;
        $fechaVisita = $_POST['fecha_visita'] ?? date('Y-m-d');
        $anio = date('Y', strtotime($fechaVisita));

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

    public function ajaxObtenerRemisiones()
    {
        ob_clean();
        header('Content-Type: application/json');

        $idTecnico = intval($_POST['id_tecnico'] ?? 0);
        $remisionActual = trim($_POST['remision_actual'] ?? '');

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

        $tipo = $_POST['tipo'];
        $idOrden = $_POST['id_orden'];
        $idRepuesto = $_POST['id_repuesto'];
        $origen = $_POST['origen'];
        $idTecnico = $_POST['id_tecnico'];

        if ($tipo === 'agregar') {
            $cantidad = $_POST['cantidad'];
            $res = $this->modelo->agregarRepuestoRealTime($idOrden, $idRepuesto, $cantidad, $origen, $idTecnico);
        } else {
            $res = $this->modelo->eliminarRepuestoRealTime($idOrden, $idRepuesto, $origen, $idTecnico);
        }

        echo json_encode($res);
        exit;
    }

    public function guardarCambios()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $servicios = $_POST['servicios'] ?? [];
            $fechaOrigen = $_POST['fecha_origen'] ?? date('Y-m-d');
            $esBusqueda = isset($_POST['es_busqueda']) && $_POST['es_busqueda'] == '1';

            // Atrapamos el rol para pasárselo al Modelo
            $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;

            $errores = 0;

            foreach ($servicios as $id => $datos) {

                // Formateamos los números como de costumbre si NO es rol 5
                if ($rolUsuario !== 5 && isset($datos['valor'])) {
                    $valorLimpio = str_replace('.', '', $datos['valor']);
                    $datos['valor'] = str_replace(',', '.', $valorLimpio);
                }

                if (!isset($datos['tiempo']) || empty($datos['tiempo'])) {
                    $datos['tiempo'] = '00:00';
                    if (!empty($datos['entrada']) && !empty($datos['salida'])) {
                        try {
                            $d1 = new DateTime($datos['entrada']);
                            $d2 = new DateTime($datos['salida']);
                            if ($d2 < $d1)
                                $d2->modify('+1 day');
                            $datos['tiempo'] = $d1->diff($d2)->format('%H:%I');
                        } catch (Exception $e) {
                        }
                    }
                }

                if (empty($datos['fecha_individual'])) {
                    $datos['fecha_individual'] = $fechaOrigen;
                }

                // 🔥 Le pasamos el $rolUsuario al Modelo para que sepa qué hacer con el precio
                $resultado = $this->modelo->actualizarOrdenFull($id, $datos, $rolUsuario);

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
            exit;
        } else {
            header('Location: ' . BASE_URL . 'ordenDetalle');
            exit;
        }
    }

    public function cargarVistaBusqueda()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $listaClientes = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();
        $listaEstados = $this->modelo->obtenerEstados();
        $listaCalifs = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos = $this->modelo->obtenerFestivos();

        $vistaContenido = "app/views/orden/ordenBusquedaVista.php";
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }

    public function ajaxBuscarOrdenes()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $filtros = [
                'remision' => $_POST['remision'] ?? '',
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'id_punto' => $_POST['id_punto'] ?? '',
                'id_delegacion' => $_POST['id_delegacion'] ?? '',
                'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                'fecha_fin' => $_POST['fecha_fin'] ?? ''
            ];

            $servicios = $this->modelo->buscarOrdenesFiltros($filtros);
            $listaClientes = $this->modelo->obtenerTodosLosClientes();
            $listaTecnicos = $this->modelo->obtenerTodosLosTecnicos();
            $listaMantos = $this->modelo->obtenerTiposMantenimiento();
            $listaEstados = $this->modelo->obtenerEstados();
            $listaCalifs = $this->modelo->obtenerCalificaciones();
            $listaModalidades = $this->modelo->obtenerModalidades();

            // 🔒 SEGURIDAD HTML F12
            $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;
            if ($rolUsuario === 5 && $servicios) {
                foreach ($servicios as &$s) {
                    $s['valor_servicio'] = 0;
                    $s['valor_viaticos'] = 0;
                }
            }

            ob_start();
            if (empty($servicios)) {
                echo '<tr><td colspan="16" class="p-4 text-center text-red-500 font-bold">No se encontraron servicios con esos filtros.</td></tr>';
            } else {
                foreach ($servicios as $s) {
                    $idFila = $s['id_ordenes_servicio'];
                    include __DIR__ . '/../../views/orden/partials/detalleFila.php';
                }
            }
            $html = ob_get_clean();

            echo $html;
            exit;
        }
    }

    public function ajaxGuardarNovedad()
    {
        ob_clean();
        header('Content-Type: application/json');

        $idOrden = $_POST['id_orden'] ?? 0;
        $arrayNovedades = isset($_POST['novedades']) ? $_POST['novedades'] : [];

        if ($idOrden <= 0) {
            echo json_encode(['success' => false, 'msg' => 'ID de orden inválido']);
            exit;
        }

        if (!is_array($arrayNovedades)) {
            $arrayNovedades = empty($arrayNovedades) ? [] : [$arrayNovedades];
        }

        $res = $this->modelo->guardarNovedadesOrden($idOrden, $arrayNovedades);
        echo json_encode(['success' => $res]);
        exit;
    }

    public function ajaxExportarDetalle()
    {
        ob_clean();
        header('Content-Type: application/json');

        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $servicios = $this->modelo->obtenerServiciosPorFecha($fecha);

        $catalogoNovedades = $this->modelo->obtenerTiposNovedad();
        $mapaNov = [];
        foreach ($catalogoNovedades as $n) {
            $mapaNov[$n['id_tipo_novedad']] = $n['nombre_novedad'];
        }

        // 🔒 SEGURIDAD EXCEL
        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;

        foreach ($servicios as &$s) {
            if ($rolUsuario === 5) {
                $s['valor_servicio'] = 0;
                $s['valor_viaticos'] = 0;
            }

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
            'datos' => $servicios
        ]);
        exit;
    }

    public function ajaxMejorarTextoIA()
    {
        ob_clean();
        header('Content-Type: application/json');

        $textoOriginal = $_POST['texto'] ?? '';

        if (empty($textoOriginal)) {
            echo json_encode(['status' => 'error', 'msg' => 'Texto vacío']);
            exit;
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? $_SERVER['GROQ_API_KEY'] ?? '';

        if (empty(trim($apiKey))) {
            echo json_encode(['status' => 'error', 'msg' => 'API Key no encontrada en el entorno.']);
            exit;
        }

        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $prompt = "Eres un ingeniero supervisor de mantenimiento experto. Toma el siguiente reporte redactado por un técnico de campo y reescríbelo para que tenga una ortografía perfecta, gramática correcta y usando un lenguaje técnico, objetivo y muy profesional.\n\nReglas estrictas:\n- NO inventes repuestos, marcas o procedimientos que no estén en el texto original.\n- NO cambies ni omitas medidas (voltajes, amperajes), tiempos o códigos de error.\n- Devuelve ÚNICAMENTE el texto mejorado, sin introducciones, saludos ni comillas.\n\nTexto original: " . $textoOriginal;

        $data = [
            "model" => "llama-3.3-70b-versatile",
            "messages" => [
                ["role" => "system", "content" => "Eres un editor técnico estricto y conciso."],
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.2
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim($apiKey)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode == 200) {
            $resultado = json_decode($response, true);
            $textoMejorado = $resultado['choices'][0]['message']['content'] ?? '';
            echo json_encode(['status' => 'ok', 'texto_mejorado' => trim($textoMejorado)]);
        } else {
            $detalleError = $error ? "Falla interna (cURL): $error" : "Groq respondió: $response";
            echo json_encode(['status' => 'error', 'msg' => "Error $httpCode. $detalleError"]);
        }
        exit;
    }

    public function ajaxGuardarCambiosJSON()
    {
        ob_clean();
        header('Content-Type: application/json');

        $servicios = isset($_POST['json_data']) ? json_decode($_POST['json_data'], true) : [];
        $fechaOrigen = $_POST['fecha_origen'] ?? date('Y-m-d');

        $rolUsuario = isset($_SESSION['nivel_acceso']) ? (int) $_SESSION['nivel_acceso'] : 0;

        if (empty($servicios)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se recibieron datos para guardar.']);
            exit;
        }

        $errores = 0;

        foreach ($servicios as $id => $datos) {

            // Formateamos los números si NO es rol 5
            if ($rolUsuario !== 5 && isset($datos['valor'])) {
                $valorLimpio = str_replace('.', '', $datos['valor']);
                $datos['valor'] = str_replace(',', '.', $valorLimpio);
            }

            $datos['entrada'] = $this->sanitizarHora($datos['entrada'] ?? '');
            $datos['salida'] = $this->sanitizarHora($datos['salida'] ?? '');

            if (!isset($datos['tiempo']) || empty($datos['tiempo'])) {
                $datos['tiempo'] = '00:00';
                if (!empty($datos['entrada']) && !empty($datos['salida'])) {
                    try {
                        $d1 = new DateTime($datos['entrada']);
                        $d2 = new DateTime($datos['salida']);
                        if ($d2 < $d1)
                            $d2->modify('+1 day');
                        $datos['tiempo'] = $d1->diff($d2)->format('%H:%I');
                    } catch (Exception $e) {
                    }
                }
            }

            if (empty($datos['fecha_individual'])) {
                $datos['fecha_individual'] = $fechaOrigen;
            }

            // 🔥 Le pasamos el $rolUsuario al Modelo 
            $resultado = $this->modelo->actualizarOrdenFull($id, $datos, $rolUsuario);

            if (!$resultado) {
                $errores++;
            }
        }

        if ($errores > 0) {
            echo json_encode(['status' => 'warning', 'msg' => "Se guardaron los cambios, pero hubo errores en $errores filas."]);
        } else {
            echo json_encode(['status' => 'ok', 'msg' => '¡Todos los cambios guardados correctamente sin recargar la página!']);
        }
        exit;
    }

    private function sanitizarHora(?string $valor): string
    {
        if (empty(trim($valor ?? '')))
            return '';
        $valor = trim($valor);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $valor, $m)) {
            $h = min(23, (int) $m[1]);
            $min = min(59, (int) $m[2]);
            return sprintf('%02d:%02d', $h, $min);
        }
        $nums = preg_replace('/\D/', '', $valor);
        if (!$nums)
            return '';
        $nums = str_pad($nums, 4, '0', STR_PAD_RIGHT);
        $nums = substr($nums, 0, 4);
        $h = min(23, (int) substr($nums, 0, 2));
        $min = min(59, (int) substr($nums, 2, 2));
        return sprintf('%02d:%02d', $h, $min);
    }
}