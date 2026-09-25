// Planilla de notas: recalcula saber, hacer, ser, la nota y el desempeño
// mientras el docente escribe. Es solo una ayuda: el servidor calcula igual
// (App\Support\ClassGrades) y es el que manda.
export function initGradeSheet() {
    const form = document.querySelector('[data-grade-sheet]');
    if (!form) return;

    const weights = JSON.parse(form.dataset.weights);
    const levels = JSON.parse(form.dataset.levels);
    const decimals = Number(form.dataset.decimals);
    const min = Number(form.dataset.min);
    const max = Number(form.dataset.max);

    const round = (n) => Math.round(n * 10 ** decimals) / 10 ** decimals;
    const format = (n) => (n === null ? '—' : round(n).toFixed(decimals).replace('.', ','));
    const parse = (value) => {
        const n = Number(value.trim().replace(',', '.'));
        return value.trim() === '' || Number.isNaN(n) || n < min || n > max ? null : n;
    };

    const update = (row) => {
        const components = {};
        Object.keys(weights).forEach((c) => {
            const values = [...row.querySelectorAll(`input[data-component="${c}"]`)]
                .map((input) => parse(input.value))
                .filter((n) => n !== null);
            components[c] = values.length ? values.reduce((a, b) => a + b, 0) / values.length : null;
        });

        let sum = 0;
        let total = 0;
        Object.entries(components).forEach(([c, value]) => {
            if (value === null) return;
            sum += value * weights[c];
            total += weights[c];
        });
        const score = total ? round(sum / total) : null;
        const level = score === null ? null : levels.find((l) => score >= l.from);

        Object.entries(components).forEach(([c, value]) => {
            row.querySelector(`[data-out="${c}"]`).textContent = format(value);
        });
        row.querySelector('[data-out="score"]').textContent = format(score);
        row.querySelector('[data-out="performance"]').textContent = level ? level.label : '—';
    };

    form.addEventListener('input', (e) => {
        const input = e.target.closest('input[data-component]');
        if (!input) return;
        input.closest('td').classList.toggle('has-error', input.value.trim() !== '' && parse(input.value) === null);
        update(input.closest('[data-row]'));
    });

    // Enter baja a la casilla del siguiente estudiante (como en una hoja de cálculo)
    form.addEventListener('keydown', (e) => {
        const input = e.target.closest('input[data-component]');
        if (!input || e.key !== 'Enter') return;
        e.preventDefault();
        const cell = input.closest('td');
        const next = input.closest('tr').nextElementSibling?.children[cell.cellIndex]?.querySelector('input');
        next?.focus();
        next?.select();
    });
}

// Logros: vista previa con la frase de cada desempeño
export function initObjectivePreview() {
    document.querySelectorAll('[data-objective]').forEach((textarea) => {
        const targets = textarea.closest('.objective-field').querySelectorAll('[data-objective-text]');
        textarea.addEventListener('input', () => {
            const text = textarea.value.trim();
            const value = text ? text.charAt(0).toLowerCase() + text.slice(1) : '…';
            targets.forEach((t) => {
                t.textContent = value;
            });
        });
    });
}
