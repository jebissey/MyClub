import ApiClient from '../../Common/js/ApiClient.js';

const t = (key, params = {}) =>
    Object.entries(params).reduce(
        (text, [name, value]) => text.replaceAll(`{${name}}`, String(value)),
        window.i18n?.[key] ?? key
    );

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
        const maxSizeMb = 2;
        const maxSize = maxSizeMb * 1024 * 1024;

        if (!file) return false;

        if (!file.name.toLowerCase().endsWith('.csv')) {
            alert(t('import.js.invalid_extension'));
            return false;
        }

        if (!allowedTypes.includes(file.type) && file.type !== '') {
            alert(t('import.js.invalid_type'));
            return false;
        }

        if (file.size > maxSize) {
            alert(t('import.js.file_too_large', { max: `${maxSizeMb} MB` }));
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

    function fillSelect(select, headers, savedValue) {
        select.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = t('import.form.select_column');
        select.appendChild(placeholder);

        headers.forEach((header, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = t('import.js.column_option', { header, index: index + 1 });
            select.appendChild(option);
        });

        if (savedValue !== undefined && savedValue !== null) {
            select.value = savedValue;
        }
    }

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
                throw new Error(response?.error || t('import.js.unknown_error'));
            }

            const headers = response.data.headers;
            currentHeaders = headers;

            ['emailColumn', 'firstNameColumn', 'lastNameColumn', 'phoneColumn'].forEach(selectId => {
                fillSelect(
                    document.getElementById(selectId),
                    headers,
                    importSettings?.mapping?.[selectId.replace('Column', '')]
                );
            });

            document.querySelectorAll('select[data-custom-key]').forEach(select => {
                fillSelect(select, headers, importSettings?.mapping?.custom?.[select.dataset.customKey]);
            });

            mappingSection.style.display = 'block';
            submitBtn.style.display = 'block';
            updateSubmitState();

        } catch (error) {
            console.error(error);
            alert(t('import.js.read_error'));
        } finally {
            headerRowInput.disabled = false;
        }
    }

    document.getElementById('importForm').addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = t('import.form.submitting');
    });
});
