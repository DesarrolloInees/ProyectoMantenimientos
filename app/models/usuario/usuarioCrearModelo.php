<?php
// modelos/usuario/usuarioCrearModelo.php

if (!defined('ENTRADA_PRINCIPAL')) {
    die("Acceso denegado.");
}

class UsuarioCrearModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crearUsuario($datos)
    {
        try {
            // 1. Encriptar contraseña
            $pass_cifrada = password_hash($datos['pass'], PASSWORD_BCRYPT);

            // 2. Consulta SQL exacta para tu tabla usuarios
            $sql = "INSERT INTO usuarios (
                        nombre, 
                        cedula, 
                        cargo, 
                        email, 
                        celular, 
                        usuario, 
                        password_hash, 
                        nivel_acceso, 
                        forzar_cambio_pwd, 
                        pwd_ultimo_cambio,
                        estado
                    ) VALUES (
                        :nombre, 
                        :cedula, 
                        :cargo, 
                        :email, 
                        :celular, 
                        :usuario, 
                        :pass, 
                        :nivel_acceso, 
                        1, 
                        NOW(),
                        'activo'
                    )";

            $stmt = $this->conn->prepare($sql);

            // 3. Vincular parámetros
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':cedula', $datos['cedula']);
            $stmt->bindParam(':cargo', $datos['cargo']);
            $stmt->bindParam(':email', $datos['email']);
            $stmt->bindParam(':celular', $datos['celular']);
            $stmt->bindParam(':usuario', $datos['usuario']);
            $stmt->bindParam(':pass', $pass_cifrada);
            $stmt->bindParam(':nivel_acceso', $datos['nivel_acceso'], PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Esto guardará el error exacto en tu log de errores de PHP (xampp/apache/logs/error.log)
            error_log("Error Base de Datos (crearUsuario): " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTiposUsuario()
    {
        try {
            $sql = "SELECT idTipoUsuario, nombreTipoUsuario FROM tipousuario ORDER BY nombreTipoUsuario ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener tipos de usuario: " . $e->getMessage());
            return [];
        }
    }
}
