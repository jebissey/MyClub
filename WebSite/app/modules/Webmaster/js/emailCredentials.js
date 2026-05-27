class EmailCredentials {

    constructor() {
        this.select = document.getElementById('sendMethod');

        this.sections = {
            mail: { alert: 'alert-mail', fields: null },
            smtp: { alert: 'alert-smtp', fields: 'fields-smtp' },
            mailjet: { alert: 'alert-mailjet', fields: 'fields-mailjet' },
            brevo: { alert: 'alert-brevo', fields: 'fields-brevo' },
        };

        this.smtpFields = [
            'smtpAccount',
            'smtpPassword',
            'smtpFrom',
            'smtpHost',
            'smtpPort',
            'smtpEncryption'
        ];

        this.mailjetFields = [
            'mailjetApiKey',
            'mailjetApiSecret',
            'mailjetSender'
        ];

        this.brevoFields = [
            'brevoApiKey',
            'brevoSender'
        ];

        this.limitPlaceholders = {
            mail: { daily: '0', monthly: '0' },
            smtp: { daily: '0', monthly: '0' },
            mailjet: { daily: '200', monthly: '6000' },
            brevo: { daily: '300', monthly: '9000' },
        };
    }

    init() {

        if (!this.select) return;

        this.select.addEventListener('change', (e) => {
            this.applyMethod(e.target.value);
        });

        this.applyMethod(this.select.value);

        this.setupPasswordToggle('togglePassword', 'smtpPassword');
        this.setupPasswordToggle('toggleSecret', 'mailjetApiSecret');
        this.setupPasswordToggle('toggleBrevoKey', 'brevoApiKey');
    }

    applyMethod(method) {

        Object.entries(this.sections).forEach(([key, section]) => {

            const alertEl = document.getElementById(section.alert);
            if (alertEl) {
                alertEl.classList.toggle('d-none', key !== method);
            }

            if (section.fields) {
                const fieldsEl = document.getElementById(section.fields);
                if (fieldsEl) {
                    fieldsEl.classList.toggle('d-none', key !== method);
                }
            }
        });

        this.setRequired(this.smtpFields, method === 'smtp');
        this.setRequired(this.mailjetFields, method === 'mailjet');
        this.setRequired(this.brevoFields, method === 'brevo');

        const placeholders = this.limitPlaceholders[method] ?? { daily: '0', monthly: '0' };
        const dailyEl = document.getElementById('dailyLimit');
        const monthlyEl = document.getElementById('monthlyLimit');
        if (dailyEl) dailyEl.placeholder = placeholders.daily;
        if (monthlyEl) monthlyEl.placeholder = placeholders.monthly;
    }

    setRequired(fields, required) {
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.required = required;
        });
    }

    setupPasswordToggle(btnId, inputId) {

        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);

        if (!btn || !input) return;

        btn.addEventListener('click', () => {

            const icon = btn.querySelector('i');

            input.type = input.type === 'password'
                ? 'text'
                : 'password';

            if (icon) {
                icon.className = input.type === 'password'
                    ? 'bi bi-eye'
                    : 'bi bi-eye-slash';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new EmailCredentials().init();
});