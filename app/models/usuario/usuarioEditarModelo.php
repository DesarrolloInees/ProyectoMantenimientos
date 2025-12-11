<?php
// modelos/usuario/usuarioEditarModelo.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class UsuarioEditarModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function obtenerUsuarioPorId($id_usuario)
    {
        try {
            $sql = "SELECT * FROM usuarios WHERE usuario_id = :id_usuario";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function editarUsuario($id_usuario, $datos)
    {
        try {
            // Construimos la SQL dinámica (si hay pass o no)
            $sql = "UPDATE usuarios SET 
                        nombre = :nombre,
                        cedula = :cedula,
                        cargo = :cargo,
                        email = :email,
                        celular = :celular,
                        usuario = :usuario,
                        nivel_acceso = :nivel_acceso,
                        estado = :estado";

            // Solo actualizamos contraseña si el usuario escribió algo
            if (!empty($datos['pass'])) {
                $sql .= ", password_hash = :pass"; // OJO: Tu columna se llama password_hash
            }

            $sql .= " WHERE usuario_id = :id_usuario";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':cedula', $datos['cedula']);
            $stmt->bindParam(':cargo', $datos['cargo']);
            $stmt->bindParam(':email', $datos['email']);
            $stmt->bindParam(':celular', $datos['celular']);
            $stmt->bindParam(':usuario', $datos['usuario']);
            $stmt->bindParam(':nivel_acceso', $datos['nivel_acceso']);
            $stmt->bindParam(':estado', $datos['estado']);
            $stmt->bindParam(':id_usuario', $id_usuario);

            if (!empty($datos['pass'])) {
                $pass_hash = password_hash($datos['pass'], PASSWORD_BCRYPT);
                $stmt->bindParam(':pass', $pass_hash);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error editarUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTiposUsuario()
    {
        $stmt = $this->conn->query("SELECT idTipoUsuario, nombreTipoUsuario FROM tipousuario ORDER BY nombreTipoUsuario ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}