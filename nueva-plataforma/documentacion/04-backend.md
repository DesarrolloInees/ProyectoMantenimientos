# 4. Backend (Spring Boot)

El backend es una aplicación Spring Boot 4.1.2 con Java 21, en `nueva-plataforma/backend`. No usa JPA ni entidades: cada controlador ejecuta SQL escrito a mano mediante la clase `core/Db`, con las mismas consultas que tenía el PHP. Esa decisión mantiene idénticos los datos que recibe el frontend y facilita comparar con el código PHP original.

## Estructura de paquetes

```text
co.inees.mantenimientos
├── InneesApplication.java      ← punto de entrada (@SpringBootApplication, @EnableScheduling)
├── core/                       ← infraestructura compartida (ver tabla abajo)
└── modulos/
    ├── admin/                  ← catálogos, clientes, puntos, máquinas, repuestos, tarifas, remisiones, inventario, festivos, novedades
    ├── asistencia/             ← procesador de huellero → Excel de nómina
    ├── costos/                 ← costos de motorizados y administrativos
    ├── cronometro/             ← cronómetro de servicios, monitoreo, reportes
    ├── herramientas/           ← cargas iniciales por CSV (antes helps/*.php)
    ├── horaextra/              ← horas extra con evidencias
    ├── importar/               ← importaciones masivas de Excel
    ├── inicio/                 ← tablero de inicio
    ├── inventario/             ← solicitudes de inventario
    ├── login/                  ← login, sesión, recuperación de contraseña
    ├── orden/                  ← órdenes de servicio, detalle, reportes, PDF
    ├── parqueadero/            ← facturas de parqueadero
    ├── programacion/           ← programación, consolidado, mapa, planeación
    ├── reportes/               ← tablero ejecutivo, facturación, informes
    ├── supervisor/             ← panel en vivo y alertas logísticas
    ├── tareas/                 ← tareas programadas (reemplazo de los cron PHP)
    ├── tecnico/                ← programación y reporte del técnico
    ├── transporte/             ← transportes
    ├── turno/                  ← marcación de entrada y reportes de turnos
    ├── usuario/                ← usuarios
    └── varios/                 ← info de BD, exportar, geolocalizar, rastreo
```

Cada módulo tiene uno o más `*Controller.java` y, cuando el PHP tenía un modelo grande, un `*Modelo.java` (`@Service` o `@Component`) con las consultas. En módulos pequeños el SQL está en el mismo controlador.

## Ciclo de una petición

```mermaid
sequenceDiagram
    participant A as Angular (Api)
    participant I as RbacInterceptor
    participant C as Controlador
    participant D as core/Db
    participant BD as MariaDB
    A->>I: POST /api/ordenVer?accion=ajaxListar (cookie PHPSESSID_INEES)
    I->>I: página = "ordenVer"; ¿sesión? ¿rol con permiso?
    alt sin sesión
        I-->>A: 401 {redirect: "login"}
    else sin permiso o página inexistente
        I-->>A: 404 {success:false, message:"Página no encontrada o acceso denegado."}
    end
    I->>C: método con @PostMapping(value="/api/ordenVer", params="accion=ajaxListar")
    C->>D: db.list("SELECT ... WHERE fecha = :fecha", "fecha", f)
    D->>BD: consulta con parámetros
    BD-->>D: filas
    D-->>C: List<Map<String,Object>> (mismos tipos que PDO)
    C-->>A: Map → JSON  (o ResponseEntity<byte[]> para archivos)
```

