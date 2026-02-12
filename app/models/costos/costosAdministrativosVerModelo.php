<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class costosAdministrativosVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerResumenMensual()
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(T.mes_reporte, '%Y-%m') AS mes_reporte,

                        -- Suma de Gastos Generales (solo activos)
                        (SELECT COALESCE(SUM(valor), 0) 
                            FROM gastos_administrativos 
                            WHERE mes_reporte = T.mes_reporte
                                AND estado = 1
                        ) as total_gastos,

                        -- Suma de Nómina Administrativa (solo activos, excluye técnicos)
                        (SELECT COALESCE(SUM(c.salario + c.horas_extra + c.bono_meta + c.gasolina + c.auxilio_comunicacion), 0) 
                            FROM costos_operativos c
                            INNER JOIN usuarios u ON c.id_usuario = u.usuario_id
                            WHERE c.mes_reporte = T.mes_reporte
                                AND c.estado = 1
                                AND u.nivel_acceso != 3
                        ) as total_nomina

                    FROM (
                        SELECT DISTINCT mes_reporte FROM gastos_administrativos WHERE estado = 1
                        UNION
                        SELECT DISTINCT mes_reporte FROM costos_operativos 
                        WHERE id_usuario IS NOT NULL AND estado = 1
                    ) AS T
                    ORDER BY T.mes_reporte DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerResumenMensual: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Borrado lógico COMPLETO del mes administrativo:
     * 1. Gastos Generales (Arriendo, servicios, etc.)
     * 2. Nómina Administrativa (Solo donde id_usuario IS NOT NULL)
     */
    public function eliminarMesAdministrativoCompleto($mes)
    {
        try {
            // Iniciamos transacción para asegurar consistencia
            $this->conn->beginTransaction();

            // 1. Desactivar Gastos Generales (Tabla gastos_administrativos)
            $sql1 = "UPDATE gastos_administrativos 
                     SET estado = 0 
                     WHERE DATE_FORMAT(mes_reporte, '%Y-%m') = :mes";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bindParam(':mes', $mes);
            $stmt1->execute();

            // 2. Desactivar Nómina Administrativa (Tabla costos_operativos)
            // OJO: Solo donde id_usuario tenga datos (id_usuario IS NOT NULL)
            $sql2 = "UPDATE costos_operativos 
                     SET estado = 0, 
                         fecha_actualizacion = NOW()
                     WHERE DATE_FORMAT(mes_reporte, '%Y-%m') = :mes
                       AND id_usuario IS NOT NULL"; // <--- Esta es la clave que pediste
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bindParam(':mes', $mes);
            $stmt2->execute();

            // Si todo salió bien, confirmamos los cambios
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            // Si algo falla, deshacemos todo
            $this->conn->rollBack();
            error_log("Error eliminando mes administrativo completo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Borrado lógico de un gasto individual por ID
     */
    public function eliminarGastoIndividual($id_gasto)
    {
        try {
            $sql = "UPDATE gastos_administrativos 
                    SET estado = 0 
                    WHERE id_gasto = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id_gasto, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando gasto individual: " . $e->getMessage());
            return false;
        }
    }
}
