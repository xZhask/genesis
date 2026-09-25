import { initTheme } from './theme';
import { initForms } from './forms';
import { initUploader } from './uploader';

initTheme();
initForms();

// Cambio de estado: la fecha de entrevista solo se muestra cuando aplica.
// El servidor la exige igual (UpdateAdmissionStatusRequest).
const select = document.querySelector('[data-status-select]');
const field = document.querySelector('[data-interview-field]');

if (select && field) {
    const sync = () => {
        field.hidden = select.value !== 'interview_scheduled' && !field.classList.contains('has-error');
    };
    select.addEventListener('change', () => {
        field.classList.remove('has-error');
        sync();
    });
    sync();
}

// Confirmación antes de eliminar
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
});

document.querySelectorAll('button[data-confirm]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
    });
});

initUploader();

// Selectores que cambian la vista al elegir (sin botón "Ver"; hay <noscript> de respaldo)
document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
});

// Plan de estudios: total de materias y horas mientras se edita
const curriculum = document.querySelector('[data-curriculum]');
const hoursTotal = document.querySelector('[data-hours-total]');

if (curriculum && hoursTotal) {
    const update = () => {
        let subjects = 0;
        let hours = 0;
        curriculum.querySelectorAll('.plan-row').forEach((row) => {
            if (!row.querySelector('[data-plan-check]').checked) return;
            subjects++;
            hours += Number(row.querySelector('[data-plan-hours]').value) || 0;
        });
        hoursTotal.textContent = `${subjects} materias · ${hours} h/semana`;
    };
    curriculum.addEventListener('input', update);
    curriculum.addEventListener('change', update);
}

// Recursos: muestra solo los campos que aplican al tipo elegido.
// El servidor ignora los que no corresponden (ResourceRequest::resourceData).
const typeRadios = [...document.querySelectorAll('[data-type-radio]')];

if (typeRadios.length) {
    const blocks = [...document.querySelectorAll('[data-for-types]')];
    const sync = () => {
        const type = typeRadios.find((r) => r.checked)?.value;
        blocks.forEach((block) => {
            block.hidden = !block.dataset.forTypes.split(' ').includes(type) && !block.querySelector('.has-error');
        });
    };
    typeRadios.forEach((r) => r.addEventListener('change', sync));
    sync();
}

// Eventos: las horas solo se piden si no es "todo el día"
const allDay = document.querySelector('[data-all-day]');
const times = document.querySelector('[data-times]');

if (allDay && times) {
    allDay.addEventListener('change', () => {
        times.hidden = allDay.checked;
    });
}

// Fichas de acceso: imprimir
document.querySelectorAll('[data-print]').forEach((btn) => {
    btn.addEventListener('click', () => window.print());
});
