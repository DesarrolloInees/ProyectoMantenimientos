<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TarifaVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerTarifas()
    {
        // AQUÍ ESTÁ LA MAGIA: Unimos las tablas para traer los nombres
        $sql = "SELECT 
                    t.id_tarifa,
                    t.precio,
                    t.año_vigencia,
                    tm.nombre_tipo_maquina,
                    tmt.nombre_completo AS nombre_mantenimiento,
                    mo.nombre_modalidad
                FROM tarifa t
                INNER JOIN tipo_maquina tm ON t.id_tipo_maquina = tm.id_tipo_maquina
                INNER JOIN tipo_mantenimiento tmt ON t.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
                INNER JOIN modalidad_operativa mo ON t.id_modalidad = mo.id_modalidad
                ORDER BY t.año_vigencia DESC, tm.nombre_tipo_maquina ASC";

        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarTarifa($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM tarifa WHERE id_tarifa = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
