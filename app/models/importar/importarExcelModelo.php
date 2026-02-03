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
            $cod1 = trim($cod1); // Asegúrate de que el Excel traiga este código

            // 1. PRIMERO: BUSCAR POR CÓDIGO (La identidad real)
            // Esto diferencia "Tienda" de "Tienda." si tienen códigos distintos (ej: 101 vs 102)
            if (!empty($cod1)) {
                $sqlCode = "SELECT id_punto, nombre_punto FROM punto WHERE codigo_1 = :c1 AND id_cliente = :cli LIMIT 1";
                $stmtCode = $this->conn->prepare($sqlCode);
                $stmtCode->execute([':c1' => $cod1, ':cli' => $idCliente]);
                $resCode = $stmtCode->fetch(PDO::FETCH_ASSOC);

                if ($resCode) {
                    $idEncontrado = $resCode['id_punto'];

                    // OPCIONAL: Si quieres que el nombre del Excel sobrescriba al de la BD, deja esto.
                    // Si prefieres respetar el nombre original de la BD (con el punto), comenta el UPDATE.
                    $sqlUpdate = "UPDATE punto 
                              SET nombre_punto = :nom, 
                                  direccion = :dir, 
                                  id_delegacion = :del,
                                  estado = 1 
                              WHERE id_punto = :id";
                    $stmtUpdate = $this->conn->prepare($sqlUpdate);
                    $stmtUpdate->execute([
                        ':nom' => $nombrePunto,
                        ':dir' => $direccion,
                        ':del' => $idDelegacionReal,
                        ':id'  => $idEncontrado
                    ]);

                    return $idEncontrado; // ¡Encontrado por código! Salimos aquí.
                }
            }

            // 2. SEGUNDO: BUSCAR POR NOMBRE (Solo si no hay código o no se encontró)
            // Aquí es donde puede haber confusión si los nombres son idénticos
            $sql = "SELECT id_punto FROM punto WHERE nombre_punto = :nom AND id_cliente = :cli LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombrePunto, ':cli' => $idCliente]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) return $res['id_punto'];

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
    // --- 4. GESTIÓN TIPO MÁQUINA (VERSIÓN BLINDADA) ---
    public function obtenerIdTipoMaquina($nombreTipo)
    {
        try {
            // 1. Limpieza básica (Mayúsculas y quitar espacios extra a los lados)
            $nombreTipo = trim(mb_strtoupper($nombreTipo, 'UTF-8'));

            // 2. EL DICCIONARIO DE TRADUCCIÓN
            // Izquierda: Como viene MAL en el Excel
            // Derecha: Como DEBE SER en tu Base de Datos (El que tiene la tarifa)
            $mapaCorrecciones = [
                'MACH 6'      => 'MATCH 6',    // Corrige el nombre
                'MACH6'       => 'MATCH 6',
                'SDM 500'     => 'SDM-500',    // Estandariza el guion
                'SDM500'      => 'SDM-500',
                'SDM 10'      => 'SDM-10',
                'SDM10'       => 'SDM-10',
                'JH 600'      => 'JH-600',
                'JH600'       => 'JH-600',
                'MINI MEI'    => 'MINI MEI',   // A veces viene 'MINIMEI'
                'MINIMEI'     => 'MINI MEI',
                'SNBC'     => 'PRO EFECTIVO X', // Ejemplo: Si quieres forzar un cambio radical
                // ... Agrega aquí todos los casos que veas en tu Excel ...
            ];

            // 3. APLICAR LA TRADUCCIÓN
            if (array_key_exists($nombreTipo, $mapaCorrecciones)) {
                $nombreTipo = $mapaCorrecciones[$nombreTipo];
            }

            // 4. BÚSQUEDA DEL ID (Ahora buscamos el nombre CORRECTO)
            $sql = "SELECT id_tipo_maquina FROM tipo_maquina WHERE nombre_tipo_maquina = :nom LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nom' => $nombreTipo]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                return $res['id_tipo_maquina'];
            }

            // 5. CREACIÓN CONTROLADA (Opcional: Bloquear creación)
            // Si llegamos aquí, es un tipo de máquina que NO existe y NO estaba en tu mapa.
            // Opción A: Dejar que lo cree (pero tendrás que asignarle tarifa manualmente luego).
            // Opción B (RECOMENDADA): Asignar un ID por defecto "DESCONOCIDO" para no ensuciar la BD.

            /* Si quieres evitar que se creen basuras nuevas, descomenta esto y devuelve el ID 
           de un tipo "GENÉRICO" o "POR DEFINIR" que tengas en tu BD (ej. ID 99).
           
           return 99; 
        */

            // Si prefieres que siga creando los nuevos (pero ya filtraste los errores comunes arriba):
            $sqlIns = "INSERT INTO tipo_maquina (nombre_tipo_maquina, estado) VALUES (:nom, 1)";
            $stmtIns = $this->conn->prepare($sqlIns);
            $stmtIns->execute([':nom' => $nombreTipo]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            // En caso de error, retorna un ID seguro (ej. 1) o null
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
        } catch (PDOException $e) {
        }
    }

    public function tocarPunto($idPunto)
    {
        try {
            $sql = "UPDATE punto SET fecha_actualizacion = NOW(), estado = 1 WHERE id_punto = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $idPunto]);
        } catch (PDOException $e) {
        }
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
        } catch (PDOException $e) {
        }
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
