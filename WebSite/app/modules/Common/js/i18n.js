/**
 * Lightweight i18n helper shared across the whole application.
 *
 * Reads translations from the global `window.i18n` object, which must be
 * populated server-side via {='key'|translate} assignments in the layout
 * or page-level Latte templates (see backup.latte for the pattern).
 *
 * Supports `{name}` placeholders, e.g. `t('hello', { name: 'John' })` -> "Hello John".
 *
 * Exposed both as:
 *   - ES module export:  `import { t } from '/app/modules/Common/js/i18n.js'`
 *   - Global window.t:   `window.t('key')`  (used by layout.js)
 *
 * @param {string} key  Translation key.
 * @param {Object<string, string|number>} [params]  Optional placeholders.
 * @returns {string}  Translated string, or the key itself if missing.
 */
export const t = (key, params = {}) =>
    Object.entries(params).reduce(
        (text, [name, value]) => text.replaceAll(`{${name}}`, String(value)),
        window.i18n?.[key] ?? key
    );

// Expose globally so non-module scripts (layout.js) can use window.t('...').
window.t = t;
