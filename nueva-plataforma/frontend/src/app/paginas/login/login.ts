import { Component, ElementRef, HostListener, OnInit, ViewChild, ViewEncapsulation, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

/** Port de app/views/login/loginVista.php + js/login/login.js */
@Component({
  selector: 'app-login',
  imports: [FormsModule, RouterLink],
  encapsulation: ViewEncapsulation.None,
  styleUrl: './login.css',
  template: `
    <div class="login-page flex items-center justify-center min-h-screen" [class.konami]="konami">
      <div class="animated-bg"></div>
      <div class="particles">
        @for (p of particulas; track $index) {
          <div class="particle"></div>
        }
      </div>

      <div class="card-login rounded-3xl p-10 w-full max-w-md mx-4 text-center">
        <div class="logo-container mb-8">
          <img src="app/logos/logoIneesFondoBlanco.png" alt="Logo-Inees" class="w-28 mx-auto drop-shadow-2xl transform hover:scale-110 transition-transform duration-300" />
        </div>

        <h1 class="text-4xl font-bold text-white mb-2 tracking-tight">Bienvenido</h1>
        <p class="text-blue-200 mb-8 text-lg font-light">
          Inicia sesión en <span class="font-semibold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">I-Bikers</span>
        </p>

        <form class="space-y-5" (keypress)="enter($event)" (submit)="$event.preventDefault()">
          <div class="input-wrapper" [style.transform]="foco === 'u' ? 'scale(1.02)' : 'scale(1)'">
            <input id="usuario" type="text" name="usuario" placeholder="Usuario" required [(ngModel)]="usuario"
              (focus)="foco = 'u'" (blur)="foco = ''" [style.borderColor]="usuario.length ? 'rgba(96, 165, 250, 0.5)' : 'rgba(255, 255, 255, 0.2)'"
              class="input-login w-full rounded-xl focus:outline-none text-base font-medium" autocomplete="username" />
            <i class="input-icon fa-solid fa-user"></i>
          </div>

          <div class="input-wrapper" [style.transform]="foco === 'p' ? 'scale(1.02)' : 'scale(1)'">
            <input #pwd id="password" [type]="verPassword ? 'text' : 'password'" name="password" placeholder="Contraseña" required [(ngModel)]="password"
              (focus)="foco = 'p'" (blur)="foco = ''" (keyup)="capsLock($event)" [style.borderColor]="password.length ? 'rgba(96, 165, 250, 0.5)' : 'rgba(255, 255, 255, 0.2)'"
              class="input-login w-full rounded-xl focus:outline-none text-base font-medium" autocomplete="current-password" />
            <i class="input-icon fa-solid fa-lock"></i>
            <i class="password-toggle fa-solid" [class.fa-eye]="!verPassword" [class.fa-eye-slash]="verPassword" (click)="verPassword = !verPassword"></i>
            @if (mayus) {
              <div id="caps-warning" class="mt-2 text-yellow-300 text-xs flex items-center justify-center"><i class="fa-solid fa-keyboard mr-2"></i>Bloq Mayús activado</div>
            }
          </div>

          <button type="button" id="btnLogin" (click)="login()" [disabled]="cargando" [style.opacity]="cargando ? '0.7' : '1'" [style.cursor]="cargando ? 'not-allowed' : 'pointer'"
            class="btn-login w-full text-white font-bold py-4 rounded-xl text-base tracking-wide relative">
            @if (!cargando) {
              <span>Iniciar Sesión</span>
            } @else {
              <span><span class="spinner"></span><span class="ml-2">Verificando...</span></span>
            }
          </button>
        </form>

        <div class="mt-6">
          <a routerLink="/solicitarCodigo" class="text-sm text-blue-300 hover:text-white hover:underline transition-all duration-300 font-medium inline-flex items-center">
            <i class="fa-solid fa-key mr-2"></i> ¿Olvidaste tu contraseña?
          </a>
        </div>

        <div class="mt-5">
          @if (mensaje) {
            <div class="px-4 py-3 rounded-xl text-sm font-semibold flex items-center justify-center border backdrop-blur-lg animate-shake"
              [class]="tipo === 'error' ? 'bg-red-500 bg-opacity-20 border-red-400 text-red-300' : tipo === 'ok' ? 'bg-green-500 bg-opacity-20 border-green-400 text-green-200' : 'bg-yellow-500 bg-opacity-20 border-yellow-400 text-yellow-300'">
              <i class="fa-solid mr-2" [class]="tipo === 'error' ? 'fa-circle-exclamation' : tipo === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i><span>{{ mensaje }}</span>
            </div>
          }
        </div>

        <div class="mt-8 pt-6 border-t border-white border-opacity-10">
          <p class="text-xs text-white text-opacity-60"><i class="fa-solid fa-shield-halved mr-1"></i> Sistema seguro I-Bikers &copy; {{ anio }}</p>
        </div>
      </div>
    </div>
  `
})
export class Login implements OnInit {
  private api = inject(Api);
  private auth = inject(Auth);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  @ViewChild('pwd') pwd?: ElementRef<HTMLInputElement>;

  particulas = Array.from({ length: 10 });
  usuario = '';
  password = '';
  verPassword = false;
  cargando = false;
  mensaje = '';
  tipo: 'error' | 'warning' | 'ok' = 'error';
  mayus = false;
  foco = '';
  anio = new Date().getFullYear();
  konami = false;
  private teclas: string[] = [];

  ngOnInit() {
    document.title = 'Login - I-Bikers';
    const ok = this.route.snapshot.queryParamMap.get('success');
    if (ok) {
      this.mensaje = ok;
      this.tipo = 'ok';
    }
  }

  enter(e: KeyboardEvent) {
    if (e.key === 'Enter') {
      e.preventDefault();
      this.login();
    }
  }

  capsLock(e: KeyboardEvent) {
    this.mayus = !!(e.getModifierState && e.getModifierState('CapsLock'));
  }

  private error(m: string, t: 'error' | 'warning' = 'error') {
    this.mensaje = m;
    this.tipo = t;
  }

  async login() {
    const u = this.usuario.trim();
    const p = this.password.trim();
    this.mensaje = '';
    if (u === '' || p === '') return this.error('Por favor, completa todos los campos', 'warning');
    if (p.length < 4) return this.error('La contraseña debe tener al menos 4 caracteres', 'warning');
    this.cargando = true;
    try {
      const data = await this.api.post('login', 'procesarLogin', { MM_Login: 'form1', usuario: this.usuario, password: this.password });
      if (data.status === 'success') {
        await this.auth.cargar(true);
        this.router.navigateByUrl('/' + data.redirect);
      } else {
        this.cargando = false;
        this.error(data.message || 'Usuario o contraseña incorrectos', 'error');
      }
    } catch {
      this.cargando = false;
      this.error('Error de conexión con el servidor', 'error');
    }
  }

  @HostListener('document:keydown', ['$event'])
  teclado(e: KeyboardEvent) {
    const secuencia = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
    this.teclas.push(e.key);
    this.teclas = this.teclas.slice(-10);
    if (this.teclas.join(',') === secuencia.join(',')) {
      this.konami = true;
      setTimeout(() => (this.konami = false), 5000);
    }
    if (e.key === 'Escape') {
      if (confirm('¿Limpiar formulario?')) {
        this.usuario = '';
        this.password = '';
        this.mensaje = '';
      }
    }
  }
}
