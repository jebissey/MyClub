import DragDropManager from '../../Common/js/DragDropManager.js';
import ApiClient from '../../Common/js/ApiClient.js';

const apiClient = new ApiClient();

const t = (key, params = {}) =>
    Object.entries(params).reduce(
        (text, [name, value]) => text.replaceAll(`{${name}}`, String(value)),
        window.i18n?.[key] ?? key
    );

const MAX_ODS_MB = 10;

document.addEventListener('DOMContentLoaded', () => {
    initExport();
    initOdsImport();
});

function initExport() {
    const form = document.getElementById('exportForm');
    const fileNameInput = document.getElementById('fileName');
    const button = document.getElementById('exportBtn');
    const status = document.getElementById('exportStatus');
    const supportsPicker = 'showSaveFilePicker' in window;

    if (!supportsPicker) {
        document.getElementById('exportFallbackNote').style.display = 'block';
    }

    function buildFileName() {
        const base = fileNameInput.value
            .trim()
            .replace(/\.ods$/i, '')
            .replace(/[\\/:*?"<>|]/g, '_');
        return `${base || 'export'}.ods`;
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
                        description: t('export.js.file_type'),
                        accept: { 'application/vnd.oasis.opendocument.spreadsheet': ['.ods'] },
                    }],
                })
                : null;

            button.textContent = t('export.form.submitting');

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

            showStatus(t('export.js.done'), 'success');
        } catch (error) {
            // AbortError : l'utilisateur a fermé la fenêtre d'enregistrement
            if (error.name !== 'AbortError') {
                console.error(error);
                showStatus(`${t('export.js.error')} (${error.message})`, 'danger');
            }
        } finally {
            button.disabled = false;
            button.textContent = t('export.form.submit');
        }
    });
}

function initOdsImport() {
    const zone = document.getElementById('odsDropZone');
    const fileInput = document.getElementById('odsFileInput');
    const resultsBox = document.getElementById('odsImportResults');
    if (!zone || !fileInput || !resultsBox) {
        return;
    }

    const manager = new DragDropManager({
        containerSelector: '#odsDropZone',
        itemSelector: '.ods-no-draggable-item', // aucun élément à déplacer : seuls des fichiers sont déposés
        dragOverClass: 'drag-over',
        onDrop: (_item, _container, event) => {
            const file = event.dataTransfer?.files?.[0];
            if (file) {
                processFile(file);
            }
            return false; // empêche le manager d'ajouter un élément à la zone
        },
    });
    manager.init();

    // Un fichier lâché à côté de la zone ne doit pas être ouvert par le navigateur
    ['dragover', 'drop'].forEach((type) => {
        window.addEventListener(type, (event) => {
            if (event.dataTransfer?.types?.includes('Files')) {
                event.preventDefault();
            }
        });
    });

    // Alternative au glisser-déposer : clic ou clavier
    zone.addEventListener('click', () => fileInput.click());
    zone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            fileInput.click();
        }
    });
    fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file) {
            processFile(file);
        }
    });

    async function processFile(file) {
        resultsBox.replaceChildren();

        if (!file.name.toLowerCase().endsWith('.ods')) {
            showMessage(t('export.import.error.wrong_type'), 'danger');
            return;
        }
        if (file.size > MAX_ODS_MB * 1024 * 1024) {
            showMessage(t('import.js.file_too_large', { max: `${MAX_ODS_MB} MB` }), 'danger');
            return;
        }

        setBusy(true);
        try {
            // 1) Aperçu : le serveur rejoue l'import puis annule tout
            const preview = await upload(file, true);
            const changes = preview.created + preview.updated + preview.deactivated + preview.reactivated;
            if (changes === 0) {
                renderResults(preview, false);
                return;
            }
            // 2) Confirmation, puis import réel
            if (!window.confirm(t('export.import.confirm', preview))) {
                return;
            }
            renderResults(await upload(file, false), true);
        } catch (error) {
            console.error(error);
            showMessage(error.message || t('export.import.error.generic'), 'danger');
        } finally {
            setBusy(false);
            fileInput.value = '';
        }
    }

    async function upload(file, dryRun) {
        const body = new FormData();
        body.append('odsFile', file);
        body.append('dryRun', dryRun ? '1' : '0');

        const response = await apiClient.postFormData('/api/import/ods', body);

        if (!response.success) {
            throw new Error(response.message || t('export.import.error.generic'));
        }

        return response.data;
    }

    function setBusy(busy) {
        zone.classList.toggle('opacity-50', busy);
        zone.style.pointerEvents = busy ? 'none' : '';
        if (busy) {
            showMessage(t('import.form.submitting'), 'info');
        }
    }

    function showMessage(message, kind) {
        resultsBox.replaceChildren();
        const box = document.createElement('div');
        box.className = `alert alert-${kind}`;
        box.textContent = message;
        resultsBox.appendChild(box);
    }

    function renderResults(data, applied) {
        resultsBox.replaceChildren();

        const box = document.createElement('div');
        box.className = `alert alert-${data.errors > 0 ? 'warning' : 'success'}`;

        const title = document.createElement('h5');
        title.textContent = applied ? t('export.import.result.applied') : t('export.import.no_change');
        box.appendChild(title);

        const counters = document.createElement('ul');
        [
            ['import.result.created', data.created],
            ['import.result.updated', data.updated],
            ['import.result.deactivated', data.deactivated],
            ['export.import.result.reactivated', data.reactivated],
            ['import.result.errors', data.errors],
        ].forEach(([key, count]) => {
            const item = document.createElement('li');
            item.textContent = `${t(key)} ${count}`;
            counters.appendChild(item);
        });
        box.appendChild(counters);

        if (data.messages.length > 0) {
            const details = document.createElement('h6');
            details.textContent = t('import.result.details');
            box.appendChild(details);

            // textContent : les messages contiennent des emails saisis par l'utilisateur
            const list = document.createElement('ul');
            list.className = 'small mb-0';
            list.style.maxHeight = '20rem';
            list.style.overflow = 'auto';
            data.messages.forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });
            box.appendChild(list);
        }

        resultsBox.appendChild(box);
    }
}