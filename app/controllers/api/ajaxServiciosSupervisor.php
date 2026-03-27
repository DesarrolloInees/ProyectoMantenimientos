<?php
// ajaxServiciosSupervisor.php
// Asegúrate de incluir tu conexión a la BD aquí
require_once __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

try {
    $conexionObj = new Conexion();
    $db = $conexionObj->getConexion();

    // Traemos los servicios del día (puedes ajustar el WHERE según tu lógica)
    $sql = "SELECT 
                o.id_ordenes_servicio,
                o.numero_remision,
                t.nombre_tecnico,
                o.id_estado_maquina, -- O el campo que uses para el estado del servicio (Pendiente, En Ruta, etc.)
                
                COALESCE(c.nombre_cliente, 'SIN CLIENTE') as cliente
            FROM ordenes_servicio o
            LEFT JOIN tecnico t ON o.id_tecnico = t.id_tecnico
            LEFT JOIN cliente c ON o.id_cliente = c.id_cliente
            WHERE DATE(o.fecha_visita) = CURDATE() 
            ORDER BY o.id_ordenes_servicio DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolvemos todo en formato JSON
    echo json_encode([
        'status' => 'success',
        'data' => $servicios
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

