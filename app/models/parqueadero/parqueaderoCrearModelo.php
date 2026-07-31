<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class ParqueaderoCrearModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Actualiza esta función en tu ParqueaderoModelo para que traiga también el nombre
    public function obtenerDatosTecnicoPorUsuario($idUsuario)
    {
        try {
            $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE usuario_id = :id_usuario AND estado = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC); // Devuelve array con id_tecnico y nombre_tecnico
        } catch (PDOException $e) {
            return false;
        }
    }

    // Obtener los puntos activos para llenar el select del formulario
    public function obtenerPuntosActivos()
    {
        try {
            $sql = "SELECT id_punto, nombre_punto FROM punto WHERE estado = 1 ORDER BY nombre_punto ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Insertar el registro de la factura en la BD
    public function guardarFactura($datos)
    {
        try {
            // Se agregó hora_fin en ambas partes del INSERT
            $sql = "INSERT INTO facturas_parqueadero 
                    (id_tecnico, id_punto, fecha_servicio, hora_inicio, hora_fin, numero_factura, valor_factura, ruta_foto) 
                    VALUES (:id_tecnico, :id_punto, :fecha_servicio, :hora_inicio, :hora_fin, :numero_factura, :valor_factura, :ruta_foto)";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':id_tecnico' => $datos['id_tecnico'],
                ':id_punto' => $datos['id_punto'],
                ':fecha_servicio' => $datos['fecha_servicio'],
                ':hora_inicio' => $datos['hora_inicio'],
                ':hora_fin' => $datos['hora_fin'], // <-- LÍNEA NUEVA
                ':numero_factura' => $datos['numero_factura'],
                ':valor_factura' => $datos['valor_factura'],
                ':ruta_foto' => $datos['ruta_foto']
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando factura parqueadero: " . $e->getMessage());
            return false;
        }
    }
}