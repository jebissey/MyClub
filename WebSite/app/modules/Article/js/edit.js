import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

document.addEventListener('DOMContentLoaded', function () {

    const textarea = document.getElementById('tinymce-editor');
    if (!textarea) return;

    initTinyMCE('#tinymce-editor', {
        mode: 'normal',
        imageMaxWidth: 1600,
        imageQuality: 0.85,
        onSave: function (editor) {
            document.getElementById('edit-form').submit();
        },

    });

    document.getElementById('save-btn').addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('edit-form').requestSubmit();
    });

    document.getElementById('edit-form').addEventListener('submit', function (e) {
        const title = document.getElementById('title-input').value.trim();

        const editor = tinymce.get('tinymce-editor');
        if (!editor) {
            e.preventDefault();
            appendAlert('L\'éditeur n\'est pas encore chargé. Veuillez patienter.', 'warning');
            return false;
        }

        const content = editor.getContent().trim();

        if (!title) {
            e.preventDefault();
            appendAlert('Le titre est obligatoire.', 'danger');
            return false;
        }

        if (!content || content === '<p><br></p>') {
            e.preventDefault();
            appendAlert('Le contenu ne peut pas être vide.', 'danger');
            return false;
        }

        document.getElementById('content-input').value = content;
    });
});