import { Component, OnInit, inject } from '@angular/core';
import { Api } from '../../core/api';

/**
 * Port de asistenciaVista.php. La lógica de cálculo, edición en vivo y exportación vive en
 * js/asistencia/*.js (copiados sin cambios a public/js/asistencia): se cargan igual que en PHP
 * y apuntan a los endpoints /api/asistencia?accion=...
 */
@Component({
  selector: 'app-asistencia',
  template: `
    <div class="asistencia-container">
      <div class="card card-modern">
        <div class="card-header-modern">
          <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i> {{ titulo }}</h5>
          <span class="badge bg-light text-dark"><i class="fas fa-file-excel me-1"></i> Plantilla con recargos</span>
        </div>
        <div class="card-body">
          <form id="formHuellero" class="mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
              <div class="md:col-span-2">
                <label for="archivo_huellero" class="form-label fw-bold text-secondary block mb-1">Sube el archivo CSV del reloj biométrico</label>
                <input type="file" class="form-control form-control-lg" name="archivo_huellero" id="archivo_huellero" accept=".csv" required>
              </div>
              <div>
                <button type="submit" class="w-full py-2 text-white font-bold" id="btnProcesar" style="background-color: #2a5298; border:none; border-radius: 8px;"><i class="fas fa-cogs"></i> Procesar Datos</button>
              </div>
            </div>
          </form>
          <div id="alertaMensaje" class="alert d-none shadow-sm rounded" role="alert"></div>
          <div id="editorContainer" class="d-none">
            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border-start border-4 border-success">
              <div>
                <h4 class="text-success mb-1 font-bold text-lg"><i class="fas fa-edit"></i> Edición de Nómina en Vivo</h4>
                <p class="text-muted small mb-0">Base 100% + recargos, horas extra nocturnas al 175%, dom/fest automático y recargos ordinarios. Todo se recalcula solo y puedes sobrescribir cualquier celda antes de exportar.</p>
              </div>
              <button id="btnGuardarDescargar" class="btn btn-gradient"><i class="fas fa-file-excel fs-5 me-2"></i> Exportar Consolidado</button>
            </div>
            <div class="panel-general">
              <div class="res-item"><span>Empleados</span><b id="tgl-empleados">0</b></div>
              <div class="res-item"><span>Horas extra (todos)</span><b id="tgl-horas-extras">00:00</b></div>
              <div class="res-item res-verde"><span>$ Horas extra</span><b id="tgl-valor-extras">$0</b></div>
              <div class="res-item res-morado"><span>$ Recargos y dom/fest</span><b id="tgl-valor-recargos">$0</b></div>
              <div class="res-item res-total"><span>Total general a pagar</span><b id="tgl-total">$0</b></div>
            </div>
            <div id="tablasEmpleados"></div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class Asistencia implements OnInit {
  private api = inject(Api);
  titulo = 'Procesador de Asistencias (Huellero + Servicios)';

  async ngOnInit() {
    const r: any = await this.api.get('asistencia', 'index');
    this.titulo = r.titulo || this.titulo;
    const w = window as any;
    w.AsistenciaConfig = {
      baseUrl: '/',
      urls: {
        procesar: this.api.url('asistencia', 'procesarArchivo'),
        guardar: this.api.url('asistencia', 'guardarEdicion'),
        descargar: this.api.url('asistencia', 'descargarExcel')
      },
      tasas: r.tasasDefecto || {},
      horario: r.horarioDefecto || {},
      divisor: r.divisor || 210
    };
    if (!document.getElementById('css-asistencia')) {
      const link = document.createElement('link');
      link.id = 'css-asistencia';
      link.rel = 'stylesheet';
      link.href = 'css/asistencia.css';
      document.head.appendChild(link);
    }
    if (w.Asistencia?.eventos) {
      // Scripts ya cargados en una visita anterior: sólo se vuelven a enlazar al nuevo DOM
      w.Asistencia.eventos.iniciar();
      return;
    }
    for (const f of ['core', 'calculo', 'render', 'eventos', 'exportar']) {
      await new Promise<void>((ok, fail) => {
        const s = document.createElement('script');
        s.src = `js/asistencia/${f}.js?v=1`;
        s.onload = () => ok();
        s.onerror = () => fail(new Error('No se pudo cargar ' + f));
        document.body.appendChild(s);
      });
    }
    // En el PHP, eventos.js se carga antes que exportar.js; al enlazar el botón de exportar
    // exportar.js aún no existía, por eso se enlaza aquí después de cargar todo.
    w.Asistencia.exportar?.enlazar?.();
  }
}
