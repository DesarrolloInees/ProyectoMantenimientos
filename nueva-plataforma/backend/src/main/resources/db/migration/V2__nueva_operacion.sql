-- =====================================================================================
-- V2: cambios de la nueva operación (propuesta del 10-11-2026)
--   · Excel diario de Prosegur  · bolsa con prioridades  · rutas por motorizado
--   · flujo guiado en campo     · inventario con kardex  · permisos por funcionalidad
-- No se borra nada que use la aplicación PHP: rutas, rol_permisos, monitoreo_motorizados_live
-- y punto.frecuencia_mantenimiento_dias se retiran en una migración posterior, cuando
-- la plataforma nueva reemplace a la anterior.
-- =====================================================================================

-- -------------------------------------------------------------------------------------
-- 1. Estados de la orden (se conservan los códigos 1 y 2 que ya usan las 12.703 órdenes)
-- -------------------------------------------------------------------------------------
CREATE TABLE estado_orden (
  id_estado_orden TINYINT(4) NOT NULL,
  codigo          VARCHAR(30) NOT NULL,
  nombre          VARCHAR(50) NOT NULL,
  es_final        TINYINT(1)  NOT NULL DEFAULT 0,
  PRIMARY KEY (id_estado_orden),
  UNIQUE KEY uk_estado_orden_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Estados de una orden de servicio';

INSERT INTO estado_orden (id_estado_orden, codigo, nombre, es_final) VALUES
  (1, 'FINALIZADA',  'Finalizada', 1),
  (2, 'PROGRAMADA',  'Programada en una ruta publicada', 0),
  (3, 'PENDIENTE',   'En la bolsa, sin ruta', 0),
  (4, 'EN_CAMINO',   'El motorizado va hacia el punto', 0),
  (5, 'EN_PUNTO',    'En el punto, esperando ingreso', 0),
  (6, 'EN_SERVICIO', 'En servicio', 0),
  (9, 'CANCELADA',   'Cancelada', 1);

-- -------------------------------------------------------------------------------------
-- 2. Carga del Excel de Prosegur
-- -------------------------------------------------------------------------------------
CREATE TABLE carga_solicitud (
  id_carga        INT(11) NOT NULL AUTO_INCREMENT,
  nombre_archivo  VARCHAR(255) NOT NULL,
  fecha_servicio  DATE NOT NULL COMMENT 'Día en que se deben hacer los servicios',
  id_usuario      INT(10) UNSIGNED NOT NULL COMMENT 'Quién cargó el archivo',
  fecha_carga     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_filas     INT(11) NOT NULL DEFAULT 0,
  filas_ok        INT(11) NOT NULL DEFAULT 0,
  filas_error     INT(11) NOT NULL DEFAULT 0,
  estado          ENUM('EN_REVISION','CONFIRMADA','ANULADA') NOT NULL DEFAULT 'EN_REVISION',
  fecha_confirmacion DATETIME NULL,
  PRIMARY KEY (id_carga),
  KEY idx_carga_fecha (fecha_servicio),
  CONSTRAINT fk_carga_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Cada Excel de servicios del día siguiente enviado por Prosegur';

CREATE TABLE solicitud_servicio (
  id_solicitud          INT(11) NOT NULL AUTO_INCREMENT,
  id_carga              INT(11) NOT NULL,
  fila_excel            INT(11) NOT NULL,
  device_id             VARCHAR(30) NULL COMMENT 'Tal como viene en el Excel',
  referencia_externa    VARCHAR(60) NULL COMMENT 'Ticket, caso u orden de Prosegur',
  tipo_servicio_texto   VARCHAR(100) NULL,
  descripcion           TEXT NULL,
  id_maquina            INT(11) NULL,
  id_tipo_mantenimiento INT(11) NULL,
  estado                ENUM('VALIDA','ERROR','ORDEN_GENERADA','DESCARTADA') NOT NULL,
  mensaje_error         VARCHAR(255) NULL,
  id_orden_servicio     INT(11) NULL,
  datos_originales      LONGTEXT NULL COMMENT 'Fila completa del Excel en JSON',
  PRIMARY KEY (id_solicitud),
  KEY idx_solicitud_carga (id_carga),
  CONSTRAINT fk_solicitud_carga   FOREIGN KEY (id_carga) REFERENCES carga_solicitud (id_carga) ON DELETE CASCADE,
  CONSTRAINT fk_solicitud_maquina FOREIGN KEY (id_maquina) REFERENCES maquina (id_maquina),
  CONSTRAINT fk_solicitud_tipo    FOREIGN KEY (id_tipo_mantenimiento) REFERENCES tipo_mantenimiento (id_tipo_mantenimiento),
  CONSTRAINT fk_solicitud_orden   FOREIGN KEY (id_orden_servicio) REFERENCES ordenes_servicio (id_ordenes_servicio) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Cada fila del Excel de Prosegur';

-- -------------------------------------------------------------------------------------
-- 3. Máquinas: ubicación exacta, VIP, frecuencia propia y seriales esperados
-- -------------------------------------------------------------------------------------
ALTER TABLE maquina
  ADD COLUMN latitud         DECIMAL(10,8) NULL COMMENT 'Ubicación exacta de la máquina',
  ADD COLUMN longitud        DECIMAL(11,8) NULL,
  ADD COLUMN geo_origen      ENUM('DIRECCION','PIN','GPS_TECNICO') NULL COMMENT 'De dónde salió la ubicación',
  ADD COLUMN geo_verificada  TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN geo_id_usuario  INT(10) UNSIGNED NULL,
  ADD COLUMN geo_fecha       DATETIME NULL,
  ADD COLUMN es_vip          TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Prelación sobre las máquinas normales',
  ADD COLUMN frecuencia_dias INT(11) NULL COMMENT 'Si es NULL se usa la de su tipo',
  ADD COLUMN serial_maquina  VARCHAR(30) NULL COMMENT 'Seriales esperados para verificar en campo',
  ADD COLUMN serial_router   VARCHAR(30) NULL,
  ADD COLUMN serial_ups      VARCHAR(30) NULL,
  ADD KEY idx_maquina_vip (es_vip);

-- Las coordenadas geocodificadas de cada punto pasan a su máquina como ubicación por dirección
UPDATE maquina m
  JOIN punto p ON p.id_punto = m.id_punto
   SET m.latitud = p.latitud, m.longitud = p.longitud, m.geo_origen = 'DIRECCION', m.geo_fecha = NOW()
 WHERE p.latitud IS NOT NULL AND p.longitud IS NOT NULL AND p.latitud <> 0 AND p.longitud <> 0;

-- Última visita real: la mayor entre sus órdenes finalizadas y la del punto
UPDATE maquina m
  JOIN punto p ON p.id_punto = m.id_punto
  LEFT JOIN (SELECT id_maquina, MAX(fecha_visita) AS f FROM ordenes_servicio WHERE estado = 1 GROUP BY id_maquina) o
         ON o.id_maquina = m.id_maquina
   SET m.ultima_visita = NULLIF(GREATEST(COALESCE(o.f, '1900-01-01'),
                                         COALESCE(p.fecha_ultima_visita, '1900-01-01'),
                                         COALESCE(m.ultima_visita, '1900-01-01')), '1900-01-01');

-- Frecuencia propia solo cuando la del punto difiere de la de su tipo
UPDATE maquina m
  JOIN punto p ON p.id_punto = m.id_punto
  JOIN tipo_maquina t ON t.id_tipo_maquina = m.id_tipo_maquina
   SET m.frecuencia_dias = p.frecuencia_mantenimiento_dias
 WHERE p.frecuencia_mantenimiento_dias IS NOT NULL
   AND (t.frecuencia_base_dias IS NULL OR p.frecuencia_mantenimiento_dias <> t.frecuencia_base_dias);

-- Seriales esperados: los del último servicio que los registró
UPDATE maquina m
  JOIN (SELECT os.id_maquina, c.serial_maquina, c.serial_router, c.serial_ups
          FROM ordenes_servicio os
          JOIN ordenes_servicio_complemento c ON c.id_orden_servicio = os.id_ordenes_servicio
          JOIN (SELECT os2.id_maquina, MAX(os2.id_ordenes_servicio) AS ultima
                  FROM ordenes_servicio os2
                  JOIN ordenes_servicio_complemento c2 ON c2.id_orden_servicio = os2.id_ordenes_servicio
                 WHERE c2.serial_maquina IS NOT NULL AND TRIM(c2.serial_maquina) <> ''
                 GROUP BY os2.id_maquina) u ON u.ultima = os.id_ordenes_servicio) s
    ON s.id_maquina = m.id_maquina
   SET m.serial_maquina = NULLIF(TRIM(s.serial_maquina), ''),
       m.serial_router  = NULLIF(TRIM(s.serial_router), ''),
       m.serial_ups     = NULLIF(TRIM(s.serial_ups), '');

-- -------------------------------------------------------------------------------------
-- 4. Técnicos: delegación base, punto de partida y jornada
-- -------------------------------------------------------------------------------------
ALTER TABLE tecnico
  ADD COLUMN id_delegacion       INT(11) NULL COMMENT 'Delegación base: define urbano o interurbano',
  ADD COLUMN latitud_partida     DECIMAL(10,8) NULL,
  ADD COLUMN longitud_partida    DECIMAL(11,8) NULL,
  ADD COLUMN hora_inicio_jornada TIME NULL,
  ADD COLUMN minutos_jornada     INT(11) NULL COMMENT 'Capacidad diaria; si es NULL se usa el parámetro',
  ADD CONSTRAINT fk_tecnico_delegacion FOREIGN KEY (id_delegacion) REFERENCES delegacion (id_delegacion);

-- Delegación base: donde más servicios urbanos ha hecho
UPDATE tecnico t
  JOIN (SELECT x.id_tecnico, x.id_delegacion
          FROM (SELECT os.id_tecnico, COALESCE(p.id_delegacion, mu.id_delegacion) AS id_delegacion,
                       ROW_NUMBER() OVER (PARTITION BY os.id_tecnico ORDER BY COUNT(*) DESC) AS rn
                  FROM ordenes_servicio os
                  JOIN punto p ON p.id_punto = os.id_punto
                  LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
                 WHERE os.id_tecnico IS NOT NULL AND os.id_modalidad = 1
                 GROUP BY os.id_tecnico, COALESCE(p.id_delegacion, mu.id_delegacion)) x
         WHERE x.rn = 1 AND x.id_delegacion IS NOT NULL) d
    ON d.id_tecnico = t.id_tecnico
   SET t.id_delegacion = d.id_delegacion;

ALTER TABLE turno_tecnico
  ADD COLUMN hora_salida    TIME NULL,
  ADD COLUMN latitud_fin    DECIMAL(10,8) NULL,
  ADD COLUMN longitud_fin   DECIMAL(11,8) NULL,
  ADD COLUMN id_ruta        INT(11) NULL;

-- -------------------------------------------------------------------------------------
-- 5. Rutas diarias
-- -------------------------------------------------------------------------------------
CREATE TABLE ruta_diaria (
  id_ruta            INT(11) NOT NULL AUTO_INCREMENT,
  id_tecnico         INT(11) NOT NULL,
  fecha              DATE NOT NULL,
  estado             ENUM('BORRADOR','PUBLICADA','EN_CURSO','CERRADA') NOT NULL DEFAULT 'BORRADOR',
  latitud_partida    DECIMAL(10,8) NULL,
  longitud_partida   DECIMAL(11,8) NULL,
  km_estimados       DECIMAL(8,2) NULL,
  minutos_estimados  INT(11) NULL,
  id_usuario_publica INT(10) UNSIGNED NULL,
  fecha_publicacion  DATETIME NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_ruta),
  UNIQUE KEY uk_ruta_tecnico_fecha (id_tecnico, fecha),
  CONSTRAINT fk_ruta_tecnico FOREIGN KEY (id_tecnico) REFERENCES tecnico (id_tecnico),
  CONSTRAINT fk_ruta_usuario FOREIGN KEY (id_usuario_publica) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Ruta de un motorizado en una fecha';

CREATE TABLE ruta_parada (
  id_parada         INT(11) NOT NULL AUTO_INCREMENT,
  id_ruta           INT(11) NOT NULL,
  id_orden_servicio INT(11) NOT NULL,
  secuencia         INT(11) NOT NULL,
  km_tramo          DECIMAL(8,2) NULL,
  minutos_tramo     INT(11) NULL,
  hora_estimada     TIME NULL,
  fijada            TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Fijada a mano: no se reasigna al recalcular',
  estado            ENUM('PENDIENTE','HECHA','NO_INGRESO','NO_ALCANZADA','RETIRADA') NOT NULL DEFAULT 'PENDIENTE',
  PRIMARY KEY (id_parada),
  UNIQUE KEY uk_parada_ruta_orden (id_ruta, id_orden_servicio),
  KEY idx_parada_orden (id_orden_servicio),
  CONSTRAINT fk_parada_ruta  FOREIGN KEY (id_ruta) REFERENCES ruta_diaria (id_ruta) ON DELETE CASCADE,
  CONSTRAINT fk_parada_orden FOREIGN KEY (id_orden_servicio) REFERENCES ordenes_servicio (id_ordenes_servicio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Cada servicio dentro de una ruta';

ALTER TABLE turno_tecnico
  ADD CONSTRAINT fk_turno_ruta FOREIGN KEY (id_ruta) REFERENCES ruta_diaria (id_ruta);

-- -------------------------------------------------------------------------------------
-- 6. Órdenes: origen, prioridad, arrastre y flujo guiado
-- -------------------------------------------------------------------------------------
ALTER TABLE ordenes_servicio
  ADD COLUMN id_solicitud         INT(11) NULL,
  ADD COLUMN origen               ENUM('HISTORICO','SOLICITUD_PROSEGUR','PREVENTIVO','PREVENTIVO_VIP','MANUAL')
                                  NOT NULL DEFAULT 'HISTORICO',
  ADD COLUMN prioridad            TINYINT(4) NOT NULL DEFAULT 4
                                  COMMENT '1 solicitud Prosegur, 2 arrastrado, 3 VIP vencida, 4 normal vencida',
  ADD COLUMN veces_reprogramada   INT(11) NOT NULL DEFAULT 0,
  ADD COLUMN etapa_actual         VARCHAR(30) NULL COMMENT 'Último paso registrado en campo',
  ADD COLUMN hora_inicio_trayecto DATETIME NULL,
  ADD COLUMN hora_llegada         DATETIME NULL,
  ADD COLUMN hora_ingreso         DATETIME NULL,
  ADD COLUMN minutos_espera       INT(11) NULL,
  ADD COLUMN minutos_servicio     INT(11) NULL COMMENT 'Reemplaza a tiempo_servicio (texto)',
  ADD COLUMN updated_at           DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  ADD KEY idx_orden_estado_fecha (estado, fecha_visita),
  ADD KEY idx_orden_tecnico_fecha (id_tecnico, fecha_visita),
  ADD CONSTRAINT fk_orden_estado_orden FOREIGN KEY (estado) REFERENCES estado_orden (id_estado_orden),
  ADD CONSTRAINT fk_orden_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitud_servicio (id_solicitud) ON DELETE SET NULL;

-- Las órdenes que hoy están Programadas sin ruta pasan a la bolsa; si su fecha ya pasó, son arrastradas
UPDATE ordenes_servicio
   SET origen = 'PREVENTIVO',
       prioridad = IF(fecha_visita < CURDATE(), 2, 4),
       veces_reprogramada = IF(fecha_visita < CURDATE(), 1, 0),
       estado = 3
 WHERE estado = 2;

CREATE TABLE orden_servicio_evento (
  id_evento         INT(11) NOT NULL AUTO_INCREMENT,
  id_orden_servicio INT(11) NOT NULL,
  etapa             VARCHAR(30) NOT NULL,
  fecha_hora        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Hora del servidor',
  latitud           DECIMAL(10,8) NULL,
  longitud          DECIMAL(11,8) NULL,
  id_usuario        INT(10) UNSIGNED NOT NULL,
  datos             LONGTEXT NULL COMMENT 'Lo que se registró en el paso, en JSON',
  PRIMARY KEY (id_evento),
  KEY idx_evento_orden (id_orden_servicio, fecha_hora),
  CONSTRAINT fk_evento_orden   FOREIGN KEY (id_orden_servicio) REFERENCES ordenes_servicio (id_ordenes_servicio) ON DELETE CASCADE,
  CONSTRAINT fk_evento_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Bitácora de pasos en campo; impide saltarse pasos';

CREATE TABLE orden_servicio_historial (
  id_historial      INT(11) NOT NULL AUTO_INCREMENT,
  id_orden_servicio INT(11) NOT NULL,
  campo             VARCHAR(40) NOT NULL,
  valor_anterior    VARCHAR(255) NULL,
  valor_nuevo       VARCHAR(255) NULL,
  motivo            VARCHAR(255) NULL,
  id_usuario        INT(10) UNSIGNED NULL COMMENT 'NULL = cambio automático del sistema',
  fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historial),
  KEY idx_historial_orden (id_orden_servicio, fecha),
  CONSTRAINT fk_historial_orden   FOREIGN KEY (id_orden_servicio) REFERENCES ordenes_servicio (id_ordenes_servicio) ON DELETE CASCADE,
  CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Quién cambió qué en un servicio y por qué';

ALTER TABLE ordenes_servicio_complemento
  ADD COLUMN seriales_coinciden        TINYINT(1) NULL COMMENT 'NULL: no había seriales registrados',
  ADD COLUMN detalle_diferencia_serial VARCHAR(255) NULL;

ALTER TABLE evidencia_servicio
  MODIFY COLUMN tipo_evidencia ENUM('antes','componentes','despues','remision','firma','llegada','seriales',
                                    'estado_inicial','trabajo','repuestos','estado_final','novedad','no_ingreso') NOT NULL,
  MODIFY COLUMN ruta_archivo VARCHAR(255) NOT NULL,
  ADD COLUMN id_evento INT(11) NULL,
  ADD COLUMN latitud   DECIMAL(10,8) NULL,
  ADD COLUMN longitud  DECIMAL(11,8) NULL,
  ADD CONSTRAINT fk_evidencia_evento FOREIGN KEY (id_evento) REFERENCES orden_servicio_evento (id_evento) ON DELETE SET NULL;

-- -------------------------------------------------------------------------------------
-- 7. Inventario con kardex
-- -------------------------------------------------------------------------------------
CREATE TABLE movimiento_inventario (
  id_movimiento     INT(11) NOT NULL AUTO_INCREMENT,
  id_tecnico        INT(11) NOT NULL,
  id_repuesto       INT(11) NOT NULL,
  tipo              ENUM('ASIGNACION','CONSUMO','DEVOLUCION','AJUSTE') NOT NULL,
  cantidad          INT(11) NOT NULL COMMENT 'Positiva entra al stock, negativa sale',
  id_orden_servicio INT(11) NULL,
  id_usuario        INT(10) UNSIGNED NULL,
  observacion       VARCHAR(255) NULL,
  fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_movimiento),
  KEY idx_mov_tecnico (id_tecnico, id_repuesto),
  CONSTRAINT fk_mov_tecnico  FOREIGN KEY (id_tecnico) REFERENCES tecnico (id_tecnico),
  CONSTRAINT fk_mov_repuesto FOREIGN KEY (id_repuesto) REFERENCES repuesto (id_repuesto),
  CONSTRAINT fk_mov_orden    FOREIGN KEY (id_orden_servicio) REFERENCES ordenes_servicio (id_ordenes_servicio) ON DELETE SET NULL,
  CONSTRAINT fk_mov_usuario  FOREIGN KEY (id_usuario) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Kardex del stock de cada motorizado';

ALTER TABLE orden_servicio_repuesto
  ADD COLUMN id_movimiento INT(11) NULL,
  ADD CONSTRAINT fk_ordrep_movimiento FOREIGN KEY (id_movimiento) REFERENCES movimiento_inventario (id_movimiento) ON DELETE SET NULL;

-- -------------------------------------------------------------------------------------
-- 8. Permisos por funcionalidad (Spring Security) y salida de Prosegur
-- -------------------------------------------------------------------------------------
CREATE TABLE permiso (
  codigo      VARCHAR(40) NOT NULL,
  descripcion VARCHAR(150) NOT NULL,
  PRIMARY KEY (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE rol_permiso (
  id_rol         INT(11) NOT NULL,
  codigo_permiso VARCHAR(40) NOT NULL,
  PRIMARY KEY (id_rol, codigo_permiso),
  CONSTRAINT fk_rolperm_rol     FOREIGN KEY (id_rol) REFERENCES tipousuario (idTipoUsuario),
  CONSTRAINT fk_rolperm_permiso FOREIGN KEY (codigo_permiso) REFERENCES permiso (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci
  COMMENT='Reemplaza a rol_permisos/rutas en la plataforma nueva';

INSERT INTO permiso (codigo, descripcion) VALUES
  ('PLANEACION',    'Cargar el Excel de Prosegur, geolocalizar máquinas, armar y publicar rutas'),
  ('SEGUIMIENTO',   'Seguir las rutas del día en vivo y ver alertas'),
  ('CONFIGURACION', 'Máquinas VIP, frecuencias, técnicos y parámetros'),
  ('CAMPO',         'Ruta del día y flujo guiado del motorizado');

INSERT INTO rol_permiso (id_rol, codigo_permiso) VALUES
  (1, 'PLANEACION'), (1, 'SEGUIMIENTO'), (1, 'CONFIGURACION'),
  (2, 'PLANEACION'),
  (3, 'CAMPO'),
  (5, 'PLANEACION'), (5, 'SEGUIMIENTO');

ALTER TABLE tipousuario ADD COLUMN estado TINYINT(1) NOT NULL DEFAULT 1;
UPDATE tipousuario SET estado = 0 WHERE idTipoUsuario = 4;          -- Prosegur deja de ser rol
UPDATE usuarios SET estado = 'inactivo' WHERE nivel_acceso = 4;     -- no se borra: conserva su historial

-- -------------------------------------------------------------------------------------
-- 9. Parámetros de la nueva operación
-- -------------------------------------------------------------------------------------
INSERT IGNORE INTO parametros (clave, valor, descripcion) VALUES
  ('tiempo_max_espera_ingreso_min', '45',  'Minutos máximos esperando ingreso antes de pasar al siguiente punto'),
  ('jornada_minutos_default',       '480', 'Minutos de jornada de un motorizado si no tiene uno propio'),
  ('hora_inicio_jornada_default',   '07:00', 'Hora de inicio de jornada si el motorizado no tiene una propia'),
  ('velocidad_promedio_kmh',        '25',  'Velocidad promedio en moto para estimar tramos'),
  ('factor_ruta_vial',              '1.35', 'Multiplica la distancia en línea recta para estimar la distancia vial'),
  ('frecuencia_mantenimiento_default_dias', '30', 'Frecuencia si ni la máquina ni su tipo la tienen'),
  ('tipo_mantenimiento_solicitud_default', '3', 'Tipo de mantenimiento si el Excel no lo indica (3 = Correctivo)'),
  ('tipo_mantenimiento_preventivo', '1',   'Tipo de mantenimiento para las máquinas vencidas (1 = Preventivo básico)'),
  ('seriales_distintos_bloquean',   '0',   '1 = no deja seguir si los seriales no coinciden; 0 = sigue con alerta'),
  ('excel_prosegur_columnas',       '{"device_id":["device id","device_id","id maquina","id máquina","serial","equipo","terminal","maquina","máquina"],"referencia":["ticket","caso","orden","ot","referencia","incidente","no. caso"],"tipo":["tipo servicio","tipo de servicio","servicio","tipo","mantenimiento","actividad"],"descripcion":["descripcion","descripción","falla","observacion","observación","detalle"]}',
                                           'Encabezados que se reconocen en el Excel de Prosegur');

-- -------------------------------------------------------------------------------------
-- 10. Vista de mantenimientos: vencimiento, atraso y prioridad de cada máquina
-- -------------------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_mantenimiento_pendiente AS
SELECT m.id_maquina,
       m.device_id,
       m.id_punto,
       p.id_cliente,
       p.id_municipio,
       COALESCE(p.id_delegacion, mu.id_delegacion) AS id_delegacion,
       m.id_tipo_maquina,
       m.es_vip,
       m.activo_operativo,
       m.latitud,
       m.longitud,
       m.ultima_visita,
       COALESCE(m.frecuencia_dias, tm.frecuencia_base_dias,
                (SELECT CAST(valor AS UNSIGNED) FROM parametros WHERE clave = 'frecuencia_mantenimiento_default_dias'),
                30) AS frecuencia_dias,
       DATE_ADD(m.ultima_visita, INTERVAL COALESCE(m.frecuencia_dias, tm.frecuencia_base_dias,
                (SELECT CAST(valor AS UNSIGNED) FROM parametros WHERE clave = 'frecuencia_mantenimiento_default_dias'),
                30) DAY) AS fecha_vencimiento,
       IF(m.es_vip = 1, 3, 4) AS prioridad
  FROM maquina m
  JOIN punto p          ON p.id_punto = m.id_punto
  JOIN tipo_maquina tm  ON tm.id_tipo_maquina = m.id_tipo_maquina
  LEFT JOIN municipio mu ON mu.id_municipio = p.id_municipio
 WHERE m.estado = 1 AND p.estado = 1;
