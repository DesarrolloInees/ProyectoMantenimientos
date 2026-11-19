## Valores y convenciones importantes

Reglas que no están escritas en el esquema pero que todo el código respeta. Romperlas produce datos que las pantallas no muestran o muestran mal.

### Borrado lógico (`estado`)

Casi ninguna tabla de negocio borra filas: se marca `estado = 0`. Las pantallas de "Ver" filtran `estado = 1`.

| Tabla | Activo | Inactivo / eliminado |
| --- | --- | --- |
| `cliente`, `punto`, `maquina`, `tecnico`, `delegacion`, `tipo_maquina`, `tipo_mantenimiento`, `tipo_novedad`, `repuesto`, `inventario_tecnico`, `costos_operativos`, `gastos_administrativos` | `1` | `0` |
| `usuarios` | `'activo'` (texto) | `'inactivo'` |

Excepciones que sí hacen `DELETE` real: `calificacion_servicio`, `estado_maquina`, `modalidad_operativa`, `tipousuario` (catálogos pequeños) y las órdenes en estado programado (`ordenes_servicio` con `estado = 2`) al quitarlas del consolidado.

### Estados de `ordenes_servicio.estado`

| Valor | Significado | Dónde se usa |
| --- | --- | --- |
| `2` | Programada (pendiente de visita) | `programacionCrear` la crea, `programacionConsolidado` la edita o la borra, el técnico la ve en `tecnicoProgramacion` |
| `1` | Realizada (el técnico la reportó) | Reportes, facturación, historial |
| `0` | Anulada | Se excluye con `estado != 0` en reportes |

### Otros catálogos con significado fijo

| Tabla | Valores |
| --- | --- |
| `tipousuario` (rol, columna `usuarios.nivel_acceso`) | 1 SuperUsuario, 2 Auxiliar, 3 Técnico, 4 Prosegur, 5 Supervisor Motorizados |
| `modalidad_operativa` | 1 URBANO, 2 INTERURBANO |
| `estados_aprobacion_he` (horas extra) | 1 Pendiente, 2 Aprobada, 3 Rechazada |
| `estados_remision` | 1 USADA, 2 ANULADA, 3 DISPONIBLE, 4 KISAN, 5 INSTALACIÓN, 6 DESINSTALACIÓN, 7 CAMBIO DE MÁQUINA, 8 INSTALACIÓN FALLIDA, 9 DESANCLAJE, 10 ANCLAJE, 11 INSTALACIÓN MÁS CAPACITACIÓN |
| `monitoreo_motorizados_live.estado_actual` | `'En Progreso'`, `'Finalizado'` |

### Tabla `parametros`

Configuración que se cambia sin tocar código (se lee con `core/Parametros`).

| Clave | Valor actual | Uso |
| --- | --- | --- |
| `Recargo_Servicios_Interurbanos` | 162000 | Valor de viáticos por día cuando el servicio es fuera de la delegación (`ordenCrear`, `ordenDetalle`, `tecnicoReporte`) |
| `meses_restriccion_prosegur` | 1 | Meses recientes que el rol Prosegur no puede descargar en PDF (`pdfServicio`, `serviciosPdf`) |
| `tiempos_mantenimiento_minutos` | `{"1": 45, "2": 60, "3": 80, "4": 20}` | Tiempo meta por tipo de mantenimiento (alertas de Telegram de la tarea programada) |
| `password_vigencia_dias` | 60 | Definido pero **no usado** por el código (ni PHP ni Java) |
| `intentos_maximos` | 5 | Definido pero **no usado** |
| `session_timeout_minutos` | 30 | Definido pero **no usado**; la sesión dura lo que diga `SESSION_TIMEOUT` |

### Fechas, horas y archivos

- Fechas en columnas `DATE` (`YYYY-MM-DD`) y horas en `TIME` (`HH:MM:SS`), hora de Colombia (America/Bogota). El backend las devuelve como texto con ese mismo formato (`core/Db`).
- Valores de dinero en `DECIMAL(12,2)`; el backend los devuelve como texto (`"12500.00"`), igual que PHP.
- Las columnas que guardan archivos (`evidencia_servicio`, `evidencia_horas_extra`, `facturas_parqueadero`, `tecnico.ruta_firma`, etc.) guardan una **ruta relativa** a la raíz del proyecto PHP (por ejemplo `uploads/imagenes_servicios/2026/...`). Nunca guardar rutas absolutas: ver [Backend · Archivos](04-backend.md#archivos-subidas-y-fotos).
- Contraseñas en `usuarios.password_hash` con bcrypt `$2y$` (compatible entre PHP y Java).

## Regenerar este documento

Si se agrega una tabla o columna, este archivo se puede regenerar desde la base local con el script `documentacion/herramientas/generar-diccionario-bd.ps1`:

```powershell
cd nueva-plataforma\documentacion\herramientas
powershell -ExecutionPolicy Bypass -File .\generar-diccionario-bd.ps1
```

El script lee `information_schema`, reescribe las secciones generadas (diagramas y diccionario) y conserva estas dos últimas secciones, que están en `herramientas/bd-convenciones.md`. Si cambia el significado de un estado o un parámetro, actualizar ese archivo a mano.
