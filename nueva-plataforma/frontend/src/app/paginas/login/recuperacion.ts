import { Component, Input, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

/** Lista de requisitos de contraseÃ±a (misma lÃ³gica de cambiarPasswordValidacion.js). */
@Component({
  selector: 'app-requisitos-pwd',
  template: `
    <ul class="text-sm space-y-1 text-gray-500" [class]="caja ? 'bg-gray-50 p-3 rounded-lg' : ''">
      @for (r of lista; track r.k) {
        <li [class.text-green-600]="r.ok" [class.text-gray-500]="!r.ok">
          <i class="fa-solid mr-2 w-4 inline-block" [class.fa-check]="r.ok" [class.text-green-600]="r.ok" [class.fa-times]="!r.ok" [class.text-red-500]="!r.ok"></i>{{ r.t }}
        </li>
      }
    </ul>
  `
})
export class RequisitosPwd {
  @Input() pwd = '';
  @Input() caja = true;
  @Input() textoSimbolo = 'Un sÃ­mbolo (@, $, !, %, *, ? o &)';
  get lista() {
    const p = this.pwd || '';
    return [
      { k: 'l', t: 'Al menos 8 caracteres', ok: p.length >= 8 },
      { k: 'mi', t: 'Una letra minÃºscula', ok: /[a-z]/.test(p) },
      { k: 'ma', t: 'Una letra mayÃºscula', ok: /[A-Z]/.test(p) },
      { k: 'n', t: 'Un nÃºmero', ok: /\d/.test(p) },
      { k: 's', t: this.textoSimbolo, ok: /[@$!%*?&.]/.test(p) }
    ];
  }
}

export function pwdValida(p: string, c: string): boolean {
  return p.length >= 8 && /[a-z]/.test(p) && /[A-Z]/.test(p) && /\d/.test(p) && /[@$!%*?&.]/.test(p) && c.length > 0 && p === c;
}

/** Port de cambiarPasswordVista.php (cambio de contraseÃ±a obligatorio). */
@Component({
  selector: 'app-cambiar-password',
  imports: [FormsModule, RequisitosPwd],
  template: `
    <div class="bg-gray-100 flex items-center justify-center min-h-screen p-4" style="font-family: 'Poppins', sans-serif;">
      <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">
        <div class="text-center mb-6">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-4">
            <i class="fa-solid fa-shield-halved text-yellow-600 text-2xl"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-800">Cambio de ContraseÃ±a</h2>
          <p class="text-sm text-gray-500 mt-1">Por seguridad, debes crear una nueva contraseÃ±a antes de continuar.</p>
        </div>

        @if (error) {
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation mr-2"></i><span class="sm:inline">{{ textoError }}</span>
          </div>
        }

        <form (submit)="enviar($event)" class="space-y-4">
          <div>
            <label for="nueva_password" class="block text-sm font-medium text-gray-700">Nueva ContraseÃ±a</label>
            <div class="relative mt-1">
              <input [type]="v1 ? 'text' : 'password'" name="nueva_password" id="nueva_password" required autocomplete="new-password" [(ngModel)]="p1"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
              <i class="fa-solid absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer" [class.fa-eye]="!v1" [class.fa-eye-slash]="v1" (click)="v1 = !v1"></i>
            </div>
          </div>
          <app-requisitos-pwd [pwd]="p1" />
          <div>
            <label for="confirmar_password" class="block text-sm font-medium text-gray-700">Confirmar ContraseÃ±a</label>
            <div class="relative mt-1">
              <input [type]="v2 ? 'text' : 'password'" name="confirmar_password" id="confirmar_password" required autocomplete="new-password" [(ngModel)]="p2"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
              <i class="fa-solid absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer" [class.fa-eye]="!v2" [class.fa-eye-slash]="v2" (click)="v2 = !v2"></i>
            </div>
            <p class="text-xs mt-1 h-4" [class.text-green-600]="p1 === p2" [class.text-red-500]="p1 !== p2">
              @if (p2.length > 0) { {{ p1 === p2 ? 'âœ“ Las contraseÃ±as coinciden.' : 'âœ— Las contraseÃ±as no coinciden.' }} }
            </p>
          </div>
          <button type="submit" [disabled]="!valida || enviando"
            class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
            <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar Nueva ContraseÃ±a
          </button>
        </form>
      </div>
    </div>
  `
})
export class CambiarPassword implements OnInit {
  private api = inject(Api);
  private auth = inject(Auth);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  p1 = '';
  p2 = '';
  v1 = false;
  v2 = false;
  error: string | null = null;
  enviando = false;

  get valida() {
    return pwdValida(this.p1, this.p2);
  }

  get textoError() {
    const errores: Record<string, string> = {
      no_coinciden: 'Las contraseÃ±as no coinciden. IntÃ©ntalo de nuevo.',
      no_segura: 'La contraseÃ±a no cumple los requisitos de seguridad.',
      error_db: 'Error al guardar la contraseÃ±a. Por favor intenta de nuevo.'
    };
    return errores[this.error!] ?? 'OcurriÃ³ un error desconocido.';
  }

  async ngOnInit() {
    document.title = 'Cambio de ContraseÃ±a Obligatorio - INEES';
    const r = await this.api.get('cambiarPassword', 'index');
    if (!r.permitido) {
      this.router.navigateByUrl('/login');
      return;
    }
    this.error = this.route.snapshot.queryParamMap.get('error');
  }

  async enviar(e: Event) {
    e.preventDefault();
    this.enviando = true;
    const r = await this.api.post('procesarCambioPassword', 'index', { nueva_password: this.p1, confirmar_password: this.p2 });
    this.enviando = false;
    if (r.status === 'success') {
      await this.auth.cargar(true);
      this.router.navigateByUrl('/inicio');
    } else if (r.redirect === 'login') {
      this.router.navigateByUrl('/login');
    } else {
      this.error = r.error;
    }
  }
}

/** Port de solicitarCodigoVista.php */
@Component({
  selector: 'app-solicitar-codigo',
  imports: [FormsModule, RouterLink],
  template: `
    <div class="bg-gray-100 flex items-center justify-center min-h-screen p-4" style="font-family: 'Poppins', sans-serif;">
      <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Recuperar ContraseÃ±a</h2>
        <p class="text-center text-gray-600 mb-4">Ingresa tu email registrado.</p>
        <form (submit)="enviar($event)">
          <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" required [(ngModel)]="email"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
          </div>
          <button type="submit" [disabled]="enviando" class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors">
            {{ enviando ? 'Enviando...' : 'Enviar CÃ³digo' }}
          </button>
          <a routerLink="/login" class="block text-center mt-4 text-sm text-indigo-600 hover:underline">Volver a inicio de sesiÃ³n</a>
        </form>
      </div>
    </div>
  `
})
export class SolicitarCodigo implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  email = '';
  enviando = false;

  ngOnInit() {
    document.title = 'Recuperar ContraseÃ±a - INEES';
  }

  async enviar(e: Event) {
    e.preventDefault();
    if (!this.email) return;
    this.enviando = true;
    try {
      await this.api.post('solicitarCodigo', 'enviarCodigo', { email: this.email });
    } finally {
      this.enviando = false;
    }
    this.router.navigate(['/mensajeEnviado']);
  }
}

