-- =====================================================================================
-- V7: cuatro roles fijos y fáciles de entender (pedido del 12-11-2026).
--   Superusuario                — todo, incluidos usuarios, catálogos, parámetros y costos.
--   Supervisor de motorizados   — revisor: la operación del día, los servicios, inventario, remisiones, transportes,
--                                 reportes; además aprueba lo del personal y marca máquinas VIP.
--   Auxiliar de oficina         — la operación del día, los servicios, inventario, remisiones, transportes y reportes.
--   Motorizado                  — su ruta y el servicio paso a paso en el celular.
-- Los permisos de cada rol ya no se editan en pantalla: al crear un usuario solo se elige su rol.
-- Se conservan los id de siempre (1, 5, 2, 3); el rol Prosegur (4) sigue inactivo y no aparece en ninguna lista.
-- =====================================================================================

ALTER TABLE rol
  ADD COLUMN descripcion VARCHAR(300) NULL AFTER nombre,
  ADD COLUMN orden TINYINT NOT NULL DEFAULT 9 AFTER descripcion;

UPDATE rol SET nombre = 'Superusuario', orden = 1,
       descripcion = 'Todo: la operación, los servicios, el personal, los costos y la administración (usuarios, catálogos, tarifas y parámetros).'
 WHERE id_rol = 1;
UPDATE rol SET nombre = 'Supervisor de motorizados', orden = 2,
       descripcion = 'Revisa y dirige a los motorizados: arma y publica las rutas, sigue el día, revisa y corrige servicios, aprueba horas extra, asistencia y parqueaderos, y marca máquinas VIP.'
 WHERE id_rol = 5;
UPDATE rol SET nombre = 'Auxiliar de oficina', orden = 3,
       descripcion = 'Apoya la operación: carga el Excel de Prosegur, ubica máquinas, arma rutas, registra y corrige servicios, y maneja inventario, remisiones, transportes y reportes.'
 WHERE id_rol = 2;
UPDATE rol SET nombre = 'Motorizado', orden = 4,
       descripcion = 'Usa el celular: ve su ruta del día y hace cada servicio paso a paso, con fotos y firma.'
 WHERE id_rol = 3;
UPDATE rol SET orden = 9, estado = 0,
       descripcion = 'Retirado: Prosegur ya no entra a la plataforma; su Excel lo carga la oficina.'
 WHERE id_rol = 4;

DELETE FROM rol_permiso WHERE id_rol IN (1, 2, 3, 4, 5);
INSERT INTO rol_permiso (id_rol, codigo_permiso) VALUES
  (1, 'ADMINISTRACION'), (1, 'CONFIGURACION'), (1, 'COSTOS'), (1, 'INVENTARIO'), (1, 'ORDENES'), (1, 'PERSONAL'),
  (1, 'PLANEACION'), (1, 'REMISIONES'), (1, 'REPORTES'), (1, 'SEGUIMIENTO'), (1, 'TRANSPORTE'),
  (5, 'PLANEACION'), (5, 'SEGUIMIENTO'), (5, 'ORDENES'), (5, 'INVENTARIO'), (5, 'REMISIONES'), (5, 'TRANSPORTE'),
  (5, 'REPORTES'), (5, 'PERSONAL'), (5, 'CONFIGURACION'),
  (2, 'PLANEACION'), (2, 'SEGUIMIENTO'), (2, 'ORDENES'), (2, 'INVENTARIO'), (2, 'REMISIONES'), (2, 'TRANSPORTE'),
  (2, 'REPORTES'),
  (3, 'CAMPO');

-- Descripciones de los permisos en el lenguaje de la operación (se muestran en «Qué puede hacer cada rol»)
UPDATE permiso SET descripcion = 'Cargar el Excel de Prosegur, ubicar máquinas y armar y publicar las rutas' WHERE codigo = 'PLANEACION';
UPDATE permiso SET descripcion = 'Seguir las rutas del día en vivo, sus alertas y el rastreo de los motorizados' WHERE codigo = 'SEGUIMIENTO';
UPDATE permiso SET descripcion = 'Registrar, revisar y corregir servicios; PDF y exportaciones de servicios' WHERE codigo = 'ORDENES';
UPDATE permiso SET descripcion = 'Stock de repuestos de los motorizados, solicitudes y devoluciones' WHERE codigo = 'INVENTARIO';
UPDATE permiso SET descripcion = 'Talonarios de remisiones y remisiones salteadas' WHERE codigo = 'REMISIONES';
UPDATE permiso SET descripcion = 'Instalaciones, desinstalaciones y traslados de máquinas' WHERE codigo = 'TRANSPORTE';
UPDATE permiso SET descripcion = 'Tablero ejecutivo y reportes (técnico, repuestos, máquinas, historial, tarifas)' WHERE codigo = 'REPORTES';
UPDATE permiso SET descripcion = 'Aprobar horas extra y parqueaderos; jornadas, tiempos, asistencia y alertas logísticas' WHERE codigo = 'PERSONAL';
UPDATE permiso SET descripcion = 'Marcar máquinas VIP y su frecuencia de mantenimiento' WHERE codigo = 'CONFIGURACION';
UPDATE permiso SET descripcion = 'Costos de motorizados, nómina, gastos y analizador de cotizaciones' WHERE codigo = 'COSTOS';
UPDATE permiso SET descripcion = 'Usuarios, catálogos, tarifas, importaciones y parámetros' WHERE codigo = 'ADMINISTRACION';
UPDATE permiso SET descripcion = 'Su ruta del día y el servicio paso a paso en el celular' WHERE codigo = 'CAMPO';
