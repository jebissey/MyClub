import ApiClient from '/app/modules/Common/js/ApiClient.js';
import { escHtml } from './ui.js';

const api = new ApiClient();

export default class MemberManager {
    #onChangeCallback = null;

    onChange(cb) {
        this.#onChangeCallback = cb;
        return this;
    }

    getCheckedIds() {
        return [...document.querySelectorAll('.member-checkbox:checked')].map(cb => cb.value);
    }

    async load() {
        const data = await api.post('/api/communication/members', {
            groupId      : document.getElementById('group-select').value || null,
            presentation : this.#readTriState('filter-presentation'),
            inPublicMap  : this.#readTriState('filter-in-public-map'),
            password     : this.#readTriState('filter-password'),
            desactivated : this.#readTriState('filter-desactivated-account'),
        });

        const members = data.data?.members;
        if (!data.success || !Array.isArray(members)) return;

        document.getElementById('member-ul').innerHTML = members.length
            ? members.map(m => this.#buildItem(m)).join('')
            : '<li class="list-group-item text-muted small py-2 px-2">Aucun membre trouvé.</li>';

        this.#onChangeCallback?.();
    }

    bindEvents() {
        document.getElementById('member-ul').addEventListener('change', () => {
            this.#onChangeCallback?.();
        });

        document.getElementById('member-ul').addEventListener('click', e => {
            const item = e.target.closest('.list-group-item');
            if (!item || e.target.type === 'checkbox') return;
            const cb = item.querySelector('.member-checkbox');
            cb.checked = !cb.checked;
            item.classList.toggle('list-group-item-primary', cb.checked);
            this.#onChangeCallback?.();
        });

        document.getElementById('btn-select-all').addEventListener('click', () => {
            const cbs = document.querySelectorAll('.member-checkbox');
            const all = [...cbs].every(cb => cb.checked);
            cbs.forEach(cb => {
                cb.checked = !all;
                cb.closest('.list-group-item').classList.toggle('list-group-item-primary', !all);
            });
            this.#onChangeCallback?.();
        });

        document.getElementById('btn-apply-filter').addEventListener('click', () => this.load());
    }

    /** Tri-état : "" → null, "1" → true, "0" → false */
    #readTriState(name) {
        const val = document.querySelector(`input[name="${name}"]:checked`)?.value;
        return val === '' || val === undefined ? null : val === '1';
    }

    #buildItem(m) {
        return `
            <li class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-1 px-2" data-id="${m.id}">
                <input class="form-check-input member-checkbox mt-0 flex-shrink-0" type="checkbox" value="${m.id}">
                <span class="small">
                    ${escHtml(m.name)}
                    <span class="text-muted d-block" style="font-size:.75rem">${escHtml(m.email)}</span>
                </span>
            </li>`;
    }
}