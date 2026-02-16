import ApiClient from '../../Common/js/ApiClient.js';

document.addEventListener('DOMContentLoaded', () => {
    const api = new ApiClient();

    const csvFileInput = document.getElementById('csvFile');
    const headerRowSection = document.getElementById('headerRow');
    const mappingSection = document.getElementById('mappingSection');
    const submitBtn = document.getElementById('submitBtn');
    const headerRowInput = headerRowSection.querySelector('input[name="headerRow"]');
    const emailSelect = document.getElementById('emailColumn');
    let currentHeaders = [];

    submitBtn.disabled = true;

    function validateFile(file) {
        const allowedTypes = ['text/csv', 'application/vnd.ms-excel'];
        const maxSize = 2 * 1024 * 1024; // 2MB

        if (!file) return false;

        if (!file.name.toLowerCase().endsWith('.csv')) {
            alert('Le fichier doit être au format .csv');
            return false;
        }

        if (!allowedTypes.includes(file.type) && file.type !== '') {
            alert('Type de fichier non autorisé.');
            return false;
        }

        if (file.size > maxSize) {
            alert('Fichier trop volumineux (max 2MB)');
            return false;
        }

        return true;
    }

    function updateSubmitState() {
        const emailValue = emailSelect.value;
        const emailSelected = emailValue !== '';
        const emailHeaderValid = emailSelected && currentHeaders[parseInt(emailValue, 10)]?.trim() !== '';

        submitBtn.disabled = !emailHeaderValid;

        const warning = document.getElementById('emailColumnWarning');
        if (warning) {
            warning.style.display = emailSelected && !emailHeaderValid ? 'block' : 'none';
        }
    }

    emailSelect.addEventListener('change', updateSubmitState);

    csvFileInput.addEventListener('change', () => {
        const file = csvFileInput.files[0];

        if (file && validateFile(file)) {
            headerRowSection.style.display = 'block';
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
            submitBtn.disabled = true;
            updateHeaders();
        } else {
            csvFileInput.value = '';
            headerRowSection.style.display = 'none';
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
        }
    });

    headerRowInput.addEventListener('input', () => {
        const value = parseInt(headerRowInput.value, 10);

        if (!isNaN(value) && value > 0 && value < 1000) {
            mappingSection.style.display = 'block';
            submitBtn.style.display = 'block';
            updateHeaders();
        } else {
            mappingSection.style.display = 'none';
            submitBtn.style.display = 'none';
        }
    });

    async function updateHeaders() {
        if (csvFileInput.files.length === 0) return;

        const headerRow = parseInt(headerRowInput.value, 10);
        if (isNaN(headerRow) || headerRow <= 0) return;

        const formData = new FormData();
        formData.append('csvFile', csvFileInput.files[0]);
        formData.append('headerRow', headerRow);

        headerRowInput.disabled = true;

        try {
            const response = await api.postFormData('/api/import/headers', formData);

            if (!response || response.error) {
                throw new Error(response?.error || 'Erreur inconnue');
            }

            const headers = response.data.headers;
            currentHeaders = headers;
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

                if (importSettings?.mapping) {
                    const mappingKey = selectId.replace('Column', '');
                    if (importSettings.mapping[mappingKey] !== undefined) {
                        select.value = importSettings.mapping[mappingKey];
                    }
                }
            });

            mappingSection.style.display = 'block';
            submitBtn.style.display = 'block';
            updateSubmitState();

        } catch (error) {
            console.error(error);
            alert('Erreur lors de la lecture du fichier.');
        } finally {
            headerRowInput.disabled = false;
        }
    }

    document.getElementById('importForm').addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Import en cours...';
    });
});
