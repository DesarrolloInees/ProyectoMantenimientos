<?php
// app/controllers/login/resetPasswordControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

class resetPasswordControlador
{
    private $modelo;
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $conexionObj = new Conexion();
        $this->db = $conexionObj->getConexion();
        require_once __DIR__ . "/../../models/login/loginModelo.php";
        $this->modelo = new LoginModelo($this->db);
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarResetPassword();
            return;
        }
        
        // Vista normal
        $data = [
            'baseURL' => BASE_URL,
            'error'   => $_GET['error'] ?? null,
            'email'   => $_GET['email'] ?? ''
        ];
        require_once "app/views/login/resetPasswordVista.php";
    }

    public function procesarResetPassword()
    {
        echo "<pre style='background: #000; color: #0f0; padding: 20px;'>";
        echo "=== MODO DEPURACIÓN ACTIVADO ===\n";

        $email = $_POST['email'] ?? '';
        $codigo = trim($_POST['codigo'] ?? '');
        $p1 = $_POST['nueva_password'] ?? '';
        $p2 = $_POST['confirmar_password'] ?? '';

        echo "1. Datos Recibidos:\n";
        echo "   Email: [$email]\n";
        echo "   Código: [$codigo]\n";
        echo "   Pass 1: [$p1]\n";
        echo "   Pass 2: [$p2]\n\n";

        // VALIDACIÓN 1: Coincidencia
        if ($p1 !== $p2) {
            die("❌ ERROR: Las contraseñas no coinciden.");
        }
        echo "✅ Paso 1: Las contraseñas coinciden.\n";

        // VALIDACIÓN 2: Regex
        // Simplificamos la regex temporalmente para ver si es eso
        $regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&.])[A-Za-z\d@$!%*?&.]{8,}$/";
        if (!preg_match($regex, $p1)) {
            echo "❌ ERROR: La contraseña no cumple con la seguridad (Mayuscula, Minuscula, Numero, Simbolo).\n";
            die("   Intenta poner una más simple temporalmente o revisa la Regex.");
        }
        echo "✅ Paso 2: La contraseña es segura.\n";

        // VALIDACIÓN 3: Verificar Código en BD
        echo "🔍 Verificando código en BD...\n";
        
        // Hacemos una consulta manual para ver qué hay en la BD realmente
        $stmt = $this->db->prepare("SELECT * FROM password_reset WHERE usuario_email = :email ORDER BY id DESC LIMIT 1");
        $stmt->execute([':email' => $email]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            die("❌ ERROR: No existe ningún registro de reset para este email en la tabla 'password_reset'.");
        }

        echo "   Registro encontrado en BD:\n";
        print_r($registro);

        $ahora = date('Y-m-d H:i:s');
        echo "\n   Hora del Servidor PHP: " . $ahora . "\n";
        echo "   Hora de Expiración BD: " . $registro['expira_en'] . "\n";

        if ($registro['usado'] == 1) {
            die("❌ ERROR: Este código YA FUE USADO (usado = 1). Genera uno nuevo.");
        }

        if ($registro['expira_en'] <= $ahora) {
            die("❌ ERROR: El código ha EXPIRADO (La hora actual es mayor a la de expiración). Revisa la Zona Horaria.");
        }

        if (!password_verify($codigo, $registro['codigo_hash'])) {
             die("❌ ERROR: El código escrito NO COINCIDE con el hash guardado.");
        }

        echo "✅ Paso 3: El código es VÁLIDO.\n";

        // VALIDACIÓN 4: Actualizar Usuario
        $usuario = $this->modelo->obtenerUsuarioPorEmail($email);
        if (!$usuario) {
            die("❌ ERROR: No se encuentra el usuario en la tabla 'usuarios'.");
        }
        
        echo "✅ Paso 4: Usuario encontrado (ID: " . $usuario['usuario_id'] . ").\n";
        echo "⚙️ Intentando actualizar password...\n";

        $hash = password_hash($p1, PASSWORD_BCRYPT);
        $update = $this->modelo->actualizarPassword($usuario['usuario_id'], $hash);

        if ($update) {
            echo "🎉 ¡ÉXITO! La base de datos confirmó la actualización.\n";
            $this->modelo->marcarCodigoComoUsado($registro['id']);
            echo "   Código marcado como usado.\n";
            echo "   <a href='".BASE_URL."login' style='color: white; font-size: 20px;'>--> CLIC AQUÍ PARA IR AL LOGIN <--</a>";
        } else {
            echo "❌ ERROR CRÍTICO: El modelo devolvió FALSE al intentar hacer el UPDATE. Revisa logs de error.";
        }
        
        echo "</pre>";
        exit(); // Detenemos todo aquí
    }
}