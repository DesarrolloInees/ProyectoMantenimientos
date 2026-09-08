/**
 * js/programacion/calendario-semanal.js
 * PASO 2: calendario semanal — asigna técnico y zonas por día.
 */
(function () {
    const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];

    function toggleZonas(dia) {
        const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
        const zonasContainer = document.getElementById(`zonas_container_${dia}`);
        const checkboxes = Prog.util.qsa(`.checkbox-zona-${dia}`);
        const preview = document.getElementById(`preview_${dia}`);

        if (selectTecnico.value) {
            zonasContainer.classList.remove('opacity-50', 'pointer-events-none', 'bg-gray-50');
            zonasContainer.classList.add('bg-white');
        } else {
            zonasContainer.classList.add('opacity-50', 'pointer-events-none', 'bg-gray-50');
            zonasContainer.classList.remove('bg-white');
            checkboxes.forEach(cb => { cb.checked = false; });
            preview.classList.add('hidden');
        }
        updatePreview(dia);
    }

    function updatePreview(dia) {
        const selectTecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
        const checkboxes = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);
        const preview = document.getElementById(`preview_${dia}`);
        const previewText = document.getElementById(`preview_text_${dia}`);

        const tecnicoNombre = selectTecnico.options[selectTecnico.selectedIndex]?.text;
        const zonasSeleccionadas = checkboxes
            .map(cb => cb.parentElement.querySelector('strong')?.textContent.trim())
            .filter(Boolean);

        if (selectTecnico.value && zonasSeleccionadas.length > 0) {
            preview.classList.remove('hidden');
            previewText.innerHTML = `<strong>${Prog.util.escapeHtml(tecnicoNombre)}</strong> recorrerá:
                <span class="font-semibold text-indigo-600">${zonasSeleccionadas.map(Prog.util.escapeHtml).join(' + ')}</span>`;
        } else if (selectTecnico.value) {
            preview.classList.remove('hidden');
            previewText.innerHTML = `<strong>${Prog.util.escapeHtml(tecnicoNombre)}</strong> asignado -
                <span class="text-orange-600">Falta seleccionar zonas</span>`;
        } else {
            preview.classList.add('hidden');
        }
    }

    function validarYConfirmarEnvio(e) {
        let hayConfiguracion = false;
        const errores = [];

        DIAS.forEach(dia => {
            const tecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`).value;
            const zonas = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);

            if (tecnico && zonas.length > 0) {
                hayConfiguracion = true;
            } else if (tecnico && zonas.length === 0) {
                errores.push(`${dia.charAt(0).toUpperCase() + dia.slice(1)}: tiene técnico asignado pero no zonas seleccionadas`);
            }
        });

        if (!hayConfiguracion) {
            e.preventDefault();
            alert('⚠️ Debe configurar al menos un día de la semana con técnico y zonas.\n\nEjemplo:\n- Lunes: Juan Pérez → Sur + Sur Oriente\n- Martes: Juan Pérez → Sur Occidente + Sur');
            return;
        }
        if (errores.length > 0) {
            e.preventDefault();
            alert('⚠️ Hay días con errores:\n\n' + errores.join('\n'));
            return;
        }

        let resumen = 'Se generará la programación con:\n\n';
        DIAS.forEach(dia => {
            const tecnico = Prog.util.qs(`select[name="tecnico_${dia}"]`);
            const zonas = Prog.util.qsa(`.checkbox-zona-${dia}:checked`);
            if (tecnico.value && zonas.length > 0) {
                const tecnicoNombre = tecnico.options[tecnico.selectedIndex].text;
                const zonasNombres = zonas
                    .map(cb => cb.parentElement.querySelector('strong')?.textContent.trim())
                    .filter(Boolean).join(' + ');
                resumen += `✓ ${dia.charAt(0).toUpperCase() + dia.slice(1)}: ${tecnicoNombre} → ${zonasNombres}\n`;
            }
        });

        const semanas = Prog.util.qs('input[name="semanas"]').value;
        const maxServicios = Prog.util.qs('input[name="max_servicios"]').value;
        resumen += `\nDurante ${semanas} semana(s), máximo ${maxServicios} servicios/día.\n\n¿Continuar?`;

        if (!confirm(resumen)) {
            e.preventDefault();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('formCalendario');
        if (form) form.addEventListener('submit', validarYConfirmarEnvio);
    });

    window.toggleZonas = toggleZonas;
    window.updatePreview = updatePreview;
})();
