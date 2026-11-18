import { Component } from '@angular/core';

/**
 * Marco de las pantallas de acceso (ingreso y recuperación): a la izquierda el panel azul noche con el corte de la
 * nube del logo en grande; a la derecha el formulario. En el celular el panel queda como una banda arriba.
 */
@Component({
  selector: 'app-acceso',
  template: `
    <div class="acceso">
      <section class="marca" aria-label="INEES">
        <img class="logo" src="marca/inees-logo-sobre-azul.svg" alt="INEES Secure Ai Systems" width="200" height="60" />
        <div class="lema">
          <h2>Plataforma de mantenimientos</h2>
          <p>Rutas del día, servicios en campo con evidencia y reportes de la operación.</p>
        </div>
        <div class="corte" aria-hidden="true">
          <i class="f"></i><i class="f"></i><i class="f"></i><i class="v"></i>
        </div>
      </section>
      <section class="formulario">
        <div class="caja"><ng-content /></div>
      </section>
    </div>
  `,
  styles: `
    .acceso { display: grid; grid-template-columns: minmax(380px, 46%) 1fr; min-height: 100vh; min-height: 100dvh; background: #fff; }
    .marca {
      position: relative; overflow: hidden; isolation: isolate; display: flex; flex-direction: column; justify-content: space-between;
      padding: 44px 48px; background: var(--marca-noche); color: #fff;
    }
    .logo { align-self: flex-start; height: 54px; width: auto; }
    .lema { max-width: 26em; margin-bottom: 30vh; }
    .lema h2 { color: #fff; font-size: 34px; line-height: 1.08; font-stretch: 110%; letter-spacing: -0.03em; }
    .lema p { margin: 14px 0 0; font-size: 16px; line-height: 1.55; color: rgb(255 255 255 / 0.76); }

    /* el corte de la nube: tres franjas azules y el campo verde, inclinados como en el logo */
    .corte { position: absolute; z-index: -1; right: -4%; bottom: -6%; display: flex; align-items: flex-end; gap: 18px; height: 46%; }
    .corte i { display: block; height: 100%; transform: skewX(-32deg); transform-origin: bottom; animation: entra 0.9s var(--curva) both; }
    .corte .f { width: 26px; background: var(--marca-azul); }
    .corte .f:nth-child(2) { animation-delay: 60ms; }
    .corte .f:nth-child(3) { animation-delay: 120ms; }
    .corte .v { width: 240px; margin-left: 34px; background: var(--marca-verde); animation-delay: 180ms; }
    @keyframes entra { from { transform: skewX(-32deg) translateY(40%); opacity: 0; } }

    .formulario { display: grid; place-items: center; padding: 40px 24px; }
    .caja { width: min(380px, 100%); display: flex; flex-direction: column; gap: 4px; }

    @media (max-width: 860px) {
      .acceso { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
      .marca { padding: calc(20px + env(safe-area-inset-top)) 20px 22px; min-height: 0; }
      .logo { height: 40px; }
      .lema { margin: 18px 0 0; max-width: 70%; }
      .lema h2 { font-size: 22px; }
      .lema p { display: none; }
      .corte { height: 120%; bottom: -10%; right: -14%; gap: 10px; }
      .corte .f { width: 12px; }
      .corte .v { width: 90px; margin-left: 18px; }
      .formulario { place-items: start center; padding: 28px 20px 40px; }
    }
  `,
})
export class Acceso {}
