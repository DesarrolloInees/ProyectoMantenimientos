/**
 * Port de js/orden/ordenDetalle/detalleNotificaciones.js (y crearNotificaciones.js):
 * notificaciones tipo "toast" apiladas, con sonido, y modal de confirmación.
 */
const NotifConfig = { duracion: 4000, posicion: 'top-right', sonido: true, maxNotificaciones: 3 };

const Tipos: Record<string, { icono: string; color: string; borderColor: string; sonido: string }> = {
  exito: { icono: 'fa-check-circle', color: 'bg-green-500', borderColor: 'border-green-600', sonido: 'success' },
  info: { icono: 'fa-info-circle', color: 'bg-blue-500', borderColor: 'border-blue-600', sonido: 'info' },
  advertencia: { icono: 'fa-exclamation-triangle', color: 'bg-yellow-500', borderColor: 'border-yellow-600', sonido: 'warning' },
  error: { icono: 'fa-times-circle', color: 'bg-red-500', borderColor: 'border-red-600', sonido: 'error' },
  festivo: { icono: 'fa-calendar-day', color: 'bg-purple-500', borderColor: 'border-purple-600', sonido: 'info' },
  precio: { icono: 'fa-dollar-sign', color: 'bg-emerald-500', borderColor: 'border-emerald-600', sonido: 'success' },
  stock: { icono: 'fa-box', color: 'bg-orange-500', borderColor: 'border-orange-600', sonido: 'warning' },
  remision: { icono: 'fa-receipt', color: 'bg-pink-500', borderColor: 'border-pink-600', sonido: 'warning' },
  guardado: { icono: 'fa-save', color: 'bg-teal-500', borderColor: 'border-teal-600', sonido: 'success' }
};

let activas: string[] = [];

function reproducirSonido(tipo: string) {
  try {
    const Ctx = (window as any).AudioContext || (window as any).webkitAudioContext;
    const ctx = new Ctx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    const f: Record<string, number[]> = { success: [523.25, 659.25], info: [440], warning: [392, 440], error: [329.63, 293.66] };
    const freqs = f[tipo] || f['info'];
    osc.frequency.value = freqs[0];
    gain.gain.value = 0.1;
    osc.type = 'sine';
    osc.start(ctx.currentTime);
    if (freqs.length > 1) osc.frequency.setValueAtTime(freqs[1], ctx.currentTime + 0.1);
    osc.stop(ctx.currentTime + 0.2);
  } catch {}
}

function reposicionar() {
  activas.forEach((id, i) => {
    const n = document.getElementById(id);
    if (n) n.style.top = `${16 + i * 90}px`;
  });
}

export function cerrarNotificacion(id: string) {
  const n = document.getElementById(id);
  if (!n) return;
  n.style.opacity = '0';
  n.style.transform = 'translateX(100px)';
  setTimeout(() => {
    n.remove();
    activas = activas.filter((x) => x !== id);
    reposicionar();
  }, 300);
}

