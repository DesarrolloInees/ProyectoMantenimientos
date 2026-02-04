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
        $tecnicosSeleccionados = []; // Ahora es un ARRAY

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Recibimos el array de técnicos
            $idsTecnicos = $_POST['ids_tecnicos'] ?? [];
            
            $repuestos = $_POST['repuestos'] ?? [];
            $cantidades = $_POST['cantidades'] ?? [];

            // Validación básica
            if (empty($idsTecnicos) || !is_array($idsTecnicos)) {
                $errores[] = "Error crítico: No seleccionaste ningún técnico.";
            }

            if (empty($repuestos) || count($repuestos) === 0) {
                $errores[] = "No agregaste ningún repuesto a la lista.";
            }

            // Si no hay errores iniciales
            if (empty($errores)) {

                $totalAsignaciones = 0;
                $tecnicosProcesados = 0;

                $this->db->beginTransaction();

                try {
                    // --- BUCLE EXTERNO: Recorremos cada técnico seleccionado ---
                    foreach ($idsTecnicos as $idTecnico) {
                        
                        $tecnicosProcesados++;

                        // --- BUCLE INTERNO: Recorremos los repuestos para ESTE técnico ---
                        foreach ($repuestos as $i => $idRepuesto) {
                            $cantidad = intval($cantidades[$i] ?? 0);

                            if (!empty($idRepuesto) && $cantidad > 0) {
                                // Llamamos al modelo (que NO cambia, porque el modelo sabe guardar 1 a 1)
                                $res = $this->modelo->asignarStock($idTecnico, $idRepuesto, $cantidad);

                                if (!$res) {
                                    throw new Exception("Error al asignar repuesto ID $idRepuesto al técnico $idTecnico");
                                }
                                $totalAsignaciones++;
                            }
                        }
                    }

                    $this->db->commit();
                    
                    // Mensaje más informativo
                    $mensajeExito = "¡Éxito! Se asignaron repuestos a <b>$tecnicosProcesados técnicos</b> (Total movimientos: $totalAsignaciones).";
                    
                    // Limpiamos la selección si fue exitoso
                    $tecnicosSeleccionados = []; 

                } catch (Exception $e) {
                    $this->db->rollBack();
                    $errores[] = "Error en el proceso: " . $e->getMessage();
                    // Mantenemos la selección para que el usuario no tenga que volver a elegir
                    $tecnicosSeleccionados = $idsTecnicos;
                }
            } else {
                $tecnicosSeleccionados = $idsTecnicos;
            }
        }

        // Cargar listas
        $listaTecnicos = $this->modelo->obtenerTecnicos();
        $listaRepuestos = $this->modelo->obtenerRepuestos();

        $titulo = "Asignación Masiva de Stock";
        $vistaContenido = "app/views/admin/inventarioTecnicoCrearVista.php";
        include "app/views/plantillaVista.php";
    }
}