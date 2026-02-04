<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class PuntoCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearPunto($datos)
    {
        try {
            $sql = "INSERT INTO punto (
                    nombre_punto, direccion, codigo_1, codigo_2, 
                    id_municipio, id_delegacion, zona, id_modalidad, id_cliente, estado
                ) VALUES (
                    :nombre, :dir, :cod1, :cod2, 
                    :mun, :del, :zona, :mod, :cli, 1
                )";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':nombre', $datos['nombre_punto']);
            $stmt->bindParam(':dir', $datos['direccion']);
            $stmt->bindParam(':cod1', $datos['codigo_1']);
            $stmt->bindParam(':cod2', $datos['codigo_2']);
            $stmt->bindParam(':mun', $datos['id_municipio'], PDO::PARAM_INT);

            // Delegación puede ser NULL
            $del = !empty($datos['id_delegacion']) ? $datos['id_delegacion'] : null;
            $stmt->bindParam(':del', $del);

            // Zona puede ser NULL
            $zona = !empty($datos['zona']) ? $datos['zona'] : null;
            $stmt->bindParam(':zona', $zona);

            $stmt->bindParam(':mod', $datos['id_modalidad'], PDO::PARAM_INT);
            $stmt->bindParam(':cli', $datos['id_cliente'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crear punto: " . $e->getMessage());
            return false;
        }
    }

    // --- Helpers para llenar listas ---
    public function obtenerClientes()
    {
        return $this->conn->query("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1 ORDER BY nombre_cliente ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerMunicipios()
    {
        return $this->conn->query("SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerDelegaciones()
    {
        return $this->conn->query("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1 ORDER BY nombre_delegacion ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerModalidades()
    {
        return $this->conn->query("SELECT id_modalidad, nombre_modalidad FROM modalidad_operativa ORDER BY nombre_modalidad ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
