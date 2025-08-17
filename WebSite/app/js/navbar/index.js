document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editModal');
    const modal = new bootstrap.Modal(editModal);

    const routeSelect = document.getElementById('itemRoute');
    const idParamContainer = document.getElementById('idParamContainer');
    const idParam = document.getElementById('idParam');

    function checkRouteParams() {
        const selectedRoute = routeSelect.value;

        if (selectedRoute.includes('@id')) {
            idParamContainer.style.display = 'block';
            idParam.required = true;

            const fullRoute = selectedRoute;
            const match = fullRoute.match(/(.+\/)(\d+)$/);

            if (match && match[2]) {
                idParam.value = match[2];
            }
        } else {
            idParamContainer.style.display = 'none';
            idParam.required = false;
            idParam.value = '';
        }
    }
    routeSelect.addEventListener('change', checkRouteParams);

    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.closest('tr').dataset.id;

            fetch(`/api/navBar/getItem/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('itemId').value = data.message.Id;
                        document.getElementById('itemName').value = data.message.Name;
                    } else alert("Erreur : " + data.message);

                    let routeBase = data.message.Route;
                    let idValue = '';
                    const match = data.message.Route.match(/(.+\/)(\d+)$/);
                    if (match) {
                        routeBase = match[1] + '@id';
                        idValue = match[2];
                    }

                    for (let i = 0; i < routeSelect.options.length; i++) {
                        if (routeSelect.options[i].value === routeBase) {
                            routeSelect.selectedIndex = i;
                            break;
                        }
                    }

                    idParam.value = idValue;
                    checkRouteParams();

                    const groupSelect = document.getElementById('itemGroup');
                    const groupId = data.message.IdGroup ? data.message.IdGroup.toString() : '';
                    for (let i = 0; i < groupSelect.options.length; i++) {
                        if (groupSelect.options[i].value === groupId) {
                            groupSelect.selectedIndex = i;
                            break;
                        }
                    }

                    document.getElementById('forMembers').checked = data.message.ForMembers == 1;
                    document.getElementById('forAnonymous').checked = data.message.ForAnonymous == 1;

                    modal.show();
                })
                .catch(error => {
                    alert('Failed to load navigation item data:' + error.message);
                });
        });
    });

    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.closest('tr').dataset.id;

            if (confirm('Êtes-vous sûr de vouloir supprimer cet élément de navigation ?')) {
                fetch(`/api/navBar/deleteItem/${id}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) this.closest('tr').remove();
                        else alert(result.message || 'Échec de la suppression de l\'élément de navigation.');
                    })
                    .catch(error => {
                        alert('Une erreur s\'est produite lors de la suppression de l\'élément de navigation.');
                    });
            }
        });
    });

    document.getElementById('saveChanges').addEventListener('click', function () {
        const name = document.getElementById('itemName').value.trim();
        let route = document.getElementById('itemRoute').value;
        if (!name || !route) {
            alert('Name and Route are required fields.');
            return;
        }
        if (route.includes('@id')) {
            const paramValue = idParam.value.trim();
            if (!paramValue) {
                alert('ID Parameter is required for this route.');
                return;
            }
            route = route.replace('@id', paramValue);
        }

        const data = {
            id: document.getElementById('itemId').value,
            name: name,
            route: route,
            idGroup: document.getElementById('itemGroup').value || null,
            forMembers: document.getElementById('forMembers').checked ? 1 : 0,
            forAnonymous: document.getElementById('forAnonymous').checked ? 1 : 0
        };

        fetch('/api/navBar/saveItem', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    modal.hide();
                    location.reload();
                } else {
                    alert(result.message || 'Failed to save navigation item.');
                }
            })
            .catch(error => {
                alert('An error occurred while saving the navigation item.');
            });
    });

    document.getElementById('addNew').addEventListener('click', function () {
        document.getElementById('itemId').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('itemRoute').selectedIndex = 0;
        document.getElementById('itemGroup').selectedIndex = 0;
        document.getElementById('forMembers').value = '';
        document.getElementById('forAnonymous').value = '';
        idParam.value = '';
        idParamContainer.style.display = 'none';
        modal.show();
    });

    const navList = document.getElementById('navList');

    if (navList) {
        let draggedRow = null;

        navList.querySelectorAll('tr').forEach(row => {
            row.draggable = true;

            row.addEventListener('dragstart', function (e) {
                draggedRow = this;
                this.classList.add('table-active'); // petite classe visuelle optionnelle
                e.dataTransfer.effectAllowed = 'move';
            });

            row.addEventListener('dragend', function () {
                this.classList.remove('table-active');
                draggedRow = null;
            });

            row.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            row.addEventListener('drop', function (e) {
                e.preventDefault();
                if (draggedRow && draggedRow !== this) {
                    const rows = Array.from(navList.querySelectorAll('tr'));
                    const draggedIndex = rows.indexOf(draggedRow);
                    const targetIndex = rows.indexOf(this);

                    if (draggedIndex < targetIndex) {
                        navList.insertBefore(draggedRow, this.nextSibling);
                    } else {
                        navList.insertBefore(draggedRow, this);
                    }

                    updatePositions();
                }
            });
        });

        function updatePositions() {
            const positions = {};
            navList.querySelectorAll('tr').forEach((row, index) => {
                positions[row.dataset.id] = index + 1;
            });

            fetch('/api/navBar/updatePositions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ positions: positions })
            })
                .then(response => response.json())
                .then(result => {
                    if (!result.success) {
                        alert('Erreur lors de la mise à jour des positions : ' + result.message);
                    }
                })
                .catch(error => {
                    alert('Erreur lors de la mise à jour des positions : ' + error.message);
                });
        }
    }
});