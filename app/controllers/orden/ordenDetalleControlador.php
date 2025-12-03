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
            
            // --- NUEVO: OBTENER DELEGACIÓN ---
            if ($_POST['accion'] === 'ajaxObtenerDelegacion') {
                $this->ajaxObtenerDelegacion();
            }
        }
    }

    // ==========================================
    // 1. CARGA LA VISTA NORMAL
    // ==========================================
    public function cargarVista() {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        $servicios = $this->modelo->obtenerServiciosPorFecha($fecha);
        $listaClientes = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos = $this->modelo->obtenerTodosLosTecnicos();
        $listaMantos   = $this->modelo->obtenerTiposMantenimiento();
        $listaEstados  = $this->modelo->obtenerEstados();
        $listaCalifs   = $this->modelo->obtenerCalificaciones();

        $titulo = "Edición Total: " . $fecha;
        $vistaContenido = "app/views/orden/ordenDetalleVista.php";
        include "app/views/plantillaVista.php";
    }

    // ==========================================
    // 2. AJAX METHODS
    // ==========================================
    public function ajaxObtenerPuntos() {
        ob_clean(); 
        $id_cliente = $_POST['id_cliente'] ?? 0;
        $puntos = $this->modelo->obtenerPuntosPorCliente($id_cliente);
        header('Content-Type: application/json');
        echo json_encode($puntos);
        exit;
    }

    public function ajaxObtenerMaquinas() {
        ob_clean();
        $id_punto = $_POST['id_punto'] ?? 0;
        $maquinas = $this->modelo->obtenerMaquinasPorPunto($id_punto);
        header('Content-Type: application/json');
        echo json_encode($maquinas);
        exit;
    }

    // --- NUEVO: AJAX PARA TRAER LA DELEGACIÓN DEL PUNTO ---
    public function ajaxObtenerDelegacion() {
        ob_clean();
        $id_punto = $_POST['id_punto'] ?? 0;
        
        // NOTA: Debes agregar este pequeño método en tu MODELO
        // Si no quieres tocar el modelo, aquí hacemos una "trampa" rápida usando la conexión del modelo
        // pero lo ideal es tenerlo en ordenDetalleModelo.
        $delegacion = "Sin Asignar";
        
        // Usamos una consulta directa rápida aprovechando la conexión existente
        // IMPORTANTE: Asegúrate de que tu usuario de BD tenga permisos
        try {
            // Accedemos a la conexión pública si es posible, o instanciamos una nueva si es privada
            // Para no complicarte, asumo que agregaste la función al modelo.
            // Si no, usa este bloque:
                $db = new Database();
                $conn = $db->getConnection();
                $stmt = $conn->prepare("SELECT d.nombre_delegacion 
                                        FROM punto p 
                                        JOIN delegacion d ON p.id_delegacion = d.id_delegacion 
                                        WHERE p.id_punto = ?");
                $stmt->execute([$id_punto]);
                $delegacion = $stmt->fetchColumn() ?: "Sin Asignar";
        } catch (Exception $e) {
            $delegacion = "Error";
        }

        header('Content-Type: application/json');
        echo json_encode(['delegacion' => $delegacion]);
        exit;
    }

    // ==========================================
    // 3. GUARDAR CAMBIOS (CORREGIDO)
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

                // --- CORRECCIÓN PRECIO ---
                // 1. Quitamos los puntos de miles (150.000 -> 150000)
                // 2. Cambiamos la coma decimal por punto (150000,50 -> 150000.50)
                $valorLimpio = str_replace('.', '', $datos['valor']); // Quita miles
                $valorLimpio = str_replace(',', '.', $valorLimpio);   // Cambia decimal

                // Guardar TODO
                $this->modelo->actualizarOrdenFull($id, [
                    'id_cliente' => $datos['id_cliente'], // <--- AGREGADO: Guardamos Cliente
                    'id_punto'   => $datos['id_punto'],   // <--- AGREGADO: Guardamos Punto
                    'id_maquina' => $datos['id_maquina'],
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
                    'fecha_individual' => $datos['fecha_individual']
                ]);
            }

            echo "<script>
                alert('¡Todo actualizado! Precios y ubicaciones guardados correctamente.');
                window.location.href = 'index.php?pagina=ordenDetalle&fecha=$fechaOrigen';
            </script>";
        }
    }
}