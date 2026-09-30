/**
 * js/turno/turno.js
 * Módulo "Marcar Entrada" del técnico (rol 3).
 *
 * Abre el modal (desde la tarjeta del inicio o desde el navbar), captura la
 * ubicación del dispositivo y guarda la marcación del día por AJAX.
 * El modal vive en app/views/turno/partials/modalTurno.php y lo incluye
 * plantillaVista.php solo para el rol técnico.
 */
window.Turno = (function () {
    const config = window.TurnoConfig || {};
    let turnoDelDia = null;   // Registro guardado del día consultado
    let guardando = false;

    const $ = (id) => document.getElementById(id);

    // ---------- Utilidades ----------
    const dosDigitos = (n) => String(n).padStart(2, '0');

    function fechaHoyLocal() {
        const d = new Date();
        return d.getFullYear() + '-' + dosDigitos(d.getMonth() + 1) + '-' + dosDigitos(d.getDate());
    }

    function horaAhoraLocal() {
        const d = new Date();
        return dosDigitos(d.getHours()) + ':' + dosDigitos(d.getMinutes());
    }

    function fechaBonita(iso) {
        if (!iso) return '';
        const p = String(iso).substring(0, 10).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

    /**
     * Convierte lo que escribe el técnico a formato militar (24 horas) HH:MM.
     * El técnico solo teclea números, sin dos puntos:
     *   1300 -> 13:00   |   930 -> 09:30   |   13 -> 13:00   |   1 -> 01:00
     * Mismo criterio del módulo Reporte Técnico (js/tecnico/tecnicoReporte.js).
     */
    function horaAMilitar(valor) {
        const digitos = String(valor || '').replace(/\D/g, '').substring(0, 4);

        if (digitos.length === 4) {
            let horas = parseInt(digitos.substring(0, 2), 10);
            let minutos = parseInt(digitos.substring(2, 4), 10);

            if (horas > 23) horas = 23;
            if (minutos > 59) minutos = 59;

            return dosDigitos(horas) + ':' + dosDigitos(minutos);
        }

        if (digitos.length === 3) {
            // 3 dígitos: el primero es la hora (930 -> 09:30)
            const horas = '0' + digitos.substring(0, 1);
            let minutos = digitos.substring(1, 3);
            if (parseInt(minutos, 10) > 59) minutos = '59';
            return horas + ':' + minutos;
        }

        if (digitos.length === 2) {
            return digitos + ':00';        // 13 -> 13:00
        }

        if (digitos.length === 1) {
            return '0' + digitos + ':00';   // 1 -> 01:00
        }

        return '';
    }

    /** Escribe en el campo la hora ya formateada en militar. */
    function formatearHoraMilitar(input) {
        if (!input) return;
        input.value = horaAMilitar(input.value);
    }

    function mostrarAlerta(mensaje, tipo) {
        const caja = $('turnoAlerta');
        if (!caja) return;

        if (!mensaje) {
            caja.className = 'hidden rounded-lg px-3 py-2 text-sm';
            caja.innerHTML = '';
            return;
        }

        const esError = (tipo === 'error');
        caja.className = 'rounded-lg px-3 py-2 text-sm ' + (esError
            ? 'bg-red-50 border border-red-200 text-red-700'
            : 'bg-emerald-50 border border-emerald-200 text-emerald-700');
        caja.innerHTML = '<i class="fas ' + (esError ? 'fa-exclamation-triangle' : 'fa-check-circle') +
            ' mr-1"></i>' + mensaje;
    }

    // ---------- Ubicación ----------
    function capturarUbicacion(forzar) {
        const estado = $('turnoGeoEstado');
        const lat = $('turnoLatitud');
        const lng = $('turnoLongitud');

        if (!estado || !lat || !lng) return;

        if (forzar) {
            lat.value = '';
            lng.value = '';
        }

        if (!navigator.geolocation) {
            estado.innerHTML = '<span class="text-amber-600">Este dispositivo no permite ubicación. Puedes guardar igual.</span>';
            return;
        }

        estado.textContent = 'Obteniendo ubicación...';

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                lat.value = pos.coords.latitude.toFixed(8);
                lng.value = pos.coords.longitude.toFixed(8);
                estado.innerHTML = '<span class="text-emerald-700 font-semibold">' +
                    '<i class="fas fa-map-marker-alt"></i> ' +
                    pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5) + '</span>';
            },
            function () {
                estado.innerHTML = '<span class="text-amber-600">No se pudo obtener la ubicación (permiso negado). Puedes guardar igual.</span>';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    }

    // ---------- Tarjeta del inicio ----------
    function pintarTarjeta() {
        const tarjeta = $('turnoCard');
        if (!tarjeta) return;

        const titulo = $('turnoCardTitulo');
        const detalle = $('turnoCardDetalle');
        const chip = $('turnoCardEstado');

        if (turnoDelDia) {
            if (titulo) titulo.textContent = 'Entrada registrada';
            if (detalle) {
                detalle.textContent = 'Día ' + turnoDelDia.fecha_texto + ' · ' + turnoDelDia.hora_entrada +
                    (turnoDelDia.novedad ? ' · con novedad' : '');
            }
            if (chip) {
                chip.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200';
                chip.innerHTML = '<i class="fas fa-check mr-1"></i>' + turnoDelDia.hora_entrada;
            }
        } else {
            if (titulo) titulo.textContent = 'Marcar entrada';
            if (detalle) detalle.textContent = 'Registra la hora en que entraste hoy';
            if (chip) {
                chip.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-200 text-gray-600 border border-gray-300';
                chip.innerHTML = 'Sin registrar';
            }
        }
    }

    // ---------- Consultas al backend ----------
    async function consultar(fecha, callback) {
        if (!config.urlEstado) return null;
        try {
            const resp = await fetch(config.urlEstado + '&fecha=' + encodeURIComponent(fecha), { method: 'GET' });
            const data = await resp.json();
            if (data.ok) {
                if (typeof callback === 'function') callback(data.turno);
                return data.turno;
            }
        } catch (e) {
            console.error('[Turno] No se pudo consultar la marcación:', e);
        }
        return null;
    }

    // ---------- Modal ----------
    function abrirModal() {
        const modal = $('modalTurno');
        if (!modal) return;

        mostrarAlerta('', null);

        const fecha = $('turnoFecha');
        const hora = $('turnoHora');
        const novedad = $('turnoNovedad');
        const hoy = config.hoy || fechaHoyLocal();

        if (fecha) {
            fecha.max = hoy;
            fecha.value = hoy;
        }
        if (hora) hora.value = horaAhoraLocal();
        if (novedad) novedad.value = '';

        // Si ya marcó hoy, el modal arranca con sus datos para corregir
        if (turnoDelDia && turnoDelDia.fecha === hoy) {
            if (hora) hora.value = turnoDelDia.hora_entrada;
            if (novedad) novedad.value = turnoDelDia.novedad || '';
        }

        // La hora siempre queda y se muestra en formato militar (24 horas)
        formatearHoraMilitar(hora);

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // En el celular el teclado numérico se abre solo: el técnico marca de una
        setTimeout(function () {
            if (hora) {
                hora.focus();
                if (typeof hora.select === 'function') {
                    hora.select();
                }
            }
        }, 150);

        capturarUbicacion(true);
    }

    function cerrarModal() {
        const modal = $('modalTurno');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        mostrarAlerta('', null);
    }

    // ---------- Guardar ----------
    async function guardar(evento) {
        if (evento) evento.preventDefault();
        if (guardando) return;

        const fecha = $('turnoFecha') ? $('turnoFecha').value : '';
        const digitosHora = $('turnoHora') ? ($('turnoHora').value || '').replace(/\D/g, '') : '';
        const novedad = $('turnoNovedad') ? $('turnoNovedad').value.trim() : '';
        const hoy = config.hoy || fechaHoyLocal();

        if (!fecha || digitosHora.length < 4) {
            mostrarAlerta('Escribe la hora completa: 4 dígitos (ej: 1300 = 13:00).', 'error');
            return;
        }

        // La hora siempre le llega al backend en formato militar
        const hora = horaAMilitar(digitosHora);

        if (!/^([01]\d|2[0-3]):([0-5]\d)$/.test(hora)) {
            mostrarAlerta('La hora no es válida (usa 24 horas, ej: 07:41 o 19:30).', 'error');
            return;
        }
        if (fecha > hoy) {
            mostrarAlerta('No puedes registrar una fecha futura.', 'error');
            return;
        }

        const boton = $('turnoBtnGuardar');
        const textoBoton = boton ? boton.innerHTML : '';
        if (boton) {
            boton.disabled = true;
            boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        }
        guardando = true;

        try {
            const cuerpo = new URLSearchParams();
            cuerpo.append('fecha', fecha);
            cuerpo.append('hora_entrada', hora);
            cuerpo.append('novedad', novedad);
            if ($('turnoLatitud') && $('turnoLatitud').value) {
                cuerpo.append('latitud', $('turnoLatitud').value);
            }
            if ($('turnoLongitud') && $('turnoLongitud').value) {
                cuerpo.append('longitud', $('turnoLongitud').value);
            }

            const resp = await fetch(config.urlGuardar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: cuerpo
            });
            const data = await resp.json();

            if (!data.ok) {
                mostrarAlerta(data.msj || 'No se pudo guardar la marcación.', 'error');
                return;
            }

            turnoDelDia = data.turno;
            pintarTarjeta();
            mostrarAlerta(data.msj, 'ok');

            setTimeout(function () {
                if (fecha === hoy) cerrarModal();
            }, 1500);
        } catch (e) {
            console.error('[Turno] Error al guardar:', e);
            mostrarAlerta('Error de conexión. Revisa tu internet e intenta de nuevo.', 'error');
        } finally {
            guardando = false;
            if (boton) {
                boton.disabled = false;
                boton.innerHTML = textoBoton;
            }
        }
    }

    // ---------- Arranque ----------
    function iniciar() {
        const formulario = $('turnoFormulario');
        if (formulario) {
            formulario.addEventListener('submit', guardar);
        }

        const fecha = $('turnoFecha');
        if (fecha) {
            fecha.addEventListener('change', async function () {
                const elegida = fecha.value;
                const registro = await consultar(elegida, null);

                if (registro) {
                    if ($('turnoHora')) {
                        $('turnoHora').value = registro.hora_entrada;
                        formatearHoraMilitar($('turnoHora'));
                    }
                    if ($('turnoNovedad')) $('turnoNovedad').value = registro.novedad || '';
                    mostrarAlerta('Ya tenías una marcación el ' + registro.fecha_texto + ' a las ' +
                        registro.hora_entrada + '. Puedes corregirla.', 'ok');
                } else {
                    mostrarAlerta('Sin marcación registrada para el ' + fechaBonita(elegida) + '.', 'ok');
                }
            });
        }

        // Hora de entrada: formato militar (24 horas), igual que en el Reporte Técnico
        const horaEntrada = $('turnoHora');
        if (horaEntrada) {
            horaEntrada.addEventListener('input', function () {
                // El técnico solo escribe números: se limpian letras y signos
                const digitos = this.value.replace(/\D/g, '').substring(0, 4);

                // Al completar los 4 dígitos se muestra solo en militar (1300 -> 13:00)
                this.value = (digitos.length === 4) ? horaAMilitar(digitos) : digitos;
            });

            horaEntrada.addEventListener('blur', function () {
                formatearHoraMilitar(this);
            });

            if (horaEntrada.value) {
                formatearHoraMilitar(horaEntrada);
            }
        }

        // Cerrar con clic en el fondo del modal
        const modal = $('modalTurno');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) cerrarModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarModal();
        });

        // Estado inicial: si la vista ya lo trajo (Inicio) se usa; si no, se consulta
        const hoy = config.hoy || fechaHoyLocal();
        if (config.turnoInicial) {
            turnoDelDia = config.turnoInicial;
            pintarTarjeta();
        } else if ($('turnoCard')) {
            consultar(hoy, function (registro) {
                turnoDelDia = registro;
                pintarTarjeta();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }

    return {
        abrirModal: abrirModal,
        cerrarModal: cerrarModal,
        capturarUbicacion: capturarUbicacion,
        guardar: guardar
    };
})();


