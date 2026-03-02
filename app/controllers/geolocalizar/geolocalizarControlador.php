<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/geolocalizar/geolocalizarModelo.php';

class geolocalizarControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        $this->modelo = new geolocalizarModelo($this->db);
    }

    public function index()
    {
        set_time_limit(0);

        // --- CONFIGURACIÓN DE PRUEBA ---
        $limitePrueba = 5; // Cambia este número para hacer lotes de 5, 20, 50 o 100
        $apiKey = "AIzaSyDPZwiPOgRTXxj8zoLuktbP4RkA65w9W8A"; 
        // -------------------------------

        $puntos = $this->modelo->obtenerPuntosSinCoordenadas($limitePrueba);

        if (empty($puntos)) {
            echo "<h3>¡Todos los puntos están listos o llegaron a su límite de intentos fallidos!</h3>";
            return;
        }

        echo "<h2>Iniciando prueba con Google Maps (Lote de $limitePrueba)...</h2><br>";

        foreach ($puntos as $punto) {
            $id = $punto['id_punto'];
            
            // Le mandamos la dirección, pero eliminamos caracteres corruptos o invisibles
            // Esto cambia símbolos raros por un espacio en blanco
            $direccionCruda = preg_replace('/[^a-zA-Z0-9\s\#\-\,ñÑáéíóúÁÉÍÓÚ]/', ' ', $punto['direccion']);
            
            // Le sumamos la delegación por si acaso
            $delegacion = isset($punto['nombre_delegacion']) ? trim($punto['nombre_delegacion']) : '';
            $terminoUbicacion = !empty($delegacion) ? ", " . $delegacion : "";
            
            // Armamos la búsqueda exacta para Google
            $direccionBusqueda = $direccionCruda . $terminoUbicacion . ", Colombia";
            
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($direccionBusqueda) . "&key=" . $apiKey;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // Si te da error de certificado SSL en localhost, descomenta la siguiente línea:
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            
            $respuesta = curl_exec($ch);
            curl_close($ch);

            $datos = json_decode($respuesta, true);

            if (isset($datos['status']) && $datos['status'] === 'OK') {
                $lat = $datos['results'][0]['geometry']['location']['lat'];
                $lon = $datos['results'][0]['geometry']['location']['lng'];
                
                // Lo que Google realmente entendió
                $direccionEncontrada = $datos['results'][0]['formatted_address'];
                
                $this->modelo->actualizarCoordenadas($id, $lat, $lon);
                echo "<span style='color:green;'>✅ Éxito:</span> ID $id | Google entendió: <b>$direccionEncontrada</b><br>";
            } else {
                // Si falla, registramos el fallo en BD para que el contador suba y no se quede en un bucle infinito
                $this->modelo->registrarIntentoFallido($id);
                $motivo = isset($datos['status']) ? $datos['status'] : 'Error desconocido';
                echo "<span style='color:red;'>❌ Fallo:</span> ID $id | Error: <b>$motivo</b> (Buscó: $direccionBusqueda)<br>";
            }

            ob_flush(); 
            flush();
        }
        
        echo "<br><h3>Prueba de $limitePrueba registros terminada.</h3>";
    }
}