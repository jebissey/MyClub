document.addEventListener('DOMContentLoaded', function () {
    const replySurveyBtn = document.getElementById('reply-survey-btn');
    if (replySurveyBtn) {
        replySurveyBtn.addEventListener('click', function () {
            const surveyModal = new bootstrap.Modal(document.getElementById('surveyModal'));

            fetch(`/api/surveys/reply/${ARTICLE_ID}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('survey-form-container');
                        let html = `
                            <form id="survey-form">
                                <input type="hidden" name="survey_id" value="${data.survey.id}">
                                <input type="hidden" name="user_email" value="{$userEmail}">
                                <h4>${data.survey.question}</h4>
                                <div class="mb-3">
                        `;
                        data.survey.options.forEach(option => {
                            const isChecked = data.survey.previousAnswers &&
                                data.survey.previousAnswers.includes(option) ? 'checked' : '';
                            html += `
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                        name="survey_answers" 
                                        value="${option}" 
                                        id="option-${option.replace(/\s+/g, '-')}"
                                        ${isChecked}>
                                    <label class="form-check-label" for="option-${option.replace(/\s+/g, '-')}">
                                        ${option}
                                    </label>
                                </div>
                            `;
                        });
                        html += `
                                </div>
                                <div class="text-center">
                                    <button type="button" id="submit-survey" class="btn btn-primary">
                                        ${data.survey.previousAnswers ? 'Mettre à jour' : 'Répondre'}
                                    </button>
                                </div>
                            </form>
                        `;
                        container.innerHTML = html;

                        document.getElementById('submit-survey').addEventListener('click', function () {
                            const selectedOptions = Array.from(
                                document.querySelectorAll('input[name="survey_answers"]:checked')
                            ).map(checkbox => checkbox.value);

                            if (selectedOptions.length === 0) {
                                alert('Veuillez sélectionner au moins une option.');
                                return;
                            }
                            const formData = {
                                survey_id: document.querySelector('input[name="survey_id"]').value,
                                survey_answers: selectedOptions,
                            };
                            fetch('/api/surveys/reply', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify(formData)
                            })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        appendAlert('Votre réponse a été enregistrée avec succès.', 'info');
                                        surveyModal.hide();
                                    } else {
                                        appendAlert('Erreur_1 : ' + result.message, 'danger');
                                    }
                                })
                                .catch(error => {
                                    appendAlert('Erreur_2 : ' + error, 'danger');
                                });
                        });

                        surveyModal.show();
                    } else {
                        appendAlert('Erreur_3 : ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    appendAlert('Erreur_4 : ', error, 'danger');
                });
        });
    }

    const groupSelect = document.getElementById('group-select');
    const publishedInput = document.getElementById('published-input');
    const membersOnlyCheckbox = document.getElementById('members-only-checkbox');

    if (groupSelect && membersOnlyCheckbox && publishedInput) {
        function updateInputsState() {
            const groupSelected = groupSelect.value !== "";
            const membersOnlyChecked = membersOnlyCheckbox.checked;

            if (!groupSelected && !membersOnlyChecked) {
                publishedInput.disabled = true;
                publishedInput.checked = false;
                membersOnlyCheckbox.disabled = false;
            } else {
                publishedInput.disabled = false;
            }

            if (groupSelected) {
                membersOnlyCheckbox.checked = true;
                membersOnlyCheckbox.disabled = true;
            } else {
                membersOnlyCheckbox.disabled = false;
            }
        }

        updateInputsState();
        groupSelect.addEventListener('change', updateInputsState);
        membersOnlyCheckbox.addEventListener('change', updateInputsState);
    }
});