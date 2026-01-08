<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class TarifaCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // --- MODIFICADO: AHORA RECIBE UN ARRAY DE MÁQUINAS ---
    public function guardarTarifasMasivas($idsMaquinas, $anio, $matrizPrecios)
    {
        try {
            $this->conn->beginTransaction(); // Iniciamos transacción general

            $sql = "INSERT INTO tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, precio, año_vigencia) 
                    VALUES (:maquina, :mantenimiento, :modalidad, :precio, :anio)";
            $stmt = $this->conn->prepare($sql);

            // 1. Primer Bucle: Recorremos cada máquina seleccionada
            foreach ($idsMaquinas as $idMaquina) {

                // 2. Segundo Bucle: Recorremos los mantenimientos
                foreach ($matrizPrecios as $idManto => $modalidades) {

                    // 3. Tercer Bucle: Recorremos las modalidades y precios
                    foreach ($modalidades as $idModalidad => $precio) {

                        // Solo guardamos si hay precio válido
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
            }

            $this->conn->commit(); // Todo salió bien
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack(); // Error, deshacer todo
            error_log("Error masivo tarifa: " . $e->getMessage());
            return false;
        }
    }

    // --- Helpers (Sin cambios) ---
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

    public function obtenerMaquinasConTarifa($anio)
    {
        // Seleccionamos los IDs de máquinas que YA existen en la tabla tarifa para ese año
        $sql = "SELECT DISTINCT id_tipo_maquina FROM tarifa WHERE año_vigencia = :anio";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();

        // Devolvemos un array simple con los IDs (ej: [1, 5, 8])
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
