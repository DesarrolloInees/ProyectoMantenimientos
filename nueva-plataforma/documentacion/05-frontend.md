# 5. Frontend (Angular)

El frontend es Angular 22 con componentes *standalone* (sin `NgModule`), TypeScript, Tailwind CSS y Font Awesome, en `nueva-plataforma/frontend`. Cada vista PHP pasó a ser un componente; las rutas tienen el mismo nombre que la página en la tabla `rutas`.

## Estructura

```text
frontend/
├── angular.json            ← compila a ../backend/src/main/resources/static
├── proxy.conf.json         ← en ng serve, /api y archivos → http://localhost:8080
├── tailwind.config.js
├── public/                 ← se copia tal cual al compilado
│   ├── app/logos/          ← logos usados en pantallas y PDF del navegador
│   ├── css/asistencia.css  ← estilos del procesador de asistencia
│   └── js/asistencia/      ← scripts del procesador de asistencia (traídos del PHP)
└── src/
    ├── index.html, main.ts, styles.css
    └── app/
        ├── app.ts              ← componente raíz (solo <router-outlet>)
        ├── app.config.ts       ← router, HttpClient + interceptor, idioma es-CO
        ├── app.routes.ts       ← todas las rutas
        ├── core/
        │   ├── api.ts          ← cliente HTTP con la convención /api/{pagina}?accion=
        │   ├── auth.ts         ← sesión (señal), permisos, logout
        │   ├── guards.ts       ← authGuard, paginaGuard, loginGuard, sesionInterceptor
        │   └── ui.ts           ← SweetAlert2 (ui.ok/error/confirmar/cargando/toast), Titulo, fmt
        ├── layout/
        │   ├── layout.ts       ← barra superior, menú, título, contenido de las páginas
        │   ├── menu.ts         ← menús por rol (TECNICO, PROSEGUR, SUPERVISOR, ADMIN)
        │   └── turno-modal.ts  ← modal "Marcar Entrada" del técnico
        ├── shared/
        │   ├── tabla.ts        ← TablaDatos / TablaServidor + DtArriba, DtAbajo, th[app-th]
        │   ├── select-buscar.ts← lista desplegable con búsqueda (reemplaza Select2)
        │   ├── notificaciones.ts← avisos apilados con sonido (órdenes)
        │   └── pdfmake.ts      ← carga pdfmake bajo demanda
        └── paginas/<módulo>/   ← componentes de cada módulo
```

## Arranque y rutas

`main.ts` arranca `App` con `app.config.ts`, que registra:

- `provideRouter(routes, withComponentInputBinding())`: los parámetros de ruta (`:id`, `:fecha`) y los `data` pueden recibirse como `@Input()` del componente.
- `provideHttpClient(withInterceptors([sesionInterceptor]))`: si cualquier llamada responde 401, manda a `/login`.
- `LOCALE_ID = 'es-CO'`: fechas y números en formato colombiano con los *pipes* de Angular.

`app.routes.ts` tiene tres tipos de rutas:

```ts
// 1. Públicas, fuera del layout
{ path: 'login', component: Login, canActivate: [loginGuard] },

// 2. Todo lo demás, dentro de Layout (exige sesión con authGuard)
{ path: '', component: Layout, canActivate: [authGuard], children: [
    // 2a. Una página: pag(nombre_ruta, título, carga diferida, data extra)
    pag('ordenVer', 'Historial por Días', () => import('./paginas/orden/orden-ver-movil').then((m) => m.OrdenVer)),
    pag('clienteEditar/:id', 'Editar Cliente', () => import('./paginas/admin/cliente').then((m) => m.ClienteForm), { modo: 'editar' }),
    // 2b. Catálogos genéricos: genera xVer, xCrear, xEditar y xEditar/:id
    ...CATALOGOS.flatMap(catalogo),
    { path: '**', component: NoEncontrado }
]}
```

`pag()` agrega `canActivate: [paginaGuard]` y `data: { titulo, pagina }`. El `Layout` toma `titulo` para el encabezado y la pestaña; `paginaGuard` toma `pagina` (la parte antes de `/`) y verifica que esté en la lista de permisos que devolvió `/api/sesion`. Cada página se carga solo cuando se visita (*lazy loading*).

