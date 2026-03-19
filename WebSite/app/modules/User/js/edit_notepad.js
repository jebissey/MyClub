import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

initTinyMCE('#tinymce', {
    onSave(editor) {
        document.getElementById('edit-form').submit();
    },
});

document.getElementById('edit-form').addEventListener('submit', function (e) {
    const content = tinymce.get('tinymce').getContent().trim();
    if (!content || content === '<p><br></p>') {
        e.preventDefault();
        alert('Le contenu ne peut pas être vide.');
    }
});