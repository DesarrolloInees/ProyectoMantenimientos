import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';

/**
 * Cliente HTTP con la misma convención de la versión PHP:
 *   BASE_URL + pagina  con  accion=...  (POST como FormData => $_POST)
 * Endpoint backend: /api/{pagina}?accion={accion}
 */
@Injectable({ providedIn: 'root' })
export class Api {
  private http = inject(HttpClient);

  url(pagina: string, accion?: string, params: Record<string, any> = {}): string {
    const p = new URLSearchParams();
    if (accion) p.set('accion', accion);
    Object.entries(params).forEach(([k, v]) => {
      if (v === undefined || v === null) return;
      if (Array.isArray(v)) v.forEach((x) => p.append(k + '[]', String(x)));
      else p.set(k, String(v));
    });
    const qs = p.toString();
    return `/api/${pagina}${qs ? '?' + qs : ''}`;
  }

  get<T = any>(pagina: string, accion = 'index', params: Record<string, any> = {}): Promise<T> {
    return firstValueFrom(this.http.get<T>(this.url(pagina, accion, params)));
  }

  /** POST multipart/form-data (equivalente a $_POST / $_FILES). */
  post<T = any>(pagina: string, accion: string, data: Record<string, any> | FormData = {}): Promise<T> {
    const fd = data instanceof FormData ? data : Api.toFormData(data);
    return firstValueFrom(this.http.post<T>(this.url(pagina, accion), fd));
  }

  /** POST application/x-www-form-urlencoded (como $.post de jQuery). */
  postForm<T = any>(pagina: string, accion: string, data: Record<string, any> = {}): Promise<T> {
    const body = new HttpParams({ fromObject: Api.aplanar(data) as any });
    return firstValueFrom(
      this.http.post<T>(this.url(pagina, accion), body.toString(), {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
      })
    );
  }

  /** POST JSON (equivalente a php://input). */
  postJson<T = any>(pagina: string, accion: string, body: any): Promise<T> {
    return firstValueFrom(this.http.post<T>(this.url(pagina, accion), body));
  }

  /** Descarga un archivo (Excel/PDF) y lo guarda con el nombre que envía el servidor. */
  async descargar(pagina: string, accion: string, data: Record<string, any> | FormData = {}, metodo: 'GET' | 'POST' = 'POST', nombrePorDefecto = 'archivo'): Promise<void> {
    let resp: HttpResponse<Blob>;
    if (metodo === 'GET') {
      resp = await firstValueFrom(
        this.http.get(this.url(pagina, accion, data as Record<string, any>), { observe: 'response', responseType: 'blob' })
      );
    } else {
      const fd = data instanceof FormData ? data : Api.toFormData(data);
      resp = await firstValueFrom(this.http.post(this.url(pagina, accion), fd, { observe: 'response', responseType: 'blob' }));
    }
    const blob = resp.body as Blob;
    if (blob.type.includes('application/json')) {
      const txt = await blob.text();
      let msg = txt;
      try {
        const j = JSON.parse(txt);
        msg = j.message || j.mensaje || j.error || txt;
      } catch {}
      throw new Error(msg);
    }
    const nombre = Api.nombreArchivo(resp.headers.get('Content-Disposition')) || nombrePorDefecto;
    Api.guardarBlob(blob, nombre);
  }

  /** Abre una URL del backend en una pestaña nueva (PDF en línea). */
  abrir(pagina: string, accion: string, params: Record<string, any> = {}): void {
    window.open(this.url(pagina, accion, params), '_blank');
  }

  /** Envía un formulario oculto (POST) a una pestaña nueva (para PDFs grandes). */
  abrirPost(pagina: string, accion: string, data: Record<string, any>, target = '_blank'): void {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = this.url(pagina, accion);
    form.target = target;
    Object.entries(Api.aplanar(data)).forEach(([k, v]) => {
      const vals = Array.isArray(v) ? v : [v];
      vals.forEach((x) => {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = k;
        i.value = String(x ?? '');
        form.appendChild(i);
      });
    });
    document.body.appendChild(form);
    form.submit();
    form.remove();
  }

  static guardarBlob(blob: Blob, nombre: string) {
    const a = document.createElement('a');
    const u = URL.createObjectURL(blob);
    a.href = u;
    a.download = nombre;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(u), 2000);
  }

  static nombreArchivo(cd: string | null): string | null {
    if (!cd) return null;
    const utf = /filename\*=UTF-8''([^;]+)/i.exec(cd);
    if (utf) return decodeURIComponent(utf[1].replace(/"/g, ''));
    const m = /filename="?([^";]+)"?/i.exec(cd);
    return m ? m[1] : null;
  }

  /** Aplana objetos/arrays con notación PHP: a[0][b] = x */
  static aplanar(data: Record<string, any>, prefijo = '', out: Record<string, any> = {}): Record<string, any> {
    Object.entries(data ?? {}).forEach(([k, v]) => {
      const clave = prefijo ? `${prefijo}[${k}]` : k;
      if (v === undefined) return;
      if (v instanceof File || v instanceof Blob) {
        out[clave] = v;
      } else if (Array.isArray(v)) {
        v.forEach((x, i) => {
          if (x !== null && typeof x === 'object' && !(x instanceof File) && !(x instanceof Blob)) {
            Api.aplanar(x, `${clave}[${i}]`, out);
          } else {
            const ck = `${clave}[]`;
            if (!out[ck]) out[ck] = [];
            out[ck].push(x === null ? '' : x);
          }
        });
      } else if (v !== null && typeof v === 'object') {
        Api.aplanar(v, clave, out);
      } else {
        out[clave] = v === null ? '' : v;
      }
    });
    return out;
  }

  static toFormData(data: Record<string, any>): FormData {
    const fd = new FormData();
    Object.entries(Api.aplanar(data)).forEach(([k, v]) => {
      if (Array.isArray(v)) v.forEach((x) => fd.append(k, x instanceof Blob ? x : String(x)));
      else fd.append(k, v instanceof Blob ? v : String(v));
    });
    return fd;
  }
}
