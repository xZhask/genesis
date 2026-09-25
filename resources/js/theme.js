// Modo claro/oscuro. El script en línea del <head> aplica el tema antes de
// pintar; este módulo maneja el botón y los cambios del sistema.
const KEY = 'genesis-theme';
const root = document.documentElement;
const media = window.matchMedia('(prefers-color-scheme: dark)');

function stored() {
    try {
        return localStorage.getItem(KEY);
    } catch {
        return null;
    }
}

function save(theme) {
    try {
        localStorage.setItem(KEY, theme);
    } catch {
        // Navegación privada o almacenamiento bloqueado: el tema no se recuerda.
    }
}

function sync() {
    const dark = root.dataset.theme === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-pressed', String(dark));
        btn.title = dark ? 'Activar modo claro' : 'Activar modo oscuro';
    });
}

function apply(theme) {
    root.dataset.theme = theme;
    sync();
}

export function initTheme() {
    sync();

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            apply(next);
            save(next);
        });
    });

    // Mientras el usuario no elija, la web sigue al sistema en vivo.
    media.addEventListener('change', (e) => {
        if (!stored()) apply(e.matches ? 'dark' : 'light');
    });
}
