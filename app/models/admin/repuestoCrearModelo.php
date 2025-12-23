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
            // 1. Consulta SQL para la tabla repuesto
            // Nota: 'estado' lo definimos por defecto en 1 (Activo) si no viene en los datos,
            // aunque la base de datos también tiene un default, es mejor asegurarlo aquí.
            $sql = "INSERT INTO repuesto (
                        nombre_repuesto, 
                        codigo_referencia, 
                        estado
                    ) VALUES (
                        :nombre, 
                        :codigo, 
                        :estado
                    )";

            $stmt = $this->conn->prepare($sql);

            // 2. Vincular parámetros
            // Usamos trim para limpiar espacios en blanco innecesarios
            $stmt->bindParam(':nombre', $datos['nombre_repuesto']);

            // Si el código viene vacío, insertamos NULL (ya que la tabla permite NULL)
            $codigo = !empty($datos['codigo_referencia']) ? $datos['codigo_referencia'] : null;
            $stmt->bindParam(':codigo', $codigo);

            // Estado por defecto 1 (Activo) si no se especifica
            $estado = isset($datos['estado']) ? $datos['estado'] : 1;
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);

            // 3. Ejecutar
            return $stmt->execute();
        } catch (PDOException $e) {
            // Guardamos el error en el log del servidor para no exponerlo al usuario
            error_log("Error Base de Datos (crearRepuesto): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método opcional: Verificar si ya existe un repuesto con el mismo nombre o código
     * Útil para validar antes de insertar en el controlador.
     */
    public function existeRepuesto($nombre, $codigo = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM repuesto WHERE nombre_repuesto = :nombre";

            // Si hay código, ampliamos la búsqueda (depende de tu regla de negocio)
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
