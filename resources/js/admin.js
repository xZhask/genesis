import { initTheme } from './theme';
import { initForms } from './forms';
import { initUploader } from './uploader';
import { initGradeSheet, initObjectivePreview } from './grades';

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
document.querySelectorAll('[data-autosubmit]').forEach((select) => {
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

// Asistencia: resumen en vivo junto al botón de guardar ("22 presentes · 2 ausentes")
const attendanceForm = document.querySelector('[data-attendance]');
const attendanceSummary = document.querySelector('[data-attendance-summary]');

if (attendanceForm && attendanceSummary) {
    const labels = { present: ['presente', 'presentes'], absent: ['ausente', 'ausentes'], late: ['tarde', 'tarde'], excused: ['con excusa', 'con excusa'] };
    const update = () => {
        const counts = {};
        attendanceForm.querySelectorAll('input[type="radio"]:checked').forEach((r) => {
            counts[r.value] = (counts[r.value] || 0) + 1;
        });
        attendanceSummary.textContent = Object.keys(labels)
            .filter((k) => counts[k])
            .map((k) => `${counts[k]} ${labels[k][counts[k] === 1 ? 0 : 1]}`)
            .join(' · ');
    };
    attendanceForm.addEventListener('change', update);
    update();
}

initGradeSheet();
initObjectivePreview();

// Horario: en el celular se ve un día a la vez (sin JavaScript, todos seguidos)
document.querySelectorAll('[data-timetable]').forEach((timetable) => {
    const tabs = timetable.querySelector('.tt-tabs');
    const buttons = [...timetable.querySelectorAll('.tt-tabs [role="tab"]')];
    const days = [...timetable.querySelectorAll('.tt-day')];
    if (!tabs || !buttons.length) return;

    const show = (day) => {
        buttons.forEach((b) => {
            const selected = b.dataset.day === day;
            b.setAttribute('aria-selected', selected ? 'true' : 'false');
            b.tabIndex = selected ? 0 : -1;
        });
        days.forEach((d) => { d.hidden = d.dataset.day !== day; });
    };

    tabs.hidden = false;
    show(timetable.dataset.today);
    buttons.forEach((b, i) => {
        b.addEventListener('click', () => show(b.dataset.day));
        // Flechas entre pestañas (patrón de pestañas accesible)
        b.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            const next = buttons[(i + (e.key === 'ArrowRight' ? 1 : -1) + buttons.length) % buttons.length];
            next.focus();
            show(next.dataset.day);
        });
    });
});

// Horario de una sección (admin): horas por materia en vivo al elegir en la cuadrícula
const scheduleGrid = document.querySelector('[data-schedule-grid]');

if (scheduleGrid) {
    const recount = () => {
        const counts = {};
        scheduleGrid.querySelectorAll('[data-slot]').forEach((select) => {
            if (select.value) counts[select.value] = (counts[select.value] || 0) + 1;
        });
        document.querySelectorAll('[data-hours-row]').forEach((row) => {
            const count = counts[row.dataset.hoursRow] || 0;
            const plan = Number(row.dataset.plan);
            row.querySelector('[data-hours-count]').textContent = count;
            row.classList.toggle('is-off', plan > 0 && count !== plan);
        });
    };
    scheduleGrid.addEventListener('change', recount);
}
