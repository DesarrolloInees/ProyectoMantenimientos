<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class InventarioTecnicoCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // Cargar lista de técnicos activos
    public function obtenerTecnicos()
    {
        // Asegúrate de que tu tabla se llame 'tecnico' (singular)
        $sql = "SELECT id_tecnico, nombre_tecnico FROM tecnico WHERE estado = 1 ORDER BY nombre_tecnico ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cargar lista de repuestos activos
    public function obtenerRepuestos()
    {
        // Asegúrate de que tu tabla se llame 'repuesto' (singular)
        $sql = "SELECT id_repuesto, nombre_repuesto, codigo_referencia FROM repuesto WHERE estado = 1 ORDER BY nombre_repuesto ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // LA MAGIA: Insertar o Sumar (Versión Corregida HY093)
    public function asignarStock($idTecnico, $idRepuesto, $cantidad)
    {
        try {
            // CORRECCIÓN: Usamos dos nombres distintos para la cantidad (:cant_ini y :cant_sum)
            // para evitar que PDO se confunda al contar los parámetros.

            $sql = "INSERT INTO inventario_tecnico (id_tecnico, id_repuesto, cantidad_actual, estado) 
                    VALUES (:id_tec, :id_rep, :cant_ini, 1)
                    ON DUPLICATE KEY UPDATE 
                        cantidad_actual = cantidad_actual + :cant_sum,
                        estado = 1";

            $stmt = $this->conn->prepare($sql);

            // Vinculamos los parámetros
            $stmt->bindValue(':id_tec', $idTecnico, PDO::PARAM_INT);
            $stmt->bindValue(':id_rep', $idRepuesto, PDO::PARAM_INT);

            // Vinculamos la cantidad DOS VECES con nombres distintos
            $stmt->bindValue(':cant_ini', $cantidad, PDO::PARAM_INT); // Para el INSERT
            $stmt->bindValue(':cant_sum', $cantidad, PDO::PARAM_INT); // Para el UPDATE

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error SQL Asignar Stock: " . $e->getMessage());
            // Si quieres ver el error en pantalla mientras pruebas, descomenta esto:
            // die("Error SQL Detallado: " . $e->getMessage());
            return false;
        }
    }
}
