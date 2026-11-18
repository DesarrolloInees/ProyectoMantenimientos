import { inject } from '@angular/core';
import { Router, Routes } from '@angular/router';
import { permisoGuard } from './core/infra';
import { Sesion } from './core/sesion';

/** Ruta protegida por uno o varios permisos (basta con tener uno). */
function r(path: string, permiso: string | string[] | null, cargar: () => Promise<unknown>) {
  return {
    path,
    data: permiso ? { permiso } : {},
    canActivate: [permisoGuard],
    loadComponent: cargar as never,
  };
}

export const routes: Routes = [
  { path: 'login', loadComponent: () => import('./login/login').then((m) => m.Login) },
  { path: 'recuperar', loadComponent: () => import('./cuenta/recuperar').then((m) => m.Recuperar) },
  {
    path: '',
    loadComponent: () => import('./shell/shell').then((m) => m.Shell),
    canActivate: [permisoGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: () => inject(Router).parseUrl(inject(Sesion).inicio()) },
      r('inicio', null, () => import('./inicio/inicio').then((m) => m.Inicio)),
      r('cuenta/clave', null, () => import('./cuenta/cambiar-clave').then((m) => m.CambiarClave)),

      // Operación (fase 1)
      r('planeacion', 'PLANEACION', () => import('./oficina/planeacion').then((m) => m.Planeacion)),
      r('cargas', 'PLANEACION', () => import('./oficina/cargas').then((m) => m.Cargas)),
      r('cargas/:id', 'PLANEACION', () => import('./oficina/carga-detalle').then((m) => m.CargaDetalle)),
      r('geolocalizar', 'PLANEACION', () => import('./oficina/geolocalizar').then((m) => m.Geolocalizar)),
      r('seguimiento', 'SEGUIMIENTO', () => import('./oficina/seguimiento').then((m) => m.Seguimiento)),
      r('maquinas', 'CONFIGURACION', () => import('./oficina/maquinas').then((m) => m.Maquinas)),
      r('rastreo', ['SEGUIMIENTO', 'PERSONAL'], () => import('./personal/rastreo').then((m) => m.Rastreo)),

      // Servicios
      r('ordenes', 'ORDENES', () => import('./ordenes/dias').then((m) => m.Dias)),
      r('ordenes/buscar', 'ORDENES', () => import('./ordenes/buscar').then((m) => m.Buscar)),
      r('ordenes/dia/:fecha', 'ORDENES', () => import('./ordenes/buscar').then((m) => m.Buscar)),
      r('ordenes/registrar', 'ORDENES', () => import('./ordenes/registrar').then((m) => m.Registrar)),
      r('ordenes/pdf', 'ORDENES', () => import('./ordenes/servicios-pdf').then((m) => m.ServiciosPdf)),
      r('ordenes/exportar', 'ORDENES', () => import('./ordenes/exportar').then((m) => m.Exportar)),
      r('ordenes/:id', 'ORDENES', () => import('./ordenes/servicio').then((m) => m.Servicio)),

      // Logística
      r('inventario', 'INVENTARIO', () => import('./inventario/stock').then((m) => m.Stock)),
      r('inventario/solicitudes', 'INVENTARIO', () => import('./inventario/solicitudes').then((m) => m.Solicitudes)),
      r('inventario/devoluciones', 'INVENTARIO', () => import('./inventario/devoluciones').then((m) => m.Devoluciones)),
      r('remisiones', 'REMISIONES', () => import('./remisiones/remisiones').then((m) => m.Remisiones)),
      r('remisiones/salteadas', 'REMISIONES', () => import('./remisiones/salteadas').then((m) => m.Salteadas)),

      // Motorizados (oficina)
      r('personal/horas-extra', 'PERSONAL', () => import('./personal/horas-extra').then((m) => m.HorasExtraAdmin)),
      r('personal/parqueaderos', 'PERSONAL', () => import('./personal/parqueaderos').then((m) => m.ParqueaderosAdmin)),
      r('personal/jornadas', 'PERSONAL', () => import('./personal/jornadas').then((m) => m.Jornadas)),
      r('personal/tiempos', 'PERSONAL', () => import('./personal/tiempos').then((m) => m.Tiempos)),
      r('personal/asistencia', 'PERSONAL', () => import('./personal/asistencia').then((m) => m.Asistencia)),
      r('personal/alertas', 'PERSONAL', () => import('./personal/alertas').then((m) => m.AlertasLogisticas)),

      r('transporte', 'TRANSPORTE', () => import('./transporte/transportes').then((m) => m.Transportes)),
      r('costos', 'COSTOS', () => import('./costos/costos').then((m) => m.Costos)),
      r('costos/cotizaciones', 'COSTOS', () => import('./costos/cotizaciones').then((m) => m.Cotizaciones)),

      // Reportes
      r('reportes/ejecutivo', 'REPORTES', () => import('./reportes/ejecutivo').then((m) => m.Ejecutivo)),
      r('reportes/tecnico', 'REPORTES', () => import('./reportes/tecnico').then((m) => m.ReporteTecnico)),
      r('reportes/repuestos', 'REPORTES', () => import('./reportes/repuestos').then((m) => m.ReporteRepuestos)),
      r('reportes/maquinas', 'REPORTES', () => import('./reportes/maquinas').then((m) => m.ReporteMaquinas)),
      r('reportes/historial', 'REPORTES', () => import('./reportes/historial').then((m) => m.ReporteHistorial)),
      r('reportes/trazabilidad', 'REPORTES', () => import('./reportes/trazabilidad').then((m) => m.Trazabilidad)),
      r('reportes/tarifas', 'REPORTES', () => import('./reportes/tarifas').then((m) => m.ReporteTarifas)),

      // Administración
      r('admin/usuarios', 'ADMINISTRACION', () => import('./admin/usuarios').then((m) => m.Usuarios)),
      r('admin/roles', 'ADMINISTRACION', () => import('./admin/roles').then((m) => m.Roles)),
      r('admin/catalogos', 'ADMINISTRACION', () => import('./admin/catalogos').then((m) => m.Catalogos)),
      r('admin/c/:entidad', ['ADMINISTRACION', 'INVENTARIO'], () => import('./admin/crud').then((m) => m.Crud)),
      r('admin/tarifas', 'ADMINISTRACION', () => import('./admin/tarifas').then((m) => m.Tarifas)),
      r('admin/importar', 'ADMINISTRACION', () => import('./admin/importar').then((m) => m.Importar)),
      r('admin/informacion', 'ADMINISTRACION', () => import('./admin/informacion').then((m) => m.Informacion)),

      // Motorizado (celular)
      r('campo', 'CAMPO', () => import('./campo/mi-dia').then((m) => m.MiDiaPagina)),
      r('campo/servicio/:id', 'CAMPO', () => import('./campo/servicio').then((m) => m.ServicioPagina)),
      r('campo/historial', 'CAMPO', () => import('./campo/historial').then((m) => m.HistorialPunto)),
      r('campo/inventario', 'CAMPO', () => import('./campo/inventario').then((m) => m.MiInventario)),
      r('campo/horas-extra', 'CAMPO', () => import('./campo/horas-extra').then((m) => m.MisHorasExtra)),
      r('campo/parqueaderos', 'CAMPO', () => import('./campo/parqueaderos').then((m) => m.MisParqueaderos)),
      r('campo/tiempos', 'CAMPO', () => import('./campo/tiempos').then((m) => m.MisTiempos)),
    ],
  },
  { path: '**', redirectTo: '' },
];
