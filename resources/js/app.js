import * as bootstrap from 'bootstrap';

// Disponível globalmente para scripts das páginas (ex.: abrir modais da agenda).
window.bootstrap = bootstrap;

document.addEventListener('DOMContentLoaded', () => {
    // Tooltips declarados com data-bs-toggle="tooltip".
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));

    // Fecha automaticamente os alertas de sucesso após alguns segundos.
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach((el) => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 5000);
    });

    // Pede confirmação antes de enviar formulários marcados com data-confirm.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});
