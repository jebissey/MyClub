document.addEventListener('DOMContentLoaded', function () {
    const editorElement = document.getElementById('quill-editor');
    if (!editorElement) return;

    const BlockEmbed = Quill.import('blots/block/embed');

    class ClearBreakBlot extends BlockEmbed {
        static create() {
            const node = super.create();
            node.setAttribute('data-clearbreak', 'true');
            return node;
        }
    }
    ClearBreakBlot.blotName = 'clearBreak';
    ClearBreakBlot.tagName = 'DIV';
    ClearBreakBlot.className = 'clearbreak-display';
    Quill.register(ClearBreakBlot);

    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'image'],
            ]
        }
    });

    const toolbar = quill.getModule('toolbar');
    if (toolbar) {
        const toolbarEl = toolbar.container;
        const imageGroup = toolbarEl.querySelector('.ql-image')?.parentNode;
        if (imageGroup) {
            const clearBreakButton = document.createElement('button');
            clearBreakButton.className = 'ql-clearbreak';
            clearBreakButton.type = 'button';
            clearBreakButton.title = 'Saut après image';
            clearBreakButton.innerHTML = '↵';
            clearBreakButton.style.fontWeight = 'bold';
            imageGroup.appendChild(clearBreakButton);

            clearBreakButton.addEventListener('click', function () {
                const range = quill.getSelection();
                if (range) {
                    quill.insertEmbed(range.index, 'clearBreak', true);
                    quill.setSelection(range.index + 1, 0);
                }
            });
            const imageLeftButton = document.createElement('button');
            imageLeftButton.className = 'ql-image-left';
            imageLeftButton.type = 'button';
            imageLeftButton.title = 'Image à gauche';
            imageLeftButton.innerHTML = '⬅️';
            imageGroup.appendChild(imageLeftButton);

            const imageRightButton = document.createElement('button');
            imageRightButton.className = 'ql-image-right';
            imageRightButton.type = 'button';
            imageRightButton.title = 'Image à droite';
            imageRightButton.innerHTML = '➡️';
            imageGroup.appendChild(imageRightButton);

            imageLeftButton.addEventListener('click', () => {
                const img = quill.getSelection() && quill.getLeaf(quill.getSelection().index)[0].domNode;
                if (img && img.tagName === 'IMG') {
                    img.classList.remove('img-right');
                    img.classList.add('img-left');
                }
            });

            imageRightButton.addEventListener('click', () => {
                const img = quill.getSelection() && quill.getLeaf(quill.getSelection().index)[0].domNode;
                if (img && img.tagName === 'IMG') {
                    img.classList.remove('img-left');
                    img.classList.add('img-right');
                }
            });

        }
    }

    quill.getModule("toolbar")?.addHandler("image", function () {
        const input = document.createElement("input");
        input.setAttribute("type", "file");
        input.setAttribute("accept", "image/*");
        input.click();

        input.onchange = async function () {
            const file = input.files[0];
            if (file) {
                const resizedImage = await resizeImage(file);
                const range = quill.getSelection();
                if (range) {
                    quill.insertEmbed(range.index, "image", resizedImage);
                    quill.setSelection(range.index + 1, 0);
                }
            }
        };
    });

    const contentDisplay = document.getElementById('content-display');
    if (contentDisplay) {
        const initialContent = contentDisplay.innerHTML;
        quill.clipboard.dangerouslyPasteHTML(initialContent);
    }

    const editToggleBtn = document.getElementById('edit-toggle-btn');
    const saveBtn = document.getElementById('save-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const editorContainer = document.getElementById('editor-container');
    const titleDisplay = document.getElementById('article-title-display');
    const titleInput = document.getElementById('title-input');
    const editForm = document.getElementById('edit-form') || document.getElementById('presentationForm');
    const contentInput = document.getElementById('content-input');

    // Pour la vue présentation (sans edit-toggle-btn)
    if (!editToggleBtn && saveBtn && cancelBtn) {
        quill.on('text-change', function () {
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        });

        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (contentDisplay) {
                quill.clipboard.dangerouslyPasteHTML(contentDisplay.innerHTML);
            }
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        });
    }

    if (editToggleBtn) {
        editToggleBtn.addEventListener('click', function () {
            contentDisplay.style.display = 'none';
            editorContainer.style.display = 'block';
            editToggleBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
            titleDisplay.style.display = 'none';
        });

        cancelBtn.addEventListener('click', function () {
            contentDisplay.style.display = 'block';
            editorContainer.style.display = 'none';
            editToggleBtn.style.display = 'inline-block';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            titleDisplay.style.display = 'block';
            if (contentDisplay) {
                quill.clipboard.dangerouslyPasteHTML(contentDisplay.innerHTML);
            }
            if (titleInput && titleDisplay) {
                titleInput.value = titleDisplay.textContent;
            }
        });
    }

    if (editForm && contentInput) {
        editForm.addEventListener('submit', function (e) {
            contentInput.value = quill.root.innerHTML;
        });
    }    
});

function resizeImage(file, maxWidth = 1024, maxHeight = 1024) {
    return new Promise((resolve) => {
        const img = new Image();
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
        };

        img.onload = () => {
            let { width, height } = img;

            if (width > maxWidth || height > maxHeight) {
                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");

                const scale = Math.min(maxWidth / width, maxHeight / height);
                width = Math.round(width * scale);
                height = Math.round(height * scale);

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);

                resolve(canvas.toDataURL("image/jpeg", 0.9));
            } else {
                resolve(img.src);
            }
        };
        reader.readAsDataURL(file);
    });
}