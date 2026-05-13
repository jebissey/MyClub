/**
 * Utilitaire pur de redimensionnement d'image via Canvas.
 * Responsabilité unique : transformer un File en dataURL redimensionné.
 * Aucun effet de bord sur le DOM.
 */
export class ImageProcessor {
    /**
     * Redimensionne une image selon la stratégie choisie.
     *
     * @param {File}   file
     * @param {object} opts
     * @param {number} opts.maxW
     * @param {number} opts.maxH
     * @param {'fit'|'cover'|'exact'} opts.mode
     *   - 'fit'   : conserve le ratio, réduit pour tenir dans maxW×maxH
     *   - 'cover' : recadre au centre pour remplir exactement maxW×maxH
     *   - 'exact' : force exactement maxW×maxH (peut déformer)
     * @param {'image/png'|'image/jpeg'} opts.mimeType
     * @param {number} [opts.quality=0.92]  Qualité JPEG (0–1)
     * @returns {Promise<{dataURL: string, width: number, height: number, sizeKB: number}>}
     */
    static resize(file, { maxW, maxH, mode = 'fit', mimeType = 'image/png', quality = 0.92 }) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            const url = URL.createObjectURL(file);

            img.onload = () => {
                URL.revokeObjectURL(url);

                const { srcX, srcY, srcW, srcH, dstW, dstH } =
                    ImageProcessor.#computeDimensions(img.width, img.height, maxW, maxH, mode);

                const canvas     = document.createElement('canvas');
                canvas.width     = dstW;
                canvas.height    = dstH;
                const ctx        = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, srcX, srcY, srcW, srcH, 0, 0, dstW, dstH);

                const dataURL = canvas.toDataURL(mimeType, quality);
                const sizeKB  = Math.round((dataURL.length * 3 / 4) / 1024);

                resolve({ dataURL, width: dstW, height: dstH, sizeKB });
            };

            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Impossible de lire l\'image.'));
            };

            img.src = url;
        });
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /**
     * Calcule les rectangles source et destination selon le mode choisi.
     * @returns {{ srcX, srcY, srcW, srcH, dstW, dstH }}
     */
    static #computeDimensions(imgW, imgH, maxW, maxH, mode) {
        let srcX = 0, srcY = 0, srcW = imgW, srcH = imgH;
        let dstW, dstH;

        switch (mode) {
            case 'exact':
                dstW = maxW;
                dstH = maxH;
                break;

            case 'cover': {
                dstW = maxW;
                dstH = maxH;
                const scale  = Math.max(maxW / imgW, maxH / imgH);
                srcX = (imgW - maxW  / scale) / 2;
                srcY = (imgH - maxH  / scale) / 2;
                srcW = imgW - srcX * 2;
                srcH = imgH - srcY * 2;
                break;
            }

            case 'fit':
            default: {
                const ratio = Math.min(maxW / imgW, maxH / imgH, 1);
                dstW = Math.round(imgW * ratio);
                dstH = Math.round(imgH * ratio);
                break;
            }
        }

        return { srcX, srcY, srcW, srcH, dstW, dstH };
    }
}