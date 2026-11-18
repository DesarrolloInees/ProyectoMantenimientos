---
name: INEES Plataforma de mantenimientos
description: El lado de trabajo de inees.com.co — kit de marca INEES Secure Ai Systems aplicado a una app operativa (oficina y celular).
colors:
  azul-noche: "#072942"
  azul-noche-oscuro: "#041b2c"
  azul-nube: "#0b5591"
  azul-nube-oscuro: "#08426f"
  azul-claro: "#368aba"
  azul-tinte: "#e8f0f7"
  verde-nube: "#2d7f3e"
  verde-nube-oscuro: "#236431"
  verde-automatizacion: "#4ba844"
  verde-tinte: "#e9f4eb"
  tinta: "#0f1f2c"
  tinta-2: "#435260"
  mut: "#5c6976"
  linea: "#e0e6ec"
  linea-2: "#c7d0d9"
  fondo: "#f2f5f8"
  fondo-2: "#f7f9fb"
  superficie: "#ffffff"
  aviso-solido: "#e8a317"
  aviso-texto: "#8a5a00"
  aviso-fondo: "#fcf1d9"
  error: "#c2362b"
  error-fondo: "#fbe9e7"
typography:
  titulo-pagina:
    fontFamily: "Archivo, system-ui, -apple-system, Segoe UI, Roboto, sans-serif"
    fontSize: "24px"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "-0.018em"
  titulo-seccion:
    fontFamily: "Archivo, system-ui, sans-serif"
    fontSize: "17px"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "-0.015em"
  cifra:
    fontFamily: "Archivo, system-ui, sans-serif"
    fontSize: "44px"
    fontWeight: 600
    lineHeight: 1
    letterSpacing: "-0.035em"
  cuerpo:
    fontFamily: "Archivo, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.43
  tabla:
    fontFamily: "Archivo, system-ui, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.4
  etiqueta-grupo:
    fontFamily: "Archivo, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 600
    letterSpacing: "0.07em"
rounded:
  chip: "6px"
  campo: "8px"
  boton: "10px"
  panel: "12px"
  dialogo: "14px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
  pagina: "32px"
components:
  boton-principal:
    backgroundColor: "{colors.verde-nube}"
    textColor: "{colors.superficie}"
    rounded: "{rounded.boton}"
    height: "42px"
    padding: "0 20px"
  boton-principal-hover:
    backgroundColor: "{colors.verde-nube-oscuro}"
  boton-secundario:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.azul-nube}"
    rounded: "{rounded.boton}"
    height: "42px"
  boton-campo:
    backgroundColor: "{colors.verde-nube}"
    textColor: "{colors.superficie}"
    rounded: "{rounded.boton}"
    height: "52px"
  panel:
    backgroundColor: "{colors.superficie}"
    rounded: "{rounded.panel}"
    padding: "18px"
  riel:
    backgroundColor: "{colors.azul-noche}"
    textColor: "{colors.superficie}"
    width: "264px"
  enlace-riel-activo:
    backgroundColor: "{colors.azul-nube}"
    textColor: "{colors.superficie}"
    rounded: "{rounded.campo}"
    height: "38px"
  chip:
    backgroundColor: "{colors.fondo}"
    textColor: "{colors.tinta-2}"
    rounded: "{rounded.chip}"
---

# Design System: INEES Plataforma de mantenimientos

## Overview

La plataforma es el lado de trabajo de **inees.com.co**: usa el mismo kit de marca (azul noche, azul y verde de la nube,
Archivo) para que quien viene del sitio reconozca a la empresa, pero se comporta como una herramienta: el riel de
módulos a la izquierda, el área de trabajo clara y los controles estándar de Angular Material. La marca vive en los
detalles precisos — el color del marco, el ensanche de los títulos y una sola firma: **el corte diagonal de la nube**.

Sirve a dos públicos con el mismo peso: la oficina en escritorio (tablas, tableros, planeación) y los motorizados en
el celular, al sol y en movimiento (objetivos de 48–52 px, contraste alto, un paso a la vez).

## Colors

