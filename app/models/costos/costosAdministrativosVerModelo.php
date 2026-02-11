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
}