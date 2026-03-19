document.querySelectorAll('.toast').forEach(el => new bootstrap.Toast(el).show());

const languageSelect = document.getElementById('languageSelect');
if (languageSelect) {
    languageSelect.addEventListener('change', function () {
        document.cookie = `user_language=${this.value}; path=/; max-age=31536000`;

        const modalEl = document.getElementById('languageModal');
        if (modalEl) {
            (bootstrap.Modal.getInstance(modalEl) ?? new bootstrap.Modal(modalEl)).hide();
        }

        setTimeout(() => location.reload(), 300);
    });
}