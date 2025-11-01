document.addEventListener('DOMContentLoaded', function() {
    let currentSetting = '';

    const settingSelect = document.getElementById('settingSelect');
    const editorContainer = document.getElementById('editor-container');
    const currentSettingLabel = document.getElementById('current-setting-label');

    function waitForQuill(callback) {
        const checkQuill = () => {
            const editorElement = document.getElementById('quill-editor');
            if (editorElement && editorElement.__quill) {
                callback(editorElement.__quill);
            } else {
                setTimeout(checkQuill, 100);
            }
        };
        checkQuill();
    }

    settingSelect.addEventListener('change', function() {
        const selectedKey = this.value;

        waitForQuill(function(quill) {
            if (currentSetting) {
                settingsData[currentSetting] = quill.root.innerHTML;
                document.getElementById('content-' + currentSetting).value = settingsData[currentSetting];
            }
            if (selectedKey) {
                currentSetting = selectedKey;
                const label = settingSelect.options[settingSelect.selectedIndex].text;
                editorContainer.style.display = 'block';
                currentSettingLabel.textContent = 'Contenu - ' + label;

                const content = settingsData[selectedKey] || '';
                quill.clipboard.dangerouslyPasteHTML(content);

                document.getElementById('content-' + selectedKey).value = content;

                quill.off('text-change');
                quill.on('text-change', function() {
                    const newContent = quill.root.innerHTML;
                    settingsData[currentSetting] = newContent;
                    document.getElementById('content-' + currentSetting).value = newContent;
                });
            } else {
                editorContainer.style.display = 'none';
                currentSetting = '';
            }
        });
    });

    document.getElementById('settingsForm').addEventListener('submit', function() {
        if (currentSetting) {
            waitForQuill(function(quill) {
                settingsData[currentSetting] = quill.root.innerHTML;
                document.getElementById('content-' + currentSetting).value = settingsData[currentSetting];
            });
        }
    });
});
