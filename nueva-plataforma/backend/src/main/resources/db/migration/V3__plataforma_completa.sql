-- =====================================================================================
-- V3: la plataforma nueva reemplaza por completo a la aplicación PHP.
--   1. Un solo juego de caracteres (había utf8mb3, utf8mb4_general, unicode y spanish2 mezclados)
--   2. Roles con nombres consistentes y permisos por módulo (se retiran rutas / rol_permisos)
--   3. Columnas derivadas o duplicadas: tiempo_servicio (texto) -> minutos_servicio,
--      punto.frecuencia_mantenimiento_dias -> maquina.frecuencia_dias (V2), año_vigencia -> anio_vigencia
--   4. Llaves foráneas e índices que faltaban, catálogo de estados de remisión completo
--   5. Saldo inicial del kardex para que el stock siempre sea la suma de sus movimientos
--   6. Parámetros que estaban escritos en el código PHP (delegaciones principales, correos, metas)
-- Después de esta versión la aplicación PHP ya no puede usar esta base.
-- =====================================================================================

-- -------------------------------------------------------------------------------------
-- 1. Juego de caracteres único: utf8mb4 con orden alfabético español
-- -------------------------------------------------------------------------------------
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;

SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE calificacion_servicio          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE cliente                        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE control_devolucion_repuestos   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE control_remisiones             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE costos_operativos              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE delegacion                     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE dias_festivos                  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE estado_maquina                 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE estados_aprobacion_he          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE estados_remision               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE evidencia_horas_extra          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE evidencia_servicio             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE facturas_parqueadero           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE gastos_administrativos         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE instalaciones_desinstalaciones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE inventario_tecnico             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE login                          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE maquina                        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE modalidad_operativa            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE monitoreo_motorizados_live     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE municipio                      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE orden_servicio_novedad         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE orden_servicio_repuesto        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE ordenes_servicio               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE ordenes_servicio_complemento   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE parametros                     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE password_reset                 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE punto                          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE registro_horas_extra           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE repuesto                       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE solicitud_inventario_detalle   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE solicitudes_inventario         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE tarifa                         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE tecnico                        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE tipo_mantenimiento             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE tipo_maquina                   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE tipo_novedad                   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE tipousuario                    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE turno_tecnico                  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
ALTER TABLE usuarios                       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------------------------------
-- 2. Roles y permisos
-- -------------------------------------------------------------------------------------
DROP TABLE rol_permisos;
DROP TABLE rutas;

ALTER TABLE tipousuario
  RENAME COLUMN idTipoUsuario TO id_rol,
  RENAME COLUMN nombreTipoUsuario TO nombre;
RENAME TABLE tipousuario TO rol;
ALTER TABLE rol ADD UNIQUE KEY uk_rol_nombre (nombre);

ALTER TABLE usuarios RENAME COLUMN nivel_acceso TO id_rol;
ALTER TABLE usuarios ADD UNIQUE KEY uk_usuarios_usuario (usuario);

INSERT INTO permiso (codigo, descripcion) VALUES
  ('ORDENES',        'Registrar, corregir y valorizar servicios; PDF de servicios'),
  ('INVENTARIO',     'Repuestos, stock de los motorizados, solicitudes y devoluciones'),
  ('REMISIONES',     'Talonarios de remisiones y remisiones salteadas'),
  ('PERSONAL',       'Horas extra, parqueaderos, jornadas, tiempos de servicio y asistencia'),
  ('TRANSPORTE',     'Instalaciones, desinstalaciones y traslados de máquinas'),
  ('REPORTES',       'Reportes ejecutivo, técnico, facturación, repuestos y exportaciones'),
  ('COSTOS',         'Costos de motorizados, nómina administrativa y gastos'),
  ('ADMINISTRACION', 'Usuarios, roles, catálogos, importaciones y parámetros');

-- SuperUsuario: todo. Supervisor y Auxiliar: lo que tenían en la aplicación PHP.
INSERT INTO rol_permiso (id_rol, codigo_permiso)
SELECT 1, codigo FROM permiso WHERE codigo NOT IN (SELECT codigo_permiso FROM rol_permiso WHERE id_rol = 1) AND codigo <> 'CAMPO';
INSERT INTO rol_permiso (id_rol, codigo_permiso) VALUES
  (2, 'ORDENES'),
  (5, 'ORDENES'), (5, 'INVENTARIO'), (5, 'REMISIONES'), (5, 'PERSONAL'), (5, 'REPORTES');

