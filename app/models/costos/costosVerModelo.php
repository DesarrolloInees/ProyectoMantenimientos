<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class CostosVerModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Resumen agrupado por mes — solo registros de técnicos (id_tecnico IS NOT NULL)
     */
    public function obtenerMesesAgrupados()
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(mes_reporte, '%Y-%m') AS mes_reporte,
                        COUNT(id_tecnico)   AS cantidad_tecnicos,
                        SUM(salario)        AS total_nomina,
                        SUM(auxilio_rodamiento + gasolina + bono_meta + horas_extra + auxilio_comunicacion) AS total_operativo,
                        SUM(salario + auxilio_rodamiento + gasolina + bono_meta + horas_extra + auxilio_comunicacion) AS total_general
                    FROM costos_operativos
                    WHERE id_tecnico IS NOT NULL
                      AND estado = 1
                    GROUP BY DATE_FORMAT(mes_reporte, '%Y-%m')
                    ORDER BY mes_reporte DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo meses agrupados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detalle de un mes — solo técnicos
     */
    public function obtenerDetallePorMes($mes)
    {
        try {
            $sql = "SELECT 
                        c.id_costo,
                        t.nombre_tecnico,
                        c.salario,
                        c.auxilio_rodamiento,
                        c.gasolina,
                        c.bono_meta,
                        c.horas_extra,
                        c.auxilio_comunicacion,
                        (c.salario + c.auxilio_rodamiento + c.gasolina + c.bono_meta + c.horas_extra + c.auxilio_comunicacion) AS total_general
                    FROM costos_operativos c
                    INNER JOIN tecnico t ON c.id_tecnico = t.id_tecnico
                    WHERE DATE_FORMAT(c.mes_reporte, '%Y-%m') = :mes
                      AND c.id_tecnico  IS NOT NULL
                      AND c.estado = 1
                    ORDER BY t.nombre_tecnico ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes', $mes);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo detalle por mes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Borrado lógico: Cambia el estado de 1 a 0
     */
    public function eliminarCosto($id_costo)
    {
        try {
            // No borramos, actualizamos estado = 0
            $sql = "UPDATE costos_operativos 
                    SET estado = 0, 
                        fecha_actualizacion = NOW() 
                    WHERE id_costo = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id_costo, PDO::PARAM_INT);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error en borrado lógico: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarMesCompleto($mes)
    {
        try {
            // Borrado lógico de todo el mes
            $sql = "UPDATE costos_operativos 
                    SET estado = 0 
                    WHERE DATE_FORMAT(mes_reporte, '%Y-%m') = :mes";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mes', $mes);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}