Estrategia restringida con un marco comprometido: el riel azul noche y la barra del celular cargan la marca; el
trabajo ocurre sobre fondo frío claro (#F2F5F8) y paneles blancos.

### Primary
- **Azul noche** `#072942`: riel, barra del celular, títulos, cifras. Es el color de la marca.
- **Azul de la nube** `#0B5591`: selección, enlaces, foco de campos, botón secundario, el paso actual.

### Secondary
- **Verde de la nube** `#2D7F3E`: la acción principal (como el botón verde del sitio), lo hecho, lo que cumple.
- **Verde de Automatización** `#4BA844`: detalle sobre azul noche (las franjas del enlace activo, el botón del aviso).

### Neutral
Tinta `#0F1F2C`, tinta 2 `#435260`, mut `#5C6976`, líneas `#E0E6EC` / `#C7D0D9`, fondos `#F2F5F8` / `#F7F9FB`.

### Named Rules
- **Rojo y ámbar solo para alertas.** Ámbar (`#E8A317`, texto `#8A5A00`) = atención (arrastrados, bolsa sin ruta,
  jornada sin iniciar, sin ingreso); rojo (`#C2362B`) = error o vencido. Nunca como decoración ni para categorías.
- **Prioridades de la bolsa:** 1 solicitud Prosegur = azul de la nube; 2 arrastrado = ámbar; 3 VIP = verde;
  4 vencido normal = mut.
- **Azul claro `#368ABA` solo para detalles** (foco, una serie secundaria), como dice el kit.

## Typography

Una sola familia: **Archivo** (eje de ancho 100–125). Los títulos van en 600 con `font-stretch: 108%` y tracking
negativo, cercanos al nombre INEES; el cuerpo y los datos en Archivo normal, con cifras tabulares en tablas y montos.

### Hierarchy
Título de página 24 px (21 px en celular) · título de sección 17 px · cifra destacada 44 px (36 px en celular) ·
cuerpo 14 px · tabla 13 px · etiqueta de grupo del menú 11 px en mayúsculas con tracking 0.07em.

## Layout

Riel lateral de 264 px + área de trabajo con `padding` 24/32 px y ancho máximo 1600 px. Debajo de 900 px el riel se
vuelve un panel que se abre desde la barra azul noche del celular. Rejillas con `auto-fill` para indicadores y paneles;
la respuesta al tamaño es estructural (columnas, consultas de contenedor), no tipografía fluida.

## Elevation & Depth

Profundidad mínima y tintada con el azul noche: `sombra-1` (0 1px 2px / 0 8px 24px -16px) en paneles y `sombra-2`
para lo que sobresale (la tarjeta del siguiente punto, el menú). Bordes de 1 px `#E0E6EC` en todos los paneles.

## Shapes

Radios por función: chip 6 px, campo 8 px, botón 10 px (como el sitio), panel 12 px, diálogo 14 px. Los avatares son
círculos; los números de parada, cuadrados de 6 px.

## Components

### Buttons
Principal verde de la nube, 42 px de alto (52 px en celular, clase `boton-campo` o `.grande`), radio 10 px, peso 600.
Secundario con contorno azul de 1.5 px. Texto en azul de la nube.

### Chips
Fondo `#F2F5F8`, texto tinta 2, radio 6 px; variantes `ok`, `aviso`, `error`, `info`.

### Cards / Containers
Panel blanco, borde 1 px, radio 12 px, `sombra-1`. Los indicadores (`app-kpi`) son paneles con la cifra en azul noche;
en alerta, fondo ámbar suave y cifra ámbar. Nunca con borde lateral de color.

### Inputs / Fields
Material `outline`, radio 8 px, borde `#C7D0D9`, foco azul de la nube.

### Navigation
Riel azul noche con el logo «sobre azul», grupos plegables (se recuerdan por usuario), enlace activo con fondo azul de
la nube y las tres franjas verdes a la derecha. Usuario con iniciales al pie del riel.

### Franjas (componente firma)
El avance se dibuja como el corte de la nube: segmentos inclinados a 58° (`skewX(-32deg)`), uno por paso o por
servicio (`.franjas > i` con `hecho`, `actual`, `alerta`, `fallo`), y las barras continuas terminan en corte diagonal
(`.avance`, barras de `app-barras`). Se usa en los pasos del servicio guiado, la ruta del motorizado, el seguimiento,
la jornada de cada ruta en Planeación y el cumplimiento de la meta. El login lo muestra en grande sobre azul noche.

### Inicio guiado
Pasos numerados (cuadro azul noche con el número; verde con ✓ cuando está hecho; ámbar si hay algo por atender), cada
uno con un verbo de acción, una explicación de una línea, una etiqueta de estado y un botón directo a la pantalla. Las
franjas encima muestran cuántos pasos están listos. En el celular el botón pasa debajo del texto a todo el ancho.

### Elegir rol
Tarjetas seleccionables (`role="radio"`) con ícono, nombre y qué hace el rol; la elegida con borde y fondo azul de la nube.

## Do's and Don'ts

### Do:
- Usar la firma de franjas solo donde hay avance real que mostrar.
- Mantener el verde para la acción principal y lo hecho; el azul para selección y lo actual.
- Dar a los botones del celular 48–52 px y poner una sola acción principal por pantalla.
- Tematizar lo que el navegador dibuja: selección azul noche, cursor azul, foco azul claro, barra de desplazamiento.

### Don't:
- No usar rojo ni ámbar para categorías, tipos de servicio o decoración.
- No poner bordes laterales de color en tarjetas, filas o avisos.
- No usar emoji ni caracteres como íconos: siempre Material Symbols.
- No agregar etiquetas pequeñas encima de los títulos.
