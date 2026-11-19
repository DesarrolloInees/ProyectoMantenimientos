import Swal, { SweetAlertIcon, SweetAlertOptions } from 'sweetalert2';
import { Injectable, signal } from '@angular/core';

export { Swal };

/** Helpers de SweetAlert2 (igual a los usados en las vistas PHP). */
export const ui = {
  ok(titulo: string, texto = '', icon: SweetAlertIcon = 'success') {
    return Swal.fire({ icon, title: titulo, text: texto, confirmButtonColor: '#2563eb' });
  },
  error(titulo: string, texto = '') {
    return Swal.fire({ icon: 'error', title: titulo, text: texto, confirmButtonColor: '#dc2626' });
  },
  info(titulo: string, texto = '') {
    return Swal.fire({ icon: 'info', title: titulo, text: texto });
  },
  warn(titulo: string, texto = '') {
    return Swal.fire({ icon: 'warning', title: titulo, text: texto });
  },
  html(titulo: string, html: string, icon: SweetAlertIcon = 'info') {
    return Swal.fire({ icon, title: titulo, html });
  },
  async confirmar(titulo: string, texto = '', confirmar = 'Sí, continuar', cancelar = 'Cancelar', opts: SweetAlertOptions = {}): Promise<boolean> {
    const r = await Swal.fire({
      icon: 'warning',
      title: titulo,
      text: texto,
      showCancelButton: true,
      confirmButtonText: confirmar,
      cancelButtonText: cancelar,
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#6b7280',
      ...opts
    } as SweetAlertOptions);
    return r.isConfirmed;
  },
  cargando(titulo = 'Procesando...', texto = 'Por favor espere') {
    Swal.fire({ title: titulo, text: texto, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
  },
  cerrar() {
    Swal.close();
  },
  toast(titulo: string, icon: SweetAlertIcon = 'success', ms = 3000) {
    return Swal.fire({ toast: true, position: 'top-end', icon, title: titulo, showConfirmButton: false, timer: ms, timerProgressBar: true });
  }
};

/** Título de la página (plantillaVista.php: $titulo). */
@Injectable({ providedIn: 'root' })
export class Titulo {
  readonly valor = signal<string>('Panel de Control');
  set(t: string) {
    this.valor.set(t);
    document.title = t ? `${t} - INEES` : 'INEES Mantenimientos';
  }
}

/** Utilidades de formato equivalentes a PHP. */
export const fmt = {
  /** number_format($n, 0, ',', '.') */
  miles(n: any, dec = 0): string {
    const v = Number(n ?? 0) || 0;
    return v.toLocaleString('es-CO', { minimumFractionDigits: dec, maximumFractionDigits: dec });
  },
  pesos(n: any): string {
    return '$ ' + fmt.miles(n, 0);
  },
  /** date('d/m/Y', strtotime($f)) */
  fecha(f: any): string {
    if (!f) return '';
    const s = String(f).substring(0, 10);
    const [y, m, d] = s.split('-');
    return d && m && y ? `${d}/${m}/${y}` : String(f);
  },
  hoy(): string {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  },
  mesActual(): string {
    return fmt.hoy().substring(0, 7);
  },
  hora(h: any): string {
    return h ? String(h).substring(0, 5) : '';
  },
  esc(s: any): string {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c]!);
  }
};
