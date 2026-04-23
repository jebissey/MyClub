import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

document.addEventListener('DOMContentLoaded', function () {

    const t = window.t;

    // =========================
    // TinyMCE
    // =========================
    const textarea = document.getElementById('tinymce-editor');
    if (!textarea) return;

    initTinyMCE('#tinymce-editor', {
        mode: 'normal',
        imageMaxWidth: 1600,
        imageQuality: 0.85,
        onSave: function () {
            document.getElementById('edit-form').submit();
        },
    });

    // =========================
    // Bouton save
    // =========================
    document.getElementById('save-btn').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('edit-form').requestSubmit();
    });

    // =========================
    // Validation formulaire
    // =========================
    document.getElementById('edit-form').addEventListener('submit', function (e) {
        const title = document.getElementById('title-input').value.trim();
        const editor = tinymce.get('tinymce-editor');

        if (!editor) {
            e.preventDefault();
            appendAlert(t('editorNotReady'), 'warning');
            return;
        }

        const content = editor.getContent().trim();

        if (!title) {
            e.preventDefault();
            appendAlert(t('titleRequired'), 'danger');
            return;
        }

        if (!content || content === '<p><br></p>') {
            e.preventDefault();
            appendAlert(t('contentRequired'), 'danger');
            return;
        }

        document.getElementById('content-input').value = content;
    });

    // =========================
    // Gestion publication
    // =========================
    const publishedInput = document.getElementById('published-input');
    const membersCheckbox = document.getElementById('members-only-checkbox');
    const groupSelect = document.getElementById('group-select');

    function updatePublishState() {
        if (isEditor) {
            publishedInput.disabled = false;
            return;
        }

        const membersOnly = membersCheckbox.checked;
        const hasGroup = groupSelect.value !== '';

        if (membersOnly || hasGroup) {
            publishedInput.disabled = false;
        } else {
            publishedInput.checked = false;
            publishedInput.disabled = true;
        }
    }

    membersCheckbox.addEventListener('change', updatePublishState);
    groupSelect.addEventListener('change', updatePublishState);

    // Initialisation
    updatePublishState();
});