-- =====================================================================================================================
-- Verificación del corte (PHP → plataforma nueva). Correr ANTES de arrancar la plataforma nueva sobre la base de PHP y
-- DESPUÉS de que Flyway aplique V2…V6, y comparar. Ensayo del 12-11-2026 con el volcado del 06-11-2026: todo cuadró.
--   mariadb -uroot <base> < verificar_corte.sql
-- Antes del corte la columna de minutos se calcula del texto tiempo_servicio (la consulta 1b falla después del corte,
-- porque V3 la retira); después, de minutos_servicio (la 1a falla antes). Correr la que aplique.
-- =====================================================================================================================

-- 1a. Servicios después del corte (deben coincidir con 1b de antes)
SELECT COUNT(*) AS ordenes, SUM(estado = 1) AS finalizadas, ROUND(SUM(valor_servicio)) AS valor,
       ROUND(SUM(valor_viaticos)) AS viaticos, SUM(minutos_servicio) AS minutos
FROM ordenes_servicio;

-- 1b. Servicios antes del corte
SELECT COUNT(*) AS ordenes, SUM(estado = 1) AS finalizadas, ROUND(SUM(valor_servicio)) AS valor,
       ROUND(SUM(valor_viaticos)) AS viaticos,
       SUM(CASE WHEN tiempo_servicio REGEXP '^[0-9]+:[0-9]{2}$'
                THEN SUBSTRING_INDEX(tiempo_servicio, ':', 1) * 60 + SUBSTRING_INDEX(tiempo_servicio, ':', -1) END) AS minutos
FROM ordenes_servicio;

-- 2. Stock de los técnicos y repuestos usados (después: el kardex debe sumar lo mismo que el stock, sin descuadres)
SELECT SUM(cantidad_actual) AS stock_total, COUNT(*) AS filas_stock FROM inventario_tecnico;
SELECT SUM(cantidad) AS repuestos_usados FROM orden_servicio_repuesto;
-- (solo después del corte)
SELECT (SELECT SUM(cantidad) FROM movimiento_inventario) AS kardex,
       (SELECT COUNT(*) FROM inventario_tecnico i
         WHERE i.cantidad_actual <> COALESCE((SELECT SUM(m.cantidad) FROM movimiento_inventario m
                                              WHERE m.id_tecnico = i.id_tecnico AND m.id_repuesto = i.id_repuesto), 0)) AS descuadres;

-- 3. Remisiones por estado y máquinas
SELECT id_estado, COUNT(*) FROM control_remisiones GROUP BY id_estado ORDER BY id_estado;
SELECT COUNT(*) AS maquinas FROM maquina;

-- 4. Texto intacto (las sumas MD5 deben ser iguales antes y después)
SET SESSION group_concat_max_len = 1000000000;
SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', nombre_punto, direccion) ORDER BY id_punto)) AS puntos FROM punto;
SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', id_ordenes_servicio, actividades_realizadas) ORDER BY id_ordenes_servicio)) AS actividades
FROM ordenes_servicio;
SELECT MD5(GROUP_CONCAT(nombre_tecnico ORDER BY id_tecnico)) AS tecnicos FROM tecnico;
SELECT MD5(GROUP_CONCAT(CONCAT_WS('|', nombre, cargo, email) ORDER BY usuario_id)) AS usuarios FROM usuarios;
