---
version: 1
slug: "src-app-shell-shell-ts"
primary_target: "src/app/shell/shell.ts"
related_targets: ["src/app/login/login.ts","src/app/inicio/inicio.ts","src/styles.scss"]
---

# Marco de la aplicación (shell, login, inicio y componentes compartidos)

Modo: Operate. Público: oficina en escritorio y motorizados en celular al sol, con el mismo peso. Alcance pedido:
línea gráfica de inees.com.co en toda la app, logo nuevo en app y PDF, estados con tonos de marca, y permiso para
reorganizar el menú y rediseñar el inicio. Sin cambiar flujos, textos de negocio ni datos.

## Direction contract

THESIS: La app es el lado de trabajo de inees.com.co: el mismo kit (azul noche, azul y verde de la nube, Archivo) y el
corte diagonal de la nube como única firma. Rechaza el panel genérico de Material azul con barra gris.

OWN-WORLD: Riel lateral azul noche #072942 con el logo «sobre azul», fondo de trabajo #F2F5F8, paneles blancos con
borde #E0E6EC y radio 12, texto tinta #0F1F2C, títulos Archivo 600 ensanchados al 108 %, botón principal verde
#2D7F3E de radio 10, secundarios con contorno azul #0B5591, foco azul claro #368ABA, cifras tabulares.

STORY: Quien entra sabe dónde está cada módulo y qué le toca hoy; el motorizado ve su avance de un vistazo.

FIRST VIEWPORT: Escritorio: riel de 264 px a la izquierda (logo arriba, grupos del menú, usuario abajo) y el área de
trabajo con el título de la pantalla y sus filtros arriba. Celular: barra azul noche con menú, logo y usuario;
contenido a una columna con botones de 48 px. Login partido: panel azul noche con las franjas grandes y el formulario
en blanco.

FORM: Marco operativo estándar (riel lateral + área de trabajo), primera opción de la lista; firma: el avance se
dibuja como franjas inclinadas a 58° del corte de la nube (pasos del servicio, ruta del día, metas). seed: none
(brief-pinned: línea gráfica de inees.com.co pedida por el usuario).

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
