<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/admin/inventarioTecnicoCrearModelo.php';

class InventarioTecnicoCrearControlador
{

    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new InventarioTecnicoCrearModelo($this->db);
    }

    public function index()
    {
        $errores = [];
        $mensajeExito = "";
        $tecnicoSeleccionado = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idTecnico = $_POST['id_tecnico'] ?? '';

            // Ahora recibimos ARRAYS
            $repuestos = $_POST['repuestos'] ?? [];
            $cantidades = $_POST['cantidades'] ?? [];

            // Validación básica
            if (empty($idTecnico)) {
                $errores[] = "Error crítico: No seleccionaste al técnico.";
            }

            if (empty($repuestos) || count($repuestos) === 0) {
                $errores[] = "No agregaste ningún repuesto a la lista.";
            }

            // Si no hay errores iniciales, procesamos la lista
            if (empty($errores)) {

                $itemsGuardados = 0;

                // Iniciamos una transacción para que sea todo o nada (Opcional, pero recomendado)
                $this->db->beginTransaction();

                try {
                    // Recorremos el array de repuestos
                    // $i es el índice (0, 1, 2...) que coincide con la cantidad
                    foreach ($repuestos as $i => $idRepuesto) {

                        $cantidad = intval($cantidades[$i] ?? 0);

                        // Validar cada línea
                        if (!empty($idRepuesto) && $cantidad > 0) {

                            // Llamamos al modelo para ESTE item
                            $res = $this->modelo->asignarStock($idTecnico, $idRepuesto, $cantidad);

                            if (!$res) {
                                throw new Exception("Error al guardar el repuesto ID: $idRepuesto");
                            }
                            $itemsGuardados++;
                        }
                    }

                    // Si todo salió bien, confirmamos los cambios en la BD
                    $this->db->commit();
                    $mensajeExito = "¡Proceso completado! Se asignaron $itemsGuardados tipos de repuestos al técnico.";
                    $tecnicoSeleccionado = $idTecnico; // Mantener seleccionado por si acaso

                } catch (Exception $e) {
                    // Si algo falló, deshacemos todo
                    $this->db->rollBack();
                    $errores[] = "Ocurrió un error y no se guardó nada: " . $e->getMessage();
                    $tecnicoSeleccionado = $idTecnico;
                }
            } else {
                $tecnicoSeleccionado = $idTecnico;
            }
        }

        // Cargar listas
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaRepuestos = $this->modelo->obtenerRepuestos(); // Se enviará a JS

        $titulo = "Asignación Masiva de Stock";
        // Asegúrate de actualizar el nombre de la vista si cambiaste el archivo
        $vistaContenido = "app/views/admin/inventarioTecnicoCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}
