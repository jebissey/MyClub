document.addEventListener('DOMContentLoaded', function () {
    const noAlertsCheckbox = document.getElementById('noAlerts');
    const alertOptionsContainer = document.getElementById('alertOptions');
    const eventTypeCheckboxes = document.querySelectorAll('.event-type-checkbox');
    
    const eventInfoLinks = document.querySelectorAll('.event-info-link');
    const eventDetailCards = document.querySelectorAll('.event-detail-card');
    eventInfoLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const eventId = this.getAttribute('data-event-id');
            const targetCard = document.getElementById('eventDetailCard' + eventId);
            eventDetailCards.forEach(card => {
                card.style.display = 'none';
            });
            if (targetCard) {
                targetCard.style.display = 'block';
            }
        });
    });

    function handleNoAlertsInitial() {
        if (noAlertsCheckbox.checked) {
            alertOptionsContainer.style.display = 'none';
        }
    }

    function initializeAvailabilityOptions() {
        eventTypeCheckboxes.forEach(checkbox => {
            const eventTypeId = checkbox.id.replace('eventType', '');
            const availabilityOptions = document.getElementById('availabilityOptions' + eventTypeId);

            if (checkbox.checked && availabilityOptions) {
                availabilityOptions.style.display = 'block';
            }
        });
    }

    handleNoAlertsInitial();
    initializeAvailabilityOptions();

    noAlertsCheckbox.addEventListener('change', function () {
        if (this.checked) {
            alertOptionsContainer.style.display = 'none';

            eventTypeCheckboxes.forEach(checkbox => {
                checkbox.checked = false;

                const eventTypeId = checkbox.id.replace('eventType', '');
                const availabilityOptions = document.getElementById('availabilityOptions' + eventTypeId);
                if (availabilityOptions) {
                    availabilityOptions.style.display = 'none';
                }
            });
        } else {
            alertOptionsContainer.style.display = 'block';
        }
    });

    eventTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (this.checked) {
                noAlertsCheckbox.checked = false;
            }

            const eventTypeId = this.id.replace('eventType', '');
            const availabilityOptions = document.getElementById('availabilityOptions' + eventTypeId);

            if (availabilityOptions) {
                availabilityOptions.style.display = this.checked ? 'block' : 'none';
            }
        });
    });

    const newArticleCheckbox = document.getElementById('eventTypeNewArticle');
    const pollOnlyOptions = document.getElementById('availabilityOptionsNewArticle');

    if (newArticleCheckbox && pollOnlyOptions) {
        if (newArticleCheckbox.checked) {
            pollOnlyOptions.style.display = 'block';
        }

        newArticleCheckbox.addEventListener('change', function () {
            if (this.checked) {
                noAlertsCheckbox.checked = false;
                pollOnlyOptions.style.display = 'block';
            } else {
                pollOnlyOptions.style.display = 'none';
            }
        });
    }
});