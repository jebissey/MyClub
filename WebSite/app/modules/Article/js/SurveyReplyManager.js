import ApiClient from '../../Common/js/ApiClient.js';

export default class SurveyReplyManager {
    constructor(articleId, userEmail) {
        this.articleId = articleId;
        this.userEmail = userEmail;
        this.api = new ApiClient();
        this.modalInstance = null;
    }

    init() {
        const replyBtn = document.getElementById('reply-survey-btn');
        if (replyBtn) {
            replyBtn.addEventListener('click', () => this.handleReplyClick());
        }
    }

    async handleReplyClick() {
        try {
            const data = await this.api.get(`/api/survey/reply/${this.articleId}`);
            
            if (data.success) {
                this.showSurveyModal(data.survey);
            } else {
                this.showAlert('Erreur : ' + data.message, 'danger');
            }
        } catch (error) {
            this.showAlert('Erreur lors du chargement du sondage : ' + error.message, 'danger');
        }
    }

    showSurveyModal(survey) {
        const modalElement = document.getElementById('surveyModal');
        if (!modalElement) {
            console.error('Modal element #surveyModal not found');
            return;
        }

        const container = document.getElementById('survey-form-container');
        if (!container) {
            console.error('Container element #survey-form-container not found');
            return;
        }

        container.innerHTML = this.buildSurveyForm(survey);
        this.attachFormEventListeners(survey);

        this.modalInstance = new bootstrap.Modal(modalElement);
        this.modalInstance.show();
    }

    buildSurveyForm(survey) {
        const optionsHtml = survey.options.map(option => {
            const isChecked = survey.previousAnswers && 
                survey.previousAnswers.includes(option) ? 'checked' : '';
            const optionId = `option-${this.sanitizeId(option)}`;
            
            return `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                        name="survey_answers" 
                        value="${this.escapeHtml(option)}" 
                        id="${optionId}"
                        ${isChecked}>
                    <label class="form-check-label" for="${optionId}">
                        ${this.escapeHtml(option)}
                    </label>
                </div>
            `;
        }).join('');

        const buttonText = survey.previousAnswers ? 'Mettre à jour' : 'Répondre';

        return `
            <form id="survey-form">
                <input type="hidden" name="survey_id" value="${survey.id}">
                <input type="hidden" name="user_email" value="${this.escapeHtml(this.userEmail)}">
                <h4>${this.escapeHtml(survey.question)}</h4>
                <div class="mb-3">
                    ${optionsHtml}
                </div>
                <div class="text-center">
                    <button type="button" id="submit-survey" class="btn btn-primary">
                        ${buttonText}
                    </button>
                </div>
            </form>
        `;
    }

    attachFormEventListeners(survey) {
        const submitBtn = document.getElementById('submit-survey');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.handleSubmit());
        }
    }

    async handleSubmit() {
        const selectedOptions = Array.from(
            document.querySelectorAll('input[name="survey_answers"]:checked')
        ).map(checkbox => checkbox.value);

        if (selectedOptions.length === 0) {
            alert('Veuillez sélectionner au moins une option.');
            return;
        }

        const formData = {
            survey_id: document.querySelector('input[name="survey_id"]').value,
            survey_answers: selectedOptions
        };

        try {
            const result = await this.api.post('/api/survey/reply', formData);

            if (result.success) {
                this.showAlert('Votre réponse a été enregistrée avec succès.', 'info');
                if (this.modalInstance) {
                    this.modalInstance.hide();
                }
            } else {
                this.showAlert('Erreur : ' + result.message, 'danger');
            }
        } catch (error) {
            this.showAlert('Erreur lors de l\'enregistrement : ' + error.message, 'danger');
        }
    }

    showAlert(message, type = 'info') {
        // Utilise la fonction globale appendAlert si elle existe
        if (typeof appendAlert === 'function') {
            appendAlert(message, type);
        } else {
            // Fallback: log en console
            console.log(`[${type.toUpperCase()}] ${message}`);
            alert(message);
        }
    }

    // Utilitaires de sécurité
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    sanitizeId(text) {
        return text.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-_]/g, '');
    }
}
