/**
 * TinyMCE ES6 Module
 * @module tinymce-config
 *
 * Modes:
 *  - 'normal'     → standard editor, safe HTML content
 *  - 'permissive' → all elements/attributes allowed (valid_elements: '*[*]')
 */

/** HTML restrictions applied in normal mode */
const NORMAL_SECURITY = {
    valid_elements: [
        'p', 'br', 'strong/b', 'em/i', 'u', 's', 'strike',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'a[href|target|rel|title]',
        'img[class|src|alt|title|width|height|style]',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th[colspan|rowspan|style]', 'td[colspan|rowspan|style]',
        'div[class|style]', 'span[class|style]',
        'figure', 'figcaption',
        'hr', 'sub', 'sup',
    ].join(','),
    extended_valid_elements: 'img[class|src|border=0|alt|title|hspace|vspace|width|height|align|style]',
    valid_classes: 'img-left img-right img-center',
};

/** No restrictions in permissive mode */
const PERMISSIVE_SECURITY = {
    valid_elements: '*[*]',
    extended_valid_elements: 'img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style]',
    valid_classes: '',
};

/**
 * Builds the TinyMCE upload handler with automatic compression.
 * @param {{ imageMaxWidth?: number, imageMaxHeight?: number, imageQuality?: number }} opts
 * @returns {Function}
 */
function buildUploadHandler(opts) {
    return function (blobInfo /*, progress */) {
        return new Promise((resolve, reject) => {
            const maxWidth = opts.imageMaxWidth ?? 1200;
            const maxHeight = opts.imageMaxHeight ?? 1200;
            const quality = opts.imageQuality ?? 0.85;

            const img = new Image();
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            img.onload = function () {
                let { width, height } = img;

                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width = Math.floor(width * ratio);
                    height = Math.floor(height * ratio);

                }

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) { reject('Error while compressing the image'); return; }

                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = () => reject('Error while reading the image');
                    reader.readAsDataURL(blob);
                }, 'image/jpeg', quality);
            };

            img.onerror = () => reject('Error while loading the image');
            img.src = URL.createObjectURL(blobInfo.blob());
        });
    };
}

/**
 * Initialise TinyMCE on a CSS selector.
 *
 * @param {string} selector   - Target CSS selector (e.g. '#my-textarea')
 * @param {Object} [options]  - Customisation options
 * @param {'normal'|'permissive'} [options.mode='normal'] - HTML validation mode
 * @param {number}   [options.height]            - Editor height
 * @param {number}   [options.imageMaxWidth=1200]
 * @param {number}   [options.imageMaxHeight=1200]
 * @param {number}   [options.imageQuality=0.85]
 * @param {Function} [options.onInit]            - Callback fired after init(editor)
 * @param {Function} [options.onSave]            - Ctrl+S callback → onSave(editor)
 * @param {Function} [options.setup]             - Additional setup(editor) hook
 */
export function initTinyMCE(selector, options = {}) {
    const mode = options.mode ?? 'normal';
    const security = mode === 'permissive' ? PERMISSIVE_SECURITY : NORMAL_SECURITY;

    const config = {
        selector: selector ?? '#tinymce',
        language: 'fr_FR',
        language_url: '/app/modules/Common/js/tinymce/langs/fr_FR.js',

        base_url: '/app/modules/Common/js/tinymce',
        suffix: '.min',

        // ✅ Fix URLs relatives MyClub
        relative_urls: false,
        remove_script_host: true,
        convert_urls: true,
        document_base_url: window.location.origin + '/',

        height: options.height ?? '100%',

        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount',
        ],

        toolbar:
            'undo redo | blocks fontsize | '
            + 'bold italic underline strikethrough | '
            + 'alignleft aligncenter alignright alignjustify | '
            + 'bullist numlist outdent indent | '
            + 'forecolor backcolor | '
            + 'link image media table | '
            + 'code removeformat fullscreen help',

        // ── Image ──────────────────────────────────────────────────────────
        image_advtab: true,
        image_caption: true,
        image_title: true,
        image_dimensions: true,

        image_class_list: [
            { title: 'Aucune classe', value: '' },
            { title: '⬅️ Image à gauche (texte à droite)', value: 'img-left' },
            { title: '➡️ Image à droite (texte à gauche)', value: 'img-right' },
            { title: '⬛ Image centrée', value: 'img-center' },
        ],

        object_resizing: true,
        resize_img_proportional: true,

        // ── Upload with automatic compression ──────────────────────────────
        automatic_uploads: true,
        images_upload_handler: buildUploadHandler(options),

        // ── HTML security (injected according to mode) ─────────────────────
        ...security,

        // ── Appearance ─────────────────────────────────────────────────────
        contextmenu: 'link image table',
        menubar: false,
        statusbar: true,
        branding: false,

        content_style: `
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                             "Helvetica Neue", Arial, sans-serif;
                font-size: 14px;
                line-height: 1.6;
                padding: 10px;
            }
            img { max-width: 100%; height: auto; }
            .img-left   { float: left;  margin: 0 15px 10px 0; }
            .img-right  { float: right; margin: 0 0 10px 15px; }
            .img-center { display: block; margin: 10px auto; float: none; }
        `,

        // ── Setup ──────────────────────────────────────────────────────────
        setup(editor) {
            editor.on('init', () => {
                options.onInit?.(editor);
            });

            if (options.onSave) {
                editor.addShortcut('ctrl+s', 'Enregistrer', () => options.onSave(editor));
            }

            options.setup?.(editor);
        },
    };

    // Reserved keys must not be overridden by options
    const RESERVED = new Set([
        'selector', 'mode', 'setup',
        'valid_elements', 'extended_valid_elements', 'valid_classes',
        'images_upload_handler',
    ]);

    for (const [key, value] of Object.entries(options)) {
        if (!RESERVED.has(key)) config[key] = value;
    }

    tinymce.init(config);
}

// ---------------------------------------------------------------------------
// Global compat (optional — useful during migration)
// ---------------------------------------------------------------------------
window.initTinyMCE = initTinyMCE;