-- =====================================================================================
-- V5: urbano o interurbano lo decide el sistema, no el motorizado.
-- Un servicio es interurbano cuando el motorizado sale de la ciudad base de su delegación hacia otro municipio
-- (pedido del 12-11-2026: «si sale de Bogotá a un servicio fuera de Bogotá»).
-- Cada municipio queda marcado según su nombre: las zonas de la ciudad base ("CHAPINERO (BOGOTA)", "NORTE (BARRANQUILLA)")
-- y la ciudad misma ("BOGOTA", "MEDELLIN (ANTIOQUIA)") son urbanas; el resto ("SOACHA (CUNDINAMARCA)", "SOLEDAD
-- (ATLANTICO)", "PUERTO COLOMBIA") es interurbano. La marca se puede corregir en Catálogos → Municipios.
-- =====================================================================================

ALTER TABLE municipio
  ADD COLUMN interurbano TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = queda fuera de la ciudad base de su delegación: los servicios allí son interurbanos';

UPDATE municipio mu
  JOIN delegacion d ON d.id_delegacion = mu.id_delegacion
   SET mu.interurbano = NOT (TRIM(SUBSTRING_INDEX(mu.nombre_municipio, '(', 1)) = d.nombre_delegacion
                         OR mu.nombre_municipio LIKE CONCAT('%(', d.nombre_delegacion, ')%'));
