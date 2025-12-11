<?php
// modelos/usuario/usuarioVerModelo.php

if (!defined('ENTRADA_PRINCIPAL')) {
    die("Acceso denegado.");
}

class UsuarioVerModelo
{

    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene una lista de todos los usuarios activos.
     * Incluye el nombre del tipo de usuario (rol) en lugar de solo el ID.
     */
    public function obtenerUsuarios()
    {
        try {
            // CORRECCIÓN: Se añade un JOIN para obtener el nombre del rol.
            $sql = "SELECT 
                        u.usuario_id, 
                        u.nombre, 
                        u.cedula, 
                        u.cargo, 
                        u.email, 
                        u.celular,
                        u.usuario,
                        tu.nombreTipoUsuario AS rol -- Se obtiene el nombre del rol
                    FROM 
                        usuarios u
                    INNER JOIN 
                        tipousuario tu ON u.nivel_acceso = tu.idTipoUsuario
                    WHERE 
                        u.estado = 'activo' 
                    ORDER BY 
                        u.nombre ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener los usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca un usuario como inactivo (borrado lógico).
     */
    public function eliminarUsuarioLogicamente($id)
    {
        $sql = "UPDATE usuarios SET estado = 'inactivo' WHERE usuario_id = :id";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar el usuario lógicamente: " . $e->getMessage());
            return false;
        }
    }
}
