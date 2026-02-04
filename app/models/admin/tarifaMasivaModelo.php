<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TarifaMasivaModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Trae las tarifas filtradas para la edición en bloque
    public function obtenerTarifasPorFiltro($id_maquina, $anio)
    {
        // CAMBIO IMPORTANTE: Cambiamos INNER por LEFT
        // Usamos COALESCE para que si no encuentra el nombre, ponga un texto de aviso
        $sql = "SELECT 
                t.id_tarifa,
                t.precio,
                COALESCE(tmt.nombre_completo, '--- MANTENIMIENTO BORRADO ---') AS nombre_mantenimiento,
                COALESCE(mo.nombre_modalidad, '--- MODALIDAD BORRADA ---') AS nombre_modalidad
            FROM tarifa t
            LEFT JOIN tipo_mantenimiento tmt ON t.id_tipo_mantenimiento = tmt.id_tipo_mantenimiento
            LEFT JOIN modalidad_operativa mo ON t.id_modalidad = mo.id_modalidad
            WHERE t.id_tipo_maquina = :maquina 
              AND t.año_vigencia = :anio
            ORDER BY tmt.nombre_completo ASC, mo.nombre_modalidad ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':maquina', $id_maquina, PDO::PARAM_INT);
        $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualización masiva usando Transacciones para asegurar integridad
    public function actualizarPreciosMasivos($preciosArray)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE tarifa SET precio = :precio WHERE id_tarifa = :id";
            $stmt = $this->conn->prepare($sql);

            foreach ($preciosArray as $id_tarifa => $precio) {
                // Validar que el precio sea numérico y no negativo
                if (is_numeric($precio) && $precio >= 0) {
                    $stmt->bindValue(':precio', $precio);
                    $stmt->bindValue(':id', $id_tarifa, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Helpers para los filtros
    public function obtenerTiposMaquina()
    {
        return $this->conn->query("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
