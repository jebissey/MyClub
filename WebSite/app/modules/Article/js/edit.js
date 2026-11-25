import { t } from '/app/modules/Common/js/i18n.js';
import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

document.addEventListener('DOMContentLoaded', function () {


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
            appendAlert(t('article.edit.error.editor_not_ready'), 'warning');
            return;
        }

        const content = editor.getContent().trim();

        if (!title) {
            e.preventDefault();
            appendAlert(t('article.edit.error.title_required'), 'danger');
            return;
        }

        if (!content || content === '<p><br></p>') {
            e.preventDefault();
            appendAlert(t('article.edit.error.content_required'), 'danger');
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