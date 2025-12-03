<?php
class Database {
    // Configuración XAMPP Default
    private $host = 'localhost';
    private $db_name = 'inees_mantenimientos';
    private $username = 'root'; // Usuario por defecto de XAMPP
    private $password = '';     // Contraseña vacía por defecto de XAMPP
    private $port = '3306';     // Puerto de MySQL (No confundir con el 8080 de Apache)
    
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Estructura de conexión (DSN) incluyendo el puerto explícito
            $dsn = "mysql:host=" . $this->host . 
                    ";port=" . $this->port . 
                    ";dbname=" . $this->db_name . 
                    ";charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lanza excepciones si hay error
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve arrays asociativos
                    PDO::ATTR_EMULATE_PREPARES => false, // Seguridad contra inyección SQL
                ]
            );

        } catch(PDOException $e) {
            // Si falla, mostramos el error específico
            echo "Error de conexión: " . $e->getMessage();
            exit; // Detiene el script si no hay base de datos
        }

        return $this->conn;
    }
}
?>