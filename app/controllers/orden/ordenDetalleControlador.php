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

        if (empty(trim($textoOriginal))) {
            echo json_encode(['status' => 'error', 'msg' => 'Texto vacío']);
            exit;
        }

        // 1. Obtener API Keys desde .env (cargado vía Dotenv en index.php) o entorno
        $apiKey1 = trim($_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? $_SERVER['GROQ_API_KEY'] ?? '');
        $apiKey2 = trim($_ENV['GROQ_API_KEY_2'] ?? getenv('GROQ_API_KEY_2') ?? $_SERVER['GROQ_API_KEY_2'] ?? '');

        if (empty($apiKey1) || empty($apiKey2)) {
            // Fallback: leer .env de la RAÍZ del proyecto (app/controllers/orden -> ../../../.env)
            $envPath = realpath(__DIR__ . '/../../../.env');
            if ($envPath && file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        list($key, $val) = explode('=', $line, 2);
                        if (trim($key) === 'GROQ_API_KEY' && empty($apiKey1)) {
                            $apiKey1 = trim($val);
                        }
                        if (trim($key) === 'GROQ_API_KEY_2' && empty($apiKey2)) {
                            $apiKey2 = trim($val);
                        }
                    }
                }
            }
        }

        $apiKeys = array_filter([$apiKey1, $apiKey2]);

        if (empty($apiKeys)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se encontraron API Keys en el .env']);
            exit;
        }

        // ======================================================
        // PROMPT: REORDENAR Y AMPLIAR — NUNCA RECORTAR NI RESUMIR
        // ======================================================
        $totalCaracteres = mb_strlen($textoOriginal, 'UTF-8');
        // El resultado NO puede salir más corto. Solo se toleran unos pocos caracteres
        // (máx. 15, o 5% en textos largos) para que una simple corrección no falle.
        $tolerancia = (int) max(0, min(15, floor($totalCaracteres * 0.05)));
        $minimoCaracteres = $totalCaracteres - $tolerancia;
        $maximoCaracteres = (int) ceil($totalCaracteres * 1.4); // expansión controlada, no un ensayo

        $prompt = "TAREA: Reorganiza y mejora la redacción del siguiente reporte técnico de mantenimiento (español).

REGLAS OBLIGATORIAS:
1. CONSERVA EL 100% DE LA INFORMACIÓN: NO resumas, NO omitas y NO elimines datos, medidas, marcas, modelos, seriales, códigos de error, repuestos, cantidades, tiempos, nombres, puntos, remisiones ni observaciones.
2. EXTENSIÓN OBLIGATORIA: la respuesta debe tener entre {$minimoCaracteres} y {$maximoCaracteres} caracteres; NUNCA menos de {$minimoCaracteres}. Si una idea está abreviada, telegráfica o incompleta, AMPLÍALA con detalle técnico; si ya está completa, reordénala y redáctala mejor sin encogerla ni alargarla de más.
3. CONSERVA CIFRAS Y CÓDIGOS TAL CUAL: números, cantidades, seriales, modelos, versiones, medidas y códigos de error se escriben en dígitos exactamente como aparecen (ej: 20, 045, 3.2.1), nunca con palabras ni aproximaciones.
4. Corrige ortografía, tildes, puntuación y gramática, y usa lenguaje técnico profesional.
5. Reordena la información de forma lógica y coherente (por actividad, por equipo/sistema o cronológicamente). Puedes unir o separar ideas y mejorar la redacción, pero NUNCA descartar ninguna.
6. NO INVENTES INFORMACIÓN: amplía solo lo que ya estaba escrito; NO agregues equipos, fallas, procedimientos, pruebas, repuestos ni datos que no aparezcan en el texto original.
7. Devuelve SOLO el texto final del reporte: sin títulos, sin listas de reglas, sin comillas y sin explicaciones.

