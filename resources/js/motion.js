// Movimiento de la web pública: aparición de secciones al hacer scroll y
// encabezado con sombra al bajar. Sin JS (o con movimiento reducido) todo se
// ve de inmediato: el estado oculto solo existe si este script lo agrega.

// Contenedores cuyas tarjetas entran una tras otra
const GROUPS = '.levels, .news, .gallery, .support-grid, .quick .grid';
const STEP_MS = 70;
const MAX_STEPS = 5;

export function initMotion() {
    initHeader();

    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (calm || !('IntersectionObserver' in window)) return;

    initReveal();
}

function initHeader() {
    const header = document.querySelector('header.nav');
    if (!header) return;

    let ticking = false;
    const update = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 8);
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });
    update();
}

function initReveal() {
    const main = document.querySelector('main');
    if (!main) return;

    // El primer bloque (hero o encabezado de página) ya tiene su propia entrada.
    const sections = [...main.querySelectorAll(':scope > section')].slice(1);
    const targets = [];

    sections.forEach((section) => {
        const wrap = section.querySelector(':scope > .wrap') ?? section;

        [...wrap.children].forEach((block) => {
            const group = block.matches(GROUPS) ? block : block.querySelector(GROUPS);

            if (group) {
                if (group !== block) targets.push([block, 0]);
                [...group.children].forEach((card, i) => targets.push([card, Math.min(i, MAX_STEPS)]));
            } else {
                targets.push([block, 0]);
            }
        });
    });

    // Lo que ya está en pantalla al cargar no se oculta (evita un parpadeo).
    const fold = window.innerHeight;
    const pending = targets.filter(([el]) => el.getBoundingClientRect().top > fold);
    if (!pending.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            el.classList.add('is-in');
            observer.unobserve(el);

            // Al terminar, la tarjeta recupera sus propias transiciones (hover, toque).
            const done = (e) => {
                if (e.target !== el || e.propertyName !== 'transform') return;
                el.removeEventListener('transitionend', done);
                el.removeAttribute('data-reveal');
                el.classList.remove('is-in');
                el.style.removeProperty('--reveal-delay');
            };
            el.addEventListener('transitionend', done);
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    pending.forEach(([el, step]) => {
        el.setAttribute('data-reveal', '');
        if (step) el.style.setProperty('--reveal-delay', `${step * STEP_MS}ms`);
        observer.observe(el);
    });
}