-- -------------------------------------------------------------------------------------
-- 3. Columnas derivadas o duplicadas
-- -------------------------------------------------------------------------------------
-- Duración del servicio en minutos (número) en lugar de texto 'HH:MM'
UPDATE ordenes_servicio
   SET minutos_servicio = CAST(SUBSTRING_INDEX(tiempo_servicio, ':', 1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(tiempo_servicio, ':', -1) AS UNSIGNED)
 WHERE minutos_servicio IS NULL AND tiempo_servicio REGEXP '^[0-9]+:[0-9]{2}$';
UPDATE ordenes_servicio SET minutos_servicio = 0 WHERE minutos_servicio IS NULL AND estado = 1;
ALTER TABLE ordenes_servicio DROP COLUMN tiempo_servicio;

-- La frecuencia vive en la máquina (V2); en el punto quedaba duplicada
ALTER TABLE punto DROP COLUMN frecuencia_mantenimiento_dias;

-- Nombre de columna sin eñe y una sola tarifa por combinación y año
ALTER TABLE tarifa RENAME COLUMN `año_vigencia` TO anio_vigencia;
ALTER TABLE tarifa
  ADD UNIQUE KEY uk_tarifa (id_tipo_maquina, id_tipo_mantenimiento, id_modalidad, anio_vigencia),
  DROP KEY idx_vigencia;

-- -------------------------------------------------------------------------------------
-- 4. Catálogos, llaves foráneas e índices
-- -------------------------------------------------------------------------------------
-- La pantalla de remisiones salteadas usaba FALTANTE y ELIMINADO, que no existían en el catálogo
INSERT INTO estados_remision (nombre_estado, activo)
SELECT 'FALTANTE', 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM estados_remision WHERE nombre_estado = 'FALTANTE');
INSERT INTO estados_remision (nombre_estado, activo)
SELECT 'ELIMINADO', 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM estados_remision WHERE nombre_estado = 'ELIMINADO');
ALTER TABLE estados_remision ADD UNIQUE KEY uk_estado_remision_nombre (nombre_estado);

-- Índice repetido (el único idx_tecnico_remision ya cubre id_tecnico + numero_remision)
ALTER TABLE control_remisiones DROP KEY id_tecnico;

-- Devoluciones: tenían índices pero no llaves foráneas
ALTER TABLE control_devolucion_repuestos
  ADD CONSTRAINT fk_dev_tecnico  FOREIGN KEY (id_tecnico) REFERENCES tecnico (id_tecnico),
  ADD CONSTRAINT fk_dev_orden    FOREIGN KEY (id_orden_servicio) REFERENCES ordenes_servicio (id_ordenes_servicio) ON DELETE CASCADE,
  ADD CONSTRAINT fk_dev_repuesto FOREIGN KEY (id_repuesto) REFERENCES repuesto (id_repuesto);

-- Solicitudes de repuestos: el usuario era INT con signo (usuarios.usuario_id no lo tiene)
UPDATE solicitudes_inventario s LEFT JOIN usuarios u ON u.usuario_id = s.id_usuario
   SET s.id_usuario = NULL WHERE u.usuario_id IS NULL;
ALTER TABLE solicitudes_inventario
  MODIFY COLUMN id_usuario INT(10) UNSIGNED NULL,
  ADD CONSTRAINT fk_solinv_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (usuario_id) ON DELETE SET NULL;
ALTER TABLE solicitud_inventario_detalle
  ADD CONSTRAINT fk_solinvdet_repuesto FOREIGN KEY (id_repuesto) REFERENCES repuesto (id_repuesto);

-- Transportes: cliente y punto de origen/destino sin llaves foráneas
UPDATE instalaciones_desinstalaciones i LEFT JOIN cliente c ON c.id_cliente = i.id_cliente_origen
   SET i.id_cliente_origen = NULL WHERE i.id_cliente_origen IS NOT NULL AND c.id_cliente IS NULL;
UPDATE instalaciones_desinstalaciones i LEFT JOIN cliente c ON c.id_cliente = i.id_cliente_destino
   SET i.id_cliente_destino = NULL WHERE i.id_cliente_destino IS NOT NULL AND c.id_cliente IS NULL;
UPDATE instalaciones_desinstalaciones i LEFT JOIN punto p ON p.id_punto = i.id_punto_origen
   SET i.id_punto_origen = NULL WHERE i.id_punto_origen IS NOT NULL AND p.id_punto IS NULL;
