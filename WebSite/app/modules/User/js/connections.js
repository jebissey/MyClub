document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalEvents');
    const nameEl = document.getElementById('modalPersonName');
    const listEl = document.getElementById('modalEventList');

    modal.addEventListener('show.bs.modal', event => {
        const link = event.relatedTarget;
        const person = link.getAttribute('data-person');
        const events = link.getAttribute('data-events');

        nameEl.textContent = person;
        listEl.innerHTML = '';

        if (events) {
            events.split(' • ').forEach(ev => {
                const [id, date, title] = ev.split('|').map(s => s?.trim());
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';

                if (id && title) {
                    const a = document.createElement('a');
                    a.href = `/event/${id}`;
                    a.textContent = title;
                    a.className = 'fw-semibold text-decoration-none';

                    const small = document.createElement('small');
                    small.className = 'text-muted';
                    small.textContent = new Date(date).toLocaleDateString('fr-FR', {
                        weekday: 'short',
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    li.appendChild(a);
                    li.appendChild(small);
                } else {
                    li.textContent = ev.trim();
                }

                listEl.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.className = 'list-group-item text-muted fst-italic';
            li.textContent = 'Aucun événement trouvé';
            listEl.appendChild(li);
        }
    });
});