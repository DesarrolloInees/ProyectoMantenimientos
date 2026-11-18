# Distancias por carretera (OSRM)

Las rutas del día se arman con la **distancia real por las vías** entre los puntos, no en línea recta. Esa distancia
la calcula un servidor [OSRM](https://project-osrm.org/) propio con el mapa de Colombia de OpenStreetMap: no se paga
por consulta y las coordenadas de los clientes no salen a ningún servicio externo.

## Montarlo (una vez)

Requiere Docker en el servidor (o en el equipo donde corre la plataforma).

1. `powershell -ExecutionPolicy Bypass -File preparar.ps1` descarga el mapa (~300 MB) y lo procesa (15-30 min,
   ~6 GB de RAM mientras corre). Deja todo en `osrm/datos/` (ignorada por git).
2. `docker compose up -d` levanta el servidor en `http://localhost:5000` (solo escucha en el propio equipo). En
   uso necesita ~1-2 GB de RAM.
3. Arrancar el backend con:
   ```
   OSRM_URL=http://localhost:5000
   OSRM_MAX_TABLA=200
   ```
   (`OSRM_MAX_TABLA` debe ser igual al `--max-table-size` de `docker-compose.yml`).

Para actualizar el mapa (vías nuevas), borrar `datos/colombia-latest.osm.pbf`, volver a correr `preparar.ps1` y
`docker compose restart`. Basta con hacerlo cada pocos meses.

## Cómo lo usa la plataforma

- Al generar rutas, al recalcular una ruta y al recalcular los tramos tras un cambio manual, el backend pide a OSRM
  la matriz de distancias de todos los puntos involucrados (servicios, candidatos de relleno y puntos de partida),
  en bloques de 100 × 100. Con unos 700 puntos son ~50 consultas y tarda pocos segundos.
- **Solo se usa la distancia.** El tiempo de cada tramo sigue saliendo de `velocidad_promedio_kmh` (25 km/h, medida con
  moto y tráfico), ahora sobre los km reales. Los tiempos de OSRM son de carro sin tráfico y serían irreales en
  ciudad.
- `factor_ruta_vial` (1,35) ya no se usa para los tramos que OSRM calcula; queda solo como respaldo.
- **Respaldo automático:** si `OSRM_URL` está vacía, si el servidor no responde o si entre dos puntos no hay vía, ese
  tramo se calcula como antes (línea recta × `factor_ruta_vial`). Generar rutas nunca falla por OSRM; queda un aviso
  en el log (`OSRM no respondió ...`). Cada cálculo correcto deja en el log `OSRM: N puntos, M consultas, ...`.
- Las distancias pueden ser distintas de ida y de vuelta (vías de un solo sentido); el optimizador lo respeta.

## Limitaciones

- OSRM no trae perfil de moto: se usa el de carro. Respeta sentidos y vías no transitables, pero no conoce atajos
  que solo usa una moto ni el tráfico (el tiempo lo cubre la velocidad promedio).
- La verificación de «lejos del punto» en el celular sigue en línea recta a propósito: mide qué tan lejos está el
  GPS del motorizado de la máquina, no un recorrido.
