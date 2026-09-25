// Aviso breve en la parte inferior (anunciado por lectores de pantalla)
let timer;

export function toast(message) {
    const el = document.querySelector('[data-toast]');
    if (!el) return;

    el.textContent = message;
    el.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(() => el.classList.remove('show'), 2200);
}
