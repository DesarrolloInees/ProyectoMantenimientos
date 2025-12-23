<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TarifaCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // --- NUEVA FUNCIÓN PARA GUARDADO MASIVO ---
    public function guardarTarifasMasivas($idMaquina, $anio, $matrizPrecios)
    {
        try {
            $this->conn->beginTransaction(); // Iniciamos transacción

            $sql = "INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio, año_vigencia) 
                    VALUES (:maquina, :mantenimiento, :modalidad, :precio, :anio)";
            $stmt = $this->conn->prepare($sql);

            foreach ($matrizPrecios as $idManto => $modalidades) {
                foreach ($modalidades as $idModalidad => $precio) {

                    // Solo guardamos si escribieron un precio y es mayor a 0
                    if (is_numeric($precio) && $precio > 0) {
                        $stmt->execute([
                            ':maquina'       => $idMaquina,
                            ':mantenimiento' => $idManto,
                            ':modalidad'     => $idModalidad,
                            ':precio'        => $precio,
                            ':anio'          => $anio
                        ]);
                    }
                }
            }

            $this->conn->commit(); // Confirmamos cambios
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack(); // Si algo falla, deshacemos todo
            error_log("Error masivo tarifa: " . $e->getMessage());
            return false;
        }
    }

    // --- Helpers (Iguales que antes) ---
    public function obtenerTiposMaquina()
    {
        return $this->conn->query("SELECT * FROM tipo_maquina WHERE estado = 1 ORDER BY nombre_tipo_maquina ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerTiposMantenimiento()
    {
        return $this->conn->query("SELECT * FROM tipo_mantenimiento WHERE estado = 1 ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerModalidades()
    {
        return $this->conn->query("SELECT * FROM modalidad_operativa ORDER BY id_modalidad ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
