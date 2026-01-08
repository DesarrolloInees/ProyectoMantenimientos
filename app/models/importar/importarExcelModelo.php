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
            error_log("Error validando DeviceID: " . $e->getMessage());
            return false; // Ante la duda, asumimos que no existe para no bloquear, o true para bloquear.
        }
    }

    // --- 2. GESTIÓN DE CLIENTE (Busca o Crea) ---
    public function gestionarCliente($nombre, $codigo)
    {
        try {
            // A. Intentar buscar por CÓDIGO (si viene en el excel)
            if (!empty($codigo)) {
                $sql = "SELECT id_cliente FROM cliente WHERE codigo_cliente = :cod LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':cod' => $codigo]);
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($res) return $res['id_cliente'];
            }

            // B. Intentar buscar por NOMBRE EXACTO
            $sql = "SELECT id_cliente FROM cliente WHERE nombre_cliente = :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => trim($nombre)]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res['id_cliente'];

            // C. Si no existe, CREAR NUEVO
            $sqlInsert = "INSERT INTO cliente (nombre_cliente, codigo_cliente, estado) VALUES (:nom, :cod, 1)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->execute([
                ':nom' => trim($nombre),
                ':cod' => !empty($codigo) ? $codigo : null
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error gestionando Cliente: " . $e->getMessage());
            return false;
        }
    }

    // --- 3. GESTIÓN DE PUNTO (Busca o Crea) ---
    // --- CORREGIDO: GESTIÓN DE PUNTO CON DIRECCIÓN ---
    // Ahora recibe $direccion y $idDelegacionReal
    public function gestionarPunto($nombrePunto, $idCliente, $cod1, $cod2, $idDelegacionReal, $direccion)
    {
        try {
            // A. Buscar si el punto ya existe
            $sql = "SELECT id_punto FROM punto WHERE nombre_punto = :nom AND id_cliente = :cli LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':nom' => trim($nombrePunto),
                ':cli' => $idCliente
            ]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res['id_punto'];

            // B. CREAR PUNTO (Insertando Dirección y Delegación Correcta)
            // Asumo id_municipio = 1 por defecto si no lo estamos validando aún, pero la delegación SÍ va real.
            $idMunicipio = 1;

            $sqlInsert = "INSERT INTO punto 
                (nombre_punto, codigo_1, codigo_2, id_municipio, id_delegacion, id_cliente, direccion, estado, id_modalidad) 
                VALUES (:nom, :c1, :c2, :mun, :del, :cli, :dir, 1, 1)";

            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->execute([
                ':nom' => trim($nombrePunto),
                ':c1'  => $cod1,
                ':c2'  => $cod2,
                ':mun' => $idMunicipio,
                ':del' => $idDelegacionReal, // <--- Aquí va el ID que validamos
                ':cli' => $idCliente,
                ':dir' => trim($direccion)   // <--- Aquí va la dirección
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error gestionando Punto: " . $e->getMessage());
            return false;
        }
    }

    // --- 4. GESTIÓN TIPO MÁQUINA ---
    public function obtenerIdTipoMaquina($nombreTipo)
    {
        try {
            $nombreTipo = trim($nombreTipo);

            // Buscar ID
            $sql = "SELECT id_tipo_maquina FROM tipo_maquina WHERE nombre_tipo_maquina = :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombreTipo]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) return $res['id_tipo_maquina'];

            // Insertar si no existe
            $sqlIns = "INSERT INTO tipo_maquina (nombre_tipo_maquina) VALUES (:nom)";
            $stmtIns = $this->conn->prepare($sqlIns);
            $stmtIns->execute([':nom' => $nombreTipo]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return 1; // Retorno genérico si falla
        }
    }

    // --- 5. INSERTAR LA MÁQUINA FINAL ---
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
            error_log("Error insertando Maquina: " . $e->getMessage());
            return false;
        }
    }

    // --- AUXILIAR: Mapeo simple de Municipios ---
    private function resolverMunicipio($nombreTexto)
    {
        try {
            $sql = "SELECT id_municipio FROM municipio WHERE nombre_municipio LIKE :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => "%" . trim($nombreTexto) . "%"]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            // Retorna 1 (o el ID genérico que tengas para 'Desconocido') si no encuentra nada
            return $res ? $res['id_municipio'] : 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    public function obtenerIdDelegacion($nombreDelegacion)
    {
        try {
            // Limpiamos la entrada (Mayúsculas y sin espacios extra)
            $nombre = trim(mb_strtoupper($nombreDelegacion, 'UTF-8'));

            // Buscamos coincidencia exacta o parecida
            $sql = "SELECT id_delegacion FROM delegacion WHERE nombre_delegacion LIKE :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombre]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si existe, retornamos el ID. Si no, retornamos NULL (o 1 si tienes una genérica)
            return $res ? $res['id_delegacion'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }


    // --- NUEVO: TOCAR MÁQUINA (Marca que esta máquina vino hoy) ---
    public function tocarMaquina($deviceId)
    {
        try {
            $sql = "UPDATE maquina SET fecha_actualizacion = NOW(), estado = 1 WHERE device_id = :dev";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':dev' => $deviceId]);
        } catch (PDOException $e) {
        }
    }

    // --- NUEVO: TOCAR PUNTO (Marca que este punto vino hoy) ---
    public function tocarPunto($idPunto)
    {
        try {
            $sql = "UPDATE punto SET fecha_actualizacion = NOW(), estado = 1 WHERE id_punto = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
        } catch (PDOException $e) {
        }
    }

    // --- NUEVO: LA GUILLOTINA DOBLE (Apaga lo que no se tocó hoy) ---
    public function desactivarFantasmas($fechaInicioProceso)
    {
        $resultados = ['maquinas' => 0, 'puntos' => 0];

        try {
            // 1. DESACTIVAR MÁQUINAS NO TOCADAS
            $sqlMaq = "UPDATE maquina 
                        SET estado = 0 
                        WHERE estado = 1 
                        AND (fecha_actualizacion < :fecha OR fecha_actualizacion IS NULL)";
            $stmtMaq = $this->conn->prepare($sqlMaq);
            $stmtMaq->execute([':fecha' => $fechaInicioProceso]);
            $resultados['maquinas'] = $stmtMaq->rowCount();

            // 2. DESACTIVAR PUNTOS NO TOCADOS
            $sqlPto = "UPDATE punto 
                        SET estado = 0 
                        WHERE estado = 1 
                        AND (fecha_actualizacion < :fecha OR fecha_actualizacion IS NULL)";
            $stmtPto = $this->conn->prepare($sqlPto);
            $stmtPto->execute([':fecha' => $fechaInicioProceso]);
            $resultados['puntos'] = $stmtPto->rowCount();
        } catch (PDOException $e) {
            error_log("Error desactivando fantasmas: " . $e->getMessage());
        }

        return $resultados;
    }

    // --- NUEVO: OBTENER EL ID DEL PUNTO ACTUAL DE UNA MÁQUINA ---
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
