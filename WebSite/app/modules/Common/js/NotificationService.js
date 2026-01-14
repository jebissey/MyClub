export default class NotificationService {

    static show(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';

        const html = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top:20px; right:20px; z-index:9999; min-width:300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);

        setTimeout(() => {
            document.querySelector(`.alert.${alertClass}`)?.remove();
        }, 3000);
    }
}
