function initTinyMCE(selector, options = {}) {
    const defaultConfig = {
        selector: selector || '#tinymce',
        language: 'fr_FR',
        language_url: '/app/modules/Common/js/tinymce/langs/fr_FR.js',

        base_url: '/app/modules/Common/js/tinymce',
        suffix: '.min',

        // ✅ FIX URLs MYCLUB
        relative_urls: false,
        remove_script_host: true,
        convert_urls: true,
        document_base_url: window.location.origin + '/',

        height: options.height || '100%',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks fontsize | ' +
            'bold italic underline strikethrough | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | ' +
            'forecolor backcolor | ' +
            'link image media table | ' +
            'code removeformat fullscreen help',

        image_advtab: true,
        image_caption: true,
        image_title: true,
        image_dimensions: true,

        image_class_list: [
            { title: 'Aucune classe', value: '' },
            { title: '⬅️ Image à gauche (texte à droite)', value: 'img-left' },
            { title: '➡️ Image à droite (texte à gauche)', value: 'img-right' },
            { title: '⬛ Image centrée', value: 'img-center' }
        ],

        extended_valid_elements: 'img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style]',
        valid_elements: '*[*]',
        valid_classes: 'img-left img-right img-center',

        object_resizing: true,
        resize_img_proportional: true,

        // Upload d'images avec COMPRESSION AUTOMATIQUE
        automatic_uploads: true,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise((resolve, reject) => {
                // Configuration de la compression
                const maxWidth = options.imageMaxWidth || 1200;  // Largeur max en pixels
                const maxHeight = options.imageMaxHeight || 1200; // Hauteur max en pixels
                const quality = options.imageQuality || 0.85;     // Qualité JPEG (0.0 à 1.0)

                const img = new Image();
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                img.onload = function () {
                    let width = img.width;
                    let height = img.height;

                    if (width > maxWidth || height > maxHeight) {
                        const ratio = Math.min(maxWidth / width, maxHeight / height);
                        width = Math.floor(width * ratio);
                        height = Math.floor(height * ratio);

                        console.log(`📸 Image redimensionnée: ${img.width}x${img.height} → ${width}x${height}`);
                    }

                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function (blob) {
                        if (!blob) {
                            reject('Erreur lors de la compression de l\'image');
                            return;
                        }

                        const originalSize = blobInfo.blob().size;
                        const compressedSize = blob.size;
                        const reduction = ((1 - compressedSize / originalSize) * 100).toFixed(1);

                        console.log(`💾 Compression: ${formatBytes(originalSize)} → ${formatBytes(compressedSize)} (${reduction}% de réduction)`);

                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result);
                        reader.onerror = () => reject('Erreur lors de la lecture de l\'image');
                        reader.readAsDataURL(blob);
                    }, 'image/jpeg', quality);
                };

                img.onerror = () => reject('Erreur lors du chargement de l\'image');
                img.src = URL.createObjectURL(blobInfo.blob());
            });
        },

        contextmenu: 'link image table',

        content_style: `
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                font-size: 14px; 
                line-height: 1.6;
                padding: 10px;
            }
            img { 
                max-width: 100%; 
                height: auto;
            }
            .img-left {
                float: left;
                margin: 0 15px 10px 0;
            }
            .img-right {
                float: right;
                margin: 0 0 10px 15px;
            }
            .img-center {
                display: block;
                margin: 10px auto;
                float: none;
            }
        `,

        menubar: false,
        statusbar: true,
        branding: false,

        setup: function (editor) {
            editor.on('init', function () {
                console.log('✅ TinyMCE chargé - Sélecteur: ' + selector);
                console.log('🖼️ Compression des images activée (max: ' + (options.imageMaxWidth || 1200) + 'px, qualité: ' + ((options.imageQuality || 0.85) * 100) + '%)');
                if (options.onInit) {
                    options.onInit(editor);
                }
            });

            // Ctrl+S pour enregistrer
            if (options.onSave) {
                editor.addShortcut('ctrl+s', 'Enregistrer', function () {
                    options.onSave(editor);
                });
            }

            // Callbacks additionnels
            if (options.setup) {
                options.setup(editor);
            }
        }
    };

    const config = Object.assign({}, defaultConfig, options);
    tinymce.init(config);
}

/**
 * Formater les octets en format lisible
 */
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Octets';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Octets', 'Ko', 'Mo', 'Go'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Rendre les fonctions disponibles globalement
window.initTinyMCE = initTinyMCE;
window.formatBytes = formatBytes;