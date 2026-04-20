<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class PuntoVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

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

    // NUEVA FUNCIÓN: Trae todos los datos para el Excel pre-cargado
    public function obtenerDatosExcelPuntos()
    {
        $sql = "SELECT 
                    p.id_punto,
                    c.nombre_cliente AS 'Cliente',
                    p.nombre_punto AS 'Punto',
                    tm.nombre_tipo_maquina AS 'Tipo_Maquina',
                    m.device_id AS 'Device_ID',
                    p.direccion AS 'Direccion'
                FROM punto p
                INNER JOIN cliente c ON p.id_cliente = c.id_cliente
                LEFT JOIN maquina m ON p.id_punto = m.id_punto AND m.estado = 1
                LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE p.estado = 1
                ORDER BY c.nombre_cliente ASC, p.nombre_punto ASC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
