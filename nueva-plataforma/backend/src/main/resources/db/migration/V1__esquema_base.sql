-- V1: esquema base de INEES Mantenimientos (volcado del 06-11-2026, MariaDB 10.4).
-- En una base existente con datos, Flyway la marca como línea base y NO la ejecuta.
-- En una base vacía crea las 42 tablas y carga los catálogos.

SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificacion_servicio` (
  `id_calificacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificar de la calificación de servicio',
  `nombre_calificacion` varchar(20) NOT NULL COMMENT 'Nombre de la calificación de Servicio',
  PRIMARY KEY (`id_calificacion`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id del cliente',
  `codigo_cliente` varchar(15) DEFAULT NULL COMMENT 'código de identificación del cliente que proviene de la base de datos de Prosegur',
  `nombre_cliente` varchar(150) NOT NULL COMMENT 'Nombre del cliente que proviene de la base de datos de Prosegur',
  `estado` tinyint(4) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `codigo_cliente` (`codigo_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=4579 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_devolucion_repuestos` (
  `id_devolucion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Autoincrementable',
  `id_tecnico` int(11) NOT NULL COMMENT 'FK de la tabla tecnico',
  `id_orden_servicio` int(11) NOT NULL COMMENT 'FK de la orden donde se usó',
  `id_repuesto` int(11) NOT NULL COMMENT 'FK del repuesto cambiado',
  `cantidad` int(11) NOT NULL DEFAULT 1 COMMENT 'Cantidad a devolver',
  `estado_devolucion` enum('Pendiente','Devuelto') DEFAULT 'Pendiente' COMMENT 'Estado actual de la pieza',
  `fecha_devolucion` datetime DEFAULT NULL COMMENT 'Cuándo el técnico lo entregó en central',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_devolucion`),
  KEY `fk_dev_tecnico` (`id_tecnico`),
  KEY `fk_dev_orden` (`id_orden_servicio`),
  KEY `fk_dev_repuesto` (`id_repuesto`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_remisiones` (
  `id_control` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id de cada número de remisión',
  `numero_remision` varchar(15) NOT NULL COMMENT 'Es el número de remisión individual de cada paquete que contiene el talonario de cada técnico',
  `id_tecnico` int(11) NOT NULL COMMENT 'Es la llave foranea que trae el id del técnico de la tabla tecnico',
  `id_estado` int(11) NOT NULL DEFAULT 1 COMMENT 'Es la llave foranea que trae el id de la tabla estados_remision para complementar la información',
  `id_orden_servicio` int(11) DEFAULT NULL COMMENT 'Es la llave foranea que llama al id de la tabla ordenes_servicio con la cual se tiene el control de las remisiones con su respectiva orden de servicio',
  `id_instalacion` int(11) DEFAULT NULL COMMENT 'Llave foránea hacia instalaciones_desinstalaciones',
  `fecha_uso` datetime DEFAULT NULL COMMENT 'Es la fecha en la cual se uso la remisión en para cada servicio',
  `fecha_asignacion` datetime DEFAULT current_timestamp() COMMENT 'Es la fecha que se crea automáticamente a la hora de crear cada remisión o conjunto de remisiones',
  PRIMARY KEY (`id_control`),
  UNIQUE KEY `idx_tecnico_remision` (`id_tecnico`,`numero_remision`),
  KEY `id_tecnico` (`id_tecnico`,`numero_remision`),
  KEY `fk_control_orden` (`id_orden_servicio`),
  KEY `fk_control_estado_nuevo` (`id_estado`),
  KEY `fk_control_instalacion` (`id_instalacion`),
  CONSTRAINT `fk_control_estado_nuevo` FOREIGN KEY (`id_estado`) REFERENCES `estados_remision` (`id_estado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_control_instalacion` FOREIGN KEY (`id_instalacion`) REFERENCES `instalaciones_desinstalaciones` (`id_instalacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_control_orden` FOREIGN KEY (`id_orden_servicio`) REFERENCES `ordenes_servicio` (`id_ordenes_servicio`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_control_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48704 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `costos_operativos` (
  `id_costo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Este es el id de la tabla costos_operativos',
  `id_tecnico` int(11) DEFAULT NULL COMMENT 'Está es la llave foranea de la tabla tecnico',
  `id_usuario` int(10) unsigned DEFAULT NULL COMMENT 'Está es la llave foranea de la tabla usuarios',
  `mes_reporte` date NOT NULL COMMENT 'Es el mes en que se está consultando el reporte',
  `salario` decimal(12,2) DEFAULT 0.00 COMMENT 'Es el campo en donde se guarda el salario de cada usuario o técnico',
  `horas_extra` decimal(12,2) DEFAULT 0.00 COMMENT 'Es el campo donde se guarda el valor de las horas extra de ese usuario o técnico',
  `bono_meta` decimal(12,2) DEFAULT 0.00 COMMENT 'Es el campo en donde se pone el precio del bono extra si consigue llegar a la meta de servicios',
  `gasolina` decimal(12,2) DEFAULT 0.00 COMMENT 'Es el campo en el cual se almacena  lo que se les paga a los tecnicos de gasolina',
  `auxilio_comunicacion` decimal(12,2) DEFAULT 0.00 COMMENT 'Es el campo en donde se les paga el auxilio de comunicación a quienes lo obtienen',
  `auxilio_rodamiento` decimal(12,2) DEFAULT 0.00 COMMENT 'Es el campo en donde se almacena el dinero del auxiliio de rodamiento de los técnicos',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  `fecha_registro` datetime DEFAULT current_timestamp() COMMENT 'Es la fecha en la que se creo el registro en la base de datos',
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Es la fecha en la que se actualizo la información del campo en la base de datos',
  PRIMARY KEY (`id_costo`) USING BTREE,
  UNIQUE KEY `idx_unico_tecnico_mes` (`id_tecnico`,`mes_reporte`) USING BTREE,
  UNIQUE KEY `idx_unico_usuario_mes` (`id_usuario`,`mes_reporte`) USING BTREE,
  CONSTRAINT `fk_costos_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_costos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `delegacion` (
  `id_delegacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador Autoincrementable de la tabla delegacion',
  `nombre_delegacion` varchar(35) NOT NULL COMMENT 'Nombre de la delegación que provienen de la base de datos que nos envía Prosegur',
  `estado` tinyint(4) DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  PRIMARY KEY (`id_delegacion`),
  UNIQUE KEY `nombre_delegacion` (`nombre_delegacion`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dias_festivos` (
  `id_festivo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla dias_festivos',
  `fecha` date NOT NULL COMMENT 'es la fecha en la cual se lleva a cabo ese día festivo',
  `descripcion` varchar(60) DEFAULT NULL COMMENT 'es la justificación de que sucede ese día festivo',
  PRIMARY KEY (`id_festivo`),
  UNIQUE KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_maquina` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador Autoincrementable de la tabla estado_maquina',
  `nombre_estado` varchar(30) NOT NULL COMMENT 'Nombre del estado de máquina almacenado en la base de datos',
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estados_aprobacion_he` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado` varchar(30) NOT NULL COMMENT 'Ej: Pendiente, Aprobada, Rechazada',
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estados_remision` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla estados_remision',
  `nombre_estado` varchar(30) NOT NULL COMMENT 'Nombre del estado de remisión almacenado en la base de datos',
  `activo` tinyint(1) DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `evidencia_horas_extra` (
  `id_evidencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_registro_he` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_evidencia`),
  KEY `fk_ehe_registro` (`id_registro_he`),
  CONSTRAINT `fk_ehe_registro` FOREIGN KEY (`id_registro_he`) REFERENCES `registro_horas_extra` (`id_registro_he`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=629 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `evidencia_servicio` (
  `id_evidencia` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla eviencia_servicio',
  `id_orden_servicio` int(11) NOT NULL COMMENT 'Id de la orden del servicio a la cual se le están tomando las imágenes',
  `tipo_evidencia` enum('antes','componentes','despues','remision','firma') NOT NULL COMMENT 'Tipo de imágen que se está almacenando ',
  `ruta_archivo` varchar(120) NOT NULL COMMENT 'Ruta en la cual de almacenó la imágen',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en la cual se subio la imágen al sistema',
  PRIMARY KEY (`id_evidencia`),
  KEY `fk_evidencia_orden` (`id_orden_servicio`),
  CONSTRAINT `fk_evidencia_orden` FOREIGN KEY (`id_orden_servicio`) REFERENCES `ordenes_servicio` (`id_ordenes_servicio`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas_parqueadero` (
  `id_factura_parqueadero` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la factura',
  `id_tecnico` int(11) NOT NULL COMMENT 'Fk del técnico (tomado de la sesión)',
  `id_punto` int(11) NOT NULL COMMENT 'Fk del punto asociado al parqueadero',
  `fecha_servicio` date NOT NULL COMMENT 'Fecha del servicio en formato YYYY-MM-DD',
  `hora_inicio` time NOT NULL COMMENT 'Hora de inicio reportada por el técnico',
  `hora_fin` time NOT NULL COMMENT 'Hora de salida del parqueadero',
  `numero_factura` varchar(50) NOT NULL COMMENT 'Número impreso en la factura física',
  `valor_factura` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo total del parqueadero',
  `ruta_foto` varchar(300) NOT NULL COMMENT 'Ruta en el servidor donde se almacenó la imagen',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Activo, 0 = Eliminado (Borrado lógico)',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Cuándo se subió el registro al sistema',
  PRIMARY KEY (`id_factura_parqueadero`) USING BTREE,
  KEY `fk_parqueadero_tecnico` (`id_tecnico`) USING BTREE,
  KEY `fk_parqueadero_punto` (`id_punto`) USING BTREE,
  CONSTRAINT `fk_parqueadero_punto` FOREIGN KEY (`id_punto`) REFERENCES `punto` (`id_punto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_parqueadero_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gastos_administrativos` (
  `id_gasto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla gastos_administrativos',
  `mes_reporte` date NOT NULL COMMENT 'Se Guarda el Primer día del mes (YYYY-MM-01)',
  `concepto` varchar(200) NOT NULL COMMENT 'Concepto de los gastos administrativos Ej: Licencia Software, Arriendo Oficina',
  `categoria` varchar(50) DEFAULT 'Administrativo' COMMENT 'Categoría de los gastoa administrativos Ej: Tecnológico, Fijo, Variable',
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor de los gastos administrativos',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  `observacion` text DEFAULT NULL COMMENT 'Cualquier novedad que se tengan con los costos o cualquier ampliación del concepto',
  `fecha_registro` datetime DEFAULT current_timestamp() COMMENT 'Fecha en la que se creo el registro',
  PRIMARY KEY (`id_gasto`) USING BTREE,
  KEY `idx_mes_gasto` (`mes_reporte`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `instalaciones_desinstalaciones` (
  `id_instalacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_control_remision` int(11) DEFAULT NULL,
  `fecha_instalacion` date DEFAULT NULL,
  `categoria_servicio` enum('Prosegur_Cobro','Prosegur_NoCobro','Inees') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_servicio_nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notas` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion_inees` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lugar_recogida` enum('Innes','Prosegur') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_recogida` date DEFAULT NULL,
  `es_maquina` tinyint(1) DEFAULT 1 COMMENT '1: Máquina, 0: Otros',
  `serial_maquina` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `producto_otro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_cliente_origen` int(11) DEFAULT NULL,
  `cliente_origen_texto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_punto_origen` int(11) DEFAULT NULL,
  `punto_origen_texto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_cliente_destino` int(11) DEFAULT NULL,
  `cliente_destino_texto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_punto_destino` int(11) DEFAULT NULL,
  `punto_destino_texto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_tipo_maquina` int(11) NOT NULL,
  `id_tecnico` int(11) NOT NULL,
  `valor_servicio` decimal(12,2) DEFAULT 0.00,
  `foto_remision` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_maquina` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_chazos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_instalacion`),
  KEY `fk_inst_remision` (`id_control_remision`),
  KEY `fk_inst_tipo_maq` (`id_tipo_maquina`),
  KEY `fk_inst_tecnico` (`id_tecnico`),
  CONSTRAINT `fk_inst_remision` FOREIGN KEY (`id_control_remision`) REFERENCES `control_remisiones` (`id_control`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inst_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON UPDATE CASCADE,
  CONSTRAINT `fk_inst_tipo_maq` FOREIGN KEY (`id_tipo_maquina`) REFERENCES `tipo_maquina` (`id_tipo_maquina`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_tecnico` (
  `id_inventario` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla inventario_tecnico',
  `id_tecnico` int(11) NOT NULL COMMENT 'id del técnico que tiene asignado el repuesto',
  `id_repuesto` int(11) NOT NULL COMMENT 'id del repuesto que tiene asignado el técnico',
  `cantidad_actual` int(11) NOT NULL DEFAULT 0 COMMENT 'número de repuestos que posee ese técnico',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  `ultima_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'fecha de la última actualización del inventario',
  PRIMARY KEY (`id_inventario`),
  UNIQUE KEY `idx_tecnico_repuesto` (`id_tecnico`,`id_repuesto`),
  KEY `FK_inv_repuesto` (`id_repuesto`),
  CONSTRAINT `FK_inv_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuesto` (`id_repuesto`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_inv_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1834 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla login',
  `usuario_id` int(10) unsigned NOT NULL COMMENT 'Id del usuario que ingreso al sistema',
  `fecha` date NOT NULL COMMENT 'fecha en la que se hace el login',
  `hora` time NOT NULL COMMENT 'hora en la que el usuario accedio al sistema',
  `usuario` varchar(50) NOT NULL COMMENT 'nombre del usuario que ingreso al sistema',
  `ip` varchar(45) NOT NULL COMMENT 'ip del dispositivo en el que ingreso al sistema',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `fk_login_usuario` (`usuario_id`) USING BTREE,
  CONSTRAINT `fk_login_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6258 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maquina` (
  `id_maquina` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla maquina',
  `device_id` varchar(30) NOT NULL COMMENT 'Identificador de la máquina que viene de la base de datos de Prosegur',
  `id_punto` int(11) NOT NULL COMMENT 'Id que viene de la tabla punto en la cual está la máquina',
  `id_tipo_maquina` int(11) NOT NULL COMMENT 'Id que viene de la tabla tipo_maquina  para saber de que tipo de máquina se está consultando',
  `ultima_visita` date DEFAULT NULL COMMENT 'Fecha de la última visita, que se ha realizado',
  `estado` tinyint(4) DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  `activo_operativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Operativo, 0 = Fuera de Servicio',
  `fecha_actualizacion` datetime DEFAULT NULL COMMENT 'Fecha en la cual se actualizo la máquina o se actualizo desde la base de datos de Prosegur',
  PRIMARY KEY (`id_maquina`),
  UNIQUE KEY `device_id` (`device_id`),
  KEY `id_punto` (`id_punto`),
  KEY `id_tipo_maquina` (`id_tipo_maquina`),
  CONSTRAINT `maquina_ibfk_1` FOREIGN KEY (`id_punto`) REFERENCES `punto` (`id_punto`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `maquina_ibfk_2` FOREIGN KEY (`id_tipo_maquina`) REFERENCES `tipo_maquina` (`id_tipo_maquina`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5005 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modalidad_operativa` (
  `id_modalidad` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla modalidad_operativa',
  `nombre_modalidad` varchar(30) NOT NULL COMMENT 'Nombre de la Modalidad Operativa',
  PRIMARY KEY (`id_modalidad`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `monitoreo_motorizados_live` (
  `id_monitoreo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID único del registro de monitoreo',
  `id_tecnico` int(11) NOT NULL COMMENT 'FK del técnico motorizado',
  `id_cliente` int(11) NOT NULL COMMENT 'FK del cliente',
  `id_punto` int(11) NOT NULL COMMENT 'FK del punto visitado',
  `id_tipo_mantenimiento` int(11) NOT NULL COMMENT 'FK del tipo de servicio realizado',
  `estado_actual` enum('En Progreso','Finalizado') NOT NULL DEFAULT 'En Progreso' COMMENT 'Estado del cronómetro en vivo',
  `fecha_servicio` date NOT NULL COMMENT 'Fecha en la que se realiza el servicio',
  `hora_inicio` datetime NOT NULL COMMENT 'Momento exacto en que el técnico inició el cronómetro',
  `hora_fin` datetime DEFAULT NULL COMMENT 'Momento exacto en que finalizó el servicio',
  `duracion_real_minutos` int(11) DEFAULT NULL COMMENT 'Minutos reales calculados al finalizar',
  `servicio_modificado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 si el técnico cambió el tipo de servicio (1/1), 0 si no',
  `justificacion_retraso` text DEFAULT NULL COMMENT 'Justificación obligatoria en caso de superar el tiempo meta',
  `alerta_telegram_enviada` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_monitoreo`),
  KEY `idx_live_tecnico` (`id_tecnico`),
  KEY `idx_live_fecha` (`fecha_servicio`),
  KEY `idx_live_estado` (`estado_actual`),
  KEY `fk_live_cliente` (`id_cliente`),
  KEY `fk_live_punto` (`id_punto`),
  KEY `fk_live_tipo_mant` (`id_tipo_mantenimiento`),
  CONSTRAINT `fk_live_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_live_punto` FOREIGN KEY (`id_punto`) REFERENCES `punto` (`id_punto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_live_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_live_tipo_mant` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipo_mantenimiento` (`id_tipo_mantenimiento`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `municipio` (
  `id_municipio` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla municipio',
  `nombre_municipio` varchar(40) NOT NULL COMMENT 'Nombre del municipio',
  `id_delegacion` int(11) DEFAULT NULL COMMENT 'Id de la delegación a la cual esta asignado el municipio',
  `estado` tinyint(4) DEFAULT 1 COMMENT '1 = activo, 0 = eliminado (borrado lógico)',
  PRIMARY KEY (`id_municipio`),
  KEY `id_delegacion` (`id_delegacion`),
  CONSTRAINT `municipio_ibfk_1` FOREIGN KEY (`id_delegacion`) REFERENCES `delegacion` (`id_delegacion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orden_servicio_novedad` (
  `id_orden_servicio` int(11) NOT NULL COMMENT 'Id de la orden del servicio',
  `id_tipo_novedad` int(11) NOT NULL COMMENT 'Id del tipo de novedad ',
  PRIMARY KEY (`id_orden_servicio`,`id_tipo_novedad`),
  KEY `fk_osn_novedad` (`id_tipo_novedad`),
  CONSTRAINT `fk_osn_novedad` FOREIGN KEY (`id_tipo_novedad`) REFERENCES `tipo_novedad` (`id_tipo_novedad`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_osn_orden` FOREIGN KEY (`id_orden_servicio`) REFERENCES `ordenes_servicio` (`id_ordenes_servicio`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orden_servicio_repuesto` (
  `id_orden_repuesto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla orden_servicio_repuesto',
  `id_orden_servicio` int(11) NOT NULL COMMENT 'id de la orden de servicio en la que se utilizó el servicio',
  `id_repuesto` int(11) NOT NULL COMMENT 'id que viene de la tabla repuesto',
  `origen` enum('INEES','PROSEGUR') NOT NULL DEFAULT 'INEES' COMMENT 'Origen de donde proviene el repuesto si es INEES o PROSEGUR',
  `cantidad` int(11) DEFAULT 1 COMMENT 'Cantidad de ese tipo de repuesto que se uso en el servicio',
  `valor_unitario` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor de los repuestos que se usaron en el servicio',
  PRIMARY KEY (`id_orden_repuesto`),
  KEY `fk_osr_orden` (`id_orden_servicio`),
  KEY `fk_osr_repuesto` (`id_repuesto`),
  CONSTRAINT `fk_osr_orden` FOREIGN KEY (`id_orden_servicio`) REFERENCES `ordenes_servicio` (`id_ordenes_servicio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_osr_repuesto` FOREIGN KEY (`id_repuesto`) REFERENCES `repuesto` (`id_repuesto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3346 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_servicio` (
  `id_ordenes_servicio` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto Incrementable de la tabla ordenes_servicio',
  `numero_remision` varchar(15) DEFAULT NULL COMMENT 'texto del número de remisión que se uso en ese servicio',
  `id_cliente` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla cliente',
  `id_punto` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla punto',
  `id_modalidad` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla modalidad_operativa',
  `id_maquina` int(11) NOT NULL COMMENT 'id que viene de la tabla maquina',
  `id_tecnico` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla tecnico',
  `id_tipo_mantenimiento` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla tipo_mantenimiento',
  `valor_servicio` decimal(12,2) DEFAULT 0.00 COMMENT 'valor en texto del servicio que se realizo',
  `es_fuera_delegacion` tinyint(1) DEFAULT 0 COMMENT 'identifica si el servicio es fuera de la delegación o no',
  `dias_viaticos` int(11) DEFAULT 0 COMMENT 'identifica los días que pasaron para obtener los viaticos',
  `valor_viaticos` decimal(12,2) DEFAULT 0.00 COMMENT 'valor de los viaticos',
  `id_estado_maquina` int(11) DEFAULT NULL COMMENT 'id que viene del estado_maquina',
  `id_calificacion` int(11) DEFAULT NULL COMMENT 'id que viene de la calificacion_servicio',
  `fecha_visita` date NOT NULL COMMENT 'fecha en la que se realizo el servicio',
  `hora_entrada` time DEFAULT NULL COMMENT 'hora de entrada al servicio',
  `hora_salida` time DEFAULT NULL COMMENT 'hora de salida del servicio',
  `tiempo_servicio` varchar(10) DEFAULT '00:00' COMMENT 'tiempo que demoro el servicio',
  `actividades_realizadas` text DEFAULT NULL COMMENT 'actividad realizada en el servicio o observación',
  `soporte_remoto` varchar(40) DEFAULT NULL COMMENT 'nombre de la persona que hizo el soporte remoto',
  `tiene_novedad` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'identifica si el servicio tiene novedad o no',
  `detalle_novedad` text DEFAULT NULL COMMENT 'detalle de la novedad que se identifico',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'fecha en la que se subio el servicio (se crea automáticamente)',
  `estado` tinyint(4) DEFAULT 1 COMMENT '1 = activo, 2 = programado, 0 = eliminado (borrado lógico)',
  `repuestos_tecnico` text DEFAULT NULL COMMENT 'Almacena un JSON temporal con los repuestos sugeridos por el técnico antes de la validación',
  PRIMARY KEY (`id_ordenes_servicio`) USING BTREE,
  UNIQUE KEY `idx_orden_tecnico_remision` (`id_tecnico`,`numero_remision`),
  KEY `id_maquina` (`id_maquina`),
  KEY `id_tecnico` (`id_tecnico`),
  KEY `id_tipo_mantenimiento` (`id_tipo_mantenimiento`),
  KEY `fk_orden_estado` (`id_estado_maquina`),
  KEY `fk_orden_calif` (`id_calificacion`),
  KEY `fk_orden_cliente` (`id_cliente`),
  KEY `fk_orden_punto` (`id_punto`),
  KEY `fk_orden_modalidad` (`id_modalidad`),
  CONSTRAINT `fk_orden_calif` FOREIGN KEY (`id_calificacion`) REFERENCES `calificacion_servicio` (`id_calificacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_orden_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_orden_estado` FOREIGN KEY (`id_estado_maquina`) REFERENCES `estado_maquina` (`id_estado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_orden_modalidad` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidad_operativa` (`id_modalidad`),
  CONSTRAINT `fk_orden_punto` FOREIGN KEY (`id_punto`) REFERENCES `punto` (`id_punto`) ON UPDATE CASCADE,
  CONSTRAINT `ordenes_servicio_ibfk_1` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ordenes_servicio_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ordenes_servicio_ibfk_3` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipo_mantenimiento` (`id_tipo_mantenimiento`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13594 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_servicio_complemento` (
  `id_orden_servicio` int(11) NOT NULL COMMENT 'AutoIncrementable de la tabla ordenes_servicio_complemeto',
  `numero_maquina` varchar(30) DEFAULT NULL COMMENT 'número de máquina  que ponen los técnicos, se encuentra en el punto',
  `serial_maquina` varchar(30) DEFAULT NULL COMMENT 'serial que ponen los técnicos, se encuentra en el punto',
  `serial_router` varchar(30) DEFAULT NULL COMMENT 'serial del router que ponen los técnicos, se encuentra en el punto',
  `serial_ups` varchar(30) DEFAULT NULL COMMENT 'serial de la ups que diligencian los técnicos, se encuentra en el punto',
  `pendientes` text DEFAULT NULL COMMENT 'pendientes que tiene el punto o el servicio',
  `administrador_punto` varchar(40) DEFAULT NULL COMMENT 'persona que se encontraba encargada en el punto',
  `celular_encargado` varchar(12) DEFAULT NULL COMMENT 'celular de la persona encargada del punto',
  `id_estado_inicial` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla estado_maquina',
  `novedad_estado_inicial` text DEFAULT NULL,
  `latitud_inicio` decimal(10,8) DEFAULT NULL COMMENT 'latitud que se rellena cuando se empieza el servicio',
  `longitud_inicio` decimal(11,8) DEFAULT NULL COMMENT 'longitud que se rellena cuando se inicia el servicio',
  `latitud_fin` decimal(10,8) DEFAULT NULL COMMENT 'latitud que se rellena cuando se termina el servicio',
  `longitud_fin` decimal(11,8) DEFAULT NULL COMMENT 'longitud que se rellena cuando se finaliza el servicio',
  `estado` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Activo, 0: Borrado lógico',
  PRIMARY KEY (`id_orden_servicio`) USING BTREE,
  KEY `fk_complemento_estado_inicial` (`id_estado_inicial`),
  CONSTRAINT `fk_complemento_estado_inicial` FOREIGN KEY (`id_estado_inicial`) REFERENCES `estado_maquina` (`id_estado`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_complemento_orden` FOREIGN KEY (`id_orden_servicio`) REFERENCES `ordenes_servicio` (`id_ordenes_servicio`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametros` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Autoincrementable de la tabla parametros',
  `clave` varchar(40) NOT NULL COMMENT 'nombre de el parametro que se requiere',
  `valor` text NOT NULL COMMENT 'valor que tiene el parametro',
  `descripcion` varchar(150) DEFAULT NULL COMMENT 'descripción para que sirve ese parametro',
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'autoincrementable de la tabla password_reset',
  `usuario_email` varchar(60) NOT NULL COMMENT 'email del usuario que se solicita el cambio de contraseña',
  `codigo_hash` varchar(70) NOT NULL COMMENT 'codigo "hash" con el cual se recupera la contraseña',
  `expira_en` datetime NOT NULL COMMENT 'tiempo para que expire el código hash',
  `usado` tinyint(1) DEFAULT 0 COMMENT 'estados de la recuperación de la contraseña',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`usuario_email`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `punto` (
  `id_punto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla punto',
  `nombre_punto` varchar(90) NOT NULL COMMENT 'Nombre del punto que viene de la base de datos de Prosegur',
  `direccion` text DEFAULT NULL COMMENT 'Dirección donde se encuentra el punto viene de la base de datos de Prosegur',
  `latitud` decimal(10,8) DEFAULT NULL COMMENT 'latitud del punto',
  `longitud` decimal(11,8) DEFAULT NULL COMMENT 'longitud del punto',
  `intentos_api` int(11) NOT NULL DEFAULT 0 COMMENT 'intentos de la api para encontrar el punto',
  `codigo_1` varchar(15) DEFAULT NULL COMMENT 'codigo 1 que viene de la base de datos de prosegur',
  `codigo_2` varchar(15) DEFAULT NULL COMMENT 'codigo 2 que viene de la base de datos de Prosegur',
  `id_municipio` int(11) NOT NULL COMMENT 'id que viene de la tabla municipio',
  `id_delegacion` int(11) DEFAULT NULL COMMENT 'id que viene de la tabla delegación',
  `zona` varchar(50) DEFAULT NULL COMMENT 'zona en donde se encuentra el punto, viene de la base de datos de Prosegur',
  `id_modalidad` int(11) NOT NULL DEFAULT 1 COMMENT 'id que viene de ala tabla modalidad_operativa',
  `id_cliente` int(11) NOT NULL COMMENT 'id que viene de la tabla cliente',
  `estado` tinyint(4) DEFAULT 1 COMMENT 'estado 1 = Activo 0 = Desactivado (Borrado Lógico)',
  `fecha_actualizacion` datetime DEFAULT NULL COMMENT 'fecha en la cual de actualizo la información de la base de datos de Prosegur',
  `fecha_ultima_visita` date DEFAULT NULL COMMENT 'fecha en la que se realizo el último mantenimiento al punto',
  `id_ultimo_tipo_mantenimiento` int(11) DEFAULT NULL COMMENT 'id del ultimo tipo de mantenimiento que se realizo',
  `frecuencia_mantenimiento_dias` int(11) DEFAULT NULL COMMENT 'Cada cuántos días se le hace mantenimiento (Ej: 20, 30, 60, 90)',
  PRIMARY KEY (`id_punto`),
  KEY `id_municipio` (`id_municipio`),
  KEY `punto_ibfk_2` (`id_cliente`),
  KEY `fk_punto_modalidad` (`id_modalidad`),
  KEY `fk_punto_delegacion` (`id_delegacion`),
  KEY `fk_punto_ultimo_mantenimiento` (`id_ultimo_tipo_mantenimiento`),
  CONSTRAINT `fk_punto_delegacion` FOREIGN KEY (`id_delegacion`) REFERENCES `delegacion` (`id_delegacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_punto_modalidad` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidad_operativa` (`id_modalidad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_punto_ultimo_mantenimiento` FOREIGN KEY (`id_ultimo_tipo_mantenimiento`) REFERENCES `tipo_mantenimiento` (`id_tipo_mantenimiento`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `punto_ibfk_1` FOREIGN KEY (`id_municipio`) REFERENCES `municipio` (`id_municipio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `punto_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4990 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_horas_extra` (
  `id_registro_he` int(11) NOT NULL AUTO_INCREMENT,
  `id_tecnico` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_punto` int(11) DEFAULT NULL,
  `fecha_reporte` date NOT NULL COMMENT 'Día en que se quedó hasta tarde',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `total_horas` decimal(5,2) NOT NULL COMMENT 'Calculado automáticamente por el sistema',
  `justificacion_tecnico` text NOT NULL COMMENT 'Por qué se quedó',
  `id_estado_aprobacion` int(11) NOT NULL DEFAULT 1 COMMENT '1 = Pendiente por defecto',
  `observacion_supervisor` text DEFAULT NULL COMMENT 'Justificación al aprobar o rechazar',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_registro_he`),
  KEY `fk_rhe_tecnico` (`id_tecnico`),
  KEY `fk_rhe_estado` (`id_estado_aprobacion`),
  KEY `fk_rhe_cliente` (`id_cliente`),
  KEY `fk_rhe_punto` (`id_punto`),
  CONSTRAINT `fk_rhe_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rhe_estado` FOREIGN KEY (`id_estado_aprobacion`) REFERENCES `estados_aprobacion_he` (`id_estado`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rhe_punto` FOREIGN KEY (`id_punto`) REFERENCES `punto` (`id_punto`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rhe_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=412 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repuesto` (
  `id_repuesto` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla repuesto',
  `nombre_repuesto` varchar(100) NOT NULL COMMENT 'Nombre del repuesto para los servicios',
  `codigo_referencia` varchar(20) DEFAULT NULL COMMENT 'Código de referencia que proviene de Dataico',
  `valor_venta` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor del Repuesto',
  `estado` tinyint(4) DEFAULT 1 COMMENT 'estado 1 = Activo 0 = Desactivado (Borrado Lógico)',
  `requiere_devolucion` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = El técnico debe devolver la pieza dañada/usada a la central, 0 = No requiere devolución',
  PRIMARY KEY (`id_repuesto`)
) ENGINE=InnoDB AUTO_INCREMENT=516 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol_permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla rol_permisos',
  `rol_id` int(11) NOT NULL COMMENT 'id que proviene de la tabla tipousuario',
  `ruta_id` int(11) NOT NULL COMMENT 'id que proviene de la tabla rutas',
  PRIMARY KEY (`id`),
  KEY `fk_permiso_rol` (`rol_id`),
  KEY `fk_permiso_ruta` (`ruta_id`),
  CONSTRAINT `fk_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `tipousuario` (`idTipoUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_permiso_ruta` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id_ruta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rutas` (
  `id_ruta` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla rutas',
  `nombre_ruta` varchar(50) NOT NULL COMMENT 'Nombre de la ruta que sirve también para los controladores',
  `controlador_ruta` varchar(150) NOT NULL COMMENT 'Ruta del Controlador encargado de esa ruta o ese archivo',
  `estado` enum('activo','inactivo') DEFAULT 'activo' COMMENT 'estado  = Activo o  = Inactivado (Borrado Lógico)',
  `descripcion` varchar(100) DEFAULT NULL COMMENT 'Descripción para que es ese controlador o esa ruta',
  PRIMARY KEY (`id_ruta`),
  UNIQUE KEY `nombre_ruta` (`nombre_ruta`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitud_inventario_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(11) NOT NULL,
  `id_repuesto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_detalle`),
  KEY `id_solicitud` (`id_solicitud`),
  CONSTRAINT `solicitud_inventario_detalle_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes_inventario` (`id_solicitud`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_inventario` (
  `id_solicitud` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `solicitante_nombre` varchar(150) NOT NULL,
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `estado` enum('Pendiente','En gestión','Finalizado') DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_solicitud`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifa` (
  `id_tarifa` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla tarifa',
  `id_tipo_maquina` int(11) NOT NULL COMMENT 'Id que viene de la tabla tipo_maquina',
  `id_tipo_mantenimiento` int(11) NOT NULL COMMENT 'Id que viene de la tabla tipo_mantenimiento',
  `id_modalidad` int(11) NOT NULL COMMENT 'Id que viene de la tabla modalidad_operativa',
  `precio` decimal(12,2) NOT NULL COMMENT 'Precio del Repueto',
  `año_vigencia` int(4) NOT NULL DEFAULT 2025 COMMENT 'Año de Vigencia de la Tarifa',
  PRIMARY KEY (`id_tarifa`),
  KEY `id_tipo_maquina` (`id_tipo_maquina`),
  KEY `id_tipo_mantenimiento` (`id_tipo_mantenimiento`),
  KEY `id_tipo_zona` (`id_modalidad`),
  KEY `idx_vigencia` (`año_vigencia`),
  CONSTRAINT `tarifa_ibfk_1` FOREIGN KEY (`id_tipo_maquina`) REFERENCES `tipo_maquina` (`id_tipo_maquina`),
  CONSTRAINT `tarifa_ibfk_2` FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipo_mantenimiento` (`id_tipo_mantenimiento`),
  CONSTRAINT `tarifa_ibfk_3` FOREIGN KEY (`id_modalidad`) REFERENCES `modalidad_operativa` (`id_modalidad`)
) ENGINE=InnoDB AUTO_INCREMENT=331 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tecnico` (
  `id_tecnico` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla tecnico',
  `nombre_tecnico` varchar(60) NOT NULL COMMENT 'nombre del técnico para los servicios',
  `codigo_ruta` varchar(20) DEFAULT NULL COMMENT 'Código de la ruta (ej. 701, 702)',
  `ruta_firma` varchar(120) DEFAULT NULL COMMENT 'ruta de la imágen de la firma',
  `usuario_id` int(10) unsigned DEFAULT NULL COMMENT 'id que viene de la tala usuarios',
  `estado` tinyint(4) DEFAULT 1 COMMENT 'estado 1 = Activo 0 = Desactivado (Borrado Lógico)',
  PRIMARY KEY (`id_tecnico`),
  KEY `fk_tecnico_usuario` (`usuario_id`),
  CONSTRAINT `fk_tecnico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`usuario_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_mantenimiento` (
  `id_tipo_mantenimiento` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla tipo_mantenimiento',
  `nombre_completo` varchar(50) DEFAULT NULL COMMENT 'Nombre del tipo de mantenimiento',
  `estado` tinyint(4) DEFAULT 1 COMMENT 'estado 1 = Activo 0 = Desactivado (Borrado Lógico)',
  PRIMARY KEY (`id_tipo_mantenimiento`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_maquina` (
  `id_tipo_maquina` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla tipo_maquina',
  `nombre_tipo_maquina` varchar(40) NOT NULL COMMENT 'Nombre del tipo de máquina',
  `estado` tinyint(4) DEFAULT 1 COMMENT 'estado 1 = Activo 0 = Desactivado (Borrado Lógico)',
  `frecuencia_base_dias` int(11) DEFAULT NULL COMMENT '30=Mensual, 60=Bimestral, 90=Trimestral',
  PRIMARY KEY (`id_tipo_maquina`),
  UNIQUE KEY `nombre_tipo_maquina` (`nombre_tipo_maquina`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_novedad` (
  `id_tipo_novedad` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla tipo_novedad',
  `nombre_novedad` varchar(100) NOT NULL COMMENT 'Nombre del tipo de Novedad',
  `estado` tinyint(1) DEFAULT 1 COMMENT 'estado 1 = Activo 0 = Desactivado (Borrado Lógico)',
  PRIMARY KEY (`id_tipo_novedad`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipousuario` (
  `idTipoUsuario` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla tipousuario',
  `nombreTipoUsuario` varchar(50) NOT NULL COMMENT 'Nombre del Tipo de Usuario',
  PRIMARY KEY (`idTipoUsuario`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `turno_tecnico` (
  `id_turno` int(11) NOT NULL AUTO_INCREMENT,
  `id_tecnico` int(11) NOT NULL COMMENT 'FK tecnico (se toma de la sesión)',
  `fecha` date NOT NULL COMMENT 'Día que el técnico selecciona',
  `hora_entrada` time NOT NULL COMMENT 'Hora que el técnico digita',
  `novedad` text DEFAULT NULL COMMENT 'Texto libre: llegué tarde, permiso, etc.',
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Hora real en que guardó (auditoría)',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 activo / 0 borrado lógico',
  PRIMARY KEY (`id_turno`),
  UNIQUE KEY `idx_turno_tecnico_dia` (`id_tecnico`,`fecha`),
  CONSTRAINT `fk_turno_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnico` (`id_tecnico`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=375 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `usuario_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'AutoIncrementable de la tabla usuarios',
  `nombre` varchar(60) DEFAULT NULL COMMENT 'Nombre del Usuario ',
  `cedula` varchar(15) DEFAULT NULL COMMENT 'Cédula del Usuario',
  `cargo` varchar(50) DEFAULT NULL COMMENT 'Cargo del Usuario',
  `email` varchar(50) DEFAULT NULL COMMENT 'Correo Electrónico del Usuario',
  `celular` varchar(15) DEFAULT NULL COMMENT 'Número Célular del Usuario',
  `usuario` varchar(20) DEFAULT NULL COMMENT 'Usuario para la Aplicación',
  `password_hash` varchar(80) DEFAULT NULL COMMENT 'Hash de la Base de Datos',
  `forzar_cambio_pwd` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Identificador del Cambio de Contraseña',
  `pwd_ultimo_cambio` timestamp NULL DEFAULT current_timestamp() COMMENT 'Fecha del Último Cambio de Contraseña',
  `nivel_acceso` int(11) NOT NULL COMMENT 'Id que viene de la tabla tipousuario',
  `ultimo_acceso` timestamp NULL DEFAULT NULL COMMENT 'Fecha del Último Acceso a la Aplicación',
  `estado` varchar(10) DEFAULT 'activo' COMMENT ' Activo Desactivado (Borrado Lógico)',
  PRIMARY KEY (`usuario_id`) USING BTREE,
  KEY `fk_usuarios_tipousuario` (`nivel_acceso`) USING BTREE,
  KEY `idx_usuarios_email` (`email`) USING BTREE,
  CONSTRAINT `fk_usuarios_tipousuario` FOREIGN KEY (`nivel_acceso`) REFERENCES `tipousuario` (`idTipoUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Catálogos
INSERT INTO `calificacion_servicio` VALUES (1,'EXCELENTE');
INSERT INTO `calificacion_servicio` VALUES (2,'BUENO');
INSERT INTO `calificacion_servicio` VALUES (3,'REGULAR');
INSERT INTO `calificacion_servicio` VALUES (4,'MALO');
INSERT INTO `calificacion_servicio` VALUES (5,'NO RESPONDE');
INSERT INTO `delegacion` VALUES (1,'BOGOTA',1);
INSERT INTO `delegacion` VALUES (2,'CALI',1);
INSERT INTO `delegacion` VALUES (3,'MEDELLIN',1);
INSERT INTO `delegacion` VALUES (4,'BARRANQUILLA',1);
INSERT INTO `delegacion` VALUES (5,'PEREIRA',1);
INSERT INTO `delegacion` VALUES (6,'BUCARAMANGA',1);
INSERT INTO `delegacion` VALUES (7,'CARTAGENA',1);
INSERT INTO `delegacion` VALUES (8,'ARMENIA',1);
INSERT INTO `delegacion` VALUES (9,'SANTA MARTA',1);
INSERT INTO `delegacion` VALUES (10,'CUCUTA',1);
INSERT INTO `delegacion` VALUES (11,'MONTERIA',1);
INSERT INTO `delegacion` VALUES (12,'IBAGUE',1);
INSERT INTO `delegacion` VALUES (14,'MANIZALES',1);
INSERT INTO `delegacion` VALUES (15,'TUNJA',1);
INSERT INTO `delegacion` VALUES (16,'NEIVA',1);
INSERT INTO `delegacion` VALUES (17,'RIOHACHA',1);
INSERT INTO `delegacion` VALUES (18,'VALLEDUPAR',1);
INSERT INTO `delegacion` VALUES (19,'VILLAVICENCIO',1);
INSERT INTO `delegacion` VALUES (21,'SINCELEJO',1);
INSERT INTO `delegacion` VALUES (22,'POPAYAN',1);
INSERT INTO `delegacion` VALUES (23,'HONDA',1);
INSERT INTO `delegacion` VALUES (24,'BARRANCABERMEJA',1);
INSERT INTO `delegacion` VALUES (25,'FLORENCIA',1);
INSERT INTO `delegacion` VALUES (32,'YOPAL',1);
INSERT INTO `delegacion` VALUES (34,'PITALITO',1);
INSERT INTO `delegacion` VALUES (35,'AGUACHICA',1);
INSERT INTO `delegacion` VALUES (36,'CAUCASIA',1);
INSERT INTO `delegacion` VALUES (47,'APARTADO',1);
INSERT INTO `estado_maquina` VALUES (1,'OPERATIVA');
INSERT INTO `estado_maquina` VALUES (2,'FUERA DE SERVICIO');
INSERT INTO `estados_aprobacion_he` VALUES (1,'Pendiente');
INSERT INTO `estados_aprobacion_he` VALUES (2,'Aprobada');
INSERT INTO `estados_aprobacion_he` VALUES (3,'Rechazada');
INSERT INTO `estados_remision` VALUES (1,'USADA',1);
INSERT INTO `estados_remision` VALUES (2,'ANULADA',1);
INSERT INTO `estados_remision` VALUES (3,'DISPONIBLE',1);
INSERT INTO `estados_remision` VALUES (4,'KISAN',1);
INSERT INTO `estados_remision` VALUES (5,'INSTALACIÓN',1);
INSERT INTO `estados_remision` VALUES (6,'DESINSTALACIÓN',1);
INSERT INTO `estados_remision` VALUES (7,'CAMBIO DE MÁQUINA',1);
INSERT INTO `estados_remision` VALUES (8,'INSTALACIÓN FALLIDA',1);
INSERT INTO `estados_remision` VALUES (9,'DESANCLAJE',1);
INSERT INTO `estados_remision` VALUES (10,'ANCLAJE',1);
INSERT INTO `estados_remision` VALUES (11,'INSTALACIÓN MÁS CAPACITACIÓN',1);
INSERT INTO `modalidad_operativa` VALUES (1,'URBANO');
INSERT INTO `modalidad_operativa` VALUES (2,'INTERURBANO');
INSERT INTO `parametros` VALUES (1,'password_vigencia_dias','60','Días antes de obligar cambio de contraseña');
INSERT INTO `parametros` VALUES (2,'intentos_maximos','5','Intentos fallidos antes de bloquear');
INSERT INTO `parametros` VALUES (3,'Recargo_Servicios_Interurbanos','162000','Valor por día cuando el técnico sale de su delegación base');
INSERT INTO `parametros` VALUES (4,'session_timeout_minutos','30','Minutos de inactividad antes de cerrar la sesión automáticamente.');
INSERT INTO `parametros` VALUES (5,'meses_restriccion_prosegur','1','Cantidad de meses recientes ocultos para el rol Prosegur');
INSERT INTO `parametros` VALUES (6,'tiempos_mantenimiento_minutos','{\"1\": 45, \"2\": 60, \"3\": 80, \"4\": 20}','Tiempos estimados en minutos agrupados por ID de tipo de mantenimiento');
INSERT INTO `tipousuario` VALUES (1,'SuperUsuario');
INSERT INTO `tipousuario` VALUES (2,'Auxiliar');
INSERT INTO `tipousuario` VALUES (3,'Técnico');
INSERT INTO `tipousuario` VALUES (4,'Prosegur');
INSERT INTO `tipousuario` VALUES (5,'Supervisor Motorizados');
INSERT INTO `tipo_mantenimiento` VALUES (1,'Mantenimiento Preventivo Basico',1);
INSERT INTO `tipo_mantenimiento` VALUES (2,'Mantenimiento Preventivo Profundo',1);
INSERT INTO `tipo_mantenimiento` VALUES (3,'Mantenimiento Correctivo',1);
INSERT INTO `tipo_mantenimiento` VALUES (4,'Fallido',1);
INSERT INTO `tipo_mantenimiento` VALUES (5,'Garantia',1);
INSERT INTO `tipo_mantenimiento` VALUES (6,'Nueva Arquitectura',1);
INSERT INTO `tipo_maquina` VALUES (1,'JH-600',1,60);
INSERT INTO `tipo_maquina` VALUES (2,'JH-6000',1,30);
INSERT INTO `tipo_maquina` VALUES (3,'JH-3500',1,90);
INSERT INTO `tipo_maquina` VALUES (4,'DF4',1,30);
INSERT INTO `tipo_maquina` VALUES (5,'LOGY SAFE',1,NULL);
INSERT INTO `tipo_maquina` VALUES (6,'MATCH-6',1,30);
INSERT INTO `tipo_maquina` VALUES (7,'MINI MEI',1,90);
INSERT INTO `tipo_maquina` VALUES (8,'SDM-100',1,30);
INSERT INTO `tipo_maquina` VALUES (9,'SDM-500',1,30);
INSERT INTO `tipo_maquina` VALUES (10,'SDM-10',1,90);
INSERT INTO `tipo_maquina` VALUES (11,'SDM-10-XS',1,90);
INSERT INTO `tipo_maquina` VALUES (13,'SDM-504',1,30);
INSERT INTO `tipo_maquina` VALUES (14,'TOMBOLA',1,NULL);
INSERT INTO `tipo_maquina` VALUES (15,'PRO-EFECTIVO-X',1,30);
INSERT INTO `tipo_maquina` VALUES (32,'PS-3000',1,30);
INSERT INTO `tipo_maquina` VALUES (38,'AZKOYEN',1,NULL);
INSERT INTO `tipo_maquina` VALUES (42,'KD30-SDM100',1,30);
INSERT INTO `tipo_maquina` VALUES (43,'LOGY-SAFE',1,NULL);
INSERT INTO `tipo_maquina` VALUES (45,'KD30-SDM500',1,30);
INSERT INTO `tipo_maquina` VALUES (55,'SMART-COIN',1,NULL);
INSERT INTO `tipo_maquina` VALUES (58,'SEMAMI',1,NULL);
INSERT INTO `tipo_maquina` VALUES (59,'SDM-801',1,NULL);
INSERT INTO `tipo_maquina` VALUES (60,'CDS-801',1,NULL);
INSERT INTO `tipo_novedad` VALUES (1,'Punto Cerrado',1);
INSERT INTO `tipo_novedad` VALUES (2,'Repuesto(S) Pendiente(S)',1);
INSERT INTO `tipo_novedad` VALUES (3,'Cliente No Utiliza la Máquina',1);
INSERT INTO `tipo_novedad` VALUES (4,'Daños O Anomalias Por Mala Operación de la Máquina (Cliente)',1);
INSERT INTO `tipo_novedad` VALUES (5,'Visita Pendiente  y/o Repuesto Pendiente',1);
INSERT INTO `tipo_novedad` VALUES (6,'Intervención CIT',1);
INSERT INTO `tipo_novedad` VALUES (7,'Cambio Router',1);
INSERT INTO `tipo_novedad` VALUES (8,'Máquina Vandalizada',1);
INSERT INTO `tipo_novedad` VALUES (9,'Pendiente Cambio Validador',1);
INSERT INTO `tipo_novedad` VALUES (10,'Máquina O Punto en Seguimiento',1);
INSERT INTO `tipo_novedad` VALUES (11,'Fallas Conteo de Efectivo (APP)',1);
INSERT INTO `tipo_novedad` VALUES (12,'Reprogramar Visita Cliente No Permite Intervención',1);
INSERT INTO `tipo_novedad` VALUES (13,'UPS Pendiente Cambio o Proveer',1);
INSERT INTO `tipo_novedad` VALUES (14,'Demora En El Acceso Por Parte Del Cliente',1);
INSERT INTO `tipo_novedad` VALUES (15,'Visita Reciente, el Cliente No Permite Volver A Realizar Mantenimiento',1);
INSERT INTO `tipo_novedad` VALUES (16,'No Hay Maquina En El Punto',1);
INSERT INTO `tipo_novedad` VALUES (17,'Cliente No Deja Intervenir Maquina Por Correcto Funcionamiento',1);
INSERT INTO `tipo_novedad` VALUES (18,'Máquina No Reporta En La Plataforma',1);
INSERT INTO `tipo_novedad` VALUES (19,'Pendiente Cambio de Máquina',1);
INSERT INTO `rutas` VALUES (1,'login','app/controllers/login/loginControlador.php','activo','Pantalla de Login');
INSERT INTO `rutas` VALUES (2,'inicio','app/controllers/inicio/inicioControlador.php','activo','Dashboard Principal');
INSERT INTO `rutas` VALUES (3,'ordenDetalle','app/controllers/orden/ordenDetalleControlador.php','activo','Gestión de Órdenes');
INSERT INTO `rutas` VALUES (4,'cambiarPassword','app/controllers/login/cambiarPasswordControlador.php','activo','Cambio de clave');
INSERT INTO `rutas` VALUES (5,'logout','','activo','Cierre de sesión (Sin archivo físico, lógica interna)');
INSERT INTO `rutas` VALUES (6,'ordenVer','app/controllers/orden/ordenVerControlador.php','activo','Datatable de las Órdenes Creadas');
INSERT INTO `rutas` VALUES (7,'ordenCrear','app/controllers/orden/ordenCrearControlador.php','activo','Creación de Órdenes');
INSERT INTO `rutas` VALUES (8,'usuarioCrear','app/controllers/usuario/usuarioCrearControlador.php','activo','Creación de los Usuarios');
INSERT INTO `rutas` VALUES (9,'usuarioVer','app/controllers/usuario/usuarioVerControlador.php','activo','Ver los Usuarios Registrados');
INSERT INTO `rutas` VALUES (10,'usuarioEditar','app/controllers/usuario/usuarioEditarControlador.php','activo','Editar los Usuarios Registrados');
INSERT INTO `rutas` VALUES (11,'usuarioEliminar','app/controllers/usuario/usuarioEliminarControlador.php','activo','Elimina lógicamente los Usuarios ');
INSERT INTO `rutas` VALUES (12,'procesarCambioPassword','app/controllers/login/procesarCambioPasswordControlador.php','activo','Acción de Cambiar Contraseña');
INSERT INTO `rutas` VALUES (13,'repuestoCrear','app/controllers/admin/repuestoCrearControlador.php','activo','Formulario de Creación de Repuestos');
INSERT INTO `rutas` VALUES (14,'repuestoVer','app/controllers/admin/repuestoVerControlador.php','activo','Ver los Repuestos que están en la base de datos');
INSERT INTO `rutas` VALUES (15,'repuestoEditar','app/controllers/admin/repuestoEditarControlador.php','activo','Edita los Repuestos Registrados en la Base de Datos');
INSERT INTO `rutas` VALUES (16,'repuestoEliminar','app/controllers/admin/repuestoEliminarControlador.php','activo','Elimina Lógicamente los Repuestos');
INSERT INTO `rutas` VALUES (17,'clienteCrear','app/controllers/admin/clienteCrearControlador.php','activo','Formulario de Creación de Clientes');
INSERT INTO `rutas` VALUES (18,'clienteVer','app/controllers/admin/clienteVerControlador.php','activo','Ver los Clientes que están en la base de datos');
INSERT INTO `rutas` VALUES (19,'clienteEditar','app/controllers/admin/clienteEditarControlador.php','activo','Edita los Clientes Registrados en la Base de Datos');
INSERT INTO `rutas` VALUES (20,'clienteEliminar','app/controllers/admin/clienteEliminarControlador.php','activo','Elimina Lógicamente los Clientes');
INSERT INTO `rutas` VALUES (21,'tipoMaquinaCrear','app/controllers/admin/tipoMaquinaCrearControlador.php','activo','Formulario de Creación de los Tipos de Máquinas');
INSERT INTO `rutas` VALUES (22,'tipoMaquinaVer','app/controllers/admin/tipoMaquinaVerControlador.php','activo','Ver los Tipos de Máquinas que están en la base de datos');
INSERT INTO `rutas` VALUES (23,'tipoMaquinaEditar','app/controllers/admin/tipoMaquinaEditarControlador.php','activo','Edita los Tipos de Máquinas Registrados en la Base de Datos');
INSERT INTO `rutas` VALUES (24,'tipoMaquinaEliminar','app/controllers/admin/tipoMaquinaEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Clientes');
INSERT INTO `rutas` VALUES (25,'tipoMantenimientoCrear','app/controllers/admin/tipoMantenimientoCrearControlador.php','activo','Formulario de Creación de los Tipos de Mantenimiento');
INSERT INTO `rutas` VALUES (26,'tipoMantenimientoVer','app/controllers/admin/tipoMantenimientoVerControlador.php','activo','Ver los Tipos de Mantenimiento que están en la base de datos');
INSERT INTO `rutas` VALUES (27,'tipoMantenimientoEditar','app/controllers/admin/tipoMantenimientoEditarControlador.php','activo','Edita los Tipos de Mantenimiento Registrados en la Base de Datos');
INSERT INTO `rutas` VALUES (28,'tipoMantenimientoEliminar','app/controllers/admin/tipoMantenimientoEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Mantenimiento');
INSERT INTO `rutas` VALUES (29,'calificacionServicioCrear','app/controllers/admin/calificacionServicioCrearControlador.php','activo','Formulario de Creación de los Tipos de Calificación');
INSERT INTO `rutas` VALUES (30,'calificacionServicioVer','app/controllers/admin/calificacionServicioVerControlador.php','activo','Ver los Tipos de calificación que están en la base de datos');
INSERT INTO `rutas` VALUES (31,'calificacionServicioEditar','app/controllers/admin/calificacionServicioEditarControlador.php','activo','Edita los Tipos de calificación en la Base de Datos');
INSERT INTO `rutas` VALUES (32,'calificacionServicioEliminar','app/controllers/admin/calificacionServicioEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Calificación');
INSERT INTO `rutas` VALUES (33,'delegacionCrear','app/controllers/admin/delegacionCrearControlador.php','activo','Formulario de Creación de los Tipos de Delegación');
INSERT INTO `rutas` VALUES (34,'delegacionVer','app/controllers/admin/delegacionVerControlador.php','activo','Ver los Tipos de delegación que están en la base de datos');
INSERT INTO `rutas` VALUES (35,'delegacionEditar','app/controllers/admin/delegacionEditarControlador.php','activo','Edita los Tipos de delegación en la Base de Datos');
INSERT INTO `rutas` VALUES (36,'delegacionEliminar','app/controllers/admin/delegacionEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Delegación');
INSERT INTO `rutas` VALUES (37,'estadoMaquinaCrear','app/controllers/admin/estadoMaquinaCrearControlador.php','activo','Formulario de Creación de los Tipos de Estado de Máquina');
INSERT INTO `rutas` VALUES (38,'estadoMaquinaVer','app/controllers/admin/estadoMaquinaVerControlador.php','activo','Ver los Tipos de Estado de Máquina que están en la base de datos');
INSERT INTO `rutas` VALUES (39,'estadoMaquinaEditar','app/controllers/admin/estadoMaquinaEditarControlador.php','activo','Edita los Tipos de estado de Máquina en la Base de Datos');
INSERT INTO `rutas` VALUES (40,'estadoMaquinaEliminar','app/controllers/admin/estadoMaquinaEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Estado de Máquina');
INSERT INTO `rutas` VALUES (41,'modalidadOperativaCrear','app/controllers/admin/modalidadOperativaCrearControlador.php','activo','Formulario de Creación de las Modalidades Operativas');
INSERT INTO `rutas` VALUES (42,'modalidadOPerativaVer','app/controllers/admin/modalidadOperativaVerControlador.php','activo','Ver los Tipos de Estado de Modalidad Operativa que están en la base de datos');
INSERT INTO `rutas` VALUES (43,'modalidadOperativaEditar','app/controllers/admin/modalidadOperativaEditarControlador.php','activo','Edita los Tipos de Modalidad Operativa en la Base de Datos');
INSERT INTO `rutas` VALUES (44,'modalidadOperativaEliminar','app/controllers/admin/modalidadOperativaEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Modalidad Operativa');
INSERT INTO `rutas` VALUES (45,'tecnicoCrear','app/controllers/admin/tecnicoCrearControlador.php','activo','Formulario de Tecnico');
INSERT INTO `rutas` VALUES (46,'tecnicoVer','app/controllers/admin/tecnicoVerControlador.php','activo','Ver los Tipos de Estado de Modalidad Operativa que están en la base de datos');
INSERT INTO `rutas` VALUES (47,'tecnicoEditar','app/controllers/admin/tecnicoEditarControlador.php','activo','Edita los Tipos de Tecnico en la Base de Datos');
INSERT INTO `rutas` VALUES (48,'tecnicoEliminar','app/controllers/admin/tecnicoEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Técnicos');
INSERT INTO `rutas` VALUES (49,'tipoUsuarioCrear','app/controllers/admin/tipoUsuarioCrearControlador.php','activo','Formulario de los Tipos de Usuarios');
INSERT INTO `rutas` VALUES (50,'tipoUsuarioVer','app/controllers/admin/tipoUsuarioVerControlador.php','activo','Ver los TIpos de Usuarios que están en la base de datos');
INSERT INTO `rutas` VALUES (51,'tipoUsuarioEditar','app/controllers/admin/tipoUsuarioEditarControlador.php','activo','Edita los Tipos de Usuarios en la Base de Datos');
INSERT INTO `rutas` VALUES (52,'tipoUsuarioEliminar','app/controllers/admin/tipoUsuarioEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Usuario');
INSERT INTO `rutas` VALUES (53,'tarifaCrear','app/controllers/admin/tarifaCrearControlador.php','activo','Formulario de los Tipos de Usuarios');
INSERT INTO `rutas` VALUES (54,'tarifaVer','app/controllers/admin/tarifaVerControlador.php','activo','Ver los Tipos de tarifas que están en la base de datos');
INSERT INTO `rutas` VALUES (55,'tarifaEditar','app/controllers/admin/tarifaEditarControlador.php','activo','Edita los Tipos de Tarifas en la Base de Datos');
INSERT INTO `rutas` VALUES (56,'tarifaEliminar','app/controllers/admin/tarifaEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Tarifas');
INSERT INTO `rutas` VALUES (57,'puntoCrear','app/controllers/admin/puntoCrearControlador.php','activo','Formulario de los Tipos de Puntos');
INSERT INTO `rutas` VALUES (58,'puntoVer','app/controllers/admin/puntoVerControlador.php','activo','Ver los Tipos de puntos que están en la base de datos');
INSERT INTO `rutas` VALUES (59,'puntoEditar','app/controllers/admin/puntoEditarControlador.php','activo','Edita los Tipos de Puntos en la Base de Datos');
INSERT INTO `rutas` VALUES (60,'puntoEliminar','app/controllers/admin/puntoEliminarControlador.php','activo','Elimina Lógicamente los Tipos de Puntos');
INSERT INTO `rutas` VALUES (61,'maquinaCrear','app/controllers/admin/maquinaCrearControlador.php','activo','Formulario de las máquinas');
INSERT INTO `rutas` VALUES (62,'maquinaVer','app/controllers/admin/maquinaVerControlador.php','activo','Ver las Máquinas en la base de datos');
INSERT INTO `rutas` VALUES (66,'maquinaEditar','app/controllers/admin/maquinaEditarControlador.php','activo','Edita las Máquinas de la Base de Datos');
INSERT INTO `rutas` VALUES (67,'maquinaEliminar','app/controllers/admin/maquinaEliminarControlador.php','activo','Elimina Lógicamente las Máquinas');
INSERT INTO `rutas` VALUES (68,'reporteTecnico','app/controllers/reportes/reporteTecnicoControlador.php','activo','Reporte de los servicios hechos por tecnico');
INSERT INTO `rutas` VALUES (69,'reporteRepuesto','app/controllers/reportes/reporteRepuestoControlador.php','activo','Reporte de los repuestos usados durante el mes');
INSERT INTO `rutas` VALUES (70,'controlRemisionCrear','app/controllers/admin/controlRemisionCrearControlador.php','activo','Formulario de las Remisiones');
INSERT INTO `rutas` VALUES (71,'controlRemisionVer','app/controllers/admin/controlRemisionVerControlador.php','activo','Ver las Remisiones en la base de datos');
INSERT INTO `rutas` VALUES (72,'controlRemisionEditar','app/controllers/admin/controlRemisionEditarControlador.php','activo','Edita las Remisiones de la Base de Datos');
INSERT INTO `rutas` VALUES (73,'controlRemisionEliminar','app/controllers/admin/controlRemisionEliminarControlador.php','activo','Elimina Lógicamente las Remisiones');
INSERT INTO `rutas` VALUES (74,'diasFestivosCrear','app/controllers/admin/diasFestivosCrearControlador.php','activo','Formulario de los días Festivos');
INSERT INTO `rutas` VALUES (75,'diasFestivosVer','app/controllers/admin/diasFestivosVerControlador.php','activo','Ver los días festivos en la base de datos');
INSERT INTO `rutas` VALUES (76,'diasFestivosEditar','app/controllers/admin/diasFestivosEditarControlador.php','activo','Edita los días Festivos de la Base de Datos');
INSERT INTO `rutas` VALUES (77,'diasFestivosEliminar','app/controllers/admin/diasFestivosEliminarControlador.php','activo','Elimina Lógicamente los días Festivos');
INSERT INTO `rutas` VALUES (78,'reporteEjecutivo','app/controllers/reportes/reporteEjecutivoVerControlador.php','activo','Ver el Reporte Ejecutivo');
INSERT INTO `rutas` VALUES (79,'generarReporte','app/controllers/reportes/reporteEjecutivoGenerarControlador.php','activo','Generar el Reporte Ejecutivo');
INSERT INTO `rutas` VALUES (80,'inventarioTecnicoCrear','app/controllers/admin/inventarioTecnicoCrearControlador.php','activo','Formulario de el Inventario de Repuesto de Técnico');
INSERT INTO `rutas` VALUES (81,'inventarioTecnicoVer','app/controllers/admin/inventarioTecnicoVerControlador.php','activo','Ver inventario de Repuestos en la base de datos');
INSERT INTO `rutas` VALUES (82,'inventarioTecnicoEditar','app/controllers/admin/inventarioTecnicoEditarControlador.php','activo','Edita el Inventario de Repuestos de la Base de Datos');
INSERT INTO `rutas` VALUES (83,'inventarioTecnicoEliminar','app/controllers/admin/inventarioTecnicoEliminarControlador.php','activo','Elimina Lógicamente el inventario de Repuestos');
INSERT INTO `rutas` VALUES (84,'importarExcel','app/controllers/importar/importarExcelControlador.php','activo','Exportar Excel de Prosegur');
INSERT INTO `rutas` VALUES (85,'ordenDetalleBuscar','app/controllers/orden/ordenDetalleBuscarControlador.php','activo','Vista encargada de Buscar los servicios uno a uno filtrados');
INSERT INTO `rutas` VALUES (86,'solicitarCodigo','app/controllers/login/solicitarCodigoControlador.php','activo','Solicitar Código de Recuperación de Contraseña');
INSERT INTO `rutas` VALUES (87,'resetPassword','app/controllers/login/resetPasswordControlador.php','activo','Hace el Cambio de la Contraseña');
INSERT INTO `rutas` VALUES (88,'ordenReporte','app/controllers/orden/ordenReporteControlador.php','activo','Genera el excel, de los clientes puntos device_id y otros elementos de la base de datos');
INSERT INTO `rutas` VALUES (89,'exportarExcel','app/controllers/exportar/exportarExcelControlador.php','activo','Modulo para exportar reporte de puntos');
INSERT INTO `rutas` VALUES (90,'importarMunicipios','app/controllers/importar/importarMunicipiosControlador.php','activo','Modulo encargado de poder actualizar los puntos con la información del excel de la programación');
INSERT INTO `rutas` VALUES (91,'remisionesPendientes','app/controllers/reportes/RemisionesPendientesControlador.php','activo','Módulo encargado de mostrar las remisiones salteadas para poder hacerles el control');
INSERT INTO `rutas` VALUES (92,'informacionBaseDatos','app/controllers/informacion/informacionBaseDatosControlador.php','activo','Móduo encargado de mostrar información relevante de la base de datos');
INSERT INTO `rutas` VALUES (93,'tipoNovedadCrear','app/controllers/admin/tipoNovedadCrearControlador.php','activo','Módulo encargado de Crear un Nuevo Tipo de Novedad');
INSERT INTO `rutas` VALUES (94,'tipoNovedadVer','app/controllers/admin/tipoNovedadVerControlador.php','activo','Módulo encargado de Ver los Tipos de Novedades de la Base de Datos');
INSERT INTO `rutas` VALUES (95,'tipoNovedadEditar','app/controllers/admin/tipoNovedadEditarControlador.php','activo','Módulo encargado de Editar los Tipos de Novedades');
INSERT INTO `rutas` VALUES (96,'tipoNovedadEliminar','app/controllers/admin/tipoNovedadEliminarControlador.php','activo','Módulo encargado de Eliminar (Inactivar) los Tipos de Novedades');
INSERT INTO `rutas` VALUES (97,'reporteMaquinas','app/controllers/reportes/reporteMaquinasControlador.php','activo','Módulo encargado de Mostrar el número de puntos de cada tipo de Máquina de la Base de Datos');
INSERT INTO `rutas` VALUES (98,'tarifaMasiva','app/controllers/admin/tarifaMasivaControlador.php','activo','Módulo encargado de realizar la edición Masiva de las Tarifas');
INSERT INTO `rutas` VALUES (99,'programacionCrear','app/controllers/programacion/programacionCrearControlador.php','activo','Módulo encargado de Crear La Programación');
INSERT INTO `rutas` VALUES (100,'ordenMovil','app/controllers/orden/ordenMovilControlador.php','activo','Módulo encargado de la Visualización de el historial de Servicios Para los Técnicos');
INSERT INTO `rutas` VALUES (101,'costosCrear','app/controllers/costos/costosCrearControlador.php','activo','Módulo encargado de Crear los costos de los Motorizados');
INSERT INTO `rutas` VALUES (102,'costosAdministrativosCrear','app/controllers/costos/costosAdministrativosCrearControlador.php','activo','Módulo encargado de Crear los costos de la nomina Administrativa y los Costos Administrativos');
INSERT INTO `rutas` VALUES (103,'costosAdministrativosVer','app/controllers/costos/costosAdministrativosVerControlador.php','activo','Módulo encargado de Ver los costos de las personas Administrativas');
INSERT INTO `rutas` VALUES (104,'costosAdministrativosEditar','app/controllers/costos/costosAdministrativosEditarControlador.php','activo','Módulo encargado de Editar los costos de las personas Administrativas');
INSERT INTO `rutas` VALUES (105,'costosVer','app/controllers/costos/costosVerControlador.php','activo','Módulo encargado de Ver los costos de los Motorizados');
INSERT INTO `rutas` VALUES (106,'costosEditar','app/controllers/costos/costosEditarControlador.php','activo','Módulo encargado de Editar los costos de los Motorizados');
INSERT INTO `rutas` VALUES (107,'geolocalizar','app/controllers/geolocalizar/geolocalizarControlador.php','activo','Módulo encargado de Hacer la Geolocalización de los puntos');
INSERT INTO `rutas` VALUES (108,'tecnicoProgramacion','app/controllers/tecnico/tecnicoProgramacionControlador.php','activo','Módulo encargado de poder ver la Programación de cada motorizado');
INSERT INTO `rutas` VALUES (109,'tecnicoReporte','app/controllers/tecnico/tecnicoReporteControlador.php','activo','Módulo encargado de poder hacer el Reporte de Cada Téncnico');
INSERT INTO `rutas` VALUES (110,'serviciosPdf','app/controllers/orden/serviciosPdfControlador.php','activo','Módulo encargado de poder ver los pdfs de los Servicios');
INSERT INTO `rutas` VALUES (111,'pdfServicio','app/controllers/orden/pdfServicioControlador.php','activo','Módulo encargado de Generar el Pdf de Cada Servicio');
INSERT INTO `rutas` VALUES (112,'rastreoTecnico','app/controllers/rastreo/rastreoTecnicoControlador.php','activo','Módulo encargado de Mostrar el Rastro de los Técnicos en el Mapa');
INSERT INTO `rutas` VALUES (113,'panelSupervisor','app/controllers/supervisor/panelSupervisorControlador.php','activo','Módulo encargado de Ver el Progreso de los Técnicos para el Supervisor');
INSERT INTO `rutas` VALUES (114,'reporteHistorial','app/controllers/reportes/reporteHistorialControlador.php','activo','Módulo encargado de Generar el Historial de los Servicios que se han generado en un intervalo de Tie');
INSERT INTO `rutas` VALUES (115,'transporteCrear','app/controllers/transporte/transporteCrearControlador.php','activo','Módulo encargado de Crear los Transportes');
INSERT INTO `rutas` VALUES (116,'transporteVer','app/controllers/transporte/transporteVerControlador.php','activo','Módulo encargado de Ver los Transportes');
INSERT INTO `rutas` VALUES (117,'transporteEditar','app/controllers/transporte/transporteEditarControlador.php','activo','Módulo encargado de Editar los Transportes');
INSERT INTO `rutas` VALUES (118,'transportePdf','app/controllers/transporte/transportePdfControlador.php','activo','Módulo encargado de Generar el Pdf de los Transoprtes');
INSERT INTO `rutas` VALUES (119,'servicioEditar','app/controllers/orden/servicioEditarControlador.php','activo','Módulo encargado de completar la información de los servicios (provicionalmente)');
INSERT INTO `rutas` VALUES (120,'reporteDevolucion','app/controllers/reportes/reporteDevolucionControlador.php','activo','Módulo encargado de Generar el Reporte de las devoluciones');
INSERT INTO `rutas` VALUES (121,'gestionDevolucion','app/controllers/admin/gestionDevolucionControlador.php','activo','Módulo encargado de Marcar los Repuestos Devueltos');
INSERT INTO `rutas` VALUES (122,'asistencia','app/controllers/asistencia/asistenciaControlador.php','activo','Módulo encargado de Hacer las horas extra');
INSERT INTO `rutas` VALUES (123,'notificacionesLogistica','app/controllers/notificaciones/notificacionesLogisticaControlador.php','activo','Módulo encargado de Enviar las Notificaciones de la Logistica');
INSERT INTO `rutas` VALUES (124,'reporteFacturacion','app/controllers/reporteFacturacion/reporteFacturacionControlador.php','activo','Módulo encargado de Hacer el Reporte de la Facturación');
INSERT INTO `rutas` VALUES (125,'reporteTarifas','app/controllers/reportes/reporteTarifasControlador.php','activo','Módulo encargado de Hacer el Reporte de las Tarifas de las Máquinas');
INSERT INTO `rutas` VALUES (126,'reporteRepuestos','app/controllers/reportes/reporteRepuestosControlador.php','activo','Módulo encargado de Hacer el Reporte de los Repuestos PDF ');
INSERT INTO `rutas` VALUES (127,'parqueaderoCrear','app/controllers/parqueadero/parqueaderoCrearControlador.php','activo','Módulo encargado de Crear los Parqueaderos de los Técnicos');
INSERT INTO `rutas` VALUES (128,'parqueaderoHistorial','app/controllers/parqueadero/parqueaderoHistorialControlador.php','activo','Módulo encargado de Ver el Historial de los Parqueaderos Vista de los Técnicos');
INSERT INTO `rutas` VALUES (129,'parqueaderoAdmin','app/controllers/parqueadero/parqueaderoAdminControlador.php','activo','Módulo encargado de Ver los Parqueaderos por Parte del Administrador');
INSERT INTO `rutas` VALUES (130,'horaExtraCrear','app/controllers/horaExtra/horaExtraCrearControlador.php','activo','Módulo encargado de Crear las Horas Extra Técnicos');
INSERT INTO `rutas` VALUES (131,'horaExtraHistorial','app/controllers/horaExtra/horaExtraHistorialControlador.php','activo','Módulo encargado de Ver el Historial de las Horas Extra Técnicos');
INSERT INTO `rutas` VALUES (132,'horaExtraAdmin','app/controllers/horaExtra/horaExtraAdminControlador.php','activo','Módulo encargado de Ver el Historial de las Horas Extra Administrador');
INSERT INTO `rutas` VALUES (133,'horaExtraGenerar','app/controllers/horaExtra/horaExtraPdfControlador.php','activo','Módulo encargado de Generar el PDF de los Reportes de las Horas Extra');
INSERT INTO `rutas` VALUES (134,'horaExtraExcel','app/controllers/horaExtra/horaExtraExcelControlador.php','activo','Módulo encargado de Generar las Horas Extra en Formato Excel');
INSERT INTO `rutas` VALUES (135,'cronometroCrear','app/controllers/cronometro/cronometroCrearControlador.php','activo','Módulo encargado de Crear los Cronometros Parte Técnicos');
INSERT INTO `rutas` VALUES (136,'cronometroHistorial','app/controllers/cronometro/cronometroHistorialControlador.php','activo','Módulo encargado de Ver el Historial de los Cronometros Parte Técnicos');
INSERT INTO `rutas` VALUES (137,'cronometroAdmin','app/controllers/cronometro/cronometroAdminControlador.php','activo','Módulo encargado de Ver el Historial de los Cronometros SuperUsuario');
INSERT INTO `rutas` VALUES (138,'cronometroReportes','app/controllers/cronometro/cronometroReportesControlador.php','activo','Módulo encargado de ver los Reportes de los Técnicos en Tiempo Real');
INSERT INTO `rutas` VALUES (139,'planeacionVer','app/controllers/planeacion/planeacionControlador.php','activo','Módulo encargado de ver la planeación realizada para todos los técnicos');
INSERT INTO `rutas` VALUES (140,'importarEstadoMaquina','app/controllers/importar/importarEstadoMaquinaControlador.php','activo','Módulo encargado de Importar estado de maquinas desde Excel');
INSERT INTO `rutas` VALUES (141,'tecnicoReporteEditar','app/controllers/tecnico/tecnicoReporteEditarControlador.php','activo','Módulo encargado de Editar los Reportes de los Técnicos');
INSERT INTO `rutas` VALUES (142,'programacionConsolidado','app/controllers/programacion/programacionConsolidadoControlador.php','activo','Módulo encargado de ver la Programación antes de descargar el excel correspondiente.');
INSERT INTO `rutas` VALUES (146,'solicitarInventario','app/controllers/inventario/solicitarInventarioControlador.php','activo','Módulo encargado de Solicitar Repuestos por parte de los técnicos');
INSERT INTO `rutas` VALUES (147,'programacionMapa','app/controllers/programacion/programacionMapaControlador.php','activo','Módulo encargado de Hacer la Programación desde el Mapa');
INSERT INTO `rutas` VALUES (148,'reporteTrazabilidad','app/controllers/reportes/reporteTrazabilidadControlador.php','activo','Módulo encargado de Trazabilidad de repuestos suministrados (Excel 2 hojas)');
INSERT INTO `rutas` VALUES (149,'turno','app/controllers/turno/turnoControlador.php','activo','Marcar la entrada del turno del tecnico');
INSERT INTO `rutas` VALUES (150,'turnoReportes','app/controllers/turno/turnoReportesControlador.php','activo','Reporte de entradas de turno (Excel y PDF)');
INSERT INTO `rutas` VALUES (151,'turnoExcel','app/controllers/turno/turnoExcelControlador.php','activo','Excel de entradas de turno');
INSERT INTO `rutas` VALUES (152,'turnoPdf','app/controllers/turno/turnoPdfControlador.php','activo','PDF de entradas de turno');
INSERT INTO `rol_permisos` VALUES (1,2,2);
INSERT INTO `rol_permisos` VALUES (3,2,1);
INSERT INTO `rol_permisos` VALUES (4,2,4);
INSERT INTO `rol_permisos` VALUES (5,2,7);
INSERT INTO `rol_permisos` VALUES (6,2,3);
INSERT INTO `rol_permisos` VALUES (7,2,6);
INSERT INTO `rol_permisos` VALUES (8,2,12);
INSERT INTO `rol_permisos` VALUES (10,3,2);
INSERT INTO `rol_permisos` VALUES (11,3,100);
INSERT INTO `rol_permisos` VALUES (13,4,2);
INSERT INTO `rol_permisos` VALUES (15,4,110);
INSERT INTO `rol_permisos` VALUES (16,4,111);
INSERT INTO `rol_permisos` VALUES (17,3,108);
INSERT INTO `rol_permisos` VALUES (18,3,109);
INSERT INTO `rol_permisos` VALUES (19,5,2);
INSERT INTO `rol_permisos` VALUES (20,5,3);
INSERT INTO `rol_permisos` VALUES (21,5,6);
INSERT INTO `rol_permisos` VALUES (22,5,13);
INSERT INTO `rol_permisos` VALUES (23,5,14);
INSERT INTO `rol_permisos` VALUES (24,5,15);
INSERT INTO `rol_permisos` VALUES (25,5,16);
INSERT INTO `rol_permisos` VALUES (26,5,68);
INSERT INTO `rol_permisos` VALUES (27,5,70);
INSERT INTO `rol_permisos` VALUES (28,5,71);
INSERT INTO `rol_permisos` VALUES (29,5,72);
INSERT INTO `rol_permisos` VALUES (30,5,73);
INSERT INTO `rol_permisos` VALUES (31,5,78);
INSERT INTO `rol_permisos` VALUES (32,5,79);
INSERT INTO `rol_permisos` VALUES (33,5,85);
INSERT INTO `rol_permisos` VALUES (34,5,88);
INSERT INTO `rol_permisos` VALUES (35,5,122);
INSERT INTO `rol_permisos` VALUES (36,5,89);
INSERT INTO `rol_permisos` VALUES (37,5,81);
INSERT INTO `rol_permisos` VALUES (38,5,80);
INSERT INTO `rol_permisos` VALUES (39,5,82);
INSERT INTO `rol_permisos` VALUES (40,5,83);
INSERT INTO `rol_permisos` VALUES (41,3,127);
INSERT INTO `rol_permisos` VALUES (42,3,128);
INSERT INTO `rol_permisos` VALUES (43,3,129);
INSERT INTO `rol_permisos` VALUES (46,3,130);
INSERT INTO `rol_permisos` VALUES (47,3,131);
INSERT INTO `rol_permisos` VALUES (48,3,135);
INSERT INTO `rol_permisos` VALUES (49,3,136);
INSERT INTO `rol_permisos` VALUES (50,5,137);
INSERT INTO `rol_permisos` VALUES (51,5,138);
INSERT INTO `rol_permisos` VALUES (52,5,132);
INSERT INTO `rol_permisos` VALUES (53,5,133);
INSERT INTO `rol_permisos` VALUES (54,5,134);
INSERT INTO `rol_permisos` VALUES (55,5,129);
INSERT INTO `rol_permisos` VALUES (56,3,141);
INSERT INTO `rol_permisos` VALUES (57,3,146);
INSERT INTO `rol_permisos` VALUES (58,3,81);
INSERT INTO `rol_permisos` VALUES (59,3,68);
INSERT INTO `rol_permisos` VALUES (60,3,149);

SET FOREIGN_KEY_CHECKS = 1;
