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
if (mediaArea) {
    const picker = document.querySelector(mediaArea.dataset.mediaPicker);
    const status = document.querySelector(mediaArea.dataset.mediaStatus);

    const insertAtCursor = (text) => {
        const { selectionStart: start, selectionEnd: end, value } = mediaArea;
        mediaArea.value = value.slice(0, start) + text + value.slice(end);
        mediaArea.selectionStart = mediaArea.selectionEnd = start + text.length;
        mediaArea.focus();
    };

    const upload = async (file) => {
        const body = new FormData();
        body.append('file', file);
        const response = await fetch(mediaArea.dataset.mediaUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': mediaArea.dataset.csrf },
            body,
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(json.errors?.file?.[0] ?? json.message ?? 'Upload failed.');
        }
        return json.data;
    };

    const uploadAll = async (files) => {
        for (const file of files) {
            status.classList.remove('text-danger');
            status.textContent = `Uploading ${file.name}…`;
            try {
                const media = await upload(file);
                insertAtCursor(`${media.markdown}\n`);
                status.textContent = '';
            } catch (error) {
                status.classList.add('text-danger');
                status.textContent = `${file.name}: ${error.message}`;
            }
        }
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