1. **Enrutamiento.** Spring elige el método por la URL y el parámetro `accion`: `@PostMapping(value = "/api/ordenVer", params = "accion=ajaxListar")`. Si llega una `accion` que no tiene método, Spring responde 404 o 400.
2. **Permisos.** `core/RbacInterceptor` corre antes de cualquier `/api/**` (ver [08-permisos-y-roles.md](08-permisos-y-roles.md)).
3. **Parámetros.** El método recibe `Req req`, que da acceso a GET, POST, archivos, JSON y sesión con la semántica de PHP.
4. **Respuesta.** Devolver un `Map` produce JSON. Para archivos se devuelve `ResponseEntity<byte[]>` con `Descarga`.
5. **Errores.** Cualquier excepción no capturada la convierte `core/ApiErrores` en `{"success": false, "status": "error", "message": "Error: ..."}` **con HTTP 200**, como hacían los `try/catch` del PHP. La traza completa queda en el log del backend.

### Dos estilos de anotación (los dos válidos)

```java
// Estilo 1: URL completa en cada método
@RestController
public class CostosController {
    @GetMapping(value = "/api/costosVer", params = "accion=index")
    public Map<String, Object> index(Req req) { ... }
}

// Estilo 2: prefijo en la clase
@RestController
@RequestMapping("/api/programacionCrear")
public class ProgramacionCrearController {
    @PostMapping(params = "accion=previsualizar")
    public Map<String, Object> previsualizar(Req req) { ... }
}
```

### Formato de las respuestas

Cada módulo conserva **las mismas claves que devolvía su PHP**, porque el frontend las espera así. Por eso no hay un formato único: según el módulo verá `{exito, mensaje, error}`, `{success, message}`, `{status: "success"|"error", msg}` o `{ok, msj}`. Al agregar una acción a un módulo existente, use las claves que ya usa ese módulo. Para módulos nuevos, use:

```json
{ "exito": true, "mensaje": "Texto para el usuario", "datos": { } }
{ "exito": false, "error": "Qué salió mal" }
```

## Referencia de `core/`

| Clase | Qué hace | Equivalente PHP |
| --- | --- | --- |
| `Db` | Consultas SQL con parámetros con nombre y transacciones | PDO |
| `Req` | Parámetros GET/POST (con arreglos `a[b][]`), archivos y cuerpo JSON | `$_GET`, `$_POST`, `$_FILES`, `php://input` |
| `Sesion` | Lectura y escritura de la sesión con las claves del PHP | `$_SESSION` |
| `Rbac`, `RbacInterceptor` | Permisos por página | Router de `index.php` |
| `Conv` | Conversiones tolerantes: `toInt`, `toDouble`, `empty`, `str`, `h`, `numberFormat`, `pesos` | `(int)`, `empty()`, `htmlspecialchars`, `number_format` |
| `F` | Fechas en zona America/Bogota: `hoy`, `ahora`, `dmy`, `hm`, `fecha`, `primeroMes`, `ultimoMes` | `date()`, `strtotime()` |
| `Excel` | Crear .xlsx con estilos, fórmulas, celdas combinadas e imágenes | PhpSpreadsheet (escritura) |
| `ExcelLector` | Leer un .xlsx subido como filas columna→valor | PhpSpreadsheet `toArray()` |
| `Pdf` | HTML → PDF con Chrome sin ventana (respaldo: OpenHTMLtoPDF) | Browsershot, Dompdf, mPDF |
| `Descarga` | Respuestas de archivo (`excel`, `pdf`, `html`) con su nombre | `header('Content-Disposition...')` |
| `Archivos` | Guardar, leer, borrar y optimizar archivos en las carpetas del PHP | `move_uploaded_file`, GD |
| `Correo` | Enviar correos HTML con copia y adjuntos | PHPMailer |
| `Telegram` | Enviar mensajes a un chat de Telegram | `curl` a la API de Telegram |
| `Parametros` | Leer la tabla `parametros` | `obtenerParametro()` |
| `Hash` | bcrypt compatible con `password_hash`/`password_verify` | `password_hash()` |
| `ResumenTiposServicio` | Clasificar y resumir tipos de servicio | `app/helpers/resumenTiposServicio.php` |
| `ApiErrores` | Convierte excepciones en JSON de error | `try/catch` de cada controlador |
| `SpaController` | Devuelve `index.html` para las URLs de Angular (`/inicio`, `/ordenDetalle/2026-01-10`) | `.htaccess` + `index.php` |
| `WebConfig` | Registra el interceptor, el inyector de `Req`/`Sesion` y sirve las carpetas de archivos | — |

