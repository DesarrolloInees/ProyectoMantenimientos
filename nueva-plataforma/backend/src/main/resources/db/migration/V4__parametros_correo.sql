-- V4: destinatarios de las solicitudes de repuestos (en PHP estaban escritos en el controlador)
INSERT IGNORE INTO parametros (clave, valor, descripcion) VALUES
  ('correos_solicitud_inventario',
   'almacen@inees.co,administrativo@inees.co,desarrollo@inees.co,desarrollo2@inees.co,operaciones@inees.co,auxadministrativo@inees.co',
   'Destinatarios del aviso de nueva solicitud de repuestos de un técnico');
