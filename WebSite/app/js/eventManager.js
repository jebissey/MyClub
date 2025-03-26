
const joursFr = {
    'Mon': 'Lun',
    'Tue': 'Mar',
    'Wed': 'Mer',
    'Thu': 'Jeu',
    'Fri': 'Ven',
    'Sat': 'Sam',
    'Sun': 'Dim'
};

let currentDate = new Date();
currentDate.setHours(0, 0, 0, 0);
let startDate = new Date();

function initEventManager() {
    // Initialiser avec la date du jour
    startDate = new Date(currentDate);

    // Ajouter les événements aux boutons de navigation
    document.querySelectorAll('.move-day, .move-week').forEach(button => {
        button.addEventListener('click', handleNavigationClick);
    });

    // Délégation d'événements pour les boutons dynamiques
    document.addEventListener('click', function (event) {
        // Gestion de l'affichage des événements
        if (event.target.classList.contains('show-events')) {
            showEvents(event.target);
        }

        // Gestion de l'affichage des détails d'un événement
        if (event.target.closest('.event-row')) {
            const eventRow = event.target.closest('.event-row');
            showEventDetail(eventRow);
        }

        // Gestion de l'inscription à un événement
        if (event.target.classList.contains('register-event')) {
            registerForEvent(event.target);
        }

        // Gestion de la désinscription d'un événement
        if (event.target.classList.contains('unregister-event')) {
            unregisterFromEvent(event.target);
        }
    });

    // Initialiser le calendrier au chargement
    updateCalendar();
}

/**
 * Formater une date au format JJ/MM
 */
function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}`;
}

/**
 * Convertir une date en chaîne YYYY-MM-DD
 */
function toISODateString(date) {
    return date.toISOString().split('T')[0];
}

function updateCalendar() {
    const prevWeekBtn = document.querySelector('.move-week[data-days="-7"]');
    const prevDayBtn = document.querySelector('.move-day[data-days="-1"]');

    document.querySelectorAll('.move-week, .move-day').forEach(btn => {
        btn.disabled = false;
    });

    if (startDate <= currentDate) {
        prevWeekBtn.disabled = true;
        prevDayBtn.disabled = true;
        startDate = new Date(currentDate);
    }

    // Générer les dates de la semaine
    const weekdaysHeader = document.getElementById('weekdays-header');
    const eventsRow = document.getElementById('events-row');

    // Vider les rangées
    weekdaysHeader.innerHTML = '';
    eventsRow.innerHTML = '';

    // Créer les cellules pour chaque jour
    for (let i = 0; i < 7; i++) {
        const cellDate = new Date(startDate);
        cellDate.setDate(startDate.getDate() + i);

        const dateStr = toISODateString(cellDate);
        const dayName = cellDate.toLocaleDateString('en-US', { weekday: 'short' });
        const formattedDate = formatDate(cellDate);

        // Créer l'en-tête du jour
        const th = document.createElement('th');
        th.className = 'weekday date-cell';
        th.dataset.date = dateStr;
        th.textContent = `${joursFr[dayName]} ${formattedDate}`;
        weekdaysHeader.appendChild(th);

        // Créer la cellule pour les événements
        const td = document.createElement('td');
        td.className = 'event-cell';
        td.dataset.date = dateStr;
        eventsRow.appendChild(td);

        // Charger les événements pour cette date
        fetchEventCount(dateStr, td);
    }
}

function fetchEventCount(date, cell) {
    const params = new URLSearchParams({
        date: date
    });

    fetch(`/api/event/count?${params.toString()}`)
        .then(response => response.json())
        .then(count => {
            cell.innerHTML = '';
            if (count > 0) {
                const button = document.createElement('button');
                button.className = 'btn btn-primary show-events';
                button.dataset.date = date;
                button.textContent = count;
                cell.appendChild(button);
            }
        })
        .catch(error => console.error('Erreur lors de la récupération du nombre d\'événements:', error));
}

function handleNavigationClick(event) {
    const days = parseInt(event.target.dataset.days);
    startDate.setDate(startDate.getDate() + days);
    updateCalendar();
}

function showEvents(button) {
    document.querySelectorAll('.show-events').forEach(btn => {
        btn.classList.remove('active');
    });

    button.classList.add('active');

    const date = button.dataset.date;
    const params = new URLSearchParams({
        date: date
    });

    fetch(`/event/list?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('events-detail').innerHTML = html;
        })
        .catch(error => console.error('Erreur lors de la récupération des événements:', error));
}

function showEventDetail(eventRow) {
    const eventId = eventRow.dataset.eventId;
    const detailContainer = document.getElementById(`event-${eventId}-detail`);

    if (!detailContainer) {
        const div = document.createElement('div');
        div.id = `event-${eventId}-detail`;
        div.className = 'collapse mt-2';
        eventRow.parentNode.insertBefore(div, eventRow.nextSibling);
    }

    const params = new URLSearchParams({
        eventId: eventId
    });

    fetch(`/event/detail?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
            const detailElement = document.getElementById(`event-${eventId}-detail`);
            detailElement.innerHTML = html;

            if (detailElement.classList.contains('show')) {
                detailElement.classList.remove('show');
            } else {
                detailElement.classList.add('show');
            }
        })
        .catch(error => console.error('Erreur lors de la récupération des détails de l\'événement:', error));
}

function registerForEvent(button) {
    const eventId = button.dataset.eventId;

    const formData = new FormData();
    formData.append('eventId', eventId);

    fetch('/api/event/register', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Échec de l\'inscription');
            }
            return response.text();
        })
        .then(() => {
            const activeButton = document.querySelector('.show-events.active');
            if (activeButton) {
                showEvents(activeButton);
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'inscription:', error);
            alert('Erreur lors de l\'inscription à l\'événement');
        });
}

function unregisterFromEvent(button) {
    const eventId = button.dataset.eventId;

    if (confirm('Êtes-vous sûr de vouloir vous désinscrire de cet événement ?')) {
        const formData = new FormData();
        formData.append('eventId', eventId);

        fetch('/api/event/unregister', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Échec de la désinscription');
                }
                return response.text();
            })
            .then(() => {
                const activeButton = document.querySelector('.show-events.active');
                if (activeButton) {
                    showEvents(activeButton);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la désinscription:', error);
                alert('Erreur lors de la désinscription de l\'événement');
            });
    }
}