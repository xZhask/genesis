// Subida de fotos a un álbum: envía una foto por petición, con barra de avance.
// Así ningún envío supera el límite del hosting y, si una foto falla, las demás
// siguen. Sin JavaScript, el formulario envía todas juntas (máximo 20).
export function initUploader() {
    const form = document.querySelector('[data-uploader]');

    // Tras subir, quita "?subidas=N" de la dirección para que recargar no repita el aviso
    const url = new URL(window.location.href);
    if (url.searchParams.has('subidas')) {
        url.searchParams.delete('subidas');
        window.history.replaceState(null, '', url);
    }

    if (!form || !window.fetch || !window.FormData) return;

    const input = form.querySelector('input[type="file"]');
    const consent = form.querySelector('[name="minors_consent"]');
    const submit = form.querySelector('[data-upload-submit]');
    const formError = form.querySelector('[data-upload-error]');
    const status = form.querySelector('[data-upload-status]');
    const bar = status.querySelector('progress');
    const text = status.querySelector('[data-upload-text]');
    const errors = status.querySelector('[data-upload-errors]');
    const token = form.querySelector('[name="_token"]').value;
    const maxBytes = Number(form.dataset.maxKb) * 1024;

    const showError = (message, field) => {
        formError.textContent = message;
        formError.hidden = false;
        field.focus();
    };

    async function upload(file) {
        if (file.size > maxBytes) return 'pesa más de 10 MB.';

        const data = new FormData();
        data.append('_token', token);
        data.append('minors_consent', '1');
        data.append('photos[]', file);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: data,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) return null;

            if (res.status === 413) return 'pesa demasiado para el servidor.';
            if (res.status === 419) return 'la sesión venció. Recarga la página e inténtalo de nuevo.';
            const body = await res.json().catch(() => null);
            const first = body?.errors ? Object.values(body.errors)[0][0] : null;
            return first ?? 'no se pudo subir.';
        } catch {
            return 'no se pudo subir. Revisa la conexión a internet.';
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.hidden = true;
        errors.replaceChildren();

        const files = [...input.files];
        if (!files.length) return showError('Elige al menos una foto.', input);
        if (!consent.checked) {
            return showError('Confirma que tienes la autorización de los acudientes para publicar las fotos.', consent);
        }

        submit.disabled = true;
        input.disabled = true;
        status.hidden = false;
        bar.value = 0;

        let saved = 0;
        for (const [i, file] of files.entries()) {
            text.textContent = `Subiendo ${i + 1} de ${files.length}…`;
            const error = await upload(file);

            if (error) {
                const li = document.createElement('li');
                li.textContent = `${file.name}: ${error}`;
                errors.append(li);
            } else {
                saved++;
            }
            bar.value = Math.round(((i + 1) / files.length) * 100);
        }

        if (saved === files.length) {
            window.location.href = `${form.dataset.return}?subidas=${saved}#fotos`;
            return;
        }

        text.textContent = `Se subieron ${saved} de ${files.length} fotos. Estas no se pudieron subir:`;
        submit.disabled = false;
        input.disabled = false;
        input.value = '';

        if (saved > 0) {
            const link = document.createElement('a');
            link.href = `${form.dataset.return}?subidas=${saved}#fotos`;
            link.textContent = 'Ver las fotos subidas';
            status.append(link);
        }
    });
}
