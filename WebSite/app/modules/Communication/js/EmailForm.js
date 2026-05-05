import ApiClient from '/app/modules/Common/js/ApiClient.js';
import { showToast, showOverlay, hideOverlay } from './ui.js';

const api = new ApiClient();

export default class EmailForm {
    #quota = null;
    #members = null;

    /**
     * @param {import('./quota.js').default}   quota
     * @param {import('./members.js').default} members
     */
    constructor(quota, members) {
        this.#quota = quota;
        this.#members = members;
    }

    bindEvents() {
        document.getElementById('btn-send').addEventListener('click',
            () => this.#onSendClick());

        document.getElementById('btn-confirm-send').addEventListener('click',
            () => this.#onConfirmSend());
    }

    // Handle email sending form logic
    #onSendClick() {
        const subject = document.getElementById('email-subject').value.trim();
        const content = tinymce.get('tinymce-email')?.getContent()?.trim();

        if (!subject) {
            showToast(window.t('subjectRequired'), false);
            document.getElementById('email-subject').focus();
            return;
        }

        if (!content || content === '<p><br></p>') {
            showToast(window.t('contentRequired'), false);
            return;
        }

        const count = this.#members.getCheckedIds().length;

        const quotaLine = this.#quota.buildConfirmQuotaLine();

        document.getElementById('modal-confirm-body').innerHTML = `
        <p>${window.t('confirmSend').replace('%d', count)}</p>
        ${quotaLine ? `<p class="text-muted small mb-0">${quotaLine}</p>` : ''}
    `;

        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modal-confirm')
        ).show();
    }

    async #onConfirmSend() {
        bootstrap.Modal.getInstance(
            document.getElementById('modal-confirm')
        ).hide();

        showOverlay();

        try {
            const payload = {
                subject: document.getElementById('email-subject').value.trim(),
                content: tinymce.get('tinymce-email')?.getContent() ?? '',
                recipient_ids: this.#members.getCheckedIds(),
            };

            const response = await api.post('/api/communication/send', payload);
            const data = response.data ?? {};

            this.#quota.applyResponse(data);

            if (data.quotaHit) {
                showToast(`✗ ${data.toast}`, false);
            } else if (response.success) {
                showToast(`✓ ${data.toast}`, true);
                document.getElementById('email-subject').value = '';
                tinymce.get('tinymce-email')?.setContent('');
                this.#members.clearSelection();
            } else {
                showToast(`✗ ${response.message ?? window.t('sendError')}`, false);
            }
        } catch (e) {
            console.error('Send error:', e);
            showToast(`✗ ${window.t('unexpectedError')}`, false);
        } finally {
            hideOverlay();
        }
    }
}