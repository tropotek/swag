import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '../css/app.css';

const themeToggle = document.getElementById('theme-toggle');
if (themeToggle) {
    const syncIcon = () => {
        themeToggle.textContent = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '🌙' : '☀️';
    };
    syncIcon();
    themeToggle.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
        syncIcon();
    });
}

document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.matches('[data-autosubmit]')) {
        event.target.form.submit();
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

const mediaArea = document.querySelector('textarea[data-media-url]');
const mediaPicker = mediaArea && document.querySelector(mediaArea.dataset.mediaPicker);
const mediaStatus = mediaArea && document.querySelector(mediaArea.dataset.mediaStatus);
// All three or none: a half-present set would throw here and kill every listener below it.
if (mediaArea && mediaPicker && mediaStatus) {
    const picker = mediaPicker;
    const status = mediaStatus;

    const insertAtCursor = (text) => {
        const { selectionStart: start, selectionEnd: end, value } = mediaArea;
        mediaArea.value = value.slice(0, start) + text + value.slice(end);
        mediaArea.selectionStart = mediaArea.selectionEnd = start + text.length;
        mediaArea.focus();
    };

    const debug = mediaArea.dataset.debug === '1';

    const maxKb = Number(mediaArea.dataset.maxKb) || 0;

    const upload = async (file) => {
        // Refuse here rather than sending the whole file to be rejected: on a phone an
        // oversized video would upload in full before the server could answer 422.
        if (maxKb && file.size > maxKb * 1024) {
            const mb = (size) => `${Math.round((size / 1048576) * 10) / 10} MB`;
            throw new Error(`Too large (${mb(file.size)}). The limit is ${mb(maxKb * 1024)}.`);
        }

        const body = new FormData();
        body.append('file', file);
        const response = await fetch(mediaArea.dataset.mediaUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': mediaArea.dataset.csrf },
            body,
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            // Only `errors.file` is written for a person to read. `message` can be the raw
            // framework error when APP_DEBUG is on, so it goes to the console, never the page.
            if (debug) {
                console.error('[swag] upload failed', response.status, json);
            }
            throw new Error(
                json.errors?.file?.[0] ??
                    (response.status === 422
                        ? 'That file was rejected.'
                        : 'Upload failed. Please try again.'),
            );
        }
        return json.data;
    };

    const uploadAll = async (files) => {
        // Failures are collected rather than written straight to the status line: the next
        // file's progress would overwrite the message, and a later success would clear it,
        // so a rejected file could leave the batch looking like it all worked.
        const failures = [];

        for (const [index, file] of [...files].entries()) {
            status.classList.remove('text-danger');
            status.textContent =
                files.length > 1
                    ? `Uploading ${file.name} (${index + 1} of ${files.length})…`
                    : `Uploading ${file.name}…`;
            try {
                const media = await upload(file);
                insertAtCursor(`${media.markdown}\n`);
            } catch (error) {
                failures.push(`${file.name}: ${error.message}`);
            }
        }

        status.classList.toggle('text-danger', failures.length > 0);
        status.textContent = failures.join(' · ');
    };

    picker.addEventListener('change', async () => {
        await uploadAll([...picker.files]);
        picker.value = '';
    });

    mediaArea.addEventListener('dragover', (event) => {
        if (event.dataTransfer?.types.includes('Files')) {
            event.preventDefault();
        }
    });

    mediaArea.addEventListener('drop', (event) => {
        if (event.dataTransfer?.files.length) {
            event.preventDefault();
            uploadAll([...event.dataTransfer.files]);
        }
    });

    mediaArea.addEventListener('paste', (event) => {
        const files = [...(event.clipboardData?.files ?? [])];
        if (files.length) {
            event.preventDefault();
            uploadAll(files);
        }
    });
}
