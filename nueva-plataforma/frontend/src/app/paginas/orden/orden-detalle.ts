import { Component, HostBinding, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgTemplateOutlet } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import * as XLSX from 'xlsx';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';
import { Titulo } from '../../core/ui';
import { SelectBuscar } from '../../shared/select-buscar';
import { Horas, Notif, toastIA } from '../../shared/notificaciones';

/** Fila editable (partials/detalleFila.php) */
interface Fila {
  id: string;
  id_cliente: any;
  id_punto: any;
  fecha_individual: string;
  id_tecnico: any;
  id_manto: any;
  id_modalidad: any;
  id_maquina: any;
  obs: string;
  tiene_novedad: number;
  id_tipo_novedad: string;
  nombres_novedades: string;
  valor: string;
  valor_viaticos: number;
  json_repuestos: string;
  repuestos_db: string;
  sugeridos: any[];
  remision: string;
  remisionOriginal: string;
  entrada: string;
  salida: string;
  id_estado: any;
  id_calif: any;
  // UI
  puntos: any[];
  puntosCargados: boolean;
  maquinas: any[];
  delegacion: string;
  tipoMaquinaNombre: string;
  remisiones: { v: string; t: string }[];
  sinTarifa: boolean;
  cargandoValor: boolean;
  valorFlash: boolean;
  modalidadAuto: boolean;
  desp: { texto: string; clase: string; title: string };
  iaSegundos: number | null;
  obsExpandido: boolean;
  obsFlash: boolean;
  novPulse: boolean;
}

/** Port de ordenDetalleVista.php + ordenBusquedaVista.php + js/orden/ordenDetalle/*.js */
@Component({
  selector: 'app-orden-detalle',
  imports: [FormsModule, RouterLink, SelectBuscar, NgTemplateOutlet],
  styleUrls: ['./orden-detalle.css', './orden-busqueda.css'],
  templateUrl: './orden-detalle.html'
})
export class OrdenDetalle implements OnInit {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private auth = inject(Auth);
  private titulo = inject(Titulo);

  modo: 'fecha' | 'busqueda' = 'fecha';
  pagina = 'ordenDetalle';
  fecha = '';
  rol = 0;
  cargado = false;
  buscando = false;
  errorBusqueda = false;
  guardando = false;
  exportando = false;
  exportandoNov = false;

  clientes: any[] = [];
  tecnicos: any[] = [];
  mantos: any[] = [];
  repuestosCat: any[] = [];
  estados: any[] = [];
  califs: any[] = [];
  modalidades: any[] = [];
  festivos: string[] = [];
  novedadesCat: any[] = [];
  delegaciones: any[] = [];
  remisionesGlobales: Record<string, { numero_remision: any }[]> = {};

  filas: Fila[] = [];
  paginaActual = 1;
  readonly filasPorPagina = 6;
  private silenciar = true;

  // Búsqueda
  busq = { remision: '', id_cliente: '', id_punto: '', id_delegacion: '', fecha_inicio: '', fecha_fin: '' };
  puntosBusqueda: any[] = [];
  placeholderPuntoBusq = 'Seleccione Cliente...';
  totalBusqueda = 0;
  infoBusqueda = 'Esperando búsqueda...';
  haBuscado = false;

  // Modal repuestos
  modalRep: { fila: Fila; temporales: any[]; opciones: any[]; idSel: any; cantidad: number; origen: string; cargando: boolean; procesando: boolean } | null = null;
  // Modal novedades
  modalNov: { fila: Fila; seleccion: any[]; guardando: boolean } | null = null;

