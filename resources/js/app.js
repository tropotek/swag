import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '../css/app.css';

document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print]')) {
        window.print();
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-target]');
    if (!button) return;
    const input = document.querySelector(button.dataset.copyTarget);
    input.select();
    try {
        await navigator.clipboard.writeText(input.value);
        button.textContent = 'Copied';
    } catch {
        button.textContent = 'Selected: copy manually';
    }
});
