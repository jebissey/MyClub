export default class EmailManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.modal = null;
        this.form = null;
        this.currentEventData = {};
        this._init();
    }

    _init() {
        this.modal = document.getElementById('emailModal');
        this.form = document.getElementById('emailForm');
        if (!this.modal || !this.form) return;

        const emailTypeSelect = document.getElementById('emailTypeSelect');
        const recipientsSelect = document.getElementById('recipientsSelect');

        const openModal = e => {
            const btn = e.target.closest('.email-btn');
            if (!btn) return;

            this.currentEventData = {
                eventId: btn.dataset.eventId,
                eventTitle: btn.dataset.eventTitle,
                participantsCount: parseInt(btn.dataset.participantsCount, 10) || 0,
                messagesCount: parseInt(btn.dataset.webappMessagesCount, 10) || 0,
                canceled: btn.dataset.canceled === 'true'
            };

            document.getElementById('emailEventId').value =
                this.currentEventData.eventId;

            resetForm();
            updateEmailTypeOptions();
        };

        const updateEmailTypeOptions = () => {
            const hasMessages = this.currentEventData.messagesCount > 0;
            const canceled = this.currentEventData.canceled;

            let autoSelectValue = null;

            emailTypeSelect.innerHTML = '';
            emailTypeSelect.disabled = false;

            if (canceled) {
                emailTypeSelect.innerHTML =
                    '<option value="annule">Annulé</option>';
                autoSelectValue = 'annule';
            } else if (!hasMessages) {
                emailTypeSelect.innerHTML =
                    '<option value="nouvel-evenement">Nouvel événement</option>';
                autoSelectValue = 'nouvel-evenement';
            } else {
                emailTypeSelect.innerHTML =
                    '<option value="">Sélectionnez un type</option>' +
                    '<option value="rappel">Rappel</option>' +
                    '<option value="modifie">Modifié</option>';
            }

            if (autoSelectValue) {
                emailTypeSelect.value = autoSelectValue;
                emailTypeSelect.disabled = true;
                updateRecipientsOptions(autoSelectValue);
            }
        };

        const updateRecipientsOptions = messageType => {
            recipientsSelect.innerHTML = '';
            recipientsSelect.disabled = false;

            recipientsSelect.innerHTML =
                '<option value="">Sélectionnez les destinataires</option>';

            const options = {
                'nouvel-evenement': [
                    { value: 'all', label: 'Tous' }
                ],
                'rappel': [
                    { value: 'unregistered', label: 'Tous les non-inscrits' }
                ],
                'annule': [
                    { value: 'registered', label: 'Tous les inscrits' }
                ],
                'modifie': [
                    { value: 'registered', label: 'Tous les inscrits' }
                ]
            };

            const list = options[messageType] || [];

            list.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.label;
                recipientsSelect.appendChild(option);
            });

            if (list.length === 1) {
                recipientsSelect.value = list[0].value;
                recipientsSelect.disabled = true;
            }
        };

        const handleSubmit = async e => {
            e.preventDefault();

            const formData = new FormData(this.form);
            const selectedType =
                emailTypeSelect.options[emailTypeSelect.selectedIndex];

            const emailData = {
                EventId: this.currentEventData.eventId,
                Title: selectedType?.text || '',
                Body: formData.get('message'),
                Recipients: recipientsSelect.value
            };

            const result = await this.api.post(
                '/api/event/sendEmails',
                emailData
            );

            if (result.success) {
                alert('Courriel envoyé avec succès !');
                bootstrap.Modal.getInstance(this.modal).hide();
                resetForm();
            } else {
                alert(
                    "Erreur lors de l'envoi du courriel : " +
                    (result.message || 'Erreur inconnue')
                );
            }
        };

        const resetForm = () => {
            this.form.reset();

            emailTypeSelect.disabled = false;
            emailTypeSelect.innerHTML =
                '<option value="">Sélectionnez un type</option>';

            recipientsSelect.disabled = false;
            recipientsSelect.innerHTML =
                '<option value="">Sélectionnez d\'abord un type de message</option>';
        };

        // wiring
        document.querySelectorAll('.email-btn').forEach(btn => {
            btn.addEventListener('click', openModal);
        });

        emailTypeSelect?.addEventListener('change', e => {
            updateRecipientsOptions(e.target.value);
        });

        this.form.addEventListener('submit', handleSubmit);
    }
}
