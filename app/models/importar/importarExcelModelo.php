<?php
class importarExcelModels
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- 1. EL GUARDIÁN: Validar Device ID ---
    public function existeDeviceId($deviceId)
    {
        try {
            $deviceId = trim($deviceId);
            $sql = "SELECT COUNT(*) as total FROM maquina WHERE device_id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $deviceId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res['total'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // --- 2. GESTIÓN DE CLIENTE ---
    public function gestionarCliente($nombre, $codigo)
    {
        try {
            if (!empty($codigo)) {
                $sql = "SELECT id_cliente FROM cliente WHERE codigo_cliente = :cod LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':cod' => $codigo]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res) return $res['id_cliente'];
            }

            $sql = "SELECT id_cliente FROM cliente WHERE nombre_cliente = :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => trim($nombre)]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res['id_cliente'];

            $sqlInsert = "INSERT INTO cliente (nombre_cliente, codigo_cliente, estado) VALUES (:nom, :cod, 1)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->execute([
                ':nom' => trim($nombre),
                ':cod' => !empty($codigo) ? $codigo : null
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    // --- 3. GESTIÓN DE PUNTO ---
    public function gestionarPunto($nombrePunto, $idCliente, $cod1, $cod2, $idDelegacionReal, $direccion)
    {
        try {
            $nombrePunto = trim($nombrePunto);
            $cod1 = trim($cod1);

            // A. Buscar por NOMBRE
            $sql = "SELECT id_punto FROM punto WHERE nombre_punto = :nom AND id_cliente = :cli LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombrePunto, ':cli' => $idCliente]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res['id_punto'];

            // B. Buscar por CÓDIGO (Anti-Typos)
            if (!empty($cod1)) {
                $sqlCode = "SELECT id_punto FROM punto WHERE codigo_1 = :c1 AND id_cliente = :cli LIMIT 1";
                $stmtCode = $this->conn->prepare($sqlCode);
                $stmtCode->execute([':c1' => $cod1, ':cli' => $idCliente]);
                $resCode = $stmtCode->fetch(PDO::FETCH_ASSOC);

                if ($resCode) {
                    // Actualizar nombre y dirección para sincronizar
                    $idEncontrado = $resCode['id_punto'];
                    $sqlUpdate = "UPDATE punto SET nombre_punto = :nom, direccion = :dir, id_delegacion = :del WHERE id_punto = :id";
                    $stmtUpdate = $this->conn->prepare($sqlUpdate);
                    $stmtUpdate->execute([
                        ':nom' => $nombrePunto,
                        ':dir' => $direccion,
                        ':del' => $idDelegacionReal,
                        ':id'  => $idEncontrado
                    ]);
                    return $idEncontrado;
                }
            }

            // C. Crear NUEVO
            $idMunicipio = 1; 
            $sqlInsert = "INSERT INTO punto (nombre_punto, codigo_1, codigo_2, id_municipio, id_delegacion, id_cliente, direccion, estado, id_modalidad) VALUES (:nom, :c1, :c2, :mun, :del, :cli, :dir, 1, 1)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->execute([
                ':nom' => $nombrePunto,
                ':c1'  => $cod1,
                ':c2'  => $cod2,
                ':mun' => $idMunicipio,
                ':del' => $idDelegacionReal,
                ':cli' => $idCliente,
                ':dir' => trim($direccion)
            ]);
            return $this->conn->lastInsertId();

        } catch (PDOException $e) {
            return false;
        }
    }

    // --- 4. GESTIÓN TIPO MÁQUINA ---
    public function obtenerIdTipoMaquina($nombreTipo)
    {
        try {
            $nombreTipo = trim($nombreTipo);
            $sql = "SELECT id_tipo_maquina FROM tipo_maquina WHERE nombre_tipo_maquina = :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombreTipo]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res['id_tipo_maquina'];

            $sqlIns = "INSERT INTO tipo_maquina (nombre_tipo_maquina) VALUES (:nom)";
            $stmtIns = $this->conn->prepare($sqlIns);
            $stmtIns->execute([':nom' => $nombreTipo]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return 1;
        }
    }

    // --- 5. INSERTAR MÁQUINA ---
    public function insertarMaquina($datos)
    {
        try {
            $sql = "INSERT INTO maquina (device_id, id_punto, id_tipo_maquina, estado) VALUES (:dev, :pto, :tipo, 1)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':dev'  => $datos['device_id'],
                ':pto'  => $datos['id_punto'],
                ':tipo' => $datos['id_tipo_maquina']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // --- 6. ACTUALIZAR MÁQUINA (TRASLADO) - ¡ESTA FALTABA! ---
    public function actualizarMaquina($deviceId, $idPunto, $idTipo)
    {
        try {
            $sql = "UPDATE maquina 
                    SET id_punto = :pto, 
                        id_tipo_maquina = :tipo, 
                        fecha_actualizacion = NOW(), 
                        estado = 1 
                    WHERE device_id = :dev";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':pto'  => $idPunto,
                ':tipo' => $idTipo,
                ':dev'  => $deviceId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerIdDelegacion($nombreDelegacion)
    {
        try {
            $nombre = trim(mb_strtoupper($nombreDelegacion, 'UTF-8'));
            $sql = "SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombre]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['id_delegacion'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function tocarMaquina($deviceId)
    {
        try {
            $sql = "UPDATE maquina SET fecha_actualizacion = NOW(), estado = 1 WHERE device_id = :dev";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
        } catch (PDOException $e) {}
    }

    public function tocarPunto($idPunto)
    {
        try {
            $sql = "UPDATE punto SET fecha_actualizacion = NOW(), estado = 1 WHERE id_punto = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
        } catch (PDOException $e) {}
    }

    public function desactivarFantasmas($fechaInicioProceso)
    {
        $resultados = ['maquinas' => 0, 'puntos' => 0];
        try {
            $sqlMaq = "UPDATE maquina SET estado = 0 WHERE estado = 1 AND (fecha_actualizacion < :fecha OR fecha_actualizacion IS NULL)";
            $stmtMaq = $this->conn->prepare($sqlMaq);
            $stmtMaq->execute([':fecha' => $fechaInicioProceso]);
            $resultados['maquinas'] = $stmtMaq->rowCount();

            $sqlPto = "UPDATE punto SET estado = 0 WHERE estado = 1 AND (fecha_actualizacion < :fecha OR fecha_actualizacion IS NULL)";
            $stmtPto = $this->conn->prepare($sqlPto);
            $stmtPto->execute([':fecha' => $fechaInicioProceso]);
            $resultados['puntos'] = $stmtPto->rowCount();
        } catch (PDOException $e) {}
        return $resultados;
    }

    public function obtenerIdPuntoPorDevice($deviceId)
    {
        try {
            $sql = "SELECT id_punto FROM maquina WHERE device_id = :dev LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['id_punto'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>