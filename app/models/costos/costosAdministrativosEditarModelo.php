<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class costosAdministrativosEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ==========================================
    // PARTE 1: NÓMINA
    // ==========================================

    public function obtenerNominaCompletaPorMes($mes)
    {
        try {
            $sql = "SELECT 
                        u.usuario_id as id, 
                        u.nombre, 
                        IFNULL(u.cargo, 'Administrativo') as cargo,
                        COALESCE(c.salario, 0)             as salario,
                        COALESCE(c.horas_extra, 0)         as horas_extra,
                        COALESCE(c.bono_meta, 0)           as bono_meta,
                        COALESCE(c.gasolina, 0)            as gasolina,
                        COALESCE(c.auxilio_comunicacion, 0)as auxilio_comunicacion
                    FROM usuarios u
                    LEFT JOIN costos_operativos c 
                        ON u.usuario_id = c.id_usuario 
                       AND c.mes_reporte = :mes
                       AND c.estado = 1
                    WHERE (u.estado = 'activo' OR u.estado = '1')
                      AND u.nivel_acceso != 3
                    ORDER BY u.nombre ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes', $mes);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerNominaCompletaPorMes: " . $e->getMessage());
            return [];
        }
    }

    public function actualizarNominaMasiva($fechaReporte, $datos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO costos_operativos (
                        id_usuario, mes_reporte, salario, horas_extra, 
                        bono_meta, gasolina, auxilio_comunicacion, auxilio_rodamiento, estado
                    ) VALUES (
                        :id_usuario, :mes_reporte, :salario, :horas_extra, 
                        :bono_meta, :gasolina, :auxilio_comunicacion, 0, 1
                    ) ON DUPLICATE KEY UPDATE 
                        salario              = VALUES(salario),
                        horas_extra          = VALUES(horas_extra),
                        bono_meta            = VALUES(bono_meta),
                        gasolina             = VALUES(gasolina),
                        auxilio_comunicacion = VALUES(auxilio_comunicacion),
                        estado               = 1";

            $stmt = $this->conn->prepare($sql);

            foreach ($datos as $idUsuario => $costo) {
                $salario      = floatval($costo['salario']              ?? 0);
                $horas        = floatval($costo['horas_extra']          ?? 0);
                $bono         = floatval($costo['bono_meta']            ?? 0);
                $gasolina     = floatval($costo['gasolina']             ?? 0);
                $comunicacion = floatval($costo['auxilio_comunicacion'] ?? 0);

                $stmt->bindParam(':id_usuario',           $idUsuario);
                $stmt->bindParam(':mes_reporte',          $fechaReporte);
                $stmt->bindParam(':salario',              $salario);
                $stmt->bindParam(':horas_extra',          $horas);
                $stmt->bindParam(':bono_meta',            $bono);
                $stmt->bindParam(':gasolina',             $gasolina);
                $stmt->bindParam(':auxilio_comunicacion', $comunicacion);

                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error actualizarNominaMasiva: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // PARTE 2: GASTOS GENERALES
    // ==========================================

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

    public function actualizarGastosMasivos($gastos)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE gastos_administrativos SET 
                        concepto   = :concepto, 
                        categoria  = :categoria, 
                        valor      = :valor 
                    WHERE id_gasto = :id
                      AND estado   = 1";

            $stmt = $this->conn->prepare($sql);

            foreach ($gastos as $idGasto => $datos) {
                $stmt->bindParam(':concepto',  $datos['concepto']);
                $stmt->bindParam(':categoria', $datos['categoria']);
                $stmt->bindParam(':valor',     $datos['valor']);
                $stmt->bindParam(':id',        $idGasto, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error actualizarGastosMasivos: " . $e->getMessage());
            return false;
        }
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

    // Borrado lógico nómina admin
    public function eliminarNominaAdmin($idCosto)
    {
        try {
            $sql = "UPDATE costos_operativos 
                    SET estado = 0 
                    WHERE id_costo    = :id_costo
                      AND id_usuario IS NOT NULL";  // Protección: solo registros de usuarios

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_costo', $idCosto, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminarNominaAdmin: " . $e->getMessage());
            return false;
        }
    }
}