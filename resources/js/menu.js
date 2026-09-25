// Menú principal en celular: se abre con el botón y se cierra al elegir
// un enlace, con Escape o al tocar fuera.
export function initMenu() {
    const burger = document.querySelector('[data-menu-toggle]');
    const menu = burger && document.getElementById(burger.getAttribute('aria-controls'));
    if (!menu) return;

    const isOpen = () => menu.classList.contains('open');
    const set = (open) => {
        menu.classList.toggle('open', open);
        burger.setAttribute('aria-expanded', String(open));
    };

    burger.addEventListener('click', () => set(!isOpen()));

    menu.addEventListener('click', (e) => {
        if (e.target.closest('a')) set(false);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen()) {
            set(false);
            burger.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (isOpen() && !menu.contains(e.target) && !burger.contains(e.target)) set(false);
    });

    // Al pasar a escritorio, el menú desplegable no debe quedar abierto.
    window.matchMedia('(min-width: 1101px)').addEventListener('change', (e) => {
        if (e.matches) set(false);
    });
}
