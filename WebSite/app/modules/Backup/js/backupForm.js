const t = (key, params = {}) =>
    Object.entries(params).reduce(
        (text, [name, value]) => text.replaceAll(`{${name}}`, String(value)),
        window.i18n?.[key] ?? key
    );

document.addEventListener('DOMContentLoaded', () => {
    initBackup();
});

function initBackup() {
    const form = document.getElementById('backupForm');
    const fileNameInput = document.getElementById('fileName');
    const button = document.getElementById('backupBtn');
    const status = document.getElementById('backupStatus');
    const supportsPicker = 'showSaveFilePicker' in window;

    if (!supportsPicker) {
        document.getElementById('backupFallbackNote').style.display = 'block';
    }

    function buildFileName() {
        const base = fileNameInput.value
            .trim()
            .replace(/\.zip$/i, '')
            .replace(/[\\/:*?"<>|]/g, '_');
        return `${base || 'backup'}.zip`;
    }

    function showStatus(message, kind) {
        status.className = `mt-3 alert alert-${kind}`;
        status.textContent = message;
    }

    function downloadWithAnchor(blob, fileName) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const fileName = buildFileName();
        status.className = 'mt-3';
        status.textContent = '';
        button.disabled = true;

        try {
            const handle = supportsPicker
                ? await window.showSaveFilePicker({
                      suggestedName: fileName,
                      types: [{
                          description: t('backup.js.file_type'),
                          accept: { 'application/zip': ['.zip'] },
                      }],
                  })
                : null;

            button.textContent = t('backup.form.submitting');

            const response = await fetch(form.dataset.url, { credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const blob = await response.blob();

            if (handle) {
                const writable = await handle.createWritable();
                await writable.write(blob);
                await writable.close();
            } else {
                downloadWithAnchor(blob, fileName);
            }

            showStatus(t('backup.js.done'), 'success');
        } catch (error) {
            // AbortError : l'utilisateur a fermé la fenêtre d'enregistrement
            if (error.name !== 'AbortError') {
                console.error(error);
                showStatus(`${t('backup.js.error')} (${error.message})`, 'danger');
            }
        } finally {
            button.disabled = false;
            button.textContent = t('backup.form.submit');
        }
    });
}