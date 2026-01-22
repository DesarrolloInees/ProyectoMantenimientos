<?php
// app/controllers/orden/ordenDetalleBuscarControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

// Importamos lo necesario
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/orden/ordenDetalleModelo.php';

class ordenDetalleBuscarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        // Conectamos a la BD
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        // Reutilizamos el modelo que ya tiene toda la lógica
        $this->modelo = new ordenDetalleModelo($this->db);
    }

    // Este es el método que se llama por defecto al entrar a la URL
    public function cargarVista()
    {
        // 1. Verificamos sesión
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // 2. Cargamos las listas para los SELECTS del Buscador
        $listaClientes  = $this->modelo->obtenerTodosLosClientes();
        $listaTecnicos  = $this->modelo->obtenerTodosLosTecnicos();
        // Estas otras listas son necesarias porque se usan dentro de la tabla (detalleFila.php)
        // cuando pintamos los resultados via AJAX, pero es bueno tenerlas a mano.
        $listaMantos    = $this->modelo->obtenerTiposMantenimiento();
        $listaRepuestos = $this->modelo->obtenerListaRepuestos();
        $listaEstados   = $this->modelo->obtenerEstados();
        $listaCalifs    = $this->modelo->obtenerCalificaciones();
        $listaModalidades = $this->modelo->obtenerModalidades();
        $listaFestivos  = $this->modelo->obtenerFestivos();
        $listaNovedades = $this->modelo->obtenerTiposNovedad();

        // 3. Título de la pestaña
        $titulo = "Buscador Individual de Servicios";

        // 4. Cargamos la vista NUEVA que creamos en el paso anterior
        $vistaContenido = "app/views/orden/ordenBusquedaVista.php";

        // 5. Renderizamos usando la plantilla maestra
        require_once __DIR__ . '/../../views/plantillaVista.php';
    }
}
