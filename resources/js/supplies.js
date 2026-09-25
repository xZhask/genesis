// Listas de útiles: muestra solo la del grado elegido y recuerda la elección
// en este navegador. Sin JavaScript se ven todas, una debajo de otra.
const KEY = 'genesis-grado-utiles';

export function initSupplies() {
    const root = document.querySelector('[data-supplies]');
    if (!root) return;

    const chips = [...root.querySelectorAll('[data-grade]')];
    const panels = [...root.querySelectorAll('[data-grade-panel]')];
    if (!chips.length) return;

    root.classList.add('is-enhanced');

    function select(id, focus = false) {
        const panel = panels.find((p) => p.id === id);
        if (!panel) return false;

        panels.forEach((p) => { p.hidden = p !== panel; });
        chips.forEach((c) => c.setAttribute('aria-current', c.dataset.grade === id ? 'true' : 'false'));
        try { localStorage.setItem(KEY, id); } catch { /* sin almacenamiento: no se recuerda */ }

        if (focus) panel.focus({ preventScroll: true });
        return true;
    }

    chips.forEach((chip) => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            select(chip.dataset.grade, true);
            history.replaceState(null, '', `#${chip.dataset.grade}`);
        });
    });

    // Prioridad: enlace directo (#utiles-3), luego el último grado elegido
    let saved = null;
    try { saved = localStorage.getItem(KEY); } catch { /* ignorar */ }
    const fromHash = location.hash.slice(1);

    if (!select(fromHash) && !select(saved)) {
        // Sin elección aún: el acudiente toca su grado; ninguna lista a la vista
        panels.forEach((p) => { p.hidden = true; });
        chips.forEach((c) => c.setAttribute('aria-current', 'false'));
    }
}