Texto original ({$totalCaracteres} caracteres):
";

        // Modelos 100% GRATIS (plan Free Groq): gpt-oss-20b = más rápido (~1000 t/s) + 131K contexto.
        // No usar llama-3.3-70b-versatile: pasó a Enterprise (requiere facturación).
        $modelosDisponibles = ['openai/gpt-oss-20b', 'openai/gpt-oss-120b', 'qwen/qwen3.8-27b'];

        $textoMejorado = null;
        $detallesErrores = [];

        // Presupuesto de salida proporcional al texto recibido: evita que nos corten el comentario por max_tokens.
        // Como el resultado debe ser igual o más largo que la entrada, pedimos ~1.8x + margen.
        $tokensEntrada = (int) ceil($totalCaracteres / 3);
        $maxTokensBase = max(700, (int) ceil($tokensEntrada * 1.8) + 250);
        $maxTokensBase = min($maxTokensBase, 2048); // techo seguro para el límite gratis por minuto (TPM)

        // 2. Probar con las llaves y modelos válidos
        foreach ($apiKeys as $indexKey => $apiKey) {
            foreach ($modelosDisponibles as $modelo) {
                $maxTokens = $maxTokensBase;
                $recordatorio = '';

                // Hasta 2 intentos por modelo: si la IA se corta o resume, se reintenta con más espacio
                for ($intento = 1; $intento <= 2; $intento++) {
                    $data = [
                        "model" => $modelo,
                        "messages" => [
                            ["role" => "system", "content" => "Eres un editor técnico experto en mantenimiento industrial. Conservas el 100% de la información del reporte, nunca resumes ni recortas, amplías solo lo que ya está escrito y respetas cifras, seriales y códigos tal cual aparecen."],
                            ["role" => "user", "content" => $prompt . $textoOriginal . $recordatorio]
                        ],
                        "temperature" => 0.1,
                        "max_tokens" => $maxTokens
                    ];

                    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $apiKey
                    ]);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $errorCurl = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode == 200) {
                        $resultado = json_decode($response, true);
                        $candidato = trim($resultado['choices'][0]['message']['content'] ?? '');
                        $finishReason = $resultado['choices'][0]['finish_reason'] ?? '';

                        if ($candidato === '') {
                            $detallesErrores[] = "Key " . ($indexKey + 1) . " ($modelo) -> Respuesta 200 sin contenido";
                            break; // pasa al siguiente modelo
                        }

                        // 🚫 Se cortó por falta de tokens: reintenta el mismo modelo con más espacio
                        if ($finishReason === 'length') {
                            $detallesErrores[] = "Key " . ($indexKey + 1) . " ($modelo) -> Respuesta cortada por max_tokens ({$maxTokens}), reintentando con más espacio";
                            $maxTokens = min($maxTokens * 2, 4096);
                            continue;
                        }

                        $largoCandidato = mb_strlen($candidato, 'UTF-8');

                        // 🚫 El modelo resumió / recortó información: reintenta exigiéndole conservar todo
                        if ($largoCandidato < $minimoCaracteres) {
                            $detallesErrores[] = "Key " . ($indexKey + 1) . " ($modelo) -> Respuesta más corta que el original ({$largoCandidato} < {$minimoCaracteres} caracteres), reintentando";
                            $recordatorio = "\n\nIMPORTANTE: Tu respuesta anterior fue demasiado corta y omitió información. Vuelve a redactar el reporte conservando TODOS los datos del texto original, sin resumir nada, y con una extensión igual o mayor (mínimo {$minimoCaracteres} caracteres).";
                            continue;
                        }

                        // ✅ Válido: conservó o amplió la información
                        $textoMejorado = $candidato;
                        break 3; // ¡Éxito! Salimos de intentos, modelos y llaves
                    } elseif ($httpCode == 429) {
                        // Límite gratis alcanzado en esta key/modelo: probar siguiente sin ensuciar el log
                        $detallesErrores[] = "Key " . ($indexKey + 1) . " ($modelo) -> Límite gratis del día (429), se probó siguiente opción";
                        break;
                    } else {
                        $msgApi = $response;
                        $dec = json_decode($response, true);
                        if (isset($dec['error']['message'])) {
                            $msgApi = $dec['error']['message'];
                        }
                        $msgError = $errorCurl ? "cURL: $errorCurl" : "HTTP $httpCode: $msgApi";
                        $detallesErrores[] = "Key " . ($indexKey + 1) . " ($modelo) -> " . $msgError;
                        break;
                    }
                }
            }
        }

        // 3. Respuesta JSON limpia
        if (!empty(trim($textoMejorado))) {
            echo json_encode(['status' => 'ok', 'texto_mejorado' => trim($textoMejorado)]);
        } else {
            // Si lo único que falló fue el recorte, damos un mensaje claro al usuario (sin traza técnica)
            $soloRecortes = !empty($detallesErrores) && count(array_filter($detallesErrores, function ($d) {
                return strpos($d, 'más corta que el original') === false
                    && strpos($d, 'cortada por max_tokens') === false;
            })) === 0;

            $msgFinal = $soloRecortes
                ? 'La IA intentó recortar el comentario, así que se descartó para no perder información. Tu texto original quedó intacto, intenta de nuevo.'
                : 'Error API: ' . implode(" | ", $detallesErrores);

            echo json_encode(['status' => 'error', 'msg' => $msgFinal]);
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