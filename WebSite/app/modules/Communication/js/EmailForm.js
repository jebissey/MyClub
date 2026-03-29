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

    #onSendClick() {
        const subject = document.getElementById('email-subject').value.trim();
        const content = tinymce.get('tinymce-email')?.getContent()?.trim();

        if (!subject) {
            showToast('Veuillez renseigner l\'objet du message.', false);
            document.getElementById('email-subject').focus();
            return;
        }
        if (!content || content === '<p><br></p>') {
            showToast('Veuillez renseigner le contenu du message.', false);
            return;
        }

        const quotaLine = this.#quota.buildConfirmQuotaLine();
        document.getElementById('modal-confirm-body').innerHTML = `
            <p>Vous êtes sur le point d'envoyer ce message à
            <strong>${this.#members.getCheckedIds().length}</strong>
            destinataire(s) en copie cachée (BCC).</p>
            ${quotaLine ? `<p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> ${quotaLine}</p>` : ''}`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm')).show();
    }

    async #onConfirmSend() {
        bootstrap.Modal.getInstance(document.getElementById('modal-confirm')).hide();
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
            } else {
                showToast(`✗ ${response.message ?? 'Échec de l\'envoi.'}`, false);
            }
        } catch (e) {
            console.error('Erreur lors de l\'envoi :', e);
            showToast('✗ Une erreur inattendue est survenue.', false);
        } finally {
            hideOverlay();
        }
    }
}