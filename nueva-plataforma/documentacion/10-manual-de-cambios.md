# 10. Manual de cambios

Recetas paso a paso para los cambios más comunes. Cada una indica qué archivos tocar, en qué orden y cómo comprobar que quedó bien. Antes de empezar, tenga la plataforma corriendo en modo desarrollo (`iniciar-local.bat dev`, ver [02-instalacion-y-ejecucion-local.md](02-instalacion-y-ejecucion-local.md)).

## Contenido

1. [Agregar una página nueva completa](#1-agregar-una-página-nueva-completa)
2. [Agregar una acción a una página existente](#2-agregar-una-acción-a-una-página-existente)
3. [Agregar una columna a una tabla](#3-agregar-una-columna-a-una-tabla)
4. [Agregar un catálogo simple](#4-agregar-un-catálogo-simple)
5. [Exportar a Excel](#5-exportar-a-excel)
6. [Generar un PDF](#6-generar-un-pdf)
7. [Subir archivos o fotos](#7-subir-archivos-o-fotos)
8. [Enviar un correo o una alerta de Telegram](#8-enviar-un-correo-o-una-alerta-de-telegram)
9. [Importar un Excel](#9-importar-un-excel)
10. [Agregar una tarea programada](#10-agregar-una-tarea-programada)
11. [Cambiar el menú](#11-cambiar-el-menú)
12. [Cambiar permisos de un rol](#12-cambiar-permisos-de-un-rol)
13. [Agregar un parámetro de negocio o una variable de configuración](#13-agregar-un-parámetro-de-negocio-o-una-variable-de-configuración)
14. [Agregar una librería](#14-agregar-una-librería)
15. [Cambiar textos, estilos o una pantalla existente](#15-cambiar-textos-estilos-o-una-pantalla-existente)
16. [Mantener la documentación al día](#16-mantener-la-documentación-al-día)
17. [Lista de verificación antes de publicar](#17-lista-de-verificación-antes-de-publicar)

---

## 1. Agregar una página nueva completa

Ejemplo de principio a fin: una página **"Visitas por punto"** (`reporteVisitasPunto`) que lista cuántas visitas tuvo cada punto entre dos fechas y permite descargarlas en Excel.

Toda página nueva necesita cuatro cosas, siempre con **el mismo nombre de página**:

| # | Dónde | Qué |
| --- | --- | --- |
| 1 | Base de datos | Fila en `rutas` y permisos en `rol_permisos` |
| 2 | Backend | Controlador con los endpoints `/api/reporteVisitasPunto?accion=...` |
| 3 | Frontend | Componente en `paginas/` y ruta en `app.routes.ts` |
| 4 | Frontend | Ítem en el menú (`layout/menu.ts`), si debe aparecer ahí |

### Paso 1. Registrar la página en la base de datos

```sql
INSERT INTO rutas (nombre_ruta, controlador_ruta, estado, descripcion)
VALUES ('reporteVisitasPunto', 'java:reportes/ReporteVisitasPuntoController', 'activo',
        'Cantidad de visitas por punto en un rango de fechas');

-- Roles que la verán además del administrador (el rol 1 ve todo lo activo)
INSERT INTO rol_permisos (rol_id, ruta_id)
SELECT 5, id_ruta FROM rutas WHERE nombre_ruta = 'reporteVisitasPunto';
```

- `nombre_ruta` es único y es el nombre que se usa en todas partes.
- `controlador_ruta` es obligatorio. En PHP era la ruta del archivo; para páginas que solo existen en Java use un texto descriptivo como el del ejemplo.
- Guarde estos SQL en un archivo (por ejemplo `nueva-plataforma/sql/AAAA-MM-DD_reporteVisitasPunto.sql`) para aplicarlos también en producción.

### Paso 2. Backend: el controlador

Cree `backend/src/main/java/co/inees/mantenimientos/modulos/reportes/ReporteVisitasPuntoController.java` (o en el paquete del módulo que corresponda):

```java
package co.inees.mantenimientos.modulos.reportes;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Excel;
import co.inees.mantenimientos.core.F;
import co.inees.mantenimientos.core.Req;

/** Visitas por punto en un rango de fechas. */
@RestController
public class ReporteVisitasPuntoController {

    private final Db db;

    public ReporteVisitasPuntoController(Db db) {
        this.db = db;
    }

    /** Consulta compartida por la pantalla y el Excel. */
    private List<Map<String, Object>> consultar(String ini, String fin) {
        return db.list("""
                SELECT p.id_punto, p.nombre_punto, c.nombre_cliente,
                       COUNT(os.id_ordenes_servicio) AS visitas,
                       MAX(os.fecha_visita) AS ultima_visita
                FROM ordenes_servicio os
                INNER JOIN punto p ON p.id_punto = os.id_punto
                INNER JOIN cliente c ON c.id_cliente = p.id_cliente
                WHERE os.estado = 1 AND os.fecha_visita BETWEEN :ini AND :fin
                GROUP BY p.id_punto, p.nombre_punto, c.nombre_cliente
                ORDER BY visitas DESC""", "ini", ini, "fin", fin);
    }

    @GetMapping(value = "/api/reporteVisitasPunto", params = "accion=index")
    public Map<String, Object> index(Req req) {
        String ini = F.fechaValida(req.str("fecha_inicio"), F.primeroMes());
        String fin = F.fechaValida(req.str("fecha_fin"), F.hoy());
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("filtros", Map.of("fecha_inicio", ini, "fecha_fin", fin));
        r.put("lista", consultar(ini, fin));
        return r;
    }

    @GetMapping(value = "/api/reporteVisitasPunto", params = "accion=excel")
    public ResponseEntity<byte[]> excel(Req req) {
        String ini = F.fechaValida(req.str("fecha_inicio"), F.primeroMes());
        String fin = F.fechaValida(req.str("fecha_fin"), F.hoy());
        try (Excel x = new Excel("Visitas")) {
            x.set("A1", "VISITAS POR PUNTO DEL " + F.dmy(ini) + " AL " + F.dmy(fin)).merge("A1:D1")
             .estilo("A1:D1", Excel.E.of().bold().size(13));
            String[] enc = {"Punto", "Cliente", "Visitas", "Última visita"};
            for (int i = 0; i < enc.length; i++) x.set(Excel.col(i + 1) + "3", enc[i]);
            x.estilo("A3:D3", Excel.E.of().bold().fill("FFD9E1F2").borde());
            int fila = 4;
            for (Map<String, Object> f : consultar(ini, fin)) {
                x.set("A" + fila, f.get("nombre_punto"));
                x.set("B" + fila, f.get("nombre_cliente"));
                x.set("C" + fila, Conv.toInt(f.get("visitas")));
                x.set("D" + fila, F.dmy(f.get("ultima_visita")));
                fila++;
            }
            x.autoAncho("A", "D");
            return Descarga.excel(x.bytes(), "Visitas_por_punto_" + ini + "_" + fin + ".xlsx");
        }
    }
}
```

Spring encuentra la clase solo (está dentro de `co.inees.mantenimientos`). Compile con `mvn compile` para detectar errores y reinicie el backend.

Pruebe el endpoint antes de hacer la pantalla (ver [13 → Probar endpoints con curl](13-pruebas-y-solucion-de-problemas.md#probar-endpoints-con-curl)):

```bat
curl -b cookies.txt "http://localhost:8080/api/reporteVisitasPunto?accion=index&fecha_inicio=2026-11-01&fecha_fin=2026-11-30"
```

### Paso 3. Frontend: el componente

Cree `frontend/src/app/paginas/reportes/reporte-visitas-punto.ts`:

```ts
import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { fmt, ui } from '../../core/ui';
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

@Component({
  selector: 'app-reporte-visitas-punto',
  imports: [FormsModule, DtArriba, DtAbajo, Th],
  template: `
    <div class="w-full px-4 md:px-6">
      <div class="bg-white p-6 rounded-xl shadow-md">
        <div class="flex flex-wrap gap-3 items-end mb-4">
          <label class="text-sm">Desde
            <input type="date" class="block border rounded px-3 py-2" [(ngModel)]="ini" /></label>
          <label class="text-sm">Hasta
            <input type="date" class="block border rounded px-3 py-2" [(ngModel)]="fin" /></label>
          <button class="bg-blue-600 text-white px-4 py-2 rounded" (click)="cargar()">
            <i class="fas fa-search mr-1"></i> Consultar</button>
          <button class="bg-green-600 text-white px-4 py-2 rounded" (click)="excel()">
            <i class="fas fa-file-excel mr-1"></i> Excel</button>
        </div>
        <app-dt-arriba [dt]="dt" />
        <table class="w-full text-sm">
          <thead class="bg-gray-100 text-xs uppercase">
            <tr>
              <th app-th [dt]="dt" campo="nombre_punto" class="py-2 px-3">Punto</th>
              <th app-th [dt]="dt" campo="nombre_cliente" class="py-2 px-3">Cliente</th>
              <th app-th [dt]="dt" campo="visitas" class="py-2 px-3">Visitas</th>
              <th app-th [dt]="dt" campo="ultima_visita" class="py-2 px-3">Última visita</th>
            </tr>
          </thead>
          <tbody>
            @for (f of dt.pagina; track f.id_punto) {
              <tr class="border-b">
                <td class="py-2 px-3">{{ f.nombre_punto }}</td>
                <td class="py-2 px-3">{{ f.nombre_cliente }}</td>
                <td class="py-2 px-3 text-center">{{ f.visitas }}</td>
                <td class="py-2 px-3">{{ fmt.fecha(f.ultima_visita) }}</td>
              </tr>
            }
          </tbody>
        </table>
        <app-dt-abajo [dt]="dt" />
      </div>
    </div>
  `
})
export class ReporteVisitasPunto implements OnInit {
  private api = inject(Api);
  fmt = fmt;
  dt = new TablaDatos<any>([], 25);
  ini = '';
  fin = '';

  async ngOnInit() {
    await this.cargar();
  }

  async cargar() {
    try {
      const r: any = await this.api.get('reporteVisitasPunto', 'index', { fecha_inicio: this.ini, fecha_fin: this.fin });
      this.ini = r.filtros.fecha_inicio;
      this.fin = r.filtros.fecha_fin;
      this.dt.set(r.lista);
    } catch (e: any) {
      ui.error('No se pudo cargar el reporte', e?.message ?? '');
    }
  }

  async excel() {
    try {
      await this.api.descargar('reporteVisitasPunto', 'excel', { fecha_inicio: this.ini, fecha_fin: this.fin }, 'GET');
    } catch (e: any) {
      ui.error('No se pudo generar el Excel', e?.message ?? '');
    }
  }
}
```

Regístrelo en `frontend/src/app/app.routes.ts`, dentro de `children` del `Layout` (antes de `{ path: '**' ... }`):

```ts
pag('reporteVisitasPunto', 'Visitas por Punto', () => import('./paginas/reportes/reporte-visitas-punto').then((m) => m.ReporteVisitasPunto)),
```

El primer argumento debe ser **idéntico** a `rutas.nombre_ruta`. El segundo es el título que muestra el encabezado.

### Paso 4. Menú

En `frontend/src/app/layout/menu.ts`, agregue el ítem en el grupo y rol que corresponda (ver [receta 11](#11-cambiar-el-menú)):

```ts
{ ruta: 'reporteVisitasPunto', texto: 'Visitas por Punto', icono: 'fas fa-map-marker-alt' },
```

### Paso 5. Comprobar

1. Entrar como administrador: el ítem aparece y la página carga.
2. Entrar con un rol al que **no** se le dio permiso: `/reporteVisitasPunto` lleva a la página 404 y `curl` a la API responde 404. (El menú no se filtra solo: asegúrese de haber puesto el ítem únicamente en el menú de los roles con permiso.)
3. Descargar el Excel y abrirlo.
4. `npx ng build` sin errores y `mvn compile` sin errores.

---

## 2. Agregar una acción a una página existente

Ejemplo: en `puntoVer`, un botón que reactiva un punto dado de baja (`estado = 0`).

1. **Backend.** En el controlador de la página (búsquelo en [07-endpoints.md](07-endpoints.md); para `puntoVer` es `admin/MaquinaPuntoController`) agregue un método con la misma URL y una `accion` nueva. Use las claves de respuesta que ya usa ese controlador (en este caso `success`):

   ```java
   @PostMapping(value = "/api/puntoVer", params = "accion=reactivar")
   public Map<String, Object> reactivar(Req req) {
       int id = req.intv("id");
       if (id <= 0) return Map.of("success", false, "message", "Punto no válido.");
       int n = db.update("UPDATE punto SET estado = 1, fecha_actualizacion = NOW() WHERE id_punto = :id", "id", id);
       return n > 0 ? Map.of("success", true, "message", "Punto reactivado.")
                    : Map.of("success", false, "message", "No se encontró el punto.");
   }
   ```

2. **Frontend.** En el componente (`paginas/admin/maquina-punto.ts`, clase `PuntoVer`), llame a la acción:

   ```ts
   async reactivar(p: any) {
     if (!(await ui.confirmar('¿Reactivar este punto?'))) return;
     const r: any = await this.api.postForm('puntoVer', 'reactivar', { id: p.id_punto });
     r.success ? ui.toast(r.message) : ui.error('Error', r.message);
     await this.cargar();
   }
   ```

3. **Permisos:** no hay que hacer nada. La acción hereda el permiso de la página (`puntoVer`).

> Si la acción debe estar disponible solo para algunos roles dentro de una página que ven varios, valide el rol en el backend: `if (req.sesion().nivel() != 1) return Map.of("success", false, "message", "No autorizado");`. Ocultar el botón en el frontend no basta.

---

## 3. Agregar una columna a una tabla

Ejemplo: agregar `telefono_contacto` a `punto`.

1. **SQL** (guárdelo para producción). Mientras el PHP siga en uso, la columna nueva debe admitir `NULL` o tener valor por defecto, para que los `INSERT` del PHP no fallen:

   ```sql
   ALTER TABLE punto ADD COLUMN telefono_contacto VARCHAR(20) NULL DEFAULT NULL AFTER direccion;
   ```

2. **Backend.** Busque todas las consultas de la tabla (`Ctrl+Shift+F` por `punto` en `modulos/admin/MaquinaPuntoController.java`):
   - En los `SELECT` que alimentan la pantalla, agregue la columna (si usan `SELECT *` ya viene).
   - En el `INSERT` y el `UPDATE`, agregue `:telefono_contacto` y el valor `req.trim("telefono_contacto")`.
3. **Frontend.** En el formulario (`paginas/admin/maquina-punto.ts`, componente `PuntoForm`) agregue el campo al objeto del formulario y el `<input>` en la plantilla; si se muestra en la lista, agregue la columna en `PuntoVer`.
4. **Documentación.** Regenere el diccionario: `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-diccionario-bd.ps1`.
5. **Comprobar:** crear y editar un punto con el teléfono; verificar en la base.

Para **cambiar o borrar** una columna existente, busque antes todos sus usos en el backend, el frontend **y el PHP** (si sigue en uso): un nombre que desaparece rompe las consultas que lo usan.

---

## 4. Agregar un catálogo simple

Los catálogos de un solo campo (nombre, y opcionalmente estado) están definidos en una tabla de configuración y no necesitan pantallas nuevas. Ejemplo: catálogo `tipoFalla` sobre una tabla `tipo_falla(id_tipo_falla, nombre_falla, estado)`.

1. **Tabla** (si no existe):

   ```sql
   CREATE TABLE tipo_falla (
     id_tipo_falla INT AUTO_INCREMENT PRIMARY KEY,
     nombre_falla VARCHAR(80) NOT NULL UNIQUE,
     estado TINYINT NOT NULL DEFAULT 1
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
   ```

2. **Backend:** en `modulos/admin/CatalogosController.java`, dentro del bloque `static { ... }`, registre la definición:

   ```java
   reg(new Cat("tipoFalla", "id_tipo_falla", "nombre_falla", true,
           "SELECT * FROM tipo_falla WHERE estado = 1 ORDER BY nombre_falla ASC",
           "INSERT INTO tipo_falla (nombre_falla, estado) VALUES (:nombre, 1)",
           "SELECT * FROM tipo_falla WHERE id_tipo_falla = :id",
           "UPDATE tipo_falla SET nombre_falla = :nombre, estado = :estado WHERE id_tipo_falla = :id",
           "UPDATE tipo_falla SET estado = 0 WHERE id_tipo_falla = :id",
           "El nombre de la falla es obligatorio.", "Error: es posible que esta falla ya exista.", "Error al actualizar."));
   ```

   Y agregue las cuatro URLs a las anotaciones de los métodos `ver`, `crear`, `obtener`/`editar` y `eliminar` de la misma clase: `"/api/tipoFallaVer"`, `"/api/tipoFallaCrear"`, `"/api/tipoFallaEditar"`, `"/api/tipoFallaEliminar"`.

   El cuarto parámetro (`true`) indica si el formulario de edición muestra el campo Estado.

3. **Frontend:** en `app.routes.ts` agregue `'tipoFalla'` al arreglo `CATALOGOS`. En `paginas/admin/catalogos.ts` agregue sus textos (título, etiqueta del campo) en la configuración de catálogos de ese archivo, siguiendo cualquiera de los existentes.
4. **Rutas y permisos:** inserte en `rutas` las cuatro páginas (`tipoFallaVer`, `tipoFallaCrear`, `tipoFallaEditar`, `tipoFallaEliminar`) y los permisos que correspondan, como en la [receta 1](#paso-1-registrar-la-página-en-la-base-de-datos).
5. **Menú:** agregue `tipoFallaVer` al grupo de catálogos del menú de administración.

---

## 5. Exportar a Excel

Hay dos formas; elija según dónde estén los datos.

**A. Generado en el backend** (recomendado si se necesitan estilos, fórmulas, varias hojas o muchos datos). Ver el método `excel` de la [receta 1](#paso-2-backend-el-controlador) y la referencia de `Excel` en [04-backend.md](04-backend.md#excel-generar-hojas-de-cálculo). En el frontend:

```ts
await this.api.descargar('miPagina', 'excel', { fecha_inicio: this.ini }, 'GET');      // con parámetros en la URL
await this.api.descargar('miPagina', 'excel', { filtros: this.filtros }, 'POST');      // con muchos datos
```

**B. Generado en el navegador** con los datos que ya tiene la pantalla (rápido, sin estilos):

```ts
import * as XLSX from 'xlsx';

exportar() {
  const filas = this.dt.filtrados.map((f: any) => ({ Punto: f.nombre_punto, Visitas: f.visitas }));
  const hoja = XLSX.utils.json_to_sheet(filas);
  const libro = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(libro, hoja, 'Datos');
  XLSX.writeFile(libro, 'reporte.xlsx');
}
```

---

## 6. Generar un PDF

1. Si el diseño es propio, cree el CSS en `backend/src/main/resources/plantillas/mi-reporte.css` (A4, tipografías, tablas). Revise `servicio-pdf.css` como ejemplo.
2. En el controlador, arme el HTML, genere y devuelva el PDF (ejemplo completo en [04-backend.md → Pdf y Descarga](04-backend.md#pdf-y-descarga-generar-pdf)):

   ```java
   @GetMapping(value = "/api/miPagina", params = "accion=pdf")
   public ResponseEntity<byte[]> pdf(Req req) throws Exception {
       String css = new String(new ClassPathResource("plantillas/mi-reporte.css").getInputStream().readAllBytes(), StandardCharsets.UTF_8);
       String html = "<!doctype html><html><head><meta charset='utf-8'><style>" + css + "</style></head><body>"
               + "<img src='" + pdf.dataUri("app/logos/logoInees.jpg") + "' height='48'>"
               + "<h1>" + Conv.h(titulo) + "</h1>" + tablaHtml + "</body></html>";
       return Descarga.pdf(pdf.generar(html, "size: A4; margin: 12mm"), "Mi_reporte.pdf", true);
   }
   ```

3. En el frontend, ábralo en una pestaña: `this.api.abrir('miPagina', 'pdf', { id: 5 })`.
4. Reglas: escapar todo texto que venga de la base con `Conv.h(...)`; imágenes siempre con `pdf.dataUri(...)`; probar con datos largos (saltos de página, `page-break-inside: avoid` en filas).

---

## 7. Subir archivos o fotos

**Frontend:** envíe el `File` dentro del objeto; `api.post` arma el `multipart/form-data`.

```html
<input type="file" accept="image/*" multiple (change)="fotos = $any($event.target).files" />
```

```ts
fotos: FileList | null = null;
async guardar() {
  const datos: any = { id_punto: this.idPunto, descripcion: this.descripcion };
  datos.fotos = this.fotos ? Array.from(this.fotos) : [];        // llega como fotos[]
  const r: any = await this.api.post('miPagina', 'guardar', datos);
}
```

**Backend:**

```java
@PostMapping(value = "/api/miPagina", params = "accion=guardar")
public Map<String, Object> guardar(Req req) {
    List<String> rutas = new ArrayList<>();
    for (MultipartFile f : req.files("fotos")) {
        String ext = Archivos.extension(f.getOriginalFilename());
        if (!List.of("jpg", "jpeg", "png", "webp").contains(ext)) continue;   // validar tipo
        String rel = "app/uploads/mi_modulo/" + F.hoy().substring(0, 7) + "/" + Archivos.uniqid() + ".jpg";
        if (archivos.guardarImagenOptimizadaExif(f, rel, 1280, 75)) rutas.add(rel);
    }
    // guardar 'rutas' en la tabla correspondiente (rutas RELATIVAS)
    return Map.of("exito", true, "fotos", rutas);
}
```

- La carpeta se crea sola. Si usa una carpeta fuera de `uploads/`, `app/uploads/`, `imagenes/` o `app/imagenes/`, agréguela en `core/WebConfig.addResourceHandlers` para poder verla en el navegador.
- Para mostrar la foto: `<img [src]="'/' + ruta">`.
- Límite: 50 MB por archivo y 200 MB por envío (`application.yml`).

---

## 8. Enviar un correo o una alerta de Telegram

```java
private final Correo correo;
private final Telegram telegram;

try {
    correo.enviar(List.of("supervisor@empresa.com"), null, "Nueva solicitud",
                  "<p>Se registró la solicitud <b>" + Conv.h(numero) + "</b>.</p>", null, null);
} catch (Exception e) {
    log.error("No se pudo enviar el correo", e);
    return Map.of("exito", true, "mensaje", "Guardado, pero no se pudo enviar el correo.");
}

telegram.enviar("⚠️ <b>Alerta</b>\nTécnico: " + Conv.h(nombre));   // no lanza excepciones
```

En local, deje `SMTP_PASS` vacío o use una cuenta de pruebas, y **no** ponga correos reales de la empresa como destinatarios mientras prueba. Las direcciones fijas de un módulo conviene ponerlas en la tabla `parametros` en lugar del código (receta 13).

---

## 9. Importar un Excel

Para pocos datos (cientos de filas), en una sola petición:

```java
@PostMapping(value = "/api/miImportacion", params = "accion=subir")
public Map<String, Object> subir(Req req) throws Exception {
    MultipartFile f = req.file("archivo_excel");
    if (f == null) return Map.of("exito", false, "error", "Seleccione un archivo.");
    List<Map<String, String>> filas = ExcelLector.leer(f.getInputStream(), null);
    int ok = db.tx(() -> {
        int n = 0;
        for (int i = 1; i < filas.size(); i++) {            // fila 0 = encabezados
            Map<String, String> r = filas.get(i);
            String device = r.getOrDefault("A", "").trim();
            if (device.isEmpty()) continue;
            n += db.update("UPDATE maquina SET estado = :e WHERE device_id = :d",
                           "e", Conv.toInt(r.get("B")), "d", device);
        }
        return n;
    });
    return Map.of("exito", true, "mensaje", ok + " máquinas actualizadas");
}
```

Para miles de filas, copie el patrón de `importar/ImportarController` + `paginas/importar/importar.ts`:

1. `subirArchivo` lee el Excel y guarda las filas en la sesión; devuelve el total.
2. El frontend llama a `procesarLote` con `inicio` y `cantidad` (200) hasta terminar, mostrando el avance.
3. `finalizarImportacion` hace los pasos finales y limpia la sesión.

Siempre ofrezca una **simulación** previa (mostrar qué cambiaría sin escribir) cuando la importación modifica o da de baja datos existentes.

---

## 10. Agregar una tarea programada

En `modulos/tareas/TareasProgramadas.java` (o una clase `@Component` nueva):

```java
@Scheduled(cron = "${inees.cron-cierre-expresion:0 0 22 * * *}", zone = "America/Bogota")
public void cierreDiario() {
    if (!cierreHabilitado) return;           // bandera leída con @Value("${inees.cron-cierre-habilitado:false}")
    log.info("Cierre diario: inicio");
    // ... trabajo (no hay sesión ni usuario: usar Db directamente)
}
```

1. Agregue las propiedades en `application.yml` bajo `inees:` con su variable de entorno (`cron-cierre-habilitado: ${CRON_CIERRE:false}`).
2. Déjela **apagada por defecto**; se enciende por variable de entorno en producción.
3. Documente la variable en [03-configuracion.md](03-configuracion.md) y en `config-local.ejemplo.bat`.
4. Para probarla en local sin esperar, ponga temporalmente una expresión como `*/30 * * * * *` (cada 30 segundos) en `config-local.bat` y mire la ventana del backend.

---

## 11. Cambiar el menú

El menú está en `frontend/src/app/layout/menu.ts`, con una lista por rol: `TECNICO` (rol 3), `PROSEGUR` (4), `SUPERVISOR` (5) y `ADMIN` (1, 2 y cualquier otro). Cada lista es un arreglo de grupos:

```ts
{ texto: 'Reportes', icono: 'fas fa-chart-bar', items: [               // desplegable
    { seccion: true, texto: 'Operación' },                            // subtítulo dentro del desplegable
    { ruta: 'reporteTecnico', texto: 'Servicios por técnico', icono: 'fas fa-user-cog' },
    { separador: true, texto: '' },
    { ruta: 'reporteVisitasPunto', texto: 'Visitas por punto', icono: 'fas fa-map-marker-alt' }
]},
{ texto: 'Inicio', icono: 'fas fa-home', ruta: 'inicio' },           // enlace directo
```

- El menú **no** se filtra por permisos: cada rol ve exactamente su lista. Ponga cada ítem solo en los menús de los roles que tienen la página en `rol_permisos`; si no, el usuario verá el ítem y al abrirlo llegará a la página 404. El rol 2 (Auxiliar) usa el menú `ADMIN`, así que verá ítems que quizá no tenga permitidos.
- Los íconos son de Font Awesome 6 (`fas fa-...`, `fa-solid fa-...`).
- Cambiar el menú no da permisos: eso se hace en la base (receta 12).

---

## 12. Cambiar permisos de un rol

Solo con SQL sobre `rol_permisos`; no requiere tocar código ni reiniciar. Los comandos están en [08-permisos-y-roles.md → Dar o quitar acceso](08-permisos-y-roles.md#dar-o-quitar-acceso-a-una-página). Después regenere la matriz de la documentación:

```bat
powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-permisos.ps1
```

Para crear un **rol nuevo**: insértelo en `tipousuario` (desde `tipoUsuarioCrear`), dele permisos en `rol_permisos` y, si necesita un menú propio, agregue su lista en `menu.ts` y su caso en `menuPorRol()`.

---

## 13. Agregar un parámetro de negocio o una variable de configuración

**Valor de negocio que el administrador puede querer cambiar** (un tope, un porcentaje, una lista de correos): en la tabla `parametros`.

```sql
INSERT INTO parametros (clave, valor, descripcion)
VALUES ('correos_solicitud_inventario', 'a@empresa.com,b@empresa.com', 'Destinatarios de las solicitudes de inventario');
```

```java
private final Parametros parametros;
List<String> destinos = List.of(parametros.obtener("correos_solicitud_inventario", "").split("\\s*,\\s*"));
```

**Configuración técnica o secreta** (llaves, URLs de servicios, banderas): en `application.yml` con variable de entorno.

1. `application.yml`, bajo `inees:` → `mi-servicio-url: ${MI_SERVICIO_URL:}`.
2. Inyéctela: `public MiController(@Value("${inees.mi-servicio-url:}") String url) { ... }`.
3. Agréguela comentada en `config-local.ejemplo.bat` y documéntela en [03-configuracion.md](03-configuracion.md).
4. Nunca escriba el valor real en el código ni en `application.yml`.

---

## 14. Agregar una librería

**Backend (Maven).** En `backend/pom.xml`, dentro de `<dependencies>`:

```xml
<dependency>
    <groupId>com.ejemplo</groupId>
    <artifactId>libreria</artifactId>
    <version>1.2.3</version>
</dependency>
```

Las dependencias de Spring Boot no llevan `<version>` (la pone el *parent*). Luego `mvn compile`.

**Frontend (npm).**

```bat
cd frontend
npm install nombre-libreria --save
```

Impórtela solo en el componente que la usa, para que no aumente la carga de las demás páginas. Verifique que `npx ng build` no muestre avisos de presupuesto de tamaño.

---

## 15. Cambiar textos, estilos o una pantalla existente

1. Ubique el componente: busque la página en la tabla de [09-modulos.md](09-modulos.md) o en `app.routes.ts` (la ruta dice qué archivo y clase carga).
2. La plantilla HTML está en la propiedad `template` del `@Component`, en el mismo archivo `.ts`.
3. Estilos: clases de Tailwind en el HTML. Colores habituales de la plataforma: azul `blue-600`, índigo `indigo-600`, verde `green-600`, rojo `red-600`.
4. Con `iniciar-local.bat dev` el cambio se ve al guardar en http://localhost:4200.
5. Si el texto viene del backend (mensajes de error o éxito), está en el controlador de esa página.

---

## 16. Mantener la documentación al día

Tres partes de esta documentación se generan solas desde el código y la base; regenérelas después de cambios:

| Cambió… | Ejecutar (desde `nueva-plataforma`) | Actualiza |
| --- | --- | --- |
| Endpoints (controladores) | `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-endpoints.ps1` | `07-endpoints.md` |
| Tablas o columnas | `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-diccionario-bd.ps1` | `06-base-de-datos.md` |
| Páginas o permisos | `powershell -ExecutionPolicy Bypass -File documentacion\herramientas\generar-permisos.ps1` | `08-permisos-y-roles.md` |

Los textos escritos a mano de esos documentos están en `documentacion/herramientas/bd-convenciones.md` y `permisos-intro.md`: edítelos ahí, no en el archivo generado. El resto de documentos se edita directamente. Si agrega un módulo, descríbalo en [09-modulos.md](09-modulos.md).

---

## 17. Lista de verificación antes de publicar

- [ ] `mvn compile` (backend) y `npx ng build` (frontend) terminan sin errores.
- [ ] Las páginas nuevas están en `rutas` y tienen permisos; probadas con un rol que sí y uno que no.
- [ ] Todo SQL de cambio de estructura o de datos está guardado en un archivo `.sql` para producción.
- [ ] Columnas nuevas admiten `NULL` o tienen valor por defecto (si el PHP sigue en uso).
- [ ] Ninguna llave, contraseña o correo real quedó en el código.
- [ ] Los datos de prueba se borraron de la base.
- [ ] Probado el flujo completo en el navegador, no solo el endpoint.
- [ ] Documentación regenerada (receta 16) y, si aplica, [09-modulos.md](09-modulos.md) actualizado.
- [ ] Respaldo de la base de producción antes de aplicar el SQL (ver [12-despliegue.md](12-despliegue.md)).
