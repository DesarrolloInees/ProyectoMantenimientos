<?php
class geolocalizarModelo {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 1. Obtener puntos filtrando por intentos
    public function obtenerPuntosSinCoordenadas($limite = 10) {
        // Traemos las que no tienen coordenadas Y que tengan menos de 3 intentos
        $sql = "SELECT p.id_punto, p.direccion, d.nombre_delegacion 
                FROM punto p 
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion 
                WHERE (p.latitud IS NULL OR p.longitud IS NULL) 
                AND p.intentos_api < 3 
                LIMIT :limite"; 
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Guardar coordenadas si hay éxito
    public function actualizarCoordenadas($id_punto, $latitud, $longitud) {
        // Actualizamos las coordenadas y marcamos el intento
        $sql = "UPDATE punto SET latitud = :latitud, longitud = :longitud, intentos_api = intentos_api + 1 WHERE id_punto = :id_punto";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':latitud' => $latitud,
            ':longitud' => $longitud,
            ':id_punto' => $id_punto
        ]);
    }

    // 3. Registrar fallo (Solo suma un intento para no volver a consultarlo infinitamente)
    public function registrarIntentoFallido($id_punto) {
        $sql = "UPDATE punto SET intentos_api = intentos_api + 1 WHERE id_punto = :id_punto";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_punto' => $id_punto]);
    }
}