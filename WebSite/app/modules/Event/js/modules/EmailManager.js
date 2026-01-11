export default class EmailManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.modal = null;
        this.form = null;
        this.currentEventData = {};
    }

    init() {
        this.modal = document.getElementById('emailModal');
        this.form = document.getElementById('emailForm');

        if (!this.modal || !this.form) return;

        this.setupEventListeners();
    }

    setupEventListeners() {
        document.querySelectorAll('.email-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.openModal(e));
        });

        document.getElementById('emailTypeSelect')?.addEventListener('change', (e) => {
            this.updateRecipientsOptions(e.target.value);
        });

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    openModal(e) {
        const btn = e.target.closest('.email-btn');
        this.currentEventData = {
            eventId: btn.dataset.eventId,
            eventTitle: btn.dataset.eventTitle,
            participantsCount: parseInt(btn.dataset.participantsCount),
            messagesCount: parseInt(btn.dataset.webappMessagesCount)
        };

        document.getElementById('emailEventId').value = this.currentEventData.eventId;
        this.updateEmailTypeOptions();
        this.resetForm();
    }

    updateEmailTypeOptions() {
        const hasMessages = this.currentEventData.messagesCount > 0;
        const select = document.getElementById('emailTypeSelect');
        
        select.innerHTML = '<option value="">Sélectionnez un type</option>';

        if (!hasMessages) {
            select.innerHTML += '<option value="nouvel-evenement">Nouvel évènement</option>';
        } else {
            select.innerHTML += '<option value="rappel">Rappel</option>';
            select.innerHTML += '<option value="annule">Annulé</option>';
            select.innerHTML += '<option value="modifie">Modifié</option>';
        }
    }

    updateRecipientsOptions(messageType) {
        const select = document.getElementById('recipientsSelect');
        select.innerHTML = '<option value="">Sélectionnez les destinataires</option>';

        const options = {
            'nouvel-evenement': '<option value="all">Tous</option>',
            'rappel': '<option value="unregistered">Tous les non-inscrits</option>',
            'annule': '<option value="registered">Tous les inscrits</option>',
            'modifie': '<option value="registered">Tous les inscrits</option>'
        };

        select.innerHTML += options[messageType] || '';
    }

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.form);
        const emailTypeSelect = document.getElementById('emailTypeSelect');
        
        const emailData = {
            EventId: this.currentEventData.eventId,
            Title: emailTypeSelect.options[emailTypeSelect.selectedIndex].text,
            Body: formData.get('message'),
            Recipients: formData.get('recipients')
        };

        const result = await this.api.post('/api/event/sendEmails', emailData);

        if (result.success) {
            alert('Courriel envoyé avec succès !');
            bootstrap.Modal.getInstance(this.modal).hide();
            this.resetForm();
        } else {
            alert('Erreur lors de l\'envoi du courriel : ' + (result.message || 'Erreur inconnue'));
        }
    }

    resetForm() {
        this.form.reset();
        document.getElementById('recipientsSelect').innerHTML = '<option value="">Sélectionnez d\'abord un type de message</option>';
    }
}