  /** number_format($n, 0, ',', '.') */
  static miles(n: any): string {
    return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  miles = OrdenDetalle.miles;

  @HostBinding('class.rol-5') get esRol5() {
    return this.rol === 5;
  }

  textoCliente = (c: any) => (c.nombre_cliente || '').substring(0, 20) + '...';
  textoPunto = (p: any) => (p.nombre_punto || '') + (p.inactivo ? ' ⚠️' : '');
  textoMaquina = (m: any) => (m.tipo_label !== undefined ? m.tipo_label : `${m.device_id} (${m.nombre_tipo_maquina})${m.inactivo ? ' ⚠️' : ''}`);
  claseInactivo = (o: any) => (o.inactivo ? 'text-red-600 font-bold' : '');

  async ngOnInit() {
    this.rol = this.auth.nivel;
    this.modo = this.route.snapshot.data['modo'] === 'busqueda' ? 'busqueda' : 'fecha';
    this.pagina = this.modo === 'busqueda' ? 'ordenDetalleBuscar' : 'ordenDetalle';
    setTimeout(() => (this.silenciar = false), 2000);
    if (this.modo === 'busqueda') {
      const r = await this.api.get('ordenDetalleBuscar', 'index');
      this.aplicarCatalogos(r);
      this.delegaciones = r.listaDelegaciones;
      this.titulo.set(r.titulo);
    } else {
      this.fecha = this.route.snapshot.paramMap.get('fecha') || this.route.snapshot.queryParamMap.get('fecha') || '';
      const r = await this.api.get('ordenDetalle', 'index', { fecha: this.fecha });
      this.aplicarCatalogos(r);
      this.fecha = r.fecha;
      this.titulo.set(r.titulo);
      this.filas = (r.servicios || []).map((s: any) => this.crearFila(s));
      this.filas.forEach((f) => this.cargarRemisionesLocal(f));
      this.calcularDesplazamientos();
    }
    this.cargado = true;
  }

  private aplicarCatalogos(r: any) {
    this.clientes = r.listaClientes || [];
    this.tecnicos = r.listaTecnicos || [];
    this.mantos = r.listaMantos || [];
    this.repuestosCat = r.listaRepuestos || [];
    this.estados = r.listaEstados || [];
    this.califs = r.listaCalifs || [];
    this.modalidades = r.listaModalidades || [];
    this.festivos = r.listaFestivos || [];
    this.novedadesCat = r.listaNovedades || [];
    this.remisionesGlobales = r.remisionesGlobales || {};
  }

  private crearFila(s: any): Fila {
    const tieneNov = Number(s.tiene_novedad) === 1 || !!s.ids_novedades;
    let sugeridos: any[] = [];
    try {
      sugeridos = JSON.parse(s.repuestos_tecnico || '[]') || [];
    } catch {}
    const valorNum = Number(s.valor_servicio) || 0;
    return {
      id: String(s.id_ordenes_servicio),
      id_cliente: s.id_cliente,
      id_punto: s.id_punto ?? '',
      fecha_individual: s.fecha_visita,
      id_tecnico: s.id_tecnico,
      id_manto: s.id_manto,
      id_modalidad: s.id_modalidad,
      id_maquina: s.id_maquina,
      obs: s.que_se_hizo ?? '',
      tiene_novedad: tieneNov ? 1 : 0,
      id_tipo_novedad: s.ids_novedades ?? '',
      nombres_novedades: s.nombres_novedades ?? '',
      valor: OrdenDetalle.miles(valorNum),
      valor_viaticos: Number(s.valor_viaticos) || 0,
      json_repuestos: s.repuestos_json || '[]',
      repuestos_db: s.repuestos_texto || '',
      sugeridos: Array.isArray(sugeridos) ? sugeridos : [],
      remision: s.numero_remision ?? '',
      remisionOriginal: s.numero_remision ?? '',
      entrada: s.hora_entrada ? String(s.hora_entrada).substring(0, 5) : '',
      salida: s.hora_salida ? String(s.hora_salida).substring(0, 5) : '',
      id_estado: s.id_estado,
      id_calif: s.id_calif,
      puntos: [{ id_punto: s.id_punto ?? '', nombre_punto: s.nombre_punto ?? '' }],
      puntosCargados: false,
      maquinas: [{ id_maquina: s.id_maquina, device_id: s.device_id, nombre_tipo_maquina: s.nombre_tipo_maquina, id_tipo_maquina: s.id_tipo_maquina, tipo_label: s.device_id ?? '' }],
      delegacion: s.delegacion ?? 'Sin asignar',
      tipoMaquinaNombre: s.nombre_tipo_maquina ?? '',
      remisiones: s.numero_remision ? [{ v: s.numero_remision, t: `${s.numero_remision} ✓ (actual)` }] : [{ v: '', t: '- Sin remisión -' }],
      sinTarifa: false,
      cargandoValor: false,
      valorFlash: false,
      modalidadAuto: false,
      desp: { texto: '-', clase: 'text-gray-400', title: '' },
      iaSegundos: null,
      obsExpandido: false,
      obsFlash: false,
      novPulse: false
    };
  }

  // ------------------------------------------------------------ paginación

  get totalPaginas() {
    return Math.max(1, Math.ceil(this.filas.length / this.filasPorPagina));
  }

  get filasVisibles(): Fila[] {
    if (this.modo === 'busqueda') return this.filas;
    const ini = (this.paginaActual - 1) * this.filasPorPagina;
    return this.filas.slice(ini, ini + this.filasPorPagina);
  }

  get infoPagina() {
    if (!this.filas.length) return '–';
    const ini = (this.paginaActual - 1) * this.filasPorPagina;
    const fin = Math.min(ini + this.filasPorPagina, this.filas.length);
    return `${ini + 1} - ${fin} de ${this.filas.length}`;
  }

  cambiarPagina(dir: number) {
    const n = this.paginaActual + dir;
    if (n > 0 && n <= this.totalPaginas) {
      this.paginaActual = n;
      this.calcularDesplazamientos();
    }
  }

  // ------------------------------------------------------------ AJAX de fila (detalleAjax.js)

  private post(accion: string, datos: Record<string, any>) {
    return this.api.post(this.pagina, accion, datos);
  }

  async abrirPuntos(f: Fila) {
    if (f.puntosCargados) return;
    if (!f.id_cliente) {
      alert('⚠️ Seleccione primero un cliente.');
      return;
    }
    await this.cargarPuntos(f, f.id_cliente, true);
  }

  async cargarPuntos(f: Fila, idCliente: any, mantener = false) {
    const previo = f.id_punto;
    const nombrePrevio = f.puntos.find((p) => String(p.id_punto) === String(previo))?.nombre_punto ?? '';
    if (!mantener) {
      f.puntos = [];
      f.maquinas = [];
    }
    try {
      const data: any[] = await this.post('ajaxObtenerPuntos', { id_cliente: idCliente, id_punto_actual: previo || '', nombre_punto_actual: nombrePrevio || '' });
      f.puntos = data;
      f.puntosCargados = true;
      if (mantener && previo) {
        f.id_punto = previo;
      } else if (data.length > 0) {
        f.id_punto = data[0].id_punto;
        this.cargarMaquinas(f, data[0].id_punto);
      }
      if (!this.silenciar && data.length > 0) Notif.datosCargados('Puntos', data.length);
    } catch (e) {
      console.error('Error cargando puntos:', e);
      Notif.error('No se pudieron cargar los puntos');
    }
  }

  cambioCliente(f: Fila) {
    f.puntosCargados = false;
    this.cargarPuntos(f, f.id_cliente);
  }

  async cargarMaquinas(f: Fila, idPunto: any) {
    const previaId = f.id_maquina;
    const previaNombre = f.maquinas.find((m) => String(m.id_maquina) === String(previaId))?.device_id ?? '';
    f.maquinas = [];
    this.post('ajaxObtenerDelegacion', { id_punto: idPunto }).then((d) => (f.delegacion = d.delegacion || 'Sin asignar'));
    try {
      const data: any[] = await this.post('ajaxObtenerMaquinas', { id_punto: idPunto, id_maquina_actual: previaId || '', nombre_maquina_actual: previaNombre || '' });
      f.maquinas = data;
      if (data.length > 0) {
        f.id_maquina = data[0].id_maquina;
        this.actualizarTipoMaquina(f);
        this.actualizarTarifa(f);
        if (!this.silenciar) Notif.datosCargados('Máquinas', data.length);
      } else {
        f.id_maquina = '';
      }
    } catch (e) {
      console.error('Error cargando máquinas:', e);
      Notif.error('No se pudieron cargar las máquinas');
    }
  }

  actualizarTipoMaquina(f: Fila) {
    const m = f.maquinas.find((x) => String(x.id_maquina) === String(f.id_maquina));
    f.tipoMaquinaNombre = m?.nombre_tipo_maquina ?? '';
  }

  cambioMaquina(f: Fila) {
    this.actualizarTipoMaquina(f);
    this.actualizarTarifa(f);
  }

  async actualizarTarifa(f: Fila) {
    const m = f.maquinas.find((x) => String(x.id_maquina) === String(f.id_maquina));
    const idTipoMaquina = m?.id_tipo_maquina ?? '';
    f.sinTarifa = false;
    if (!idTipoMaquina || !f.id_manto) return;
    f.cargandoValor = true;
    try {
      const data = await this.post('ajaxObtenerPrecio', {
        id_tipo_maquina: idTipoMaquina,
        id_tipo_mantenimiento: f.id_manto,
        id_modalidad: f.id_modalidad,
        fecha_visita: f.fecha_individual
      });
      const precio = parseInt(data.precio);
      if (precio === -1) {
        f.valor = '';
        f.sinTarifa = true;
        Notif.error(`Fila ${f.id}: Sin tarifa configurada`);
      } else {
        f.valor = new Intl.NumberFormat('es-CO').format(precio);
        f.valorFlash = true;
        setTimeout(() => (f.valorFlash = false), 500);
      }
    } catch {
      Notif.error('Error de conexión al obtener precio');
    } finally {
      f.cargandoValor = false;
    }
  }

  cambioTecnico(f: Fila) {
    this.calcularDesplazamientos(f);
    this.cargarRemisiones(f);
  }

  private cargarRemisionesLocal(f: Fila) {
    if (!f.id_tecnico || f.id_tecnico == 0) {
      f.remisiones = [{ v: '', t: '- Sin técnico -' }];
      return;
    }
    const actual = f.remisionOriginal || f.remision || '';
    f.remisionOriginal = actual;
    const ops: { v: string; t: string }[] = [{ v: '', t: '- Sin remisión -' }];
    if (actual) ops.push({ v: actual, t: `${actual} ✓ (actual)` });
    (this.remisionesGlobales[f.id_tecnico] || []).forEach((r) => {
      if (String(r.numero_remision) !== String(actual)) ops.push({ v: String(r.numero_remision), t: String(r.numero_remision) });
    });
    f.remisiones = ops;
    f.remision = actual;
  }

  /** En la búsqueda las remisiones se piden al servidor (ajaxObtenerRemisiones) */
  private async cargarRemisionesServidor(f: Fila) {
    const actual = f.remision;
    const data: any[] = await this.post('ajaxObtenerRemisiones', { id_tecnico: f.id_tecnico, remision_actual: actual });
    const ops = [{ v: '', t: 'S/N' }];
    (data || []).forEach((r) => ops.push({ v: String(r.numero_remision), t: String(r.numero_remision) }));
    f.remisiones = ops;
    f.remision = actual;
  }

  cargarRemisiones(f: Fila) {
    if (this.modo === 'busqueda' && !Object.keys(this.remisionesGlobales).length) this.cargarRemisionesServidor(f);
    else this.cargarRemisionesLocal(f);
  }

  // ------------------------------------------------------------ fechas (detalleFechaUtils.js)

  cambioFecha(f: Fila) {
    if (Horas.esDiaEspecial(f.fecha_individual, this.festivos)) {
      if (String(f.id_modalidad) !== '2') {
        f.id_modalidad = 2;
        f.modalidadAuto = true;
        setTimeout(() => (f.modalidadAuto = false), 2000);
        Notif.festivo(f.fecha_individual, new Date(f.fecha_individual + 'T12:00:00').getDay() === 0);
      }
    } else if (String(f.id_modalidad) === '2') {
      f.id_modalidad = 1;
    }
    this.actualizarTarifa(f);
  }

  inputHora(f: Fila, campo: 'entrada' | 'salida', ev: Event) {
    const el = ev.target as HTMLInputElement;
    const sel = el.selectionStart ?? 0;
    const v = Horas.mascara(el.value);
    el.value = v;
    f[campo] = v;
    let pos = sel;
    if (v.length >= 3 && pos === 2) pos = 3;
    try {
      el.setSelectionRange(pos, pos);
    } catch {}
  }

  blurHora(f: Fila, campo: 'entrada' | 'salida', ev: Event) {
    const el = ev.target as HTMLInputElement;
    f[campo] = Horas.normalizar(el.value);
    el.value = f[campo];
    this.calcularDesplazamientos(f);
  }

  // ------------------------------------------------------------ desplazamientos (detalleDesplazamientos.js)

  calcularDesplazamientos(editada: Fila | null = null) {
    const objetivo = editada ? parseInt(editada.id_tecnico, 10) : null;
    let datos = this.filas.map((f) => ({
      f,
      tecnico: parseInt(f.id_tecnico, 10) || 0,
      ent: Horas.aMinutos(f.entrada),
      sal: Horas.aMinutos(f.salida)
    }));
    if (objetivo !== null && !isNaN(objetivo)) datos = datos.filter((d) => d.tecnico === objetivo);
    datos.sort((a, b) => (a.tecnico !== b.tecnico ? a.tecnico - b.tecnico : (a.ent ?? 99999) - (b.ent ?? 99999)));
    for (let i = 0; i < datos.length; i++) {
      const a = datos[i];
      if (i === 0 || datos[i - 1].tecnico !== a.tecnico || a.tecnico === 0) {
        a.f.desp = { texto: '00:00', clase: 'text-gray-400', title: '' };
        continue;
      }
      const p = datos[i - 1];
      if (a.ent === null || p.sal === null) {
        a.f.desp = { texto: '--', clase: 'text-gray-400', title: '' };
        continue;
      }
      const diff = a.ent - p.sal;
      if (diff < 0) {
        a.f.desp = { texto: 'Err H.', clase: 'text-red-500', title: 'Conflicto: Entrada antes de la salida anterior' };
      } else {
        const h = Math.floor(diff / 60);
        const m = diff % 60;
        const txt = (h > 0 ? `${h}h ` : '') + `${m}m`;
        if (diff > 60) {
          a.f.desp = { texto: txt, clase: 'text-red-600 bg-red-100 px-1 rounded', title: '' };
          if (diff > 120 && !this.silenciar) Notif.desplazamientoAlto(a.f.id, txt);
        } else {
          a.f.desp = { texto: txt, clase: 'text-green-600', title: '' };
        }
      }
    }
  }

  // ------------------------------------------------------------ textarea / IA

  toggleObs(f: Fila, el: HTMLTextAreaElement) {
    if (f.obsExpandido) {
      el.style.height = '';
      f.obsExpandido = false;
    } else {
      el.style.height = 'auto';
      el.style.height = el.scrollHeight + 2 + 'px';
      f.obsExpandido = true;
    }
  }

  async mejorarTextoIA(f: Fila) {
    const texto = (f.obs || '').trim();
    if (!texto) return toastIA('warn', 'No hay texto escrito para mejorar.');
    const esEstadoInicial = /^\s*\[ESTADO\s+INICIAL\s*:/i.test(texto);
    const cacheKey = 'ia_' + texto.length + '_' + texto.slice(0, 120);
    try {
      const g = sessionStorage.getItem(cacheKey);
      if (g) {
        this.aplicarTextoIA(f, g, esEstadoInicial);
        return;
      }
    } catch {}
    f.iaSegundos = 0;
    const ticker = setInterval(() => f.iaSegundos !== null && f.iaSegundos++, 1000);
    const ctrl = new AbortController();
    const to = setTimeout(() => ctrl.abort(), 22000);
    try {
      const fd = new FormData();
      fd.append('texto', texto);
      fd.append('es_estado_inicial', esEstadoInicial ? '1' : '0');
      const resp = await fetch(this.api.url(this.pagina, 'ajaxMejorarTextoIA'), { method: 'POST', body: fd, signal: ctrl.signal });
      const data = await resp.json();
      if (data.status === 'ok' && data.texto_mejorado && data.texto_mejorado.trim() !== '') {
        const fin = data.texto_mejorado.trim();
        if (this.aplicarTextoIA(f, fin, esEstadoInicial)) {
          try {
            sessionStorage.setItem(cacheKey, fin);
          } catch {}
        }
      } else {
        toastIA('error', data.msg || 'La IA no pudo procesar el texto. Tus datos originales están a salvo.');
      }
    } catch (e: any) {
      if (e?.name === 'AbortError') toastIA('error', 'La IA tardó demasiado y se canceló. Tu texto original quedó intacto.');
      else toastIA('error', 'Error de conexión con el servidor. Tu texto original quedó intacto.');
    } finally {
      clearInterval(ticker);
      clearTimeout(to);
      f.iaSegundos = null;
    }
  }

  private aplicarTextoIA(f: Fila, fin: string, esEstadoInicial: boolean): boolean {
    if (esEstadoInicial && !/^\s*\[ESTADO\s+INICIAL\s*:/i.test(fin)) {
      toastIA('warn', 'La IA quitó la sección de [ESTADO INICIAL]. Este tipo de comentarios no se puede editar con IA para preservar el diagnóstico original. Tu texto original permanece intacto.');
      return false;
    }
    const minimo = Math.floor((f.obs || '').trim().length * 0.8);
    if (fin.length < minimo) {
      toastIA('warn', 'La IA recortó demasiado el comentario, así que se descartó para no perder información. Tu texto original quedó intacto.');
      return false;
    }
    f.obs = fin;
    f.obsFlash = true;
    setTimeout(() => (f.obsFlash = false), 1500);
    return true;
  }

  // ------------------------------------------------------------ repuestos (detalleRepuestos.js)

  totalRepuestos(f: Fila): number {
    try {
      const arr = JSON.parse(f.json_repuestos || '[]');
      return Array.isArray(arr) ? arr.reduce((a: number, it: any) => a + (parseInt(it.cantidad) || 1), 0) : 0;
    } catch {
      return 0;
    }
  }

  private convertirTextoARepuestos(texto: string) {
    const r: any[] = [];
    if (!texto || !texto.trim()) return r;
    const ignorar = ['NO', 'NINGUNO', 'NINGUNA', 'SIN REPUESTOS', 'N/A', 'NA', '.', '-', '0', 'VACIO'];
    texto.split(',').forEach((item) => {
      let limpio = item.trim();
      if (!limpio || ignorar.includes(limpio.toUpperCase())) return;
      let origen = 'INEES';
      let cantidad = 1;
      const mc = limpio.match(/\(x(\d+)\)$/i);
      if (mc) {
        cantidad = parseInt(mc[1]);
        limpio = limpio.replace(/\(x\d+\)$/i, '').trim();
      }
      let nombre = limpio;
      if (limpio.toUpperCase().includes('(PROSEGUR)')) {
        origen = 'PROSEGUR';
        nombre = limpio.replace(/\(PROSEGUR\)/gi, '').trim();
      } else if (limpio.toUpperCase().includes('(INEES)')) {
        nombre = limpio.replace(/\(INEES\)/gi, '').trim();
      }
      const cat = this.repuestosCat.find(
        (x) => x.nombre_repuesto.toLowerCase() === nombre.toLowerCase() || x.nombre_repuesto.toLowerCase().includes(nombre.toLowerCase())
      );
      r.push({ id: cat ? cat.id_repuesto : '', nombre, origen, cantidad });
    });
    return r;
  }

  async abrirModalRepuestos(f: Fila) {
    if (!f.id_tecnico || f.id_tecnico == 0) {
      alert('⚠️ Primero debes seleccionar un TÉCNICO en la fila.');
      return;
    }
    let temporales: any[] = [];
    let valido = false;
    if (f.json_repuestos && f.json_repuestos.trim() !== '[]') {
      try {
        temporales = JSON.parse(f.json_repuestos).map((r: any) => ({ ...r, cantidad: parseInt(r.cantidad) || 1 }));
        valido = true;
      } catch {}
    }
    if (!valido && f.repuestos_db) temporales = this.convertirTextoARepuestos(f.repuestos_db);
    this.modalRep = { fila: f, temporales, opciones: [], idSel: '', cantidad: 1, origen: 'INEES', cargando: true, procesando: false };
    try {
      const inv: any[] = await this.post('ajaxObtenerStockTecnico', { id_tecnico: f.id_tecnico });
      const stock: Record<string, number> = {};
      (inv || []).forEach((i) => (stock[i.id_repuesto] = parseInt(i.cantidad_actual)));
      this.modalRep.opciones = this.repuestosCat.map((g) => {
        const s = stock[g.id_repuesto] || 0;
        return {
          id: g.id_repuesto,
          stock: s,
          nombre: g.nombre_repuesto,
          texto: s > 0 ? `✅ ${g.nombre_repuesto} (Stock Mío: ${s})` : `📦 ${g.nombre_repuesto} (Sin Stock)`
        };
      });
    } catch (e) {
      console.error('Error cargando inventario:', e);
    } finally {
      if (this.modalRep) this.modalRep.cargando = false;
    }
  }

  cerrarModalRep() {
    this.modalRep = null;
  }

  async agregarRepuestoALista() {
    const m = this.modalRep!;
    const op = m.opciones.find((o) => String(o.id) === String(m.idSel));
    if (!m.idSel || !op) return alert('⚠️ Seleccione un repuesto.');
    const cantidad = m.cantidad ? parseInt(String(m.cantidad)) : 1;
    if (cantidad < 1) return alert('La cantidad debe ser mayor a 0.');
    if (m.origen === 'INEES' && cantidad > op.stock) {
      alert(`🛑 STOCK INSUFICIENTE (INEES)\n\nDisponible en stock: ${op.stock}\nSolicitado: ${cantidad}\n\nSi el repuesto lo suministró el cliente, cambie el origen a "PROSEGUR".`);
      return;
    }
    m.procesando = true;
    try {
      const data = await this.post('ajaxGestionarRepuestoRT', {
        tipo: 'agregar', id_orden: m.fila.id, id_repuesto: m.idSel, cantidad, origen: m.origen, id_tecnico: m.fila.id_tecnico
      });
      if (data.status === 'ok') {
        if (m.origen === 'INEES') {
          op.stock -= cantidad;
          op.texto = op.stock > 0 ? `✅ ${op.nombre} (Stock Técnico: ${op.stock})` : `📦 ${op.nombre} (Sin Stock)`;
          m.opciones = [...m.opciones];
        }
        m.temporales.push({ id: m.idSel, nombre: op.nombre, origen: m.origen, cantidad });
        this.actualizarJsonFila(m.fila, m.temporales);
        m.cantidad = 1;
        m.idSel = '';
      } else {
        alert('❌ Error del servidor: ' + data.msg);
      }
    } catch (err) {
      console.error(err);
      alert('❌ Ocurrió un error de red al guardar el repuesto. Por favor, verifica tu conexión e intenta de nuevo.');
    } finally {
      m.procesando = false;
    }
  }

  async borrarRepuestoTemporal(index: number) {
    if (!confirm('¿Eliminar este repuesto de la orden? (Si era de INEES, el stock se devolverá al técnico)')) return;
    const m = this.modalRep!;
    const item = m.temporales[index];
    try {
      const data = await this.post('ajaxGestionarRepuestoRT', { tipo: 'eliminar', id_orden: m.fila.id, id_repuesto: item.id, origen: item.origen, id_tecnico: m.fila.id_tecnico });
      if (data.status === 'ok') {
        if (item.origen === 'INEES') {
          const op = m.opciones.find((o) => String(o.id) === String(item.id));
          if (op) {
            op.stock += parseInt(item.cantidad) || 1;
            op.texto = `✅ ${op.nombre} (Stock Mío: ${op.stock})`;
            m.opciones = [...m.opciones];
          }
        }
        m.temporales.splice(index, 1);
        this.actualizarJsonFila(m.fila, m.temporales);
      } else {
        alert('Error al eliminar: ' + data.msg);
      }
    } catch {
      alert('❌ Error de red al intentar eliminar el repuesto.');
    }
  }

  private actualizarJsonFila(f: Fila, temporales: any[]) {
    f.json_repuestos = JSON.stringify(temporales);
  }

  guardarCambiosModal() {
    const m = this.modalRep!;
    m.fila.json_repuestos = JSON.stringify(m.temporales);
    this.cerrarModalRep();
    Notif.cambioGuardado();
  }

  // ------------------------------------------------------------ novedades (detalleNovedades.js)

  abrirModalNovedad(f: Fila) {
    const ids = f.id_tipo_novedad && f.id_tipo_novedad.trim() !== '' ? f.id_tipo_novedad.split(',') : [];
    this.modalNov = { fila: f, seleccion: ids, guardando: false };
  }

  async guardarNovedad() {
    const m = this.modalNov!;
    if (!m.seleccion.length) return alert('Debes seleccionar al menos un tipo de novedad.');
    m.guardando = true;
    try {
      const resp = await this.api.postForm(this.pagina, 'ajaxGuardarNovedad', { tipo: 'guardar', id_orden: m.fila.id, novedades: m.seleccion });
      if (resp.success) {
        m.fila.tiene_novedad = 1;
        m.fila.id_tipo_novedad = m.seleccion.join(',');
        m.fila.nombres_novedades = this.novedadesCat.filter((n) => m.seleccion.map(String).includes(String(n.id_tipo_novedad))).map((n) => n.nombre_novedad).join(', ');
        m.fila.novPulse = true;
        setTimeout(() => (m.fila.novPulse = false), 2000);
        this.modalNov = null;
      } else alert('Error al guardar novedad.');
    } catch {
      alert('Error de conexión al guardar.');
    } finally {
      if (this.modalNov) this.modalNov.guardando = false;
    }
  }

  async eliminarNovedad() {
    const m = this.modalNov!;
    if (!confirm('¿Seguro que deseas quitar todas las novedades de esta orden?')) return;
    try {
      const resp = await this.api.postForm(this.pagina, 'ajaxGuardarNovedad', { tipo: 'eliminar', id_orden: m.fila.id });
      if (resp.success) {
        m.fila.tiene_novedad = 0;
        m.fila.id_tipo_novedad = '';
        m.fila.nombres_novedades = '';
        this.modalNov = null;
      } else alert('No se pudo eliminar.');
    } catch {
      alert('Error de conexión al eliminar.');
    }
  }

  tooltipNovedad(f: Fila) {
    if (f.tiene_novedad && f.nombres_novedades) return f.nombres_novedades;
    return f.tiene_novedad ? 'Tiene Novedad Reportada' : 'Reportar Novedad';
  }

  // ------------------------------------------------------------ guardar

  private validarCamposEstrictos(): boolean {
    let ok = true;
    this.filas.forEach((f, i) => {
      if ((f.obs || '').trim() === '') {
        ok = false;
        Notif.mostrar(`❌ Fila ${i + 1}: El campo "¿Qué se Realizó?" no puede estar vacío.`, 'error', 5000);
      }
    });
    return ok;
  }

  procesarGuardado() {
    if (this.modo === 'busqueda' && !this.filas.length) {
      alert('⚠️ No hay servicios en la tabla para guardar.');
      return;
    }
    let correctivoSinRep = false;
    this.filas.forEach((f) => {
      const txt = (this.mantos.find((m) => String(m.id_tipo_mantenimiento) === String(f.id_manto))?.nombre_completo || '').toUpperCase().trim();
      if (txt.includes('CORRECTIVO') && this.totalRepuestos(f) === 0) correctivoSinRep = true;
    });
    let msg =
      this.modo === 'busqueda'
        ? '¿Estás seguro de que deseas guardar todos los cambios de tu búsqueda actual?'
        : '¿Estás seguro de que deseas guardar todos los cambios de esta página?';
    if (correctivoSinRep) msg = '⚠️ <b>¡ADVERTENCIA!</b><br><br>Hay servicios <b>CORRECTIVOS que NO tienen repuestos</b>.<br>Esto es inusual.<br><br>¿Deseas guardar de todas formas?';
    if (!this.validarCamposEstrictos()) return;
    Notif.confirmar(msg, () => this.ejecutarGuardadoJSON());
  }

  private async ejecutarGuardadoJSON() {
    Notif.enviandoFormulario(this.filas.length);
    this.guardando = true;
    const datos: Record<string, any> = {};
    this.filas.forEach((f) => {
      datos[f.id] = {
        id_cliente: f.id_cliente ?? '', id_punto: f.id_punto ?? '', fecha_individual: f.fecha_individual ?? '', id_tecnico: f.id_tecnico ?? '',
        id_manto: f.id_manto ?? '', id_modalidad: f.id_modalidad ?? '', id_maquina: f.id_maquina ?? '', obs: f.obs ?? '',
        tiene_novedad: String(f.tiene_novedad), id_tipo_novedad: f.id_tipo_novedad ?? '', valor: f.valor ?? '',
        valor_viaticos: String(f.valor_viaticos), json_repuestos: f.json_repuestos, remision: f.remision ?? '',
        entrada: f.entrada ?? '', salida: f.salida ?? '', id_estado: f.id_estado ?? '', id_calif: f.id_calif ?? ''
      };
    });
    try {
      const fechaOrigen = this.modo === 'busqueda' ? new Date().toISOString().slice(0, 10) : this.fecha;
      const data = await this.api.post(this.pagina, 'ajaxGuardarCambiosJSON', { fecha_origen: fechaOrigen, json_data: JSON.stringify(datos) });
      if (data.status === 'ok') {
        alert('✅ ' + data.msg);
        if (this.modo === 'busqueda') this.realizarBusqueda();
      } else if (data.status === 'warning') {
        alert('⚠️ ' + data.msg);
        if (this.modo === 'busqueda') this.realizarBusqueda();
      } else alert('❌ Error: ' + data.msg);
    } catch (e) {
      console.error('Error guardando:', e);
      alert('❌ Error de red o del servidor al intentar guardar.');
    } finally {
      this.guardando = false;
    }
  }

  // ------------------------------------------------------------ búsqueda (ordenBusquedaVista.php)

  async cambioClienteBusqueda() {
    this.busq.id_punto = '';
    this.puntosBusqueda = [];
    if (!this.busq.id_cliente) {
      this.placeholderPuntoBusq = 'Seleccione Cliente...';
      return;
    }
    this.placeholderPuntoBusq = 'Cargando...';
    try {
      const data: any[] = await this.api.postForm('ordenDetalleBuscar', 'ajaxObtenerPuntos', { id_cliente: this.busq.id_cliente });
      this.puntosBusqueda = data;
      this.placeholderPuntoBusq = data.length ? 'Todos los puntos' : 'Sin puntos asignados';
    } catch {
      this.placeholderPuntoBusq = 'Error al cargar';
    }
  }

  async realizarBusqueda() {
    this.buscando = true;
    this.errorBusqueda = false;
    this.haBuscado = true;
    try {
      const r = await this.api.postForm('ordenDetalleBuscar', 'ajaxBuscarOrdenes', this.busq);
      this.filas = (r.servicios || []).map((s: any) => this.crearFila(s));
      this.totalBusqueda = this.filas.length;
      this.infoBusqueda = `Filtro aplicado (${this.busq.fecha_inicio || 'Inicio'} a ${this.busq.fecha_fin || 'Fin'})`;
      this.filas.forEach((f) => this.cargarRemisionesServidor(f));
      this.calcularDesplazamientos();
    } catch {
      this.errorBusqueda = true;
      this.totalBusqueda = 0;
      this.filas = [];
    } finally {
      this.buscando = false;
    }
  }

  // ------------------------------------------------------------ Excel (detalleExcel.js)

  private difHoras(i: string, f: string) {
    if (!i || !f) return '';
    const d1 = new Date(`2000-01-01T${i}`);
    const d2 = new Date(`2000-01-01T${f}`);
    if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return '';
    const diff = d2.getTime() - d1.getTime();
    if (diff < 0) return '';
    const mins = Math.floor(diff / 60000);
    return `${String(Math.floor(mins / 60)).padStart(2, '0')}:${String(mins % 60).padStart(2, '0')}`;
  }

  async exportarExcelLimpio() {
    let accion = 'ajaxExportarDetalle';
    let datos: Record<string, any> = { fecha: this.fecha };
    let sufijo = this.fecha;
    if (this.modo === 'busqueda') {
      accion = 'ajaxExportarBusqueda';
      datos = { ...this.busq };
      const partes = ['Filtro'];
      const cli = this.clientes.find((c) => String(c.id_cliente) === String(this.busq.id_cliente));
      if (cli) partes.push(cli.nombre_cliente.trim());
      const pun = this.puntosBusqueda.find((p) => String(p.id_punto) === String(this.busq.id_punto));
      if (pun) partes.push(pun.nombre_punto.trim());
      if (partes.length === 1) partes.push('General');
      sufijo = partes.join('_').replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_áéíóúÁÉÍÓÚñÑ-]/g, '');
    }
    this.exportando = true;
    try {
      const r = await this.api.postForm(this.pagina, accion, datos);
      if (r.status !== 'ok' || !r.datos || !r.datos.length) {
        alert('No se encontraron registros con los filtros aplicados.');
        return;
      }
      this.generarExcelServicios(r.datos, sufijo);
    } catch (e) {
      console.error(e);
      alert('Error de conexión al exportar.');
    } finally {
      this.exportando = false;
    }
  }

  async exportarExcelNovedades() {
    this.exportandoNov = true;
    try {
      const r = await this.api.postForm(this.pagina, 'ajaxExportarDetalle', { fecha: this.fecha });
      if (r.status !== 'ok') return alert('Error al obtener datos.');
      const con = r.datos.filter((d: any) => parseInt(d.tiene_novedad) === 1 || d.ids_novedades);
      if (!con.length) return alert('No hay servicios con novedad en esta fecha.');
      this.generarExcelNovedades(con, this.fecha);
    } catch (e) {
      console.error(e);
      alert('Error de conexión al exportar.');
    } finally {
      this.exportandoNov = false;
    }
  }

  private generarExcelServicios(datos: any[], fecha: string) {
    const wb = XLSX.utils.book_new();
    const porDel: Record<string, any[]> = {};
    let viatico: any = null;
    let prevTec: any = null;
    let prevFecha: any = null;
    let prevSalida: any = null;
    const norm = (s: any) => String(s || 'SIN ASIGNAR').replace(/[\r\n\t\s]+/g, ' ').trim().toUpperCase();
    datos.forEach((d, idx) => {
      const del = norm(d.delegacion);
      (porDel[del] ||= []);
      let desp = '';
      if (d.nombre_tecnico === prevTec && d.fecha_visita === prevFecha && prevSalida) desp = this.difHoras(prevSalida, d.hora_entrada);
      prevTec = d.nombre_tecnico;
      prevFecha = d.fecha_visita;
      prevSalida = d.hora_salida;
      const txt = (d.tipo_servicio || '').toLowerCase();
      let pb = txt.includes('basico') || txt.includes('básico') ? 'X' : '';
      const pp = txt.includes('profundo') || txt.includes('completo') ? 'X' : '';
      const co = txt.includes('correctivo') || txt.includes('reparacion') ? 'X' : '';
      if (!pb && !pp && !co && txt.includes('preventivo')) pb = 'X';
      let dur = d.tiempo_servicio || '';
      if (!dur || dur === '00:00' || dur === '00:00:00') dur = this.difHoras(d.hora_entrada, d.hora_salida);
      const vs = parseFloat(d.valor_servicio) || 0;
      const vv = parseFloat(d.valor_viaticos) || 0;
      porDel[del].push([
        d.device_id || '', d.numero_remision || '', d.nombre_cliente || '', d.nombre_punto || '', pb, pp, co, vs,
        d.que_se_hizo || d.actividades_realizadas || '', del, d.fecha_visita || '', d.nombre_tecnico || '', d.nombre_tipo_maquina || '',
        d.tipo_servicio || '', d.hora_entrada || '', d.hora_salida || '', dur, desp, d.repuestos_texto || '',
        d.estado_maquina || d.nombre_estado || '', d.nombre_calificacion || '', d.tipo_zona || ''
      ]);
      if (vv > 0) viatico = ['', '', '', '', '', '', '', vv, 'TARIFA ADICIONAL POR DÍA', '', '', '', '', '', '', '', '', '', '', '', '', ''];
      const sig = datos[idx + 1];
      const cerrar = !sig || sig.fecha_visita !== d.fecha_visita || sig.nombre_tecnico !== d.nombre_tecnico || norm(sig.delegacion) !== del;
      if (cerrar && viatico) {
        porDel[del].push(viatico);
        viatico = null;
      }
    });
    const enc = [
      'Device_id', 'Número de Remisión', 'Cliente', 'Nombre Punto', 'Preventivo Básico', 'Preventivo Profundo', 'Correctivo', 'Tarifa',
      'Observaciones', 'Delegación', 'Fecha', 'Técnico', 'Tipo de Máquina', 'Tipo de Servicio', 'Hora Entrada', 'Hora Salida',
      'Duración', 'Desplazamiento', 'Repuestos', 'Estado de la Máquina', 'Calificación del Servicio', 'Modalidad Operativa'
    ];
    const fmtMoneda = '_-"$"* #,##0_-;-"$"* #,##0_-;-"$"* "-"??_-;-_-@_-';
    Object.keys(porDel).forEach((del) => {
      const ws: any = XLSX.utils.aoa_to_sheet([enc, ...porDel[del]]);
      if (ws['!ref']) {
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
          const ref = XLSX.utils.encode_cell({ c: 7, r: R });
          if (!ws[ref]) ws[ref] = { t: 'n', v: 0 };
          ws[ref].t = 'n';
          ws[ref].z = fmtMoneda;
        }
      }
      ws['!cols'] = [15, 14, 25, 25, 8, 8, 8, 15, 50, 15, 12, 20, 15, 20, 10, 10, 10, 12, 30, 15, 15, 15].map((w) => ({ wch: w }));
      XLSX.utils.book_append_sheet(wb, ws, del.replace(/[:\\/?*\[\]]/g, '').substring(0, 30) || 'General');
    });
    XLSX.writeFile(wb, `Reporte_Servicios_${fecha}.xlsx`);
  }

