<?php
// app/models/admin/repuestoCrearModelo.php

if (!defined('ENTRADA_PRINCIPAL')) {
    die("Acceso denegado.");
}

class RepuestoCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearRepuesto($datos)
    {
        try {
            // 1. Consulta SQL para la tabla repuesto (Actualizada con requiere_devolucion)
            $sql = "INSERT INTO repuesto (
                        nombre_repuesto, 
                        codigo_referencia, 
                        estado,
                        requiere_devolucion
                    ) VALUES (
                        :nombre, 
                        :codigo, 
                        :estado,
                        :requiere_devolucion
                    )";

            $stmt = $this->conn->prepare($sql);

            // 2. Vincular parámetros
            $stmt->bindParam(':nombre', $datos['nombre_repuesto']);

            $codigo = !empty($datos['codigo_referencia']) ? $datos['codigo_referencia'] : null;
            $stmt->bindParam(':codigo', $codigo);

            $estado = isset($datos['estado']) ? $datos['estado'] : 1;
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);

            // Nuevo campo: si no se envía, por defecto es 0 (No requiere)
            $requiereDevolucion = isset($datos['requiere_devolucion']) ? $datos['requiere_devolucion'] : 0;
            $stmt->bindParam(':requiere_devolucion', $requiereDevolucion, PDO::PARAM_INT);

            // 3. Ejecutar
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error Base de Datos (crearRepuesto): " . $e->getMessage());
            return false;
        }
    }

    public function existeRepuesto($nombre, $codigo = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM repuesto WHERE nombre_repuesto = :nombre";

            if ($codigo) {
                $sql .= " OR codigo_referencia = :codigo";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);

            if ($codigo) {
                $stmt->bindParam(':codigo', $codigo);
            }

            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}