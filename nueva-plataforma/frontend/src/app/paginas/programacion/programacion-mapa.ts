import { AfterViewInit, Component, ElementRef, OnDestroy, ViewChild, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import * as L from 'leaflet';
import { Api } from '../../core/api';

const icono = (color: string) =>
  new L.Icon({
    iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
  });

/** Port de programacionMapaVista.php (sandbox: selección de puntos sobre el mapa) */
@Component({
  selector: 'app-programacion-mapa',
  imports: [FormsModule],
  template: `
    <div class="w-full max-w-6xl mx-auto">
      <div class="bg-white p-6 rounded-xl shadow-md border border-indigo-200 mb-6">
        <h2 class="text-2xl font-bold text-indigo-800 mb-4"><i class="fas fa-map-marked-alt mr-2"></i> Sandbox: Asignación por Mapa</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Delegación</label>
            <select [(ngModel)]="delegacion" (change)="cambiarDelegacion()" class="w-full border border-gray-300 rounded-lg p-2">
              <option value="">-- Seleccione delegación --</option>
              @for (d of delegaciones; track d.id_delegacion) {
                <option [value]="'' + d.id_delegacion">{{ d.nombre_delegacion }}</option>
              }
            </select>
          </div>
          @if (delegacion) {
            <div>
              <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                <label class="block text-sm font-bold text-gray-700">Selecciona Zonas a Cargar</label>
                <div class="flex items-center gap-1.5">
                  <button type="button" (click)="todas(true)" class="text-[11px] bg-green-100 hover:bg-green-200 text-green-800 px-2.5 py-1 rounded font-bold transition" title="Marcar todas las zonas"><i class="fas fa-check-double mr-1"></i> Todas</button>
                  <button type="button" (click)="todas(false)" class="text-[11px] bg-red-100 hover:bg-red-200 text-red-800 px-2.5 py-1 rounded font-bold transition" title="Desmarcar todas las zonas"><i class="fas fa-times mr-1"></i> Ninguna</button>
                </div>
              </div>
              <div class="border border-gray-300 rounded-lg p-2 bg-gray-50 h-32 overflow-y-auto">
                @for (z of zonas; track z) {
                  <label class="flex items-center space-x-2 p-1 hover:bg-white cursor-pointer">
                    <input type="checkbox" class="rounded text-indigo-600" [checked]="marcadas.has(z)" (change)="alternar(z, $any($event.target).checked)" />
                    <span class="text-sm font-semibold">{{ z }}</span>
                  </label>
                }
              </div>
            </div>
          }
        </div>
        @if (delegacion) {
          <div class="mt-4 flex justify-between items-center">
            <button type="button" (click)="cargarPuntos()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700"><i class="fas fa-sync-alt mr-2"></i> Cargar al Mapa</button>
            <div class="text-sm font-bold text-gray-700">Puntos seleccionados: <span class="text-indigo-600 text-lg">{{ seleccionados.size }}</span></div>
          </div>
        }
      </div>
      <div class="bg-white p-2 rounded-xl shadow-lg border border-gray-300 relative">
        <div #mapa style="height: 600px; width: 100%; border-radius: 0.5rem; z-index: 1"></div>
        <button type="button" (click)="confirmar()" class="absolute bottom-6 right-6 z-[1000] bg-green-600 text-white px-6 py-3 rounded-full font-bold shadow-xl hover:bg-green-700 text-lg flex items-center transition transform hover:scale-105"><i class="fas fa-check-circle mr-2"></i> Guardar Ruta</button>
      </div>
    </div>
  `
})
export class ProgramacionMapa implements AfterViewInit, OnDestroy {
  private api = inject(Api);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  @ViewChild('mapa', { static: true }) mapaRef!: ElementRef<HTMLDivElement>;

  delegaciones: any[] = [];
  zonas: string[] = [];
  delegacion = '';
  marcadas = new Set<string>();
  seleccionados = new Set<any>();
  private mapa!: L.Map;
  private capa!: L.FeatureGroup;
  private gris = icono('grey');
  private verde = icono('green');

  async ngAfterViewInit() {
    this.mapa = L.map(this.mapaRef.nativeElement).setView([4.6097, -74.0817], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.mapa);
    this.capa = L.featureGroup().addTo(this.mapa);
    this.delegacion = this.route.snapshot.queryParamMap.get('delegacion') || '';
    await this.cargar();
  }

  ngOnDestroy() {
    this.mapa?.remove();
  }

  private async cargar() {
    const r: any = await this.api.get('programacionMapa', 'index', this.delegacion ? { delegacion: this.delegacion } : {});
    this.delegaciones = r.listaDelegaciones || [];
    this.zonas = (r.listaZonas || []).map(String);
    this.marcadas.clear();
  }

  /** En PHP el select recargaba la página con ?delegacion= */
  cambiarDelegacion() {
    this.router.navigate([], { queryParams: { delegacion: this.delegacion || null } });
    this.capa.clearLayers();
    this.seleccionados.clear();
    this.cargar();
  }

  todas(m: boolean) {
    this.marcadas = m ? new Set(this.zonas) : new Set();
  }

  alternar(z: string, m: boolean) {
    if (m) this.marcadas.add(z);
    else this.marcadas.delete(z);
  }

  async cargarPuntos() {
    const zonas = Array.from(this.marcadas);
    if (zonas.length === 0) {
      alert('Selecciona al menos una zona para cargar.');
      return;
    }
    this.capa.clearLayers();
    this.seleccionados = new Set();
    const r: any = await this.api.postForm('programacionMapa', 'ajax_obtener_puntos', { delegacion: this.delegacion, zonas });
    if (r.success && r.data.length > 0) this.dibujar(r.data);
    else alert('No hay puntos pendientes en estas zonas o no tienen coordenadas válidas.');
  }

  private dibujar(puntos: any[]) {
    const limites = L.latLngBounds([]);
    let sinCoord = 0;
    puntos.forEach((p) => {
      const lat = parseFloat(p.latitud),
        lon = parseFloat(p.longitud);
      if (p.latitud && p.longitud && lat != 0 && lon != 0) {
        const ll = L.latLng(lat, lon);
        limites.extend(ll);
        const m = L.marker(ll, { icon: this.gris }).addTo(this.capa);
        m.bindTooltip(`<b>${p.nombre_punto}</b><br>${p.nombre_cliente}`, { direction: 'top', offset: [0, -35] });
        m.on('click', () => {
          if (this.seleccionados.has(p.id_punto)) {
            this.seleccionados.delete(p.id_punto);
            m.setIcon(this.gris);
          } else {
            this.seleccionados.add(p.id_punto);
            m.setIcon(this.verde);
          }
          this.seleccionados = new Set(this.seleccionados);
        });
      } else sinCoord++;
    });
    if (sinCoord > 0) console.warn(`${sinCoord} puntos omitidos por no tener coordenadas registradas.`);
    if (this.capa.getLayers().length > 0) this.mapa.fitBounds(limites, { padding: [40, 40] });
  }

  confirmar() {
    if (this.seleccionados.size === 0) {
      alert('No has seleccionado ningún punto en el mapa.');
      return;
    }
    const ids = Array.from(this.seleccionados);
    console.log('IDs listos para programar:', ids);
    alert('¡Excelente! Has seleccionado ' + ids.length + ' puntos. \nIDs: ' + ids.join(', '));
  }
}