### `Db`: base de datos

```java
private final Db db;
public MiController(Db db) { this.db = db; }   // inyección por constructor

// Varias filas (List<Map>), parámetros como pares nombre/valor
List<Map<String, Object>> filas = db.list(
    "SELECT id_punto, nombre_punto FROM punto WHERE id_cliente = :cli AND estado = 1",
    "cli", idCliente);

// Una fila o null
Map<String, Object> punto = db.one("SELECT * FROM punto WHERE id_punto = :id", "id", id);

// Un valor
long total = db.scalarLong("SELECT COUNT(*) FROM maquina WHERE id_punto = :id", "id", id);
Object nombre = db.scalar("SELECT nombre_punto FROM punto WHERE id_punto = :id", "id", id);

// Una columna como lista
List<Object> ids = db.column("SELECT id_tecnico FROM tecnico WHERE estado = 1");

// INSERT que devuelve el id nuevo
long id = db.insert("INSERT INTO repuesto (nombre_repuesto, estado) VALUES (:n, 1)", "n", nombre);

// UPDATE/DELETE: devuelve filas afectadas
int n = db.update("UPDATE punto SET estado = 0 WHERE id_punto = :id", "id", id);

// Parámetros en un Map (útil para SQL dinámico)
Map<String, Object> p = new LinkedHashMap<>();
StringBuilder sql = new StringBuilder("SELECT * FROM ordenes_servicio WHERE 1=1");
if (!fecha.isEmpty()) { sql.append(" AND fecha_visita = :f"); p.put("f", fecha); }
db.list(sql.toString(), p);

// IN (...) con lista
String in = Db.in("t", List.of(3, 5, 8), p);         // ":t0,:t1,:t2" y llena p
db.list("SELECT * FROM tecnico WHERE id_tecnico IN (" + in + ")", p);

// Transacción: si algo lanza excepción, se revierte todo
long idOrden = db.tx(() -> {
    long nuevo = db.insert("INSERT INTO ordenes_servicio (...) VALUES (...)", ...);
    db.update("INSERT INTO ordenes_servicio_complemento (id_orden_servicio, ...) VALUES (:id, ...)", "id", nuevo);
    return nuevo;
});
db.txRun(() -> { ... });   // igual, sin valor de retorno
```

Tipos que devuelve (iguales a PDO, para que el JSON no cambie): `DATE`/`TIME`/`DATETIME` como texto (`"2026-11-19"`, `"07:30:00"`), `DECIMAL` como texto (`"12500.00"`), enteros como `Integer`/`Long`, `NULL` como `null`.

**Reglas:** siempre parámetros `:nombre`, nunca concatenar valores del usuario en el SQL. Si una parte del SQL depende del usuario (columna de orden, dirección ASC/DESC), validarla contra una lista fija antes de concatenarla.

### `Req`: parámetros de la petición

```java
@PostMapping(value = "/api/miPagina", params = "accion=guardar")
public Map<String, Object> guardar(Req req) {
    String nombre = req.trim("nombre");              // trim($_POST['nombre'] ?? '')
    String fecha  = req.str("fecha", F.hoy());       // con valor por defecto
    int idPunto   = req.intv("id_punto");            // (int) $_POST['id_punto'], 0 si falta
    Integer idMaq = req.intOrNull("id_maquina");     // null si viene vacío
    double valor  = req.dbl("valor");
    List<String> ids = req.strs("ids");              // ids[] → lista
    Map<String, Object> filtros = req.mapa("filtros"); // filtros[fecha]=...&filtros[tecnico]=...
    Object x = req.en("order", "0", "column");       // $_POST['order'][0]['column']
    Map<String, Object> cuerpo = req.json();         // si el Content-Type es application/json
    MultipartFile foto = req.file("foto");           // un archivo (o null)
    List<MultipartFile> fotos = req.files("fotos");  // fotos[] o varias "fotos"
    int usuario = req.sesion().usuarioId();          // usuario en sesión
    ...
}
```

