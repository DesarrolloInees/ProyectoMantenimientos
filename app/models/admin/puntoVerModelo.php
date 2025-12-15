<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class PuntoVerModelo
{
    private $conn;

    public function __construct(PDO $db) { $this->conn = $db; }

    public function obtenerPuntos()
    {
        // JOIN MASIVO para traer nombres en vez de IDs
        // Usamos LEFT JOIN en delegacion porque puede ser NULL
        $sql = "SELECT 
                    p.id_punto, p.nombre_punto, p.direccion,
                    c.nombre_cliente,
                    mun.nombre_municipio,
                    del.nombre_delegacion,
                    mo.nombre_modalidad
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                INNER JOIN municipio mun ON p.id_municipio = mun.id_municipio
                INNER JOIN modalidad_operativa mo ON p.id_modalidad = mo.id_modalidad
                LEFT JOIN delegacion del ON p.id_delegacion = del.id_delegacion
                WHERE p.estado = 1
                ORDER BY p.id_punto DESC";
        
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarPuntoLogicamente($id)
    {
        $stmt = $this->conn->prepare("UPDATE punto SET estado = 0 WHERE id_punto = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}