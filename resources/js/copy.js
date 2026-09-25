import { toast } from './toast';

// Botones "Copiar" (datos bancarios). Usa el portapapeles moderno y, si no
// está disponible (http o navegadores antiguos), el método clásico.
function fallbackCopy(text) {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    const ok = document.execCommand('copy');
    area.remove();
    return ok;
}

async function copy(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        return fallbackCopy(text);
    }
}

export function initCopy() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-copy]');
        if (!btn) return;

        const text = btn.dataset.copy;
        toast((await copy(text)) ? `Copiado: ${text}` : 'No se pudo copiar. Mantén presionado el texto para copiarlo.');
    });
}