`Req` mezcla los parámetros de la URL y del cuerpo, igual que `$_REQUEST`. Las claves con corchetes (`fotos[3][desc]`) se convierten en mapas anidados.

### `Sesion`

```java
Sesion s = req.sesion();
s.usuarioId();      // usuario_id
s.nivel();          // nivel_acceso (rol)
s.nombre();         // usuario_name
s.cargo();          // usuario_cargo
s.logueado();
s.set("mi_clave", valor); s.get("mi_clave"); s.remove("mi_clave");
```

Se puede guardar cualquier objeto Java en la sesión (por ejemplo, filas de un Excel entre dos pasos de una importación). La sesión vive en memoria: no guarde en ella cosas enormes y recuerde que se pierde al reiniciar.

### Archivos: subidas y fotos

Las rutas en la base de datos son **relativas a `LEGACY_ROOT`** (la raíz del proyecto PHP). `Archivos` trabaja siempre con esas rutas relativas y no permite salir de la raíz.

```java
private final Archivos archivos;

// Guardar tal cual
String rel = "app/uploads/transportes/" + Archivos.uniqid() + "." + Archivos.extension(f.getOriginalFilename());
archivos.guardar(f, rel);                       // devuelve la ruta relativa
db.update("UPDATE ... SET foto = :r WHERE ...", "r", rel);

// Foto optimizada: máx. 1280 px de ancho, JPEG calidad 75
archivos.guardarImagenOptimizada(f, rel, 1280, 75);
// Igual, corrigiendo orientación EXIF y forzando vertical (fotos del reporte del técnico)
archivos.guardarImagenOptimizada(f, rel, 1280, 75, true);
// Solo corrige orientación EXIF (horas extra, parqueaderos)
archivos.guardarImagenOptimizadaExif(f, rel, 1280, 75);

archivos.existe(rel); archivos.leer(rel); archivos.borrar(rel);
Path abs = archivos.resolver(rel);              // ruta absoluta en disco
Archivos.limpiarNombre("Juan Pérez");           // "Juan_Perez": sin tildes ni espacios, para carpetas
```

Se sirven al navegador con la misma URL relativa: una foto guardada como `uploads/imagenes_servicios/2026/x.jpg` se ve en `/uploads/imagenes_servicios/2026/x.jpg`. Las carpetas publicadas son `uploads/`, `app/uploads/`, `imagenes/`, `app/imagenes/`, `app/logos/` y `app/temp/` (`core/WebConfig`). Si guarda en otra carpeta, agréguela ahí.

### `Excel`: generar hojas de cálculo

API pequeña al estilo PhpSpreadsheet sobre Apache POI. Los estilos se acumulan por celda y se aplican al final.

```java
try (Excel x = new Excel("Reporte")) {                 // crea el libro con una hoja
    x.set("A1", "REPORTE DE SERVICIOS").merge("A1:E1")
     .estilo("A1:E1", Excel.E.of().bold().size(14).color("FFFFFFFF").fill("FF1F4E78").center());
    String[] enc = {"Fecha", "Punto", "Técnico", "Valor", "Total"};
    for (int i = 0; i < enc.length; i++) x.set(Excel.col(i + 1) + "3", enc[i]);
    x.estilo("A3:E3", Excel.E.of().bold().fill("FFD9E1F2").borde());
    int fila = 4;
    for (Map<String, Object> r : filas) {
        x.set("A" + fila, r.get("fecha_visita"));
        x.set("B" + fila, r.get("nombre_punto"));
        x.setTexto("C" + fila, r.get("codigo"));         // fuerza texto (no número)
        x.set("D" + fila, Conv.toDouble(r.get("valor_servicio")));
        x.set("E" + fila, "=D" + fila + "*1.19");       // un texto que empieza por "=" es fórmula
        fila++;
    }
    x.estilo("D4:E" + (fila - 1), Excel.E.of().formato("#,##0"));
    x.ancho("B", 40).congelar("A4").autoFiltro("A3:E" + (fila - 1));
    x.nuevaHoja("Resumen");                             // otra hoja (pasa a ser la actual)
    x.imagen(archivos.resolver("app/logos/logoInees.jpg"), "A1", 60, 0, 0);
    return Descarga.excel(x.bytes(), "Reporte_Servicios.xlsx");
}
```

