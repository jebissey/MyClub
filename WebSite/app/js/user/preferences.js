document.addEventListener('DOMContentLoaded', function () {
    const noAlertsCheckbox = document.getElementById('noAlerts');
    const alertOptionsContainer = document.getElementById('alertOptions');
    const eventTypeCheckboxes = document.querySelectorAll('.event-type-checkbox');

    function handleNoAlertsInitial() {
        if (noAlertsCheckbox.checked) {
            alertOptionsContainer.style.display = 'none';
        }
    }

    handleNoAlertsInitial();

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

            if (this.checked) {
                availabilityOptions.style.display = 'block';
            } else {
                availabilityOptions.style.display = 'none';
            }
        });
    });

    const newArticleCheckbox = document.getElementById('eventTypeNewArticle');
    const pollOnlyOptions = document.getElementById('availabilityOptionsNewArticle');

    newArticleCheckbox.addEventListener('change', function () {
        if (this.checked) {
            noAlertsCheckbox.checked = false;
            pollOnlyOptions.style.display = 'block';
        } else {
            pollOnlyOptions.style.display = 'none';
        }
    });
});