  private generarExcelNovedades(datos: any[], fecha: string) {
    const lista = datos.map((d) => ({
      'Tipo de Novedad': d.nombres_novedades_resueltos || d.nombres_novedades || 'SIN ESPECIFICAR',
      'Descripción del Servicio': d.que_se_hizo || d.actividades_realizadas || '',
      Cliente: d.nombre_cliente || '',
      Punto: d.nombre_punto || '',
      Delegación: (d.delegacion || '').trim(),
      'Tipo de Máquina': d.nombre_tipo_maquina || '',
      Device_id: d.device_id || '',
      'Número de Remisión': d.numero_remision || '',
      'Fecha del Servicio': d.fecha_visita || '',
      'Nombre del Técnico': d.nombre_tecnico || ''
    }));
    const wb = XLSX.utils.book_new();
    const ws: any = XLSX.utils.json_to_sheet(lista);
    const headers = Object.keys(lista[0]);
    ws['!cols'] = headers.map((h) => {
      let max = h.length;
      lista.forEach((r: any) => {
        const v = r[h] ? String(r[h]) : '';
        if (v.length > max) max = v.length;
      });
      return { wch: Math.max(10, Math.min(70, max)) + 2 };
    });
    XLSX.utils.book_append_sheet(wb, ws, 'Novedades');
    XLSX.writeFile(wb, `Novedades_${fecha}.xlsx`);
  }
}
