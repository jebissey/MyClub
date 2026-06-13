export default class ImageHelper {

    static resizeCanvas(canvas, maxPx) {
        const { width, height } = canvas;

        if (width <= maxPx && height <= maxPx) {
            return canvas;
        }

        const ratio = Math.min(maxPx / width, maxPx / height);

        const out = document.createElement('canvas');
        out.width = Math.round(width * ratio);
        out.height = Math.round(height * ratio);

        out.getContext('2d').drawImage(
            canvas,
            0,
            0,
            out.width,
            out.height
        );

        return out;
    }

    static async fileToDataUrl(file, maxPx = 1200, quality = 0.9) {
        return new Promise((resolve, reject) => {

            const img = new Image();

            img.onload = () => {

                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;

                canvas
                    .getContext('2d')
                    .drawImage(img, 0, 0);

                const resized = this.resizeCanvas(canvas, maxPx);

                resolve(
                    resized.toDataURL(
                        'image/jpeg',
                        quality
                    )
                );

                URL.revokeObjectURL(img.src);
            };

            img.onerror = reject;
            img.src = URL.createObjectURL(file);
        });
    }
}