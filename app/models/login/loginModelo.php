<?php
date_default_timezone_set('America/Bogota');

class LoginModelo
{
    private $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function authenticateUser($usuario, $password)
    {
        try {
            // Seleccionamos todas las columnas de 'u' y el idTipoUsuario de 'ut'
            $sql = "SELECT u.*, ut.idTipoUsuario 
                    FROM usuarios u 
                    INNER JOIN tipousuario ut ON u.nivel_acceso = ut.idTipoUsuario -- <-- CORREGIDO AQUÍ
                    WHERE u.usuario = :usuario AND u.estado = 'activo'
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Ahora sí debería existir $user['password_hash']
            if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                return $user;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error de autenticación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un nuevo acceso en la tabla de historial `login`.
     * @param int $usuario_id El ID del usuario que inicia sesión.
     * @param string $nombre_usuario El nombre del usuario que inicia sesión.
     * @param string $ip La dirección IP del usuario.
     * @return bool True si el registro es exitoso, false en caso de error.
     */
    public function logAccess($usuario_id, $nombre_usuario, $ip)
    {
        try {
            $sql = "INSERT INTO login (usuario_id, fecha, hora, usuario, ip) VALUES (:usuario_id, CURDATE(), CURTIME(), :usuario, :ip)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':usuario', $nombre_usuario);
            $stmt->bindParam(':ip', $ip);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar el acceso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la fecha del último acceso del usuario en la tabla `usuarios`.
     * Esto es para tener una referencia rápida sin consultar la tabla `login`.
     * @param int $usuario_id El ID del usuario.
     * @return bool True si la actualización es exitosa, false en caso de error.
     */
    public function updateLastLoginTime($usuario_id)
    {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario_id = :usuario_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar el último acceso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el último acceso de un usuario a partir de la tabla `login`.
     * Excluye el acceso actual para mostrar el acceso anterior.
     * @param int $usuario_id El ID del usuario.
     * @return string La fecha y hora del último acceso previo, o un mensaje si no existe.
     */
    public function getLastPreviousAccess($usuario_id)
    {
        try {
            // Se selecciona el último acceso del usuario, ordenado de forma descendente, pero se limita a 2 para obtener el penúltimo registro.
            $sql = "SELECT fecha, hora FROM login WHERE usuario_id = :usuario_id ORDER BY fecha DESC, hora DESC LIMIT 2";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();

            $acceso = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si hay al menos 2 registros, se devuelve el segundo (el penúltimo)
            if (count($acceso) >= 2) {
                return "Último acceso: " . $acceso[1]['fecha'] . " a las " . $acceso[1]['hora'];
            }
        } catch (PDOException $e) {
            error_log("Error al obtener el último acceso: " . $e->getMessage());
        }
        return "Sin registro de acceso previo.";
    }

    // Esta función se encargará de actualizar la contraseña del usuario.
    public function actualizarPassword($usuario_id, $nueva_password)
    {
        try {
            $nuevo_hash = password_hash($nueva_password, PASSWORD_BCRYPT);

            $sql = "UPDATE usuarios SET 
                        password_hash = :hash, 
                        forzar_cambio_pwd = FALSE, 
                        pwd_ultimo_cambio = NOW() 
                    WHERE usuario_id = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':hash', $nuevo_hash);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar la contraseña: " . $e->getMessage());
            return false;
        }
    }

    // ***** NUEVA FUNCIÓN OPCIONAL PERO RECOMENDADA *****
    // Para marcar a un usuario cuando su contraseña expira.
    public function marcarParaCambioPassword($usuario_id)
    {
        try {
            $stmt = $this->conn->prepare("UPDATE usuarios SET forzar_cambio_pwd = TRUE WHERE usuario_id = :id");
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al marcar para cambio de pwd: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el valor de CUALQUIER parámetro del sistema desde la tabla 'parametros'.
     *
     * @param string $nombreClave El nombre de la 'clave' que queremos buscar.
     * @param mixed $valorPorDefecto Valor a devolver si la clave no se encuentra.
     * @return string El valor de la configuración.
     */
    public function obtenerParametro($nombreClave, $valorPorDefecto = null)
    {
        try {
            $stmt = $this->conn->prepare("SELECT valor FROM parametros WHERE clave = :clave LIMIT 1");
            $stmt->bindParam(':clave', $nombreClave);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                return $resultado['valor'];
            }
            return $valorPorDefecto;
        } catch (PDOException $e) {
            error_log("Error al obtener parámetro (" . $nombreClave . "): " . $e->getMessage());
            return $valorPorDefecto;
        }
    }


    // En modelos/login/loginModelo.php

    /**
     * Busca un usuario por su email.
     */
    public function obtenerUsuarioPorEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = :email AND estado = 'activo' LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda el código hasheado en la BD. Invalida los códigos viejos.
     */
    public function guardarCodigoReset($email, $codigo_hash, $expiracion)
    {
        // Invalida códigos viejos de ese email
        $stmt_invalidar = $this->conn->prepare("UPDATE password_reset SET usado = TRUE WHERE usuario_email = :email");
        $stmt_invalidar->bindParam(':email', $email);
        $stmt_invalidar->execute();

        // Inserta el nuevo código
        $sql = "INSERT INTO password_reset (usuario_email, codigo_hash, expira_en) VALUES (:email, :hash, :expira)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hash', $codigo_hash);
        $stmt->bindParam(':expira', $expiracion);
        return $stmt->execute();
    }

    /**
     * Verifica si un código es válido, no está usado y no ha expirado.
     */
    public function verificarCodigoReset($email, $codigo_enviado)
    {
        // Busca un código que coincida, que no esté usado, y que no haya expirado
        $sql = "SELECT * FROM password_reset 
                WHERE usuario_email = :email AND usado = FALSE AND expira_en > NOW()
                ORDER BY id DESC"; // Obtener el más reciente
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $codigos_en_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Comparamos el código enviado con los hashes vigentes
        foreach ($codigos_en_bd as $row) {
            if (password_verify($codigo_enviado, $row['codigo_hash'])) {
                return $row['id']; // ¡Coincidencia! Devolvemos el ID del registro
            }
        }
        return false; // No se encontró coincidencia
    }

    /**
     * Marca un código como 'usado' después de un reseteo exitoso.
     */
    public function marcarCodigoComoUsado($id_del_codigo)
    {
        $stmt_update = $this->conn->prepare("UPDATE password_reset SET usado = TRUE WHERE id = :id");
        $stmt_update->bindParam(':id', $id_del_codigo);
        return $stmt_update->execute();
    }
}
