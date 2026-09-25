// Visor de fotos de la galería. Usa <dialog> nativo: atrapa el foco,
// cierra con Escape y devuelve el foco a la foto que lo abrió.
export function initLightbox() {
    const gallery = document.querySelector('[data-gallery]');
    const dialog = document.querySelector('[data-lightbox]');
    if (!gallery || !dialog || typeof dialog.showModal !== 'function') return;

    const items = [...gallery.querySelectorAll('button')];
    const frame = dialog.querySelector('[data-lightbox-frame]');
    const caption = dialog.querySelector('[data-lightbox-caption]');
    let current = 0;

    function render(index) {
        current = (index + items.length) % items.length;
        const { src, tone, alt, caption: text } = items[current].dataset;

        frame.replaceChildren();
        if (src) {
            const img = document.createElement('img');
            img.className = 'media';
            img.src = src;
            img.alt = alt;
            frame.append(img);
        } else {
            const ph = document.createElement('div');
            ph.className = `media ph ${tone}`;
            ph.innerHTML = '<div class="in"><span></span></div>';
            ph.querySelector('span').textContent = alt;
            frame.append(ph);
        }

        const count = `${current + 1} de ${items.length}`;
        caption.textContent = text ? `${text} (${count})` : `Foto ${count}`;

        // Precarga la siguiente para que el cambio sea inmediato
        const next = items[(current + 1) % items.length].dataset.src;
        if (next) new Image().src = next;
    }

    items.forEach((btn, i) => {
        btn.addEventListener('click', () => {
            render(i);
            dialog.showModal();
        });
    });

    dialog.addEventListener('close', () => items[current].focus());
    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => render(current - 1));
    dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => render(current + 1));

    dialog.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') render(current + 1);
        if (e.key === 'ArrowLeft') render(current - 1);
    });

    // Clic en el fondo oscuro (fuera de la foto y de los botones) cierra el visor
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });

    // Deslizar con el dedo para cambiar de foto
    let startX = null;
    dialog.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    }, { passive: true });
    dialog.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) render(current + (dx < 0 ? 1 : -1));
        startX = null;
    });
}
