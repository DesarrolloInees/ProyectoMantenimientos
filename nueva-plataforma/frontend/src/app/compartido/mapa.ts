import { afterNextRender, Component, effect, ElementRef, input, OnDestroy, output, viewChild } from '@angular/core';
import * as L from 'leaflet';

export interface PuntoMapa {
  lat: number;
  lon: number;
  color: string;
  etiqueta?: string;
  titulo?: string;
}

export interface CapaMapa {
  puntos: PuntoMapa[];
  color: string;
  /** Une los puntos con una línea en el orden dado (recorrido de la ruta). */
  unir?: boolean;
  /** Punto de partida del recorrido (casa o centro de la zona). */
  partida?: [number, number] | null;
}

const COLOMBIA: L.LatLngTuple = [4.65, -74.1];

/** Mapa de OpenStreetMap con capas de puntos numerados y, opcionalmente, un pin que se mueve con un clic. */
@Component({
  selector: 'app-mapa',
  template: `<div #contenedor class="mapa" role="application" aria-label="Mapa"></div>`,
  styles: `
    :host { display: block; }
    .mapa { width: 100%; height: 100%; min-height: 280px; border-radius: 12px; }
  `,
})
export class Mapa implements OnDestroy {
  readonly capas = input<CapaMapa[]>([]);
  /** Pin editable (modo geolocalizar). */
  readonly pin = input<[number, number] | null>(null);
  /** Centro sugerido cuando no hay puntos. */
  readonly centro = input<[number, number] | null>(null);
  readonly clicMapa = output<{ lat: number; lon: number }>();

  private readonly contenedor = viewChild.required<ElementRef<HTMLDivElement>>('contenedor');
  private mapa?: L.Map;
  private grupo = L.layerGroup();
  private marcadorPin?: L.Marker;
  private ultimoEncuadre = '';

  constructor() {
    afterNextRender(() => {
      this.mapa = L.map(this.contenedor().nativeElement, { zoomControl: true }).setView(COLOMBIA, 6);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
      }).addTo(this.mapa);
      this.grupo.addTo(this.mapa);
      this.mapa.on('click', (e: L.LeafletMouseEvent) => this.clicMapa.emit({ lat: e.latlng.lat, lon: e.latlng.lng }));
      new ResizeObserver(() => this.mapa?.invalidateSize()).observe(this.contenedor().nativeElement);
      this.dibujar();
    });
    effect(() => {
      this.capas();
      this.pin();
      this.centro();
      this.dibujar();
    });
  }

  ngOnDestroy(): void {
    this.mapa?.remove();
  }

  private dibujar(): void {
    const mapa = this.mapa;
    if (!mapa) return;
    this.grupo.clearLayers();
    const limites: L.LatLngTuple[] = [];

    for (const capa of this.capas()) {
      const recorrido: L.LatLngTuple[] = [];
      if (capa.partida) {
        recorrido.push(capa.partida);
        L.circleMarker(capa.partida, { radius: 6, color: '#072942', fillColor: '#fff', fillOpacity: 1, weight: 3 })
          .bindTooltip('Partida')
          .addTo(this.grupo);
      }
      for (const p of capa.puntos) {
        const pos: L.LatLngTuple = [p.lat, p.lon];
        recorrido.push(pos);
        limites.push(pos);
        const icono = L.divIcon({
          className: '',
          html: `<div class="marcador" style="background:${p.color}">${p.etiqueta ?? ''}</div>`,
          iconSize: [26, 26],
          iconAnchor: [13, 13],
        });
        const m = L.marker(pos, { icon: icono });
        if (p.titulo) m.bindTooltip(p.titulo);
        m.addTo(this.grupo);
      }
      if (capa.unir && recorrido.length > 1) {
        L.polyline(recorrido, { color: capa.color, weight: 3, opacity: 0.8 }).addTo(this.grupo);
      }
    }

    const pin = this.pin();
    if (pin) {
      if (!this.marcadorPin) {
        this.marcadorPin = L.marker(pin, {
          icon: L.divIcon({
            className: '',
            html: '<div class="marcador pin" style="background:#0b5591;width:30px;height:30px"><span class="material-symbols-outlined">location_on</span></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 28],
          }),
        });
      }
      this.marcadorPin.setLatLng(pin).addTo(mapa);
      limites.push(pin);
    } else {
      this.marcadorPin?.remove();
    }

    // Solo reencuadra cuando cambian los puntos, para no mover el mapa en cada clic del pin.
    const clave = JSON.stringify(this.capas().map((c) => c.puntos.length)) + JSON.stringify(this.centro());
    if (clave !== this.ultimoEncuadre) {
      this.ultimoEncuadre = clave;
      if (limites.length > 1) {
        mapa.fitBounds(L.latLngBounds(limites), { padding: [30, 30], maxZoom: 15 });
      } else if (limites.length === 1) {
        mapa.setView(limites[0], 16);
      } else if (this.centro()) {
        mapa.setView(this.centro()!, 13);
      }
    }
  }
}

/** Una ruta por color: primero los del kit de marca, luego tonos de apoyo que se distinguen en el mapa. */
export const COLORES_RUTA = ['#0b5591', '#2d7f3e', '#d98e04', '#072942', '#368aba', '#4ba844', '#7b4b94', '#0f8b8d'];