## Hablar con el backend: `Api`

Todas las pantallas usan el servicio `Api` (`core/api.ts`). Arma la URL `/api/{pagina}?accion={accion}` y convierte objetos al formato de PHP (`a[0][b]`).

```ts
private api = inject(Api);

// GET (accion por defecto: 'index') con parámetros en la URL
const r = await this.api.get('ordenVer', 'index', { fecha: '2026-11-19' });

// POST multipart (como un <form> con archivos): objetos y arreglos se aplanan a notación PHP
await this.api.post('transporteCrear', 'guardar', { id_punto: 5, fotos: [archivo1, archivo2] });

// POST x-www-form-urlencoded (como $.post de jQuery)
await this.api.postForm('costosVer', 'eliminar', { id: 12 });

// POST con cuerpo JSON (el backend lo lee con req.json())
await this.api.postJson('ordenDetalle', 'ajaxGuardarCambiosJSON', { cambios });

// Descargar un archivo (Excel/PDF): usa el nombre que manda el servidor;
// si el backend responde JSON de error, lanza Error con el mensaje
await this.api.descargar('reporteEjecutivo', 'descargarExcel', { tipo: 'puntos' }, 'GET', 'reporte.xlsx');

// Abrir un PDF en otra pestaña
this.api.abrir('pdfServicio', 'generar', { id: 1234 });
this.api.abrirPost('generarReporte', 'generar', datosGrandes);   // por POST, para muchos datos
```

Todos los métodos devuelven `Promise`: se usan con `async/await` y `try/catch`.

## Sesión y permisos en pantalla

```ts
private auth = inject(Auth);
this.auth.sesion();            // { logueado, usuario_id, usuario_name, nivel_acceso, usuario_cargo, permisos[] }
this.auth.nivel;               // rol (1..5)
this.auth.puede('reporteFacturacion');   // ¿tiene la página?
await this.auth.logout();
```

Úselo para mostrar u ocultar botones que llevan a otras páginas. **No es una medida de seguridad**: el backend vuelve a validar cada llamada.

## Mensajes y formato: `ui`, `fmt`, `Titulo`

```ts
import { ui, fmt, Titulo } from '../../core/ui';

await ui.ok('Guardado', 'La orden se registró');           // éxito
await ui.error('No se pudo guardar', r.error);
if (await ui.confirmar('¿Eliminar este punto?', 'No se puede deshacer')) { ... }
ui.cargando('Generando PDF...'); /* ... */ ui.cerrar();
ui.toast('Cambios guardados');                               // aviso pequeño arriba a la derecha

fmt.pesos(1234567);        // "$ 1.234.567"
fmt.miles(1234.5, 1);      // "1.234,5"
fmt.fecha('2026-11-19');   // "19/11/2026"
fmt.hora('07:30:00');      // "07:30"
fmt.hoy();                 // "2026-11-19"

inject(Titulo).set('Otro título');   // normalmente no hace falta: lo pone la ruta
```

## Componentes compartidos

### Tabla con búsqueda, orden y paginación (`shared/tabla.ts`)

Reemplaza a DataTables. Los datos se filtran y paginan en el navegador.

```ts
import { DtAbajo, DtArriba, TablaDatos, Th } from '../../shared/tabla';

@Component({
  imports: [DtArriba, DtAbajo, Th],
  template: `
    <app-dt-arriba [dt]="dt" />                       <!-- "Mostrar N" y buscador -->
    <table class="w-full text-sm">
      <thead><tr>
        <th app-th [dt]="dt" campo="nombre_punto">Punto</th>   <!-- clic = ordenar -->
        <th app-th [dt]="dt" campo="fecha_visita">Fecha</th>
      </tr></thead>
      <tbody>
        @for (f of dt.pagina; track f.id_ordenes_servicio) {
          <tr><td>{{ f.nombre_punto }}</td><td>{{ f.fecha_visita }}</td></tr>
        }
      </tbody>
    </table>
    <app-dt-abajo [dt]="dt" />                        <!-- "Mostrando x a y" y paginación -->
  `
})
export class MiLista {
  dt = new TablaDatos<any>([], 25);                  // datos, filas por página, (campos de búsqueda opcionales)
  async ngOnInit() { this.dt.set((await this.api.get('miPagina')).lista); }
}
```