export function mostrarNotificacion(mensaje: string, tipo = 'info', duracion: number | null = null) {
  if (activas.length >= NotifConfig.maxNotificaciones && activas.length) cerrarNotificacion(activas[0]);
  const c = Tipos[tipo] || Tipos['info'];
  const id = `notif_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
  const n = document.createElement('div');
  n.id = id;
  n.className = `fixed z-[9999] ${c.color} text-white px-6 py-4 rounded-lg shadow-2xl border-l-4 ${c.borderColor} transform transition-all duration-300 flex items-center gap-3 min-w-[300px] max-w-[450px] top-4 right-4`;
  n.style.top = `${16 + activas.length * 90}px`;
  n.innerHTML = `
    <div class="flex-shrink-0"><i class="fas ${c.icono} text-2xl"></i></div>
    <div class="flex-1"><p class="font-semibold text-sm leading-relaxed">${mensaje}</p></div>
    <button class="flex-shrink-0 hover:bg-white hover:bg-opacity-20 rounded p-1 transition"><i class="fas fa-times"></i></button>`;
  (n.querySelector('button') as HTMLButtonElement).onclick = () => cerrarNotificacion(id);
  n.style.opacity = '0';
  n.style.transform = 'translateX(100px)';
  document.body.appendChild(n);
  activas.push(id);
  if (NotifConfig.sonido) reproducirSonido(c.sonido);
  setTimeout(() => {
    n.style.opacity = '1';
    n.style.transform = 'translateX(0)';
  }, 10);
  setTimeout(() => cerrarNotificacion(id), duracion || NotifConfig.duracion);
}

function formatearFecha(fecha: string) {
  if (!fecha) return '';
  const [y, m, d] = fecha.split('-');
  const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  return `${d} ${meses[parseInt(m) - 1]} ${y}`;
}

export function formatearMoneda(v: number) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(v);
}

export const Notif = {
  mostrar: mostrarNotificacion,
  cerrar: cerrarNotificacion,
  festivo: (fecha: string, esDomingo = false) =>
    mostrarNotificacion(`📅 ${esDomingo ? 'Domingo' : 'Festivo'} detectado: ${formatearFecha(fecha)}<br>✨ Modalidad cambiada a INTERURBANO automáticamente`, 'festivo', 5000),
  desplazamientoAlto: (idFila: any, tiempo: string) => mostrarNotificacion(`⏰ Desplazamiento alto en Fila ${idFila}<br>🚗 Tiempo: ${tiempo}`, 'advertencia', 3000),
  cambioGuardado: () => mostrarNotificacion(`✅ Cambios guardados correctamente`, 'exito', 2000),
  precioCalculado: (idFila: any, precio: number) => mostrarNotificacion(`💰 Precio calculado en Fila #${idFila}<br>✅ Tarifa: ${formatearMoneda(precio)}`, 'precio', 3000),
  remisionDuplicada: (num: string) => mostrarNotificacion(`⚠️ Remisión DUPLICADA: ${num}<br>📋 Esta remisión ya fue usada en otra fila`, 'remision', 5000),
  remisionValida: (num: string) => mostrarNotificacion(`✅ Remisión válida: ${num}<br>📝 Disponible para uso`, 'exito', 2000),
  tecnicoSinRemisiones: (nombre: string) => mostrarNotificacion(`🚫 ${nombre} no tiene remisiones<br>⚠️ Asigne un talonario en administración`, 'advertencia', 5000),
  stockInsuficiente: (rep: string, disp: number, sol: number) =>
    mostrarNotificacion(`⚠️ Stock insuficiente: ${rep}<br>📦 Disponible: ${disp} | Solicitado: ${sol}`, 'stock', 4000),
  repuestosAgregados: (c: number, t: number) => mostrarNotificacion(`✅ ${c} repuesto(s) agregado(s)<br>📦 Total en lista: ${t} items`, 'exito', 2500),
  datosCargados: (tipo: string, cantidad: number, idFila: any = '') =>
    mostrarNotificacion(`🔄 ${tipo} cargados${idFila ? ` en Fila #${idFila}` : ''}<br>📋 ${cantidad} disponible(s)`, 'info', 2000),
  filaAgregada: (n: any) => mostrarNotificacion(`➕ Nueva fila agregada: #${n}<br>📝 Completa los datos del servicio`, 'info', 2000),
  filaEliminada: (n: any) => mostrarNotificacion(`🗑️ Fila #${n} eliminada<br>✅ Servicio removido del reporte`, 'info', 2000),
  autoGuardado: () => mostrarNotificacion(`💾 Progreso guardado automáticamente<br>🔒 Tus datos están seguros`, 'guardado', 2000),
  borradorRecuperado: (n: number) => mostrarNotificacion(`📂 Borrador recuperado exitosamente<br>📋 ${n} servicio(s) restaurado(s)`, 'exito', 3000),
  incoherencia: (idFila: any, tipoServicio: string, tieneRepuestos: boolean) => {
    let m = '';
    if (tipoServicio.includes('CORRECTIVO') && !tieneRepuestos) m = `🤔 Fila #${idFila}: Correctivo SIN repuestos<br>¿Seguro que no se usaron piezas?`;
    else if (tipoServicio.includes('PREVENTIVO') && tieneRepuestos) m = `🤔 Fila #${idFila}: Preventivo CON repuestos<br>¿Debería ser Correctivo?`;
    if (m) mostrarNotificacion(m, 'advertencia', 4000);
  },
  camposIncompletos: (idFila: any, campos: string[]) => mostrarNotificacion(`⚠️ Fila #${idFila}: Datos incompletos<br>📝 Faltan: ${campos.join(', ')}`, 'advertencia', 4000),
  horaInvalida: (idFila: any, campo: string) => mostrarNotificacion(`⚠️ Hora inválida en Fila #${idFila}<br>⏰ Use formato 24h (00:00 - 23:59) en ${campo}`, 'error', 4000),
  duracionCalculada: (idFila: any, d: string) => mostrarNotificacion(`⏱️ Duración calculada en Fila #${idFila}<br>✅ Tiempo de servicio: ${d}`, 'info', 2000),
  error: (m: string) => mostrarNotificacion(`❌ Error: ${m}`, 'error', 4000),
  advertencia: (m: string) => mostrarNotificacion(`⚠️ Advertencia: ${m}`, 'advertencia', 3500),
  guardadoExitoso: (n: number) => mostrarNotificacion(`🎉 ¡Reporte guardado exitosamente!<br>✅ ${n} servicio(s) registrado(s)`, 'exito', 5000),
  enviandoFormulario: (n: number) => mostrarNotificacion(`⏳ Guardando reporte...<br>📤 Procesando ${n} servicio(s)`, 'info', 10000),
  inventarioCargado: (tec: string, n: number) => mostrarNotificacion(`📦 Inventario de ${tec}<br>✅ ${n} repuesto(s) disponible(s)`, 'stock', 2500),
  maquinaAutoSeleccionada: (idFila: any, dev: string) => mostrarNotificacion(`🤖 Máquina seleccionada automáticamente<br>📟 Device ID: ${dev}`, 'info', 2000),

  /** Modal "¿Estás seguro?" con botón "Confirmar y Guardar". */
  confirmar(cuerpo: string, onConfirmar: () => void, textoBoton = 'Confirmar y Guardar') {
    const id = 'modal_confirm_' + Date.now();
    const modal = document.createElement('div');
    modal.id = id;
    modal.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300';
    modal.innerHTML = `
      <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full transform scale-100 transition-transform duration-300 border-t-4 border-blue-600">
        <div class="text-center mb-6">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4"><i class="fas fa-question text-blue-600 text-xl"></i></div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">¿Estás seguro?</h3>
          <div class="mt-2"><p class="text-sm text-gray-500">${cuerpo}</p></div>
        </div>
        <div class="flex justify-center gap-3">
          <button data-c class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-medium">Cancelar</button>
          <button data-ok class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium shadow-lg">${textoBoton}</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    const cerrar = (cb?: () => void) => {
      modal.style.opacity = '0';
      setTimeout(() => {
        modal.remove();
        cb?.();
      }, 300);
    };
    (modal.querySelector('[data-c]') as HTMLElement).onclick = () => cerrar();
    (modal.querySelector('[data-ok]') as HTMLElement).onclick = () => cerrar(onConfirmar);
    modal.style.opacity = '0';
    setTimeout(() => (modal.style.opacity = '1'), 10);
  }
};

/** Toast simple de la IA (toastIA en las vistas PHP). */
export function toastIA(tipo: 'warn' | 'error' | 'ok', mensaje: string) {
  const colores = {
    warn: { bg: '#fffbeb', border: '#f59e0b', icon: 'fa-exclamation-triangle' },
    error: { bg: '#fef2f2', border: '#dc2626', icon: 'fa-times-circle' },
    ok: { bg: '#f0fdf4', border: '#16a34a', icon: 'fa-check-circle' }
  };
  const c = colores[tipo] || colores.warn;
  const t = document.createElement('div');
  t.innerHTML = '<i class="fas ' + c.icon + '"></i> <span></span>';
  t.style.cssText =
    'position:fixed;top:18px;right:18px;z-index:999999;max-width:420px;display:flex;gap:.6rem;align-items:flex-start;padding:.9rem 1.1rem;border-radius:10px;' +
    'background:' + c.bg + ';border-left:4px solid ' + c.border + ';color:#1a2233;font:600 .8rem "DM Sans",sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.15);';
  (t.querySelector('span') as HTMLElement).textContent = mensaje;
  document.body.appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s';
    t.style.opacity = '0';
    setTimeout(() => t.remove(), 300);
  }, tipo === 'ok' ? 2500 : 5000);
}

/** Utilidades de horas compartidas (detalleFechaUtils.js / timeManager.js). */
export const Horas = {
  aMinutos(h: string): number | null {
    if (!h) return null;
    const p = h.split(':');
    if (p.length < 2) return null;
    return parseInt(p[0]) * 60 + parseInt(p[1]);
  },
  /** Máscara mientras escribe: "1430" -> "14:30", "5" -> "05" */
  mascara(valor: string): string {
    let nums = String(valor || '').replace(/\D/g, '');
    if (nums.length > 4) nums = nums.slice(0, 4);
    if (nums.length === 1 && parseInt(nums[0]) > 2) nums = '0' + nums;
    return nums.length <= 2 ? nums : nums.slice(0, 2) + ':' + nums.slice(2);
  },
  /** Normalización al salir del campo: rellena con ceros y limita 23:59 */
  normalizar(valor: string): string {
    let nums = String(valor || '').replace(/\D/g, '');
    if (!nums) return '';
    while (nums.length < 4) nums += '0';
    nums = nums.slice(0, 4);
    const h = Math.min(23, parseInt(nums.slice(0, 2), 10));
    const m = Math.min(59, parseInt(nums.slice(2, 4), 10));
    return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
  },
  esDiaEspecial(fecha: string, festivos: string[]): boolean {
    if (!fecha) return false;
    if (festivos.includes(fecha)) return true;
    return new Date(fecha + 'T12:00:00').getDay() === 0;
  }
};
