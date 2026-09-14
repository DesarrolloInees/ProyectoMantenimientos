<?php
// app/config/conexion.php

// Configuración SMTP centralizada.
// Los secretos SIEMPRE vienen de variables de entorno / .env (cargado con vlucas/phpdotenv).
// No poner contraseñas reales en este archivo ni en ningún controlador.
function smtpEnv(string $key, $default = null)
{
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    return $default;
}

class Conexion
{
    // Configuración de base de datos
    private $host = 'localhost';
    private $db_name = 'inees_mantenimientos';
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

/**
 * Configuración SMTP para notificaciones
 * Centralizada para evitar credenciales dispersas en los controladores.
 *
 * Lee únicamente de variables de entorno / .env:
 *   SMTP_HOST, SMTP_USER, SMTP_PASS, SMTP_PORT, SMTP_SECURE
 */
function getConfiguracionSmtp(): array
{
    $host   = smtpEnv('SMTP_HOST', 'smtp.gmail.com');
    $user   = smtpEnv('SMTP_USER', 'ineesmensajesautomaticos@gmail.com');
    $pass   = smtpEnv('SMTP_PASS', '');
    $port   = (int) smtpEnv('SMTP_PORT', 465);
    $secure = strtolower((string) smtpEnv('SMTP_SECURE', 'ssl'));

    if ($pass === '') {
        throw new RuntimeException(
            'Falta SMTP_PASS en variables de entorno/.env. ' .
            'Configúralo en el archivo .env (ver .env.example) sin commitear secretos.'
        );
    }

    return [
        'host'      => $host,
        'user'      => $user,
        'pass'      => $pass,
        'port'      => $port,
        'secure'    => $secure,
        'from'      => $user,
        'from_name' => 'Sistema I-Nexis',
    ];
}
