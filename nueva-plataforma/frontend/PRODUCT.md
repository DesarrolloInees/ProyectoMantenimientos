# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Oficina** (Supervisor de motorizados, Auxiliar, SuperUsuario): en computador de escritorio, a diario. Cargan el
  Excel de Prosegur, ubican máquinas, arman y publican las rutas, hacen seguimiento en vivo, corrigen y valorizan
  servicios, manejan inventario, remisiones, personal, costos, reportes y administración.
- **Motorizados (técnicos de campo)**: en el celular, en la calle y muchas veces a pleno sol, entre un punto y otro.
  Siguen su día y registran cada servicio paso a paso con fotos, firma, repuestos y novedades.
- Confirmado (12-11-2026): los dos públicos pesan igual en las decisiones de diseño.
- Roles fijos (12-11-2026): Superusuario, Supervisor de motorizados (revisor), Auxiliar de oficina y Motorizado.
  El Supervisor hace lo del Auxiliar y además aprueba lo del personal y marca máquinas VIP; costos y administración
  solo el Superusuario. Se pidió una plataforma más sencilla y didáctica: cada rol entra a un inicio guiado.

## Product Purpose

Plataforma interna de INEES S.A.S. para el mantenimiento de máquinas de manejo de efectivo de Prosegur en Colombia:
de la solicitud diaria de Prosegur a la ruta del motorizado, el servicio registrado con evidencia, y los reportes,
la facturación y los costos que salen de ahí. Reemplaza a la aplicación PHP anterior. Éxito: la oficina programa y
controla el día sin hojas de cálculo aparte, y el motorizado completa cada servicio sin saltarse pasos.

## Operating Context

- Ciclo diario: Excel de Prosegur del día siguiente → pines de ubicación → bolsa con prioridades (solicitud Prosegur,
  arrastrado, VIP, vencido) → rutas óptimas por motorizado → publicación → flujo guiado en campo (llegada, espera
  máxima de 45 min, ingreso, seriales, estados, trabajo, repuestos, novedad, cierre) → seguimiento y reportes.
- Documentos que salen de la plataforma: PDF de cada servicio, reporte técnico, reporte ejecutivo, Excel maestro de
  la programación, reportes de repuestos, tarifas, trazabilidad y costos.
- Delegaciones en todo el país; los motorizados trabajan con datos móviles.

## Capabilities and Constraints

- Frontend Angular 22 con Angular Material; backend Spring Boot. Permisos por módulo (`rol_permiso`).
- Idioma: español de Colombia; moneda en pesos sin decimales; fechas dd/mm/aaaa.
- Pendiente: modo sin conexión para el celular.

## Brand Commitments

- Línea gráfica de **inees.com.co** (pedido del usuario el 12-11-2026): kit de marca «INEES Secure Ai Systems» del
  3-11-2026 — azul noche #072942, azul de la nube #0B5591, verde de la nube #2D7F3E, verde de Automatización #4BA844,
  grafito #182A36, azul claro #368ABA solo para detalles; tipografía Archivo; recurso gráfico de las tres franjas
  diagonales del corte de la nube; logos `inees-logo.svg` (sobre claro) e `inees-logo-sobre-azul.svg`.
- El logo nuevo va en la app y en los PDF.
- Los colores de estado conservan su significado, con tonos de la paleta de la marca; rojo y ámbar solo para
  alertas reales.

## Evidence on Hand

- Activos de marca del sitio (logos SVG, favicon) descargados del sitio público inees.com.co.
- Datos reales de operación en la base de desarrollo `inees_plataforma`.

## Product Principles

1. El trabajo primero: la marca se reconoce en los detalles, nunca a costa de la tarea.
2. El celular se usa al sol y en movimiento: contraste alto, objetivos grandes, un paso a la vez.
3. Ningún paso se salta y todo queda con evidencia: la interfaz hace visible en qué paso y en qué estado está cada cosa.
4. Lo que funciona en PHP se mantiene; lo nuevo se explica en el lenguaje de la operación.