Estilos disponibles en `Excel.E`: `bold`, `italic`, `size`, `color` (ARGB), `fill` (ARGB), `borde()` / `borde("medium")`, `bordeExterior`, `bordeColor`, `center`, `h("left|center|right")`, `v("top|center|bottom")`, `wrap`, `formato("#,##0.00" | "0%" | "[h]:mm" ...)`, `fuente`.

Para que Excel recalcule las fórmulas al abrir: `x.wb().setForceFormulaRecalculation(true)`.

### `ExcelLector`: leer un Excel subido

```java
MultipartFile f = req.file("archivo_excel");
List<Map<String, String>> filas = ExcelLector.leer(f.getInputStream(), "Hoja1"); // null = hoja activa
// filas.get(0) es la fila 1; cada fila es columna → valor como texto formateado
for (int i = 1; i < filas.size(); i++) {
    String device = filas.get(i).getOrDefault("A", "").trim();
    String punto  = filas.get(i).getOrDefault("C", "").trim();
}
```

Las fórmulas se evalúan y los números salen como los muestra Excel. Para importaciones grandes, ver el patrón por lotes de `modulos/importar/ImportarController` (las filas se guardan en sesión y el frontend las procesa de 200 en 200).

### `Pdf` y `Descarga`: generar PDF

El PDF se arma como **HTML + CSS** y Chrome lo imprime. Chrome espera hasta 15 segundos a que corra el JavaScript de la página, así que una plantilla puede usar Tailwind por CDN o dibujar gráficas antes de imprimirse.

```java
private final Pdf pdf;

String css = new String(new ClassPathResource("plantillas/mi-reporte.css").getInputStream().readAllBytes(), UTF_8);
String logo = pdf.dataUri("app/logos/logoInees.jpg");          // imagen incrustada en base64
StringBuilder h = new StringBuilder();
h.append("<!doctype html><html><head><meta charset='utf-8'><style>").append(css).append("</style></head><body>");
h.append("<img src='").append(logo).append("' height='50'>");
h.append("<h1>").append(Conv.h(titulo)).append("</h1>");       // escapar siempre con Conv.h
...
h.append("</body></html>");
byte[] b = pdf.generar(h.toString(), "size: A4 landscape; margin: 12mm");
return Descarga.pdf(b, "Reporte.pdf", true);                    // true = abrir en el navegador; false = descargar
```

- Plantillas existentes en `src/main/resources/plantillas/`: `servicio-pdf.css`, `cronometro-pdf.css`, `hora-extra-pdf.css`, `transporte-pdf.css` y `reporte-ejecutivo.html` (plantilla completa que recibe los datos como JSON y los pinta con JavaScript).
- Pies de página con número de página: usar las cajas de margen de `@page` (`@bottom-center { content: counter(page) }`).
- Imágenes del disco: siempre con `pdf.dataUri(rutaRelativa)`; Chrome no puede leer rutas locales.
- Sin Chrome (`CHROME_PATH` inválido) se usa OpenHTMLtoPDF, que no ejecuta JavaScript ni soporta todo el CSS moderno.

### `Correo` y `Telegram`

