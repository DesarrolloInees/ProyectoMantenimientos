<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class costosAdministrativosCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ==========================================
    // PARTE 1: GASTOS GENERALES
    // ==========================================

    public function crearGasto($datos)
    {
        try {
            $sql = "INSERT INTO gastos_administrativos (mes_reporte, concepto, categoria, valor, estado) 
                    VALUES (:mes, :concepto, :categoria, :valor, 1)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes',      $datos['mes_reporte']);
            $stmt->bindParam(':concepto', $datos['concepto']);
            $stmt->bindParam(':categoria',$datos['categoria']);
            $stmt->bindParam(':valor',    $datos['valor']);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error crearGasto: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerGastosPorMes($mes)
    {
        try {
            $sql = "SELECT * FROM gastos_administrativos 
                    WHERE mes_reporte = :mes 
                      AND estado = 1
                    ORDER BY id_gasto DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes', $mes);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    // Borrado lógico
    public function eliminarGasto($id)
    {
        try {
            $sql = "UPDATE gastos_administrativos SET estado = 0 WHERE id_gasto = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) { return false; }
    }

    // ==========================================
    // PARTE 2: NÓMINA ADMINISTRATIVA
    // ==========================================

    public function obtenerPersonalAdmin()
    {
        try {
            $sql = "SELECT 
                        usuario_id as id, 
                        nombre, 
                        IFNULL(cargo, 'Administrativo') as cargo
                    FROM usuarios 
                    WHERE (estado = 'activo' OR estado = '1')
                      AND nivel_acceso != 3
                    ORDER BY nombre ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo personal admin: " . $e->getMessage());
            return [];
        }
    }

    public function guardarNominaAdmin($fechaReporte, $datos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO costos_operativos (
                        id_usuario, mes_reporte, salario, horas_extra, 
                        bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado
                    ) VALUES (
                        :id_usuario, :mes_reporte, :salario, :horas_extra, 
                        :bono_meta, :gasolina, :auxilio_comunicacion, :auxilio_rodamiento, 1
                    ) ON DUPLICATE KEY UPDATE 
                        salario              = VALUES(salario),
                        horas_extra          = VALUES(horas_extra),
                        bono_meta            = VALUES(bono_meta),
                        gasolina             = VALUES(gasolina),
                        auxilio_comunicacion = VALUES(auxilio_comunicacion),
                        auxilio_rodamiento   = VALUES(auxilio_rodamiento),
                        estado               = 1";  

            $stmt = $this->conn->prepare($sql);

            foreach ($datos as $idUsuario => $costo) {
                $salario      = !empty($costo['salario'])              ? floatval($costo['salario'])              : 0;
                $horas        = !empty($costo['horas_extra'])          ? floatval($costo['horas_extra'])          : 0;
                $bono         = !empty($costo['bono_meta'])            ? floatval($costo['bono_meta'])            : 0;
                $gasolina     = !empty($costo['gasolina'])             ? floatval($costo['gasolina'])             : 0;
                $comunicacion = !empty($costo['auxilio_comunicacion']) ? floatval($costo['auxilio_comunicacion']) : 0;

                if (($salario + $horas + $bono + $gasolina + $comunicacion) == 0) continue;

                $stmt->bindParam(':id_usuario',           $idUsuario);
                $stmt->bindParam(':mes_reporte',          $fechaReporte);
                $stmt->bindParam(':salario',              $salario);
                $stmt->bindParam(':horas_extra',          $horas);
                $stmt->bindParam(':bono_meta',            $bono);
                $stmt->bindParam(':gasolina',             $gasolina);
                $stmt->bindParam(':auxilio_comunicacion', $comunicacion);
                $stmt->bindValue(':auxilio_rodamiento',   0);

                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error guardarNominaAdmin: " . $e->getMessage());
            return false;
        }
    }
}