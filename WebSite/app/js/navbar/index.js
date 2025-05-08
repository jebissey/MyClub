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
            const id = this.closest('li').dataset.id;

            fetch(`/api/navBar/getItem/${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('itemId').value = data.Id;
                    document.getElementById('itemName').value = data.Name;

                    let routeBase = data.Route;
                    let idValue = '';
                    const match = data.Route.match(/(.+\/)(\d+)$/);
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
                    const groupId = data.IdGroup ? data.IdGroup.toString() : '';
                    for (let i = 0; i < groupSelect.options.length; i++) {
                        if (groupSelect.options[i].value === groupId) {
                            groupSelect.selectedIndex = i;
                            break;
                        }
                    }

                    document.getElementById('forMembers').checked = data.ForMembers == 1;
                    document.getElementById('forAnonymous').checked = data.ForAnonymous == 1;

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
            const id = this.closest('li').dataset.id;

            if (confirm('Êtes-vous sûr de vouloir supprimer cet élément de navigation ?')) {
                fetch(`/api/navBar/deleteItem/${id}`, {
                    method: 'DELETE'
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            this.closest('li').remove();
                        } else {
                            alert(result.message || 'Échec de la suppression de l\'élément de navigation.');
                        }
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
    let draggedItem = null;
    Array.from(navList.children).forEach(item => {
        initDraggable(item);
    });
    function initDraggable(element) {
        element.setAttribute('draggable', 'true');

        element.addEventListener('dragstart', function () {
            draggedItem = this;
            setTimeout(() => {
                this.classList.add('dragging');
            }, 0);
        });

        element.addEventListener('dragend', function () {
            draggedItem = null;
            this.classList.remove('dragging');

            const positions = {};
            Array.from(navList.children).forEach((item, index) => {
                positions[item.dataset.id] = index + 1; // Position starts from 1
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
                        alert('Error updating positions:' + result.message);
                    }
                })
                .catch(error => {
                    alert('Error updating positions:' + error.message);
                });
        });

        element.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!draggedItem || draggedItem === this) return;

            const rect = this.getBoundingClientRect();
            const midpoint = (rect.top + rect.bottom) / 2;

            if (e.clientY < midpoint) {
                navList.insertBefore(draggedItem, this);
            } else {
                navList.insertBefore(draggedItem, this.nextSibling);
            }
        });
    }
});