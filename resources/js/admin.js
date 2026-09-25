import { initTheme } from './theme';
import { initForms } from './forms';

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
