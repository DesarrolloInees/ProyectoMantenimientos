<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/controlRemisionCrearModelo.php';

class controlRemisionCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new ControlRemisionCrearModelo($this->db);
    }

    public function index()
    {

        // --- ZONA AJAX (API) ---
        // Esto responde al JavaScript cuando cambias el técnico
        if (isset($_GET['ajax']) && $_GET['ajax'] == 'getUltima') {
            $idTecnico = $_GET['id_tecnico'] ?? 0;
            $ultima = $this->modelo->obtenerUltimaRemision($idTecnico);

            // Calculamos la siguiente sugerida (si es numérica)
            $siguiente = is_numeric($ultima) ? $ultima + 1 : '';

            echo json_encode(['ultima' => $ultima, 'siguiente' => $siguiente]);
            exit; // ¡IMPORTANTE! Aquí cortamos para no cargar la vista
        }

        // --- LÓGICA NORMAL DE LA VISTA ---
        $errores = [];
        $datosPrevios = [];
        $listaTecnicos = $this->modelo->obtenerTecnicos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $datosPrevios = [
                'id_tecnico'      => $_POST['id_tecnico'] ?? '',
                'remision_inicio' => trim($_POST['remision_inicio'] ?? ''),
                'remision_fin'    => trim($_POST['remision_fin'] ?? '')
            ];

            if (empty($datosPrevios['id_tecnico'])) $errores[] = "Seleccione un técnico.";
            if (empty($datosPrevios['remision_inicio'])) $errores[] = "Falta el número inicial.";

            if (empty($errores)) {
                $inicio = $datosPrevios['remision_inicio'];
                $fin = !empty($datosPrevios['remision_fin']) ? $datosPrevios['remision_fin'] : $inicio;

                if ($fin < $inicio) {
                    $errores[] = "El final no puede ser menor al inicial.";
                } else {
                    $creadas = 0;
                    $duplicadas = 0;

                    for ($i = $inicio; $i <= $fin; $i++) {
                        $datosInsertar = [
                            'numero_remision' => $i, // Lo guardamos tal cual (varchar)
                            'id_tecnico'      => $datosPrevios['id_tecnico']
                        ];

                        $resultado = $this->modelo->crearRemision($datosInsertar);

                        if ($resultado === true) $creadas++;
                        elseif ($resultado === "DUPLICADO") $duplicadas++;
                    }

                    if ($creadas > 0 && $duplicadas == 0) {
                        header("Location: " . BASE_URL . "controlRemisionVer");
                        exit();
                    } elseif ($duplicadas > 0) {
                        $errores[] = "Se asignaron $creadas. OJO: $duplicadas números ya los tenía este técnico y se omitieron.";
                    } else {
                        $errores[] = "No se guardó nada. Al parecer este técnico ya tiene asignados todos esos números.";
                    }
                }
            }
        }

        $titulo = "Asignar Remisiones";
        $vistaContenido = "app/views/admin/controlRemisionCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
