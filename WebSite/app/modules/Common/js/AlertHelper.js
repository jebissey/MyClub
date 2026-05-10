export class AlertHelper {
    #placeholder;

    constructor(placeholderId = 'liveAlertPlaceholder') {
        this.#placeholder = document.getElementById(placeholderId);
    }

    append(message, type) {
        if (!this.#placeholder) return;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="alert alert-${type} alert-dismissible" role="alert">
                <div>${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        this.#placeholder.append(wrapper);
    }
}