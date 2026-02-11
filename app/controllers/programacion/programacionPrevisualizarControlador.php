<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../models/programacion/programacionPrevisualizarModelo.php';

class programacionPrevisualizarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->getConexion();
        $this->modelo = new programacionPrevisualizarModelo($this->db);
    }

    public function index()
    {
        // 1. Verificar si hay filtros en sesión (que vienen del paso anterior)
        if (!isset($_SESSION['datos_filtro_programacion'])) {
            // Si no hay datos, devolvemos al inicio para evitar errores
            header('Location: ' . BASE_URL . 'programacionCrear');
            exit;
        }

        $filtros = $_SESSION['datos_filtro_programacion'];

        // 2. Obtener datos básicos
        // Aseguramos que listaTecnicos sea un array aunque venga vacío
        $listaTecnicos = $this->modelo->obtenerNombresTecnicos($filtros['tecnicos']) ?? [];
        $tecnicosIds = array_keys($listaTecnicos);

        // 3. Obtener Puntos Reales de la BD
        $puntos = $this->modelo->obtenerPuntosCandidatos($filtros);

        // 4. Algoritmo de Asignación
        $simulacion = [];
        
        if (!empty($puntos) && !empty($tecnicosIds)) {
            
            // Configuración de fechas
            try {
                $fechaActual = new DateTime($filtros['fecha_inicio']);
                $fechaFin = new DateTime($filtros['fecha_fin']);
            } catch (Exception $e) {
                $fechaActual = new DateTime();
                $fechaFin = new DateTime();
            }

            $metaDiaria = (int)$filtros['meta_diaria'];
            
            $idxTecnico = 0;
            $contadorCarga = 0;

            foreach ($puntos as $pt) {
                // Parar si nos pasamos de fecha
                if ($fechaActual > $fechaFin) break;

                // Validaciones de días (Domingo y Sábado)
                $diaSemana = $fechaActual->format('N'); // 1=Lun, 7=Dom
                
                if ($diaSemana == 7) { // Domingo
                    $fechaActual->modify('+1 day');
                    $contadorCarga = 0; 
                    $idxTecnico = 0;
                }
                elseif ($diaSemana == 6 && empty($filtros['usar_sabados'])) { // Sábado (sin buffer)
                    $fechaActual->modify('+2 days'); 
                    $contadorCarga = 0;
                    $idxTecnico = 0;
                }

                // Asignar Técnico
                // Si por alguna razón faltan técnicos, prevenimos error
                if (!isset($tecnicosIds[$idxTecnico])) $idxTecnico = 0;
                $idTecnicoActual = $tecnicosIds[$idxTecnico];

                // CONSTRUCCIÓN DEL ARRAY PARA LA VISTA (Aquí estaba el error)
                // Aseguramos que existan todas las claves que pide la vista
                $simulacion[] = [
                    'id_maquina' => $pt['id_maquina'],
                    'info'       => $pt['nombre_punto'],
                    'direccion'  => $pt['direccion'] ?? 'Sin dirección',
                    'ubicacion'  => ($pt['nombre_municipio'] ?? '') . " - " . ($pt['zona'] ?? ''), // Clave 'ubicacion' corregida
                    'id_tecnico' => $idTecnicoActual,
                    'fecha'      => $fechaActual->format('Y-m-d')
                ];

                // Rotación
                $contadorCarga++;
                if ($contadorCarga >= $metaDiaria) {
                    $contadorCarga = 0;
                    $idxTecnico++;
                    
                    // Si se acabaron los técnicos hoy, pasar a mañana
                    if ($idxTecnico >= count($tecnicosIds)) {
                        $idxTecnico = 0;
                        $fechaActual->modify('+1 day');
                    }
                }
            }
        }

        // 5. Cargar Vista
        $titulo = "Verificar Programación";
        require_once 'app/views/programacion/programacionPrevisualizarVista.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ordenes'])) {
            try {
                $this->db->beginTransaction();
                
                $sql = "INSERT INTO ordenes_servicio 
                        (id_tecnico, id_maquina, fecha_visita, id_cliente, estado, created_at) 
                        VALUES 
                        (:tec, :maq, :fec, (SELECT id_cliente FROM maquina WHERE id_maquina = :maq2), 1, NOW())";
                
                $stmt = $this->db->prepare($sql);
                $contador = 0;

                foreach ($_POST['ordenes'] as $orden) {
                    if (!empty($orden['id_tecnico']) && !empty($orden['id_maquina']) && !empty($orden['fecha'])) {
                        $stmt->execute([
                            ':tec' => $orden['id_tecnico'],
                            ':maq' => $orden['id_maquina'],
                            ':maq2'=> $orden['id_maquina'],
                            ':fec' => $orden['fecha']
                        ]);
                        $contador++;
                    }
                }

                $this->db->commit();
                unset($_SESSION['datos_filtro_programacion']);

                echo "<script>
                        alert('¡Éxito! Se crearon $contador órdenes de servicio.'); 
                        window.location='" . BASE_URL . "ordenesServicio';
                      </script>";

            } catch (PDOException $e) {
                $this->db->rollBack();
                echo "<script>alert('Error al guardar: " . $e->getMessage() . "'); window.history.back();</script>";
            }
        } else {
            header('Location: ' . BASE_URL . 'programacionPrevisualizar');
        }
    }
}