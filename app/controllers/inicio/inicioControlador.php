<?php
class inicioControlador
{

    // Método estándar que llama el index.php
    // AGREGA ESTA FUNCIÓN AQUÍ 👇
    // Sirve de puente: si el router busca "index", lo manda a "cargarVista"
    public function index()
    {
        $this->cargarVista();
    }
    public function cargarVista()
    {

        // 1. Lógica o llamadas al modelo (si hubiera)
        // $modelo = new inicioModelo();
        // $datos = $modelo->obtenerDatosDashboard();

        // 2. Datos para la vista
        $titulo = "Dashboard General";

        // 3. Definimos cuál es el pedazo de HTML interno
        $vistaContenido = "app/views/inicio/inicioVista.php";

        // 4. Cargamos la plantilla maestra (que incluirá a $vistaContenido)
        include "app/views/plantillaVista.php";
    }
}
