<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class PuntoEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerPuntoPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM punto WHERE id_punto = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editarPunto($id, $datos)
    {
        try {
            $sql = "UPDATE punto SET 
                    nombre_punto = :nombre, direccion = :dir, codigo_1 = :cod1, codigo_2 = :cod2,
                    id_municipio = :mun, id_delegacion = :del, id_modalidad = :mod, id_cliente = :cli, estado = :est
                    WHERE id_punto = :id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':nombre', $datos['nombre_punto']);
            $stmt->bindParam(':dir', $datos['direccion']);
            $stmt->bindParam(':cod1', $datos['codigo_1']);
            $stmt->bindParam(':cod2', $datos['codigo_2']);
            $stmt->bindParam(':mun', $datos['id_municipio'], PDO::PARAM_INT);

            $del = !empty($datos['id_delegacion']) ? $datos['id_delegacion'] : null;
            $stmt->bindParam(':del', $del);

            $stmt->bindParam(':mod', $datos['id_modalidad'], PDO::PARAM_INT);
            $stmt->bindParam(':cli', $datos['id_cliente'], PDO::PARAM_INT);
            $stmt->bindParam(':est', $datos['estado'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Reutilizamos helpers
    public function obtenerClientes()
    {
        return $this->conn->query("SELECT id_cliente, nombre_cliente FROM cliente WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerMunicipios()
    {
        return $this->conn->query("SELECT id_municipio, nombre_municipio FROM municipio")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerDelegaciones()
    {
        return $this->conn->query("SELECT id_delegacion, nombre_delegacion FROM delegacion WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerModalidades()
    {
        return $this->conn->query("SELECT id_modalidad, nombre_modalidad FROM modalidad_operativa")->fetchAll(PDO::FETCH_ASSOC);
    }
}
