// /app/modules/Communication/js/tinymceInsertButtonPlugin.js
//
// Plugin TinyMCE : insère un bouton HTML "bulletproof" (compatible clients mail)
// avec data-link-no-tracking="true" pour éviter le 404 Brevo sur les liens
// réservés aux membres connectés.

tinymce.PluginManager.add('insertbutton', (editor) => {

    function buildButtonHtml(label, url) {
        const safeLabel = editor.dom.encode(label);
        const safeUrl   = editor.dom.encode(url);

        return `
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:16px auto;">
  <tr>
    <td align="center" bgcolor="#0d6efd" style="border-radius:6px;">
      <a href="${safeUrl}"
         data-link-no-tracking="true"
         target="_blank"
         style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;
                font-size:15px;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">
        ${safeLabel}
      </a>
    </td>
  </tr>
</table>
<p></p>`;
    }

    function openDialog() {
        const selectedText = editor.selection.getContent({ format: 'text' }) || '';

        editor.windowManager.open({
            title: window.t ? window.t('insertButtonTitle') : 'Insérer un bouton',
            body: {
                type: 'panel',
                items: [
                    {
                        type: 'input',
                        name: 'label',
                        label: window.t ? window.t('insertButtonLabel') : 'Texte du bouton',
                        value: selectedText || (window.t ? window.t('insertButtonDefaultLabel') : 'Cliquer pour ouvrir le document'),
                    },
                    {
                        type: 'input',
                        name: 'url',
                        label: window.t ? window.t('insertButtonUrl') : 'URL du document',
                        placeholder: 'https://...',
                    },
                ],
            },
            buttons: [
                { type: 'cancel', text: window.t ? window.t('cancel') : 'Annuler' },
                { type: 'submit', text: window.t ? window.t('insert') : 'Insérer', primary: true },
            ],
            onSubmit: (api) => {
                const data = api.getData();
                if (!data.url || !data.label) {
                    return;
                }
                editor.insertContent(buildButtonHtml(data.label, data.url));
                api.close();
            },
        });
    }

    editor.ui.registry.addButton('insertbutton', {
        icon: 'browse',
        tooltip: window.t ? window.t('insertButtonTooltip') : 'Insérer un bouton cliquable',
        onAction: openDialog,
    });

    return {
        getMetadata: () => ({ name: 'Insert Button', url: '' }),
    };
});