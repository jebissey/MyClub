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

    let selectedNeeds = [];
    const needsList = document.getElementById('needsList');
    const availableNeedsSelect = document.getElementById('availableNeedsSelect');

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('tr');
            const eventId = row.getAttribute('onclick').match(/\/events\/(\d+)/)[1];
            fetchEventDetails(eventId);
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('tr');
            const eventId = row.getAttribute('onclick').match(/\/events\/(\d+)/)[1];
            confirmAndDeleteEvent(eventId);
        });
    });

    document.querySelectorAll(".duplicate-btn").forEach(btn => {
        btn.addEventListener("click", function (e) {
            e.stopPropagation();
            const eventId = this.dataset.id;
            if (confirm("Dupliquer cet événement à aujourd'hui 23:59 ?")) {
                fetch(`/api/event/duplicate/${eventId}`, { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert("Erreur : " + data.message);
                        }
                    });
            }
        });
    });

    function fetchEventDetails(eventId) {
        fetch(`/api/event/${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.event && data.attributes) {
                    openUpdateModal(data.event, data.attributes);
                } else {
                    alert('Erreur lors de la récupération des détails de l\'événement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de communication avec le serveur (1) :' + error.message);
            });
    }

    function confirmAndDeleteEvent(eventId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
            fetch('/api/event/delete/' + eventId, {
                method: 'DELETE',
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
        } else {
            availableAttributesSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type d\'événement</option>';
        }
    });

    eventTypeInput.addEventListener('change', function () {
        const selectedEventTypeId = this.value;
        if (selectedEventTypeId) {
            loadNeedsByEventType(selectedEventTypeId);
            selectedNeeds = [];
            needsList.innerHTML = '';
        } else {
            availableAttributesSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type de besoin</option>';
        }
    });

    const availableAttributesSelect = document.getElementById('availableAttributesSelect');
    function loadAttributesByEventType(eventTypeId) {
        availableAttributesSelect.innerHTML = '<option value="">Chargement...</option>';
        fetch(`/api/attributes-by-event-type/${eventTypeId}`)
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
        document.getElementById('attributesList').innerHTML = '';
        availableAttributesSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type d\'événement</option>';

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

        fetch(`/api/event-needs/${event.Id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.needs) {
                    needsList.innerHTML = '';
                    selectedNeeds = [];
                    data.needs.forEach(need => {
                        const needElement = createNeedElement(need.IdNeed, need.Name, need.Quantity, need.Counter);
                        needsList.appendChild(needElement);
                        selectedNeeds.push({
                            id: need.IdNeed,
                            name: need.Name,
                            quantity: need.ParticipantDependent,
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

    function loadNeedsByEventType(eventTypeId) {
        availableNeedsSelect.innerHTML = '<option value="">Chargement...</option>';
        fetch(`/api/needs-by-event-type/${eventTypeId}`)
            .then(response => response.json())
            .then(data => {
                availableNeedsSelect.innerHTML = '';
                if (data.needs && data.needs.length > 0) {
                    data.needs.forEach(need => {
                        const option = document.createElement('option');
                        option.value = need.Id;
                        option.textContent = `${need.Name} (${need.TypeName})`;
                        option.dataset.quantity = need.ParticipantDependent;
                        availableNeedsSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "Aucun besoin disponible";
                    availableNeedsSelect.appendChild(option);
                }
            })
            .catch(error => {
                availableNeedsSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    function createNeedElement(needId, needName, needQuantity, counter = 0) {
        const needElement = document.createElement('div');
        needElement.className = 'border rounded p-2 mb-2';
        needElement.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>${needName}</strong>
                <div class="input-group input-group-sm mt-1" style="max-width: 150px;">
                <span class="input-group-text">Quantité:</span>
                <input type="number" class="form-control need-counter" value="${counter}" min="1" max="${needQuantity}" 
                        data-need-id="${needId}" data-max="${needQuantity}">
                <span class="input-group-text">/ ${needQuantity}</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-need" data-need-id="${needId}">
                <i class="bi bi-x"></i>
            </button>
            </div>
        `;

        const removeBtn = needElement.querySelector('.remove-need');
        removeBtn.addEventListener('click', function () {
            const idToRemove = this.dataset.needId;
            selectedNeeds = selectedNeeds.filter(need => need.id !== idToRemove);
            needElement.remove();
        });

        const counterInput = needElement.querySelector('.need-counter');
        counterInput.addEventListener('change', function () {
            const idToUpdate = this.dataset.needId;
            const maxValue = parseInt(this.dataset.max);
            const value = parseInt(this.value);

            if (value > maxValue) {
                this.value = maxValue;
            } else if (value < 1) {
                this.value = 1;
            }

            const needIndex = selectedNeeds.findIndex(need => need.id === idToUpdate);
            if (needIndex !== -1) {
                selectedNeeds[needIndex].counter = parseInt(this.value);
            }
        });

        return needElement;
    }

    document.getElementById('addNeedBtn').addEventListener('click', function () {
        const select = document.getElementById('availableNeedsSelect');
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const needId = selectedOption.value;
        const needName = selectedOption.text;
        const needQuantity = selectedOption.dataset.quantity;

        if (selectedNeeds.some(need => need.id === needId)) {
            alert('Ce besoin a déjà été ajouté.');
            return;
        }

        const needElement = createNeedElement(needId, needName, needQuantity, 1);
        document.getElementById('needsList').appendChild(needElement);
        selectedNeeds.push({
            id: needId,
            name: needName,
            quantity: needQuantity,
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
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur inconnue'));
                }
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

    // Gestion de l'ouverture de la modale email
    document.querySelectorAll('.email-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentEventData = {
                eventId: this.dataset.eventId,
                eventTitle: this.dataset.eventTitle,
                participantsCount: parseInt(this.dataset.participantsCount),
                messagesCount: parseInt(this.dataset.messagesCount)
            };

            document.getElementById('emailEventId').value = currentEventData.eventId;
            updateEmailTypeOptions();
            resetEmailForm();
        });
    });

    // Mise à jour des options de type de message selon l'état de l'événement
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

    // Gestion du changement de type de message
    emailTypeSelect.addEventListener('change', function () {
        updateRecipientsOptions(this.value);
    });

    // Mise à jour des options de destinataires selon le type de message
    function updateRecipientsOptions(messageType) {
        recipientsSelect.innerHTML = '<option value="">Sélectionnez les destinataires</option>';

        switch (messageType) {
            case 'nouvel-evenement':
                recipientsSelect.innerHTML += '<option value="tous">Tous</option>';
                break;
            case 'rappel':
                recipientsSelect.innerHTML += '<option value="non-inscrits">Tous les non-inscrits</option>';
                break;
            case 'annule':
            case 'modifie':
                recipientsSelect.innerHTML += '<option value="inscrits">Tous les inscrits</option>';
                break;
        }
    }

    // Gestion de l'envoi du formulaire email
    emailForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const emailData = {
            EventId: currentEventData.eventId,
            Title: emailTypeSelect.options[emailTypeSelect.selectedIndex].text,
            Body: formData.get('message'),
            Recipients: formData.get('recipients')
        };

        fetch('/api/email/send', {
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