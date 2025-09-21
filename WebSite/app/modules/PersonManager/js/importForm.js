document.addEventListener('DOMContentLoaded', function () {
    const csvFileInput = document.getElementById('csvFile');
    const headerRowSection = document.getElementById('headerRow');
    const mappingSection = document.getElementById('mappingSection');
    const submitBtn = document.getElementById('submitBtn');
    const headerRowInput = headerRowSection.querySelector('input[name="headerRow"]');

    csvFileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            headerRowSection.style.display = 'block';
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
            updateHeaders();
        } else {
            headerRowSection.style.display = 'none';
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
        }
    });

    headerRowInput.addEventListener('input', function () {
        if (this.value.trim() !== '') {
            mappingSection.style.display = 'block';
            submitBtn.style.display = 'block';
            updateHeaders();
        } else {
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
        }
    });

    function updateHeaders() {
        if (!csvFileInput.files.length > 0) {
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
            return;
        }

        const formData = new FormData();
        formData.append('csvFile', csvFileInput.files[0]);
        formData.append('headerRow', headerRowInput.value);

        headerRowInput.disabled = true;

        fetch('/api/import/headers', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                const headers = data.headers;
                const selects = ['emailColumn', 'firstNameColumn', 'lastNameColumn', 'phoneColumn'];

                selects.forEach(selectId => {
                    const select = document.getElementById(selectId);
                    select.innerHTML = '<option value="">Sélectionnez une colonne</option>';

                    headers.forEach((header, index) => {
                        const option = document.createElement('option');
                        option.value = index;
                        option.textContent = `${header} (colonne ${index + 1})`;
                        select.appendChild(option);
                    });

                    if (importSettings && importSettings.mapping) {
                        const mappingKey = selectId.replace('Column', '');
                        if (importSettings.mapping[mappingKey] !== undefined) {
                            select.value = importSettings.mapping[mappingKey];
                        }
                    }
                });

                mappingSection.style.display = 'block';
                submitBtn.style.display = 'block';
            })
            .catch(error => {
                alert('Erreur lors de la lecture du fichier: ' + error.message);
            })
            .finally(() => {
                headerRowInput.disabled = false;
            });
    }
});