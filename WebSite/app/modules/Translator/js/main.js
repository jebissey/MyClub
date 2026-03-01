import ApiClient from '../../Common/js/ApiClient.js';

document.addEventListener('DOMContentLoaded', () => {
    new TranslatorController().init();
});

class TranslatorController {

    constructor() {
        this.api = new ApiClient();
    }

    init() {
        this.#bindLangSelects();
        this.#bindRefCells();
        this.#bindPreviewTabs();

        document
            .querySelectorAll('.translation-field')
            .forEach(textarea => this.#bindField(textarea));
    }

    #bindLangSelects() {
        const form = document.querySelector('#translator-form');
        if (!form) return;

        form.querySelectorAll('select[name="ref"], select[name="lang"]')
            .forEach(select => {
                select.addEventListener('change', () => form.submit());
            });
    }

    #bindRefCells() {
        document.querySelectorAll('.ref-value').forEach(cell => {
            cell.addEventListener('click', (e) => {
                if (e.target.closest('.nav-tabs')) return;

                const text = cell.dataset.raw ?? cell.innerHTML.trim();
                if (!text) return;

                this.#copyToClipboard(text)
                    .then(() => this.#flashCell(cell, 'copied'))
                    .catch(() => this.#flashCell(cell, 'error'));
            });
        });
    }

    async #copyToClipboard(text) {
        if (navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(text);
                return;
            } catch {
                // blocked on HTTP → fallback below
            }
        }

        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        if (!ok) throw new Error('execCommand échoué');
    }

    #flashCell(cell, state) {
        const prev = cell.style.color;
        cell.style.color = state === 'copied' ? 'green' : 'red';
        setTimeout(() => { cell.style.color = prev; }, 1000);
    }

    #bindPreviewTabs() {
        document.querySelectorAll('.nav-tabs').forEach(nav => {
            const cell = nav.closest('td');

            nav.querySelectorAll('[data-tab]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const target = link.dataset.tab;

                    nav.querySelectorAll('[data-tab]').forEach(l => l.classList.remove('active'));
                    link.classList.add('active');

                    cell.querySelectorAll('[data-content]').forEach(el => {
                        el.classList.toggle('d-none', el.dataset.content !== target);
                    });

                    if (target === 'preview') {
                        const textarea = cell.querySelector('.translation-field');
                        const preview = cell.querySelector('[data-content="preview"]');
                        if (textarea && preview) {
                            preview.innerHTML = textarea.value;
                        }
                    }
                });
            });
        });
    }

    #bindField(textarea) {
        textarea.addEventListener('blur', () => this.#handleBlur(textarea));
    }

    async #handleBlur(textarea) {
        const original = textarea.dataset.original ?? '';
        const current = textarea.value;

        if (current === original) return;

        const id = textarea.dataset.id;
        const lang = textarea.dataset.lang;
        const status = textarea.closest('td').querySelector('.translation-status');

        this.#setStatus(status, 'saving');

        const result = await this.api.post('/api/translator/save', {
            id: parseInt(id, 10),
            lang: lang,
            value: current,
        });

        if (result?.success) {
            textarea.dataset.original = current;
            this.#setStatus(status, 'saved');
        } else {
            this.#setStatus(status, 'error', result?.message);
        }
    }

    #setStatus(el, state, message = '') {
        const states = {
            saving: { text: '⏳ Saving…', cls: 'text-secondary' },
            saved: { text: '✅ Saved', cls: 'text-success' },
            error: { text: '❌ Error', cls: 'text-danger' },
        };

        const { text, cls } = states[state];
        el.className = `translation-status small ms-1 ${cls}`;
        el.textContent = message ? `❌ ${message}` : text;

        if (state === 'saved') {
            setTimeout(() => {
                el.textContent = '';
                el.className = 'translation-status small ms-1';
            }, 2500);
        }
    }
}