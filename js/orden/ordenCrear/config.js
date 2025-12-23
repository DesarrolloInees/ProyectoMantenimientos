/**
 * MÓDULO DE CONFIGURACIÓN (config.js)
 * Centraliza el estado de la aplicación
 */
window.AppConfig = {
    // 1. Contenedor de datos (Listas maestras)
    datos: {
        clientes: [],
        mantos: [],
        tecnicos: [],
        estados: [],
        califs: [],
        repuestos: [],
        festivos: []
    },

    // 2. Variables de estado
    contadorFilas: 0,
    almacenRepuestos: {},
    ignorarCambios: false,
    enviandoFormulario: false,

    // 3. Constantes
    CLAVE_GUARDADO: 'borrador_orden_servicios',

    /**
     * Inicializar datos globales recibidos desde PHP
     */
    inicializarDatosGlobales: function (datosRecibidos) {
        this.datos.clientes = datosRecibidos.clientes || [];
        this.datos.mantos = datosRecibidos.mantos || [];
        this.datos.tecnicos = datosRecibidos.tecnicos || [];
        this.datos.estados = datosRecibidos.estados || [];
        this.datos.califs = datosRecibidos.califs || [];
        this.datos.repuestos = datosRecibidos.repuestos || [];
        this.datos.festivos = datosRecibidos.festivos || [];

        console.log('✅ Configuración cargada. Clientes:', this.datos.clientes.length);
    }
};