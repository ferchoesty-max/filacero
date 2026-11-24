document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const mensaje = form.getAttribute('data-confirm') || '¿Confirmas esta acción?';
            if (!window.confirm(mensaje)) {
                event.preventDefault();
            }
        });
    });
});