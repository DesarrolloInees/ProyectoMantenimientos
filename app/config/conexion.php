<?php
// app/config/conexion.php

class Conexion
{
    // Configuración
    private $host = 'localhost';
    private $db_name = 'inees_mantenimientos'; // Asegúrate que este sea el nombre real de tu BD nueva
    private $username = 'root';
    private $password = '';
    private $port = '3306';

    public $conn;

    public function getConexion()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->db_name .
                ";charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
            exit;
        }

        return $this->conn;
    }
}
