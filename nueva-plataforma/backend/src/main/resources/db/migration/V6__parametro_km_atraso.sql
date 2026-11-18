-- V6: las vencidas de relleno se eligen por cercanía con un premio por atraso (Optimizador.rellenar).
-- Cada día de atraso equivale a este número de km de desvío (hasta 120 días). 0 = solo cercanía.
INSERT IGNORE INTO parametros (clave, valor, descripcion) VALUES
  ('km_por_dia_atraso', '0.25',
   'Rutas: km de desvío que vale cada día de atraso de un mantenimiento vencido al elegir con qué completar la jornada (0 = solo cercanía)');
