document.addEventListener('DOMContentLoaded', function () {
    const eventModal = document.getElementById('eventModal');
    const eventForm = document.getElementById('eventForm');
    const submitButton = document.getElementById('submitEventBtn');
    const formMode = document.getElementById('formMode');
    const eventId = document.getElementById('eventId');
    const modalTitle = document.getElementById('eventModalLabel');

    let selectedAttributes = [];
    const eventTypeInput = document.getElementById('eventTypeInput');
    const attributesList = document.getElementById('attributesList');
    const needTypeInput = document.getElementById('needTypeInput');

    let selectedNeeds = [];
    const needsList = document.getElementById('needsList');
    const availableNeedsSelect = document.getElementById('availableNeedsSelect');

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('tr');
            const eventId = row.getAttribute('onclick').match(/\/event\/(\d+)/)[1];
            fetchEventDetails(eventId);
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('tr');
            const eventId = row.getAttribute('onclick').match(/\/event\/(\d+)/)[1];
            confirmAndDeleteEvent(eventId);
        });
    });

    document.querySelectorAll(".duplicate-btn").forEach(btn => {
        btn.addEventListener("click", function (e) {
            e.stopPropagation();
            const eventId = this.dataset.id;
            const duplicateModal = new bootstrap.Modal(document.getElementById('duplicateModal'));
            duplicateModal.show();
            document.querySelectorAll("input[name='duplicateChoice']").forEach(input => input.checked = false);
            document.getElementById("confirmDuplicate").onclick = function () {
                const choice = document.querySelector("input[name='duplicateChoice']:checked");
                if (!choice) {
                    alert("Merci de sélectionner une option.");
                    return;
                }
                const selectedValue = choice.value;
                let mode = "";
                if (selectedValue === "1") mode = "today";
                if (selectedValue === "2") mode = "week";
                fetch(`/api/event/duplicate/${eventId}?mode=${mode}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert("Erreur : " + data.message);
                        }
                    });
                duplicateModal.hide();
            };
        });
    });

    function fetchEventDetails(eventId) {
        fetch(`/api/event/${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.event && data.attributes) {
                    openUpdateModal(data.event, data.attributes);
                } else alert('Erreur lors de la récupération des détails de l\'événement');
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de communication avec le serveur (1) :' + error.message);
            });
    }

    function confirmAndDeleteEvent(eventId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            fetch('/api/event/delete/' + eventId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    alert('Erreur de communication avec le serveur(2):' + error);
                });
        }
    }

    eventTypeInput.addEventListener('change', function () {
        const selectedEventTypeId = this.value;
        if (selectedEventTypeId) {
            loadAttributesByEventType(selectedEventTypeId);
            selectedAttributes = [];
            attributesList.innerHTML = '';
        } else availableAttributesSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type d\'événement</option>';
    });

    needTypeInput.addEventListener('change', function () {
        const selectedNeedTypeId = this.value;
        if (selectedNeedTypeId) loadNeedsByNeedType(selectedNeedTypeId);
        else availableAttributesSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type de besoin</option>';
    });

    const availableAttributesSelect = document.getElementById('availableAttributesSelect');
    function loadAttributesByEventType(eventTypeId) {
        availableAttributesSelect.innerHTML = '<option value="">Chargement...</option>';
        fetch(`/api/event/attributes/eventType/${eventTypeId}`)
            .then(response => response.json())
            .then(data => {
                availableAttributesSelect.innerHTML = '';
                if (data.attributes && data.attributes.length > 0) {
                    data.attributes.forEach(attribute => {
                        const option = document.createElement('option');
                        option.value = attribute.Id;
                        option.textContent = attribute.Name;
                        option.dataset.color = attribute.Color;
                        option.dataset.detail = attribute.Detail;
                        availableAttributesSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "Aucun attribut disponible";
                    availableAttributesSelect.appendChild(option);
                }
            })
            .catch(error => {
                availableAttributesSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    function openCreateModal() {
        modalTitle.textContent = 'Créer un événement';
        submitButton.textContent = 'Créer';
        formMode.value = 'create';
        eventForm.reset();

        selectedAttributes = [];
        loadAttributesByEventType(eventTypeInput.value);

        selectedNeeds = [];
        document.getElementById('needsList').innerHTML = '';

        document.getElementById('maxParticipantsInput').value = 0;
        document.getElementById('audienceInput').value = 'ClubMembersOnly';

        new bootstrap.Modal(eventModal).show();
    }

    function openUpdateModal(event, attributes) {
        modalTitle.textContent = 'Mettre à jour l\'événement';
        submitButton.textContent = 'Mettre à jour';
        formMode.value = 'update';
        eventId.value = event.Id;

        document.getElementById('summaryInput').value = event.Summary;
        document.getElementById('descriptionInput').value = event.Description;
        document.getElementById('locationInput').value = event.Location;
        document.getElementById('eventTypeInput').value = event.IdEventType;
        document.getElementById('maxParticipantsInput').value = event.MaxParticipants || 0;
        document.getElementById('audienceInput').value = event.Audience || 'ClubMembersOnly';

        loadAttributesByEventType(event.IdEventType);

        const startDate = new Date(event.StartTime);
        document.getElementById('dateInput').value = startDate.toISOString().split('T')[0];
        document.getElementById('startTimeInput').value = startDate.toTimeString().split(' ')[0].slice(0, 5);
        const durationHours = event.Duration / (60 * 60);
        document.getElementById('durationInput').value = durationHours.toFixed(1);

        attributesList.innerHTML = '';
        selectedAttributes = [];
        if (attributes) {
            attributes.forEach(attr => {
                const attributeElement = createAttributeElement(attr.AttributeId, attr.Name, attr.Color, attr.Detail);
                attributesList.appendChild(attributeElement);
                selectedAttributes.push({
                    id: String(attr.AttributeId),
                    name: attr.Name,
                    color: attr.Color,
                    detail: attr.Detail
                });
            });
        }

        fetch(`/api/event/needs/${event.Id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.needs) {
                    needsList.innerHTML = '';
                    selectedNeeds = [];
                    data.needs.forEach(need => {
                        const needElement = createNeedElement(need.IdNeed, need.Label, need.Name, need.ParticipantDependent, need.Counter);
                        needsList.appendChild(needElement);
                        selectedNeeds.push({
                            id: need.IdNeed,
                            name: need.Name,
                            label: need.label,
                            participantDependent: need.ParticipantDependent,
                            counter: need.Counter
                        });
                    });
                }
            })
            .catch(error => {
                alert('Erreur:' + error.message);
            });

        new bootstrap.Modal(eventModal).show();
    }

    function createAttributeElement(attributeId, attributeName, attributeColor, attributeDetail) {
        const attributeElement = document.createElement('span');
        attributeElement.className = 'badge me-2 mb-2 position-relative';
        attributeElement.style.backgroundColor = attributeColor;
        attributeElement.style.color = getContrastYIQ(attributeColor);
        attributeElement.innerHTML = `
            ${attributeName}
            <button type="button" class="btn-close position-absolute top-0 end-0" 
                    aria-label="Supprimer" 
                    data-attribute-id="${attributeId}"></button>
        `;
        attributeElement.style.position = 'relative';
        attributeElement.style.paddingRight = '25px';

        attributeElement.setAttribute('title', attributeDetail);
        attributeElement.classList.add('tooltip-trigger');

        const removeBtn = attributeElement.querySelector('.btn-close');
        removeBtn.addEventListener('click', function () {
            const idToRemove = String(this.dataset.attributeId);
            selectedAttributes = selectedAttributes.filter(attr => String(attr.id) !== idToRemove);
            attributeElement.remove();
        });

        return attributeElement;
    }

    document.getElementById('addAttributeBtn').addEventListener('click', function () {
        const select = document.getElementById('availableAttributesSelect');
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const attributeId = String(selectedOption.value);
        const attributeName = selectedOption.text;
        const attributeColor = selectedOption.dataset.color;
        const attributeDetail = selectedOption.dataset.detail;

        if (selectedAttributes.some(attr => String(attr.id) === attributeId)) {
            alert('Cet attribut a déjà été ajouté.');
            return;
        }

        const attributeElement = createAttributeElement(attributeId, attributeName, attributeColor, attributeDetail);
        document.getElementById('attributesList').appendChild(attributeElement);
        selectedAttributes.push({
            id: attributeId,
            name: attributeName,
            color: attributeColor,
            detail: attributeDetail
        });
    });

    function loadNeedsByNeedType(needTypeId) {
        availableNeedsSelect.innerHTML = '<option value="">Chargement...</option>';
        fetch(`/api/needs-by-need-type/${needTypeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    availableNeedsSelect.innerHTML = '';
                    if (data.needs && data.needs.length > 0) {
                        data.needs.forEach(need => {
                            const option = document.createElement('option');
                            option.value = need.Id;
                            option.textContent = `${need.Name}`;
                            option.dataset.needLabel = need.Label;
                            option.dataset.needParticipantDependent = need.ParticipantDependent;
                            availableNeedsSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = "";
                        option.textContent = "Aucun besoin disponible";
                        availableNeedsSelect.appendChild(option);
                    }
                }
            })
            .catch(error => {
                availableNeedsSelect.innerHTML = '<option value="">Erreur de chargement</option>' + error.message;
            });
    }

    function createNeedElement(needId, needLabel, needName, participantDependent, counter = 0) {
        const needElement = document.createElement('div');
        needElement.className = 'border rounded p-2 mb-2';
        needElement.setAttribute('title', needName);

        let quantityHtml = '';
        if (participantDependent == 0) {
            quantityHtml = `
                <div class="input-group input-group-sm mt-1" style="width: 70px;">
                    <input type="number" class="form-control need-counter" value="${counter}" data-need-id="${needId}" maxlength="3">
                </div>
            `;
        }
        needElement.innerHTML = `
            <div class="d-flex flex-row align-items-center bg-light rounded p-2">
                <strong class="me-2">${needLabel}</strong>
                ${quantityHtml}
                <button type="button" class="btn btn-sm btn-danger remove-need ms-2" data-need-id="${needId}">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;

        needElement.querySelector('.remove-need').onclick = () => {
            selectedNeeds = selectedNeeds.filter(need => need.id !== needId);
            needElement.remove();
        };

        needElement.querySelector('.need-counter')?.addEventListener('change', e => {
            const value = parseInt(e.target.value);
            const needIndex = selectedNeeds.findIndex(need => need.id === needId);
            if (needIndex !== -1) selectedNeeds[needIndex].counter = value;
        });

        return needElement;
    }

    document.getElementById('addNeedBtn').addEventListener('click', function () {
        const select = document.getElementById('availableNeedsSelect');
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const needId = selectedOption.value;
        const needName = selectedOption.text;
        const needLabel = selectedOption.dataset.needLabel;
        const needParticipantDependent = selectedOption.dataset.needParticipantDependent;

        if (selectedNeeds.some(need => need.id === needId)) {
            alert('Ce besoin a déjà été ajouté.');
            return;
        }

        const needElement = createNeedElement(needId, needLabel, needName, needParticipantDependent, 1);
        document.getElementById('needsList').appendChild(needElement);
        selectedNeeds.push({
            id: needId,
            name: needName,
            participantDependent: needParticipantDependent,
            counter: 1
        });
    });

    eventForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = {
            id: eventId.value,
            formMode: formMode.value,
            summary: document.getElementById('summaryInput').value,
            description: document.getElementById('descriptionInput').value,
            location: document.getElementById('locationInput').value,
            idEventType: document.getElementById('eventTypeInput').value,
            attributes: selectedAttributes.map(attr => attr.id),
            maxParticipants: parseInt(document.getElementById('maxParticipantsInput').value),
            audience: document.getElementById('audienceInput').value,
            needs: selectedNeeds.map(need => ({
                id: need.id,
                counter: need.counter
            }))
        };

        const dateValue = document.getElementById('dateInput').value;
        const timeValue = document.getElementById('startTimeInput').value;
        formData.startTime = `${dateValue}T${timeValue}:00`;
        formData.duration = parseInt(document.getElementById('durationInput').value * 60 * 60);

        fetch('/api/event/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(eventModal).hide();
                    window.location.reload();
                } else alert('Erreur: ' + (data.message || 'Erreur inconnue'));
            })
            .catch(error => {
                alert('Erreur de communication avec le serveur (3) :' + error.message);
            });
    });

    document.querySelector('[data-bs-target="#eventModal"]').addEventListener('click', function () {
        openCreateModal();
    });

    const emailModal = document.getElementById('emailModal');
    const emailTypeSelect = document.getElementById('emailTypeSelect');
    const recipientsSelect = document.getElementById('recipientsSelect');
    const emailForm = document.getElementById('emailForm');

    let currentEventData = {};

    document.querySelectorAll('.email-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentEventData = {
                eventId: this.dataset.eventId,
                eventTitle: this.dataset.eventTitle,
                participantsCount: parseInt(this.dataset.participantsCount),
                messagesCount: parseInt(this.dataset.webappMessagesCount)
            };

            document.getElementById('emailEventId').value = currentEventData.eventId;
            updateEmailTypeOptions();
            resetEmailForm();
        });
    });

    function updateEmailTypeOptions() {
        const hasMessages = currentEventData.messagesCount > 0;
        emailTypeSelect.innerHTML = '<option value="">Sélectionnez un type</option>';

        if (!hasMessages) {
            emailTypeSelect.innerHTML += '<option value="nouvel-evenement">Nouvel évènement</option>';
        } else {
            emailTypeSelect.innerHTML += '<option value="rappel">Rappel</option>';
            emailTypeSelect.innerHTML += '<option value="annule">Annulé</option>';
            emailTypeSelect.innerHTML += '<option value="modifie">Modifié</option>';
        }
    }

    emailTypeSelect.addEventListener('change', function () {
        updateRecipientsOptions(this.value);
    });

    function updateRecipientsOptions(messageType) {
        recipientsSelect.innerHTML = '<option value="">Sélectionnez les destinataires</option>';

        switch (messageType) {
            case 'nouvel-evenement':
                recipientsSelect.innerHTML += '<option value="all">Tous</option>';
                break;
            case 'rappel':
                recipientsSelect.innerHTML += '<option value="unregistered">Tous les non-inscrits</option>';
                break;
            case 'annule':
            case 'modifie':
                recipientsSelect.innerHTML += '<option value="registered">Tous les inscrits</option>';
                break;
        }
    }

    emailForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const emailData = {
            EventId: currentEventData.eventId,
            Title: emailTypeSelect.options[emailTypeSelect.selectedIndex].text,
            Body: formData.get('message'),
            Recipients: formData.get('recipients')
        };

        fetch('/api/event/sendEmails', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(emailData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Courriel envoyé avec succès !');
                    bootstrap.Modal.getInstance(emailModal).hide();
                    resetEmailForm();
                } else {
                    alert('Erreur lors de l\'envoi du courriel : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de l\'envoi du courriel');
            });
    });

    // Réinitialisation du formulaire email
    function resetEmailForm() {
        emailForm.reset();
        recipientsSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type de message</option>';
    }
});

function getContrastYIQ(hexcolor) {
    hexcolor = hexcolor.replace("#", "");
    const r = parseInt(hexcolor.substr(0, 2), 16);
    const g = parseInt(hexcolor.substr(2, 2), 16);
    const b = parseInt(hexcolor.substr(4, 2), 16);
    const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return (yiq >= 128) ? 'black' : 'white';
}

function togglePreferencesFilter() {
    const checkbox = document.getElementById('filterByPreferences');
    const currentUrl = new URL(window.location.href);

    if (checkbox.checked) {
        currentUrl.searchParams.set('filterByPreferences', '1');
    } else {
        currentUrl.searchParams.delete('filterByPreferences');
    }
    currentUrl.searchParams.set('offset', '0');
    window.location.href = currentUrl.toString();
}