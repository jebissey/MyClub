import { AlertHelper } from '/app/modules/Common/js/AlertHelper.js';

const alert = new AlertHelper();

function copyToClipboard() {
    const emailsString = window.emailsJson.join(' , ');

    navigator.clipboard.writeText(emailsString).then(
        () => alert.append(t('copySuccess'), 'info'),
        (err) => alert.append(t('copyError') + err, 'danger'),
    );
}

copyToClipboard();