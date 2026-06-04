// membership.js  – module
// Handles the "Pay" button and the HelloAsso iFrame widget lifecycle.

const HA_ORIGINS = [
    'https://www.helloasso-sandbox.com',
    'https://www.helloasso.com',
];

// ── DOM refs ──────────────────────────────────────────────────────────────────
const btnPay = document.getElementById('btnPay');
const paySpinner = document.getElementById('paySpinner');
const payAlert = document.getElementById('payAlert');
const widgetWrapper = document.getElementById('ha-widget-wrapper');
const haIframe = document.getElementById('ha-widget');
const btnCancel = document.getElementById('btnCancelWidget');

// ── "Pay" button → show iFrame ────────────────────────────────────────────────
if (btnPay && widgetWrapper) {
    btnPay.addEventListener('click', () => {
        // Hide the button area, reveal the widget
        btnPay.closest('.card-body').querySelector('#payAlert')?.classList.add('d-none');
        btnPay.classList.add('d-none');
        widgetWrapper.classList.remove('d-none');

        // Smooth scroll to the widget
        widgetWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

// ── "Cancel" button → hide iFrame ────────────────────────────────────────────
if (btnCancel && widgetWrapper && btnPay) {
    btnCancel.addEventListener('click', () => {
        widgetWrapper.classList.add('d-none');
        btnPay.classList.remove('d-none');
    });
}

// ── HelloAsso postMessage events ──────────────────────────────────────────────
window.addEventListener('message', (event) => {
    if (!HA_ORIGINS.includes(event.origin)) return;

    const name = event.data?.name;

    // Dynamic iFrame height resize
    if (name === 'wfResize' && haIframe && event.data.height) {
        haIframe.style.height = event.data.height + 'px';
        return;
    }

    // Payment validated
    if (name === 'wfValidated') {
        widgetWrapper?.classList.add('d-none');

        // Show success banner above the card
        showPayAlert('success', window.t('membership.payment_success'));

        // The webhook will update the DB; reload after a short delay so the
        // status badge reflects the new "paid" state without a manual refresh.
        setTimeout(() => location.reload(), 3000);
        return;
    }

    // Payment cancelled or aborted by the user
    if (name === 'wfCanceled' || name === 'wfAborted') {
        widgetWrapper?.classList.add('d-none');
        btnPay?.classList.remove('d-none');
        showPayAlert('warning', window.t('membership.payment_error'));
    }
});

// ── Helper ────────────────────────────────────────────────────────────────────
function showPayAlert(type, message) {
    if (!payAlert) return;
    payAlert.className = `alert alert-${type} mt-3 mb-0`;
    payAlert.textContent = message;
    payAlert.classList.remove('d-none');
}