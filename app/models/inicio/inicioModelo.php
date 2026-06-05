<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

class inicioModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Total de órdenes de servicio del mes actual (basado en fecha_visita)
     * Solo órdenes activas o programadas (estado 1 o 2)
     */
    public function totalOrdenesMes()
    {
        try {
            $primerDia = date('Y-m-01');
            $ultimoDia = date('Y-m-t');

            $sql = "SELECT COUNT(*) as total 
                    FROM ordenes_servicio 
                    WHERE fecha_visita BETWEEN :inicio AND :fin
                    AND estado IN (1, 2)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':inicio' => $primerDia,
                ':fin'    => $ultimoDia
            ]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en totalOrdenesMes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Total de clientes activos (estado = 1)
     */
    public function totalClientes()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM cliente WHERE estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en totalClientes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Total de técnicos activos (estado = 1)
     */
    public function totalTecnicos()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM tecnico WHERE estado = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en totalTecnicos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * (Opcional) Órdenes completadas en los últimos 7 días
     * Necesitarías definir qué campo/estado representa "completada"
     * Por ahora lo dejo comentado hasta que definas la lógica.
     */
    /*
    public function ordenesCompletadasUltimaSemana()
    {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM ordenes_servicio 
                    WHERE id_estado_maquina = :estado_completado
                    AND fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':estado_completado' => 1]); // Cambia según tu catálogo
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } catch (PDOException $e) {
            error_log("ERROR en ordenesCompletadasUltimaSemana: " . $e->getMessage());
            return 0;
        }
    }
    */
}