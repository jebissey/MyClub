let groupsModal;

document.addEventListener('DOMContentLoaded', function () {
    groupsModal = new bootstrap.Modal(document.getElementById('groupsModal'));
});

function showGroups(personId) {
    fetch(`/registration/groups/${personId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('groupsContent').innerHTML = html;
            groupsModal.show();
        });
}

function addToGroup(personId, groupId) {
    fetch(`/api/registration/add/${personId}/${groupId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showGroups(personId);
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        });
}

function removeFromGroup(personId, groupId) {
    fetch(`/api/registration/remove/${personId}/${groupId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showGroups(personId);
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        });
}