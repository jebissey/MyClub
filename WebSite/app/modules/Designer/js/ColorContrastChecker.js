/**
 * Utilitaire pur de calcul du contraste de couleurs selon la spec WCAG 2.1.
 * Responsabilité unique : arithmétique de contraste, sans manipulation du DOM.
 */
export class ColorContrastChecker {
    /**
     * @param {string} hex1  Couleur en notation hexadécimale (#rrggbb ou #rgb)
     * @param {string} hex2
     * @returns {number}  Ratio de contraste arrondi à 2 décimales
     */
    static ratio(hex1, hex2) {
        const l1 = ColorContrastChecker.#relativeLuminance(hex1);
        const l2 = ColorContrastChecker.#relativeLuminance(hex2);

        const brightest = Math.max(l1, l2);
        const darkest   = Math.min(l1, l2);

        return parseFloat(((brightest + 0.05) / (darkest + 0.05)).toFixed(2));
    }

    /**
     * Classe WCAG correspondant à un ratio.
     * @param {number} ratio
     * @returns {{ label: string, cssClass: string }}
     */
    static classify(ratio) {
        if (ratio >= 7)   return { label: 'Excellent',    cssClass: 'text-success' };
        if (ratio >= 4.5) return { label: 'Correct',      cssClass: 'text-warning' };
        return               { label: 'Insuffisant',  cssClass: 'text-danger'  };
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** @returns {{ r, g, b }} valeurs 0–255 */
    static #hexToRgb(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');

        const num = parseInt(hex, 16);
        return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
    }

    /** Luminosité relative WCAG (0–1). */
    static #relativeLuminance(hex) {
        const { r, g, b } = ColorContrastChecker.#hexToRgb(hex);

        const [rl, gl, bl] = [r, g, b].map(v => {
            v /= 255;
            return v <= 0.03928
                ? v / 12.92
                : Math.pow((v + 0.055) / 1.055, 2.4);
        });

        return 0.2126 * rl + 0.7152 * gl + 0.0722 * bl;
    }
}