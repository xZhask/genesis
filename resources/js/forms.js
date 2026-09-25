// Formularios: lleva el foco al resumen de errores y evita el doble envío.
export function initForms() {
    // Confirmación tras enviar (Apóyanos): el lector de pantalla la anuncia
    document.querySelector('[data-sent]')?.focus();

    const summary = document.querySelector('[data-error-summary]');
    if (summary) {
        summary.scrollIntoView({ block: 'start' });
        summary.focus({ preventScroll: true });
    }

    document.querySelectorAll('[data-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[data-submit]');
            if (!btn) return;

            // Se deshabilita después de que el navegador toma el envío
            requestAnimationFrame(() => {
                btn.setAttribute('aria-disabled', 'true');
                btn.disabled = true;
                const label = btn.querySelector('span');
                if (label && btn.dataset.loadingText) label.textContent = btn.dataset.loadingText;
            });
        });
    });
}