```java
private final Correo correo;
correo.enviar(List.of("destino@empresa.com"), List.of("copia@empresa.com"),
              "Asunto", "<h2>Hola</h2><p>Contenido HTML</p>", "Texto plano alternativo",
              Map.of("reporte.pdf", bytesPdf));                 // adjuntos: nombre → bytes (o null); cc también puede ser null
correo.enviar("destino@empresa.com", "Asunto", "<p>HTML</p>"); // forma corta

private final Telegram telegram;
if (telegram.configurado()) telegram.enviar("<b>Alerta</b>\nTexto con HTML básico de Telegram");
```

`Correo.enviar` lanza excepción si el SMTP falla: capturarla y responder un error entendible. `Telegram.enviar` nunca lanza excepción (registra un aviso y devuelve `null`).

### `Conv` y `F`: utilidades

```java
Conv.toInt("12abc");          // 12 (como (int) en PHP)
Conv.empty("0");              // true (como empty() en PHP)
Conv.str(null);               // ""
Conv.h("<b>");                // "&lt;b&gt;" (htmlspecialchars)
Conv.pesos(1234567);          // "$ 1.234.567"
Conv.numberFormat(1234.5, 2, ",", ".");   // "1.234,50"

F.hoy();                      // "2026-11-19" (America/Bogota)
F.ahora();                    // "2026-11-19 14:05:00"
F.dmy("2026-11-19");          // "19/11/2026"
F.hm("07:30:00");             // "07:30"
F.primeroMes(); F.ultimoMes();
F.fecha("2026-11-19");        // LocalDate (null si no es válida)
F.esYmd(texto);               // valida formato AAAA-MM-DD
```

### `Parametros`

```java
private final Parametros parametros;
String v = parametros.obtener("Recargo_Servicios_Interurbanos", "0");
```

## Tareas programadas

`modulos/tareas/TareasProgramadas.java` usa `@Scheduled` (habilitado con `@EnableScheduling` en `InneesApplication`). Cada tarea revisa su bandera (`CRON_ALERTAS`, `CRON_TELEGRAM`) antes de hacer algo, y la frecuencia sale de la configuración ([03-configuracion.md](03-configuracion.md#tareas-programadas)):

| Método | Reemplaza a | Qué hace |
| --- | --- | --- |
| `alertasLogisticas()` | `cron_alertas.php` | Llama a la misma lógica de `/api/notificacionesLogistica?accion=procesarNotificaciones` (visitas frecuentes y desplazamientos largos de los últimos 7 días) y envía el correo a los supervisores |
| `alertasTelegram()` | `cron_alertas_telegram.php` | Busca servicios "En Progreso" en `monitoreo_motorizados_live` que superan su tiempo meta (`parametros.tiempos_mantenimiento_minutos`), envía la alerta por Telegram y marca `alerta_telegram_enviada = 1` |

## Registro (logs)

Use SLF4J como en los controladores existentes:

```java
private static final Logger log = LoggerFactory.getLogger(MiController.class);
log.info("Importación terminada: {} filas", n);
log.warn("Punto sin coordenadas: {}", idPunto);
log.error("Error guardando la orden", e);   // con la excepción, para tener la traza
```

En local los mensajes salen en la ventana "INEES Backend". Las excepciones no capturadas las registra `ApiErrores` automáticamente.

## Compilar y probar el backend

```bat
cd nueva-plataforma\backend
mvn compile                 REM solo compilar (detecta errores rápido)
mvn spring-boot:run         REM ejecutar
mvn package                 REM genera target\inees-mantenimientos.jar (incluye el frontend compilado)
mvn package -DskipTests     REM igual, sin pruebas
```

`mvn-local.ps1` hace lo mismo usando el JDK de `%USERPROFILE%\inees-local` si existe: `powershell -File mvn-local.ps1 compile`.

Para probar un endpoint sin el frontend, ver [13-pruebas-y-solucion-de-problemas.md](13-pruebas-y-solucion-de-problemas.md#probar-endpoints-con-curl).
