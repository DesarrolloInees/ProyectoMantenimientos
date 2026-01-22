// ==========================================
// CONFIGURACIÓN GLOBAL
// ==========================================

// 1. Asegurar que el objeto existe (ya debería existir por el PHP)
window.DetalleConfig = window.DetalleConfig || {};

// 2. Definir propiedades SOLO SI NO EXISTEN (para no borrar los datos de PHP)
if (!window.DetalleConfig.catalogoRepuestos) window.DetalleConfig.catalogoRepuestos = [];
if (!window.DetalleConfig.FESTIVOS_DB) window.DetalleConfig.FESTIVOS_DB = [];
if (!window.DetalleConfig.listaNovedades) window.DetalleConfig.listaNovedades = [];

window.DetalleConfig.listaClientes = [];
window.DetalleConfig.listaPuntos = [];
window.DetalleConfig.listaTecnicos = [];
window.DetalleConfig.listaMantos = [];
window.DetalleConfig.listaModalidades = [];
window.DetalleConfig.listaEstados = [];
window.DetalleConfig.listaCalifs = [];

// 3. Estado de la aplicación
window.DetalleConfig.repuestosTemporales = [];
window.DetalleConfig.stockActualTecnico = {};

// 4. Paginación
window.DetalleConfig.paginaActual = 1;
window.DetalleConfig.filasPorPagina = 6;
window.DetalleConfig.totalFilas = 0;
window.DetalleConfig.totalPaginas = 0;

/**
 * Inicializar datos desde PHP
 * Esta función será llamada desde otros scripts si es necesario,
 * o mapeará las variables globales existentes.
 */
window.DetalleConfig.inicializarDatosDetalle = function(datos) {
    if (datos.repuestos) window.DetalleConfig.catalogoRepuestos = datos.repuestos;
    if (datos.festivos) window.DetalleConfig.FESTIVOS_DB = datos.festivos;
    if (datos.novedades) window.DetalleConfig.listaNovedades = datos.novedades;
    if (datos.clientes) window.DetalleConfig.listaClientes = datos.clientes;
    if (datos.tecnicos) window.DetalleConfig.listaTecnicos = datos.tecnicos;
    if (datos.mantos) window.DetalleConfig.listaMantos = datos.mantos;
    if (datos.modalidades) window.DetalleConfig.listaModalidades = datos.modalidades;
    if (datos.estados) window.DetalleConfig.listaEstados = datos.estados;
    if (datos.califs) window.DetalleConfig.listaCalifs = datos.califs;

    console.log('📊 Datos de Detalle cargados en Config:', window.DetalleConfig);
};

/**
 * Verificar si una cadena está vacía
 */
window.DetalleConfig.isEmpty = function(str) {
    return (!str || str.trim() === '');
};

// Si existen las variables globales definidas en el PHP (inline script), las capturamos aquí automáticamente
// Esto soluciona el problema de orden de carga
if (typeof catalogoRepuestos !== 'undefined') window.DetalleConfig.catalogoRepuestos = catalogoRepuestos;
if (typeof FESTIVOS_DB !== 'undefined') window.DetalleConfig.FESTIVOS_DB = FESTIVOS_DB;
if (typeof listaNovedades !== 'undefined') window.DetalleConfig.listaNovedades = listaNovedades;