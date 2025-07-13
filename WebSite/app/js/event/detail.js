function createGoogleCalendarUrl(event) {

    function formatDateForGoogle(startTime, endTime) {
        function formatDate(date) {
            return date.toISOString().replace(/-|:|\.\d+/g, '');
        }
        return formatDate(startTime) + '/' + formatDate(endTime);
    }

    var base = 'https://calendar.google.com/calendar/render';
    var text = 'text=' + encodeURIComponent(event.Summary);
    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);
    var dates = 'dates=' + encodeURIComponent(formatDateForGoogle(startTime, endTime));
    var detailsText = event.Description + '\n\nPlus d\'infos : ' + window.location.href;
    var details = 'details=' + encodeURIComponent(detailsText);
    var location = 'location=' + encodeURIComponent(event.Location);

    return base + '?' + 'action=TEMPLATE' + '&' + text + '&' + dates + '&' + details + '&' + location;
}

function createOutlookCalendarUrl(event) {
    var base = 'https://outlook.live.com/calendar/0/deeplink/compose';
    var subject = 'subject=' + encodeURIComponent(event.Summary);
    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);
    var startdt = 'startdt=' + encodeURIComponent(startTime.toISOString());
    var enddt = 'enddt=' + encodeURIComponent(endTime.toISOString());
    var location = 'location=' + encodeURIComponent(event.Location);
    var bodyText = event.Description + '\n\nPlus d\'infos : ' + window.location.href;
    var body = 'body=' + encodeURIComponent(bodyText);

    return base + '?' + subject + '&' + startdt + '&' + enddt + '&' + body + '&' + location;
}

function downloadICalFile(event) {
    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);

    function formatDateICS(date) {
        return date.toISOString().replace(/-|:|\.\d+/g, '').replace(/(\.\d+)?Z$/, 'Z');
    }

    var icsContent =
        'BEGIN:VCALENDAR\n' +
        'VERSION:2.0\n' +
        'PRODID:-//YourAppName//EN\n' +
        'BEGIN:VEVENT\n' +
        'UID:' + Date.now() + '@yourapp.com\n' +
        'DTSTAMP:' + formatDateICS(new Date()) + '\n' +
        'DTSTART:' + formatDateICS(startTime) + '\n' +
        'DTEND:' + formatDateICS(endTime) + '\n' +
        'SUMMARY:' + event.Summary + '\n' +
        'DESCRIPTION:' + event.Description.replace(/\n/g, '\\n') + '\\n\\nPlus d\'infos : ' + window.location.href + '\n' +
        'LOCATION:' + event.Location + '\n' +
        'END:VEVENT\n' +
        'END:VCALENDAR';

    var blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    var url = URL.createObjectURL(blob);

    var a = document.createElement('a');
    a.href = url;
    a.download = event.Summary.replace(/\s+/g, '_') + '.ics';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', function () {
    const supplyInputs = document.querySelectorAll('.user-supply-input');
    const updateButtons = document.querySelectorAll('.update-supply-btn');

    supplyInputs.forEach(input => {
        input.addEventListener('input', function () {
            const needId = this.dataset.needId;
            const originalValue = parseInt(this.dataset.originalValue);
            const currentValue = parseInt(this.value) || 0;
            const updateBtn = document.querySelector(`.update-supply-btn[data-need-id="${needId}"]`);

            if (currentValue !== originalValue) {
                updateBtn.style.display = 'block';
            } else {
                updateBtn.style.display = 'none';
            }
        });

        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                const needId = this.dataset.needId;
                const updateBtn = document.querySelector(`.update-supply-btn[data-need-id="${needId}"]`);
                if (updateBtn.style.display !== 'none') {
                    updateBtn.click();
                }
            }
        });
    });

    updateButtons.forEach(button => {
        button.addEventListener('click', function () {
            const eventId = this.dataset.eventId;
            const needId = this.dataset.needId;
            const input = document.querySelector(`.user-supply-input[data-need-id="${needId}"]`);
            const supply = parseInt(input.value) || 0;

            updateSupply(eventId, needId, supply, input, this);
        });
    });

    function updateSupply(eventId, needId, supply, inputElement, buttonElement) {
        buttonElement.disabled = true;
        const originalText = buttonElement.textContent;
        buttonElement.textContent = 'En cours...';

        fetch('/api/event/updateSupply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                eventId: eventId,
                needId: needId,
                supply: supply
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    const needContainer = document.querySelector(`[data-need-id="${needId}"]`);
                    if (data.updatedNeed && needContainer) {
                        // Mettre à jour la quantité fournie
                        const providedSpan = needContainer.querySelector('.provided-quantity');
                        if (providedSpan) {
                            providedSpan.textContent = data.updatedNeed.providedQuantity;
                        }

                        // Mettre à jour la barre de progression
                        const progressBar = needContainer.querySelector('.progress-bar');
                        const progressPercentage = needContainer.querySelector('.progress-percentage');
                        if (progressBar && progressPercentage) {
                            const percentage = Math.round(data.updatedNeed.percentage);
                            progressBar.style.width = percentage + '%';
                            progressBar.setAttribute('aria-valuenow', percentage);
                            progressPercentage.textContent = percentage + '%';

                            progressBar.className = 'progress-bar progress-bar-custom';
                            if (percentage >= 100) {
                                progressBar.classList.add('bg-success');
                            } else if (percentage > 0) {
                                progressBar.classList.add('bg-warning');
                            } else {
                                progressBar.classList.add('bg-danger');
                            }
                        }

                        // Mettre à jour le style du conteneur
                        needContainer.className = needContainer.className.replace(/need-\w+/, '');
                        if (data.updatedNeed.percentage >= 100) {
                            needContainer.classList.add('need-fulfilled');
                        } else if (data.updatedNeed.percentage > 0) {
                            needContainer.classList.add('need-partial');
                        } else {
                            needContainer.classList.add('need-missing');
                        }
                    }

                    // Mettre à jour la valeur originale
                    inputElement.dataset.originalValue = supply;
                    buttonElement.style.display = 'none';

                    // Notification de succès
                    showNotification('Apport mis à jour avec succès', 'success');
                } else {
                    showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur ' + error, 'error');
            })
            .finally(() => {
                buttonElement.disabled = false;
                buttonElement.textContent = originalText;
            });
    }

    function showNotification(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

        document.body.insertAdjacentHTML('beforeend', alertHtml);

        // Auto-supprimer après 3 secondes
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            const lastAlert = alerts[alerts.length - 1];
            if (lastAlert && lastAlert.classList.contains(alertClass)) {
                lastAlert.remove();
            }
        }, 3000);
    }
});