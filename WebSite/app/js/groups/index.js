function loadGroupUsers(groupId) {
    let userRow = document.getElementById(`users-group-${groupId}`);
    let userList = document.getElementById(`user-list-${groupId}`);

    if (userRow.classList.contains("d-none")) {
        userList.innerHTML = "Chargement...";
        fetch(`/api/personsInGroup/${groupId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    userList.innerHTML = "<p>Aucun utilisateur dans ce groupe.</p>";
                } else {
                    userList.innerHTML = "<ul class='list-unstyled'>" + data.map(user =>
                        `<li>${user.FirstName} ${user.LastName} (${user.Email})</li>`
                    ).join('') + "</ul>";
                }
            })
            .catch(error => {
                userList.innerHTML = "<p class='text-danger'>Erreur lors du chargement.</p>";
            });

        userRow.classList.remove("d-none");
    } else {
        userRow.classList.add("d-none");
    }
}

function confirmDelete(id) {
    document.getElementById('deleteForm').action = '/group/delete/' + id;
    const deleteModal = document.getElementById('deleteModal');
    const modal = new bootstrap.Modal(deleteModal);
    modal.show();
}