Para listas muy grandes que se paginan en el servidor existe `TablaServidor` (usada en `controlRemisionVer`): recibe una función que llama al backend con `{draw, start, length, search[value], order[0][column], order[0][dir]}` y espera `{recordsTotal, recordsFiltered, data}`, el mismo protocolo de DataTables *server-side* del PHP.

### Lista desplegable con búsqueda (`shared/select-buscar.ts`)

Reemplaza a Select2. Funciona con `[(ngModel)]`.

```html
<app-select-buscar name="cliente" [opciones]="clientes" valor="id_cliente" texto="nombre_cliente"
                   placeholder="Seleccione cliente" [(ngModel)]="idCliente" (cambio)="cargarPuntos()" />
<app-select-buscar name="tecnicos" [opciones]="tecnicos" valor="id_tecnico" [texto]="nombreTecnico"
                   [multiple]="true" [(ngModel)]="seleccionados" />
```

Entradas útiles: `opciones`, `valor` (campo id), `texto` (campo o función), `placeholder`, `multiple`, `permitirVacio`, `limpiable`, `[disabled]`. Dentro de un `<form>` necesita el atributo `name`.

## Estilos

- **Tailwind CSS**: las clases se escriben en las plantillas (`class="bg-white p-6 rounded-xl shadow-md"`). Tailwind solo genera las clases que encuentra en los archivos listados en `tailwind.config.js`; si construye nombres de clase dinámicamente, escríbalos completos en algún lugar del código.
- **Font Awesome 6**: íconos con `<i class="fas fa-user"></i>`.
- `src/styles.css`: estilos globales y algunas clases heredadas de las vistas PHP.
- Estilos propios de un componente: propiedad `styles` del `@Component`.

## Librerías de terceros

Ejemplos de importación: `import Chart from 'chart.js/auto'`, `import * as L from 'leaflet'`, `import * as XLSX from 'xlsx'`.

| Librería | Uso | Dónde se usa |
| --- | --- | --- |
| Chart.js | Gráficas | `cronometro.ts`, `reporte-ejecutivo.ts`, `varios.ts` |
| Leaflet | Mapas | `programacion-mapa.ts`, `varios.ts` (rastreo) |
| frappe-gantt | Diagrama de Gantt | `planeacion.ts` |
| SweetAlert2 | Ventanas de mensaje | `core/ui.ts` (todas las páginas lo usan a través de `ui`) |
| xlsx (SheetJS) | Exportar o leer Excel en el navegador | 11 páginas: reportes, consolidado, órdenes, máquinas, remisiones, tarifas, varios |
| pdfmake | PDF armado en el navegador | `reporte-facturacion.ts`, cargado con `await pdfMake()` de `shared/pdfmake.ts` |
| flatpickr | Instalada en `package.json`, hoy ninguna pantalla la usa | — |

Para agregar otra: `npm install nombre --save` en `frontend/` e importarla en el componente que la use (se carga solo con esa página).

## El procesador de asistencia (caso especial)

La página `asistencia` reutiliza sin cambios el JavaScript del PHP (`public/js/asistencia/core.js`, `calculo.js`, `render.js`, `eventos.js`, `exportar.js`), porque contiene toda la lógica de cálculo de nómina. El componente `paginas/asistencia/asistencia.ts` pinta el HTML original, define `window.AsistenciaConfig` con las URLs de la API y carga esos scripts en orden. Si se modifica la lógica de cálculo, se edita en esos `.js`.

## Compilar

```bat
cd nueva-plataforma\frontend
npm ci              REM instala dependencias exactas de package-lock.json (primera vez)
npx ng serve        REM desarrollo en http://localhost:4200 con recarga automática
npx ng build        REM producción → backend\src\main\resources\static
```

Después de `ng build`, el backend sirve la versión nueva al reiniciarse (o en la siguiente compilación del `.jar`). Los avisos `NG8113` (import sin usar) no impiden compilar, pero conviene corregirlos.
