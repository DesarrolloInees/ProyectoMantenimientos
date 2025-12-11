document.addEventListener('DOMContentLoaded', () => {

    // --- INICIALIZACIÓN DE DATATABLES ---
    $('#usuariosTable').DataTable({
        responsive: true, // ✨ ¡LA LÍNEA MÁGICA! ✨
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
        }
    });

    // --- LÓGICA DEL MODAL DE CONFIRMACIÓN (sin cambios) ---
    const confirmModal = document.getElementById('confirmModal');
    const confirmButton = document.getElementById('confirmButton');
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    const openButtons = document.querySelectorAll('[data-modal-trigger]');

    const openConfirmModal = (event) => {
        event.preventDefault();
        const id = event.currentTarget.getAttribute('data-id');
        const deleteUrl = `${BASE_URL}usuarioEliminar/${id}`;

        confirmButton.href = deleteUrl;
        confirmModal.classList.remove('hidden');
    };

    const closeConfirmModal = () => {
        confirmModal.classList.add('hidden');
    };

    openButtons.forEach(button => {
        button.addEventListener('click', openConfirmModal);
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', closeConfirmModal);
    });

    window.addEventListener('click', (event) => {
        if (event.target === confirmModal) {
            closeConfirmModal();
        }
    });
});