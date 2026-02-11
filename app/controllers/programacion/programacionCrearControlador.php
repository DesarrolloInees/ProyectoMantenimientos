<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/programacion/programacionCrearModelo.php';

class programacionCrearControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new programacionCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $datos = [];

        // Si se envía el formulario (Aquí meteremos la lógica matemática después)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'id_delegacion' => $_POST['id_delegacion'] ?? '',
                'fecha_inicio'  => $_POST['fecha_inicio'] ?? '',
                'fecha_fin'     => $_POST['fecha_fin'] ?? '',
                'tecnicos'      => $_POST['tecnicos'] ?? [], // Esto será un array
                'meta_diaria'   => $_POST['meta_diaria'] ?? 8
            ];

            // Validaciones básicas
            if (empty($datos['id_delegacion'])) $errores[] = "Selecciona una delegación.";
            if (empty($datos['fecha_inicio']) || empty($datos['fecha_fin'])) $errores[] = "Define el rango de fechas.";
            if (empty($datos['tecnicos'])) $errores[] = "Debes seleccionar al menos un técnico.";

            if (empty($errores)) {
                // AQUÍ IRÁ LA MAGIA DEL ALGORITMO
                // Por ahora, solo redirigimos o mostramos éxito
                // $this->modelo->generarProgramacion($datos);
                echo "<script>alert('Datos recibidos. Listo para procesar lógica.');</script>";
            }
        }

        // Cargar listas para el formulario
        $listaDelegaciones = $this->modelo->obtenerDelegaciones();
        $listaTiposMantenimiento = $this->modelo->obtenerTiposMantenimiento();
        $listaTecnicos = $this->modelo->obtenerTecnicos();

        // Configuración de la vista
        $titulo = "Generar Programación";
        // Ajusta la ruta según donde guardes tus vistas
        $vistaContenido = "app/views/programacion/programacionCrearVista.php";

        // Incluimos la plantilla maestra
        include "app/views/plantillaVista.php";
    }

    

    public function previsualizar() {
    // Recoger datos del POST
    $datos = [
        'id_delegacion' => $_POST['id_delegacion'] ?? 0,
        'fecha_inicio'  => $_POST['fecha_inicio'] ?? '',
        'fecha_fin'     => $_POST['fecha_fin'] ?? '',
        'zonas'         => $_POST['zonas'] ?? [],
        'tecnicos'      => $_POST['tecnicos'] ?? [],
        'meta_diaria'   => $_POST['meta_diaria'] ?? 8,
        'solo_correctivos' => isset($_POST['solo_correctivos']),
        'usar_sabados_buffer' => isset($_POST['usar_sabados_buffer'])
    ];
    
    // Validaciones
    $errores = [];
    if (empty($datos['id_delegacion'])) $errores[] = "Selecciona una delegación";
    if (empty($datos['fecha_inicio'])) $errores[] = "Selecciona fecha de inicio";
    if (empty($datos['fecha_fin'])) $errores[] = "Selecciona fecha de fin";
    if (empty($datos['tecnicos'])) $errores[] = "Selecciona al menos un técnico";
    
    if (!empty($errores)) {
        // Si hay errores, volver al formulario
        $_SESSION['errores_previsualizacion'] = $errores;
        header('Location: ' . BASE_URL . 'programacionCrear');
        exit;
    }
    
    // SIMULACIÓN (Reemplaza esto con tu algoritmo real)
    $simulacion = [];
    
    // Técnicos seleccionados
    $tecnicosNombres = [
        '1' => 'Juan Pérez',
        '2' => 'María Gómez', 
        '3' => 'Carlos López',
        '4' => 'Ana Rodríguez',
        '5' => 'Luis Martínez'
    ];
    
    // Generar datos de prueba
    $dias = (strtotime($datos['fecha_fin']) - strtotime($datos['fecha_inicio'])) / (60*60*24) + 1;
    $contador = 0;
    
    for ($dia = 0; $dia < $dias; $dia++) {
        $fechaActual = date('Y-m-d', strtotime($datos['fecha_inicio'] . " + $dia days"));
        $esFinSemana = (date('N', strtotime($fechaActual)) >= 6);
        
        // Saltar fines de semana si no se usan como buffer
        if ($esFinSemana && !$datos['usar_sabados_buffer']) {
            continue;
        }
        
        foreach ($datos['tecnicos'] as $idTecnico) {
            // Servicios por día por técnico (máximo la meta diaria)
            $serviciosPorDia = rand(3, $datos['meta_diaria']);
            
            for ($i = 0; $i < $serviciosPorDia; $i++) {
                $contador++;
                $simulacion[] = [
                    'tecnico' => $tecnicosNombres[$idTecnico] ?? "Técnico $idTecnico",
                    'punto' => "Punto " . $contador,
                    'direccion' => "Calle " . rand(1, 100) . " #" . rand(1, 50) . "-" . rand(1, 100),
                    'zona' => !empty($datos['zonas']) ? $datos['zonas'][array_rand($datos['zonas'])] : 'General',
                    'fecha' => $fechaActual,
                    'tipo' => $datos['solo_correctivos'] ? 'Correctivo' : (rand(0, 1) ? 'Preventivo' : 'Correctivo')
                ];
            }
        }
    }
    
    // Configurar variables para la vista
    $titulo = "Previsualización de Programación";
    
    // Incluir la vista (corrige la ruta si es necesario)
    require_once __DIR__ . '/../../views/programacion/programacionPrevisualizarVista.php';
    exit;
}

// ESTE ES EL MÉTODO QUE LLAMA EL JS
    // ESTE MÉTODO ES EL QUE DEBES LLAMAR
    public function cargarDatosAuxiliares() {
    header('Content-Type: application/json');
    
    $id_delegacion = $_POST['id_delegacion'] ?? 0;
    $respuesta = [
        'zonas' => [],
        'tecnicos' => []
    ];

    if ($id_delegacion > 0) {
        // 1. Zonas (sí depende de la delegación)
        $respuesta['zonas'] = $this->modelo->obtenerZonasPorDelegacion($id_delegacion);
        
        // 2. Técnicos (NO dependen de delegación)
        $tecnicosTodo = $this->modelo->obtenerTecnicos();
        
        // Como no tenemos delegación, podemos sugerir TODOS o usar otro criterio
        foreach($tecnicosTodo as $tec){
            // Opción A: Marcar todos como sugeridos
            $tec['sugerido'] = true;
            
            // Opción B: Si tienes otra forma de saber qué técnicos van a esa zona
            // $tec['sugerido'] = $this->esTecnicoDeZona($tec['id_tecnico'], $id_delegacion);
            
            $respuesta['tecnicos'][] = $tec;
        }
    }

    echo json_encode($respuesta);
    exit;
}

}