UPDATE instalaciones_desinstalaciones i LEFT JOIN punto p ON p.id_punto = i.id_punto_destino
   SET i.id_punto_destino = NULL WHERE i.id_punto_destino IS NOT NULL AND p.id_punto IS NULL;
ALTER TABLE instalaciones_desinstalaciones
  ADD CONSTRAINT fk_inst_cliente_origen  FOREIGN KEY (id_cliente_origen)  REFERENCES cliente (id_cliente) ON DELETE SET NULL,
  ADD CONSTRAINT fk_inst_cliente_destino FOREIGN KEY (id_cliente_destino) REFERENCES cliente (id_cliente) ON DELETE SET NULL,
  ADD CONSTRAINT fk_inst_punto_origen    FOREIGN KEY (id_punto_origen)    REFERENCES punto (id_punto) ON DELETE SET NULL,
  ADD CONSTRAINT fk_inst_punto_destino   FOREIGN KEY (id_punto_destino)   REFERENCES punto (id_punto) ON DELETE SET NULL;

-- El reporte de tarifas separaba máquinas "grandes" con los id 9, 15 y 45 escritos en el código
ALTER TABLE tipo_maquina ADD COLUMN es_grande TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Categoría de tamaño para el reporte de tarifas';
UPDATE tipo_maquina SET es_grande = 1 WHERE id_tipo_maquina IN (9, 15, 45);

-- Índices para los reportes por rango de fechas
ALTER TABLE ordenes_servicio      ADD KEY idx_orden_fecha (fecha_visita);
ALTER TABLE registro_horas_extra  ADD KEY idx_rhe_fecha (fecha_reporte);
ALTER TABLE facturas_parqueadero  ADD KEY idx_parqueadero_fecha (fecha_servicio);
ALTER TABLE turno_tecnico         ADD KEY idx_turno_fecha (fecha);
ALTER TABLE login                 ADD KEY idx_login_fecha (fecha);

-- -------------------------------------------------------------------------------------
-- 5. Kardex: saldo inicial para que el stock sea siempre la suma de los movimientos
-- -------------------------------------------------------------------------------------
INSERT INTO movimiento_inventario (id_tecnico, id_repuesto, tipo, cantidad, observacion, fecha)
SELECT i.id_tecnico, i.id_repuesto, 'AJUSTE',
       i.cantidad_actual - COALESCE(m.suma, 0), 'Saldo inicial al pasar a la plataforma nueva', NOW()
  FROM inventario_tecnico i
  LEFT JOIN (SELECT id_tecnico, id_repuesto, SUM(cantidad) AS suma FROM movimiento_inventario
              GROUP BY id_tecnico, id_repuesto) m
         ON m.id_tecnico = i.id_tecnico AND m.id_repuesto = i.id_repuesto
 WHERE i.cantidad_actual - COALESCE(m.suma, 0) <> 0;

-- -------------------------------------------------------------------------------------
-- 6. Reglas que estaban escritas en el código PHP
-- -------------------------------------------------------------------------------------
INSERT IGNORE INTO parametros (clave, valor, descripcion) VALUES
  ('delegaciones_principales',      '1,2,3,4,5', 'Delegaciones base: fuera de ellas un servicio interurbano cobra viáticos'),
  ('correos_alertas_logisticas',    'supervisorsat@inees.co,operaciones@inees.co', 'Destinatarios del reporte diario de alertas logísticas'),
  ('alerta_visitas_7_dias',         '3',   'Visitas al mismo punto en 7 días que generan alerta'),
  ('alerta_desplazamiento_min',     '40',  'Minutos entre servicios urbanos que generan alerta de desplazamiento'),
  ('meta_servicios_dia',            '6',   'Meta diaria de servicios por motorizado (lunes a viernes)'),
  ('meta_servicios_sabado',         '3',   'Meta de servicios por motorizado el sábado'),
  ('meta_nueva_arquitectura_dia',   '3',   'Meta diaria cuando el motorizado hace Nueva Arquitectura'),
  ('meta_nueva_arquitectura_sabado','2',   'Meta del sábado cuando el motorizado hace Nueva Arquitectura'),
  ('minutos_almuerzo',              '60',  'Minutos de almuerzo que se descuentan de la jornada en el reporte técnico'),
  ('alertas_retraso_telegram',      '1',   '1 = avisa por Telegram cuando un servicio supera su tiempo estimado');
