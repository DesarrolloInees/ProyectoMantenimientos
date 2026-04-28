<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class GestionDevolucionModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene la lista de técnicos para el filtro
     */
    public function obtenerTecnicos()
    {
        $stmt = $this->conn->query("SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene TODOS los repuestos que el técnico aún no ha devuelto a la sede
     */
    public function obtenerRepuestosPendientes($idTecnico = '')
    {
        try {
            $sql = "SELECT 
                        cdr.id_orden_servicio,
                        cdr.id_repuesto,
                        cdr.cantidad,
                        cdr.estado_devolucion,
                        r.nombre_repuesto,
                        r.codigo_referencia,
                        t.nombre_tecnico,
                        os.fecha_visita,
                        os.numero_remision,
                        c.nombre_cliente,
                        p.nombre_punto,
                        d.nombre_delegacion
                    FROM control_devolucion_repuestos cdr
                    INNER JOIN repuesto r ON cdr.id_repuesto = r.id_repuesto
                    INNER JOIN tecnico t ON cdr.id_tecnico = t.id_tecnico
                    INNER JOIN ordenes_servicio os ON cdr.id_orden_servicio = os.id_ordenes_servicio
                    INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE cdr.estado_devolucion = 'Pendiente'"; 

            if (!empty($idTecnico)) {
                $sql .= " AND cdr.id_tecnico = :id_tecnico";
            }

            // Ordenamos por fecha de visita (los más antiguos primero para presionar a que los devuelvan)
            $sql .= " ORDER BY os.fecha_visita ASC";

            $stmt = $this->conn->prepare($sql);

            if (!empty($idTecnico)) {
                $stmt->bindParam(':id_tecnico', $idTecnico, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerRepuestosPendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca el repuesto como 'Devuelto'
     */
    public function marcarComoDevuelto($idOrden, $idRepuesto)
    {
        try {
            // Actualizamos el estado a 'Devuelto'
            $sql = "UPDATE control_devolucion_repuestos 
                    SET estado_devolucion = 'Devuelto' 
                    WHERE id_orden_servicio = ? AND id_repuesto = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$idOrden, $idRepuesto]);
        } catch (PDOException $e) {
            error_log("Error marcando como devuelto: " . $e->getMessage());
            return false;
        }
    }
}