/** Port de mensajeEnviadoVista.php */
@Component({
  selector: 'app-mensaje-enviado',
  imports: [RouterLink],
  template: `
    <div class="bg-gray-100 flex items-center justify-center min-h-screen p-4" style="font-family: 'Poppins', sans-serif;">
      <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md text-center">
        <i class="fa-solid fa-envelope-circle-check text-6xl text-green-500 mb-6"></i>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Â¡Revisa tu correo!</h2>
        <p class="text-gray-600 mb-6">Si tu email estÃ¡ registrado, recibirÃ¡s un cÃ³digo de 6 dÃ­gitos. (Recuerda revisar tu carpeta de spam).</p>
        <a routerLink="/resetPassword" class="w-full block py-3 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors">Ya tengo el cÃ³digo</a>
        <a routerLink="/login" class="block text-center mt-4 text-sm text-indigo-600 hover:underline">Volver a inicio de sesiÃ³n</a>
      </div>
    </div>
  `
})
export class MensajeEnviado implements OnInit {
  ngOnInit() {
    document.title = 'Revisa tu Correo - INEES';
  }
}

/** Port de resetPasswordVista.php + resetPasswordControlador */
@Component({
  selector: 'app-reset-password',
  imports: [FormsModule, RequisitosPwd],
  template: `
    <div class="bg-gray-100 flex items-center justify-center min-h-screen p-4" style="font-family: 'Poppins', sans-serif;">
      <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Crear Nueva ContraseÃ±a</h2>
        @if (error) {
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ error }}</span>
          </div>
        }
        <form (submit)="enviar($event)" class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" [(ngModel)]="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" />
          </div>
          <div>
            <label for="codigo" class="block text-sm font-medium text-gray-700">CÃ³digo de 6 dÃ­gitos</label>
            <input type="text" name="codigo" id="codigo" [(ngModel)]="codigo" required autocomplete="off" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" />
          </div>
          <div>
            <label for="nueva_password" class="block text-sm font-medium text-gray-700">Nueva ContraseÃ±a</label>
            <div class="relative mt-1">
              <input [type]="v1 ? 'text' : 'password'" name="nueva_password" id="nueva_password" [(ngModel)]="p1" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10" />
              <i class="fa-solid absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer" [class.fa-eye]="!v1" [class.fa-eye-slash]="v1" (click)="v1 = !v1"></i>
            </div>
          </div>
          <app-requisitos-pwd [pwd]="p1" [caja]="false" textoSimbolo="Un sÃ­mbolo (ej: @, $, !)" />
          <div>
            <label for="confirmar_password" class="block text-sm font-medium text-gray-700">Confirmar ContraseÃ±a</label>
            <div class="relative mt-1">
              <input [type]="v2 ? 'text' : 'password'" name="confirmar_password" id="confirmar_password" [(ngModel)]="p2" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm pr-10" />
              <i class="fa-solid absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer" [class.fa-eye]="!v2" [class.fa-eye-slash]="v2" (click)="v2 = !v2"></i>
            </div>
            <p class="text-xs mt-1" [class.text-green-600]="p1 === p2" [class.text-red-500]="p1 !== p2">
              @if (p2.length > 0) { {{ p1 === p2 ? 'Las contraseÃ±as coinciden.' : 'Las contraseÃ±as no coinciden.' }} }
            </p>
          </div>
          <button type="submit" [disabled]="!valida || enviando"
            class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
            Restablecer ContraseÃ±a
          </button>
        </form>
      </div>
    </div>
  `
})
export class ResetPassword implements OnInit {
  private api = inject(Api);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  email = '';
  codigo = '';
  p1 = '';
  p2 = '';
  v1 = false;
  v2 = false;
  error = '';
  enviando = false;

  get valida() {
    return pwdValida(this.p1, this.p2);
  }

  ngOnInit() {
    document.title = 'Restablecer ContraseÃ±a - INEES';
    const q = this.route.snapshot.queryParamMap;
    this.email = q.get('email') ?? '';
    this.error = q.get('error') ?? '';
  }

  async enviar(e: Event) {
    e.preventDefault();
    this.enviando = true;
    try {
      const r = await this.api.post('resetPassword', 'procesarResetPassword', {
        email: this.email,
        codigo: this.codigo,
        nueva_password: this.p1,
        confirmar_password: this.p2
      });
      if (r.status === 'success') {
        this.router.navigate(['/login'], { queryParams: { success: r.success } });
      } else {
        this.error = r.error;
      }
    } finally {
      this.enviando = false;
    }
  }
}
