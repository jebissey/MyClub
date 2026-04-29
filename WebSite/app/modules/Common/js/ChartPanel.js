export default class ChartPanel {
    #wrapper;
    #spinner;
    #error;

    constructor(wrapperId, spinnerId, errorId) {
        this.#wrapper = document.getElementById(wrapperId);
        this.#spinner = document.getElementById(spinnerId);
        this.#error   = document.getElementById(errorId);
    }

    setLoading(loading) {
        this.#spinner.classList.toggle('d-none', !loading);
        this.#wrapper.classList.toggle('d-none',  loading);
        this.#error.classList.add('d-none');
    }

    showError(message) {
        this.#spinner.classList.add('d-none');
        this.#wrapper.classList.add('d-none');
        this.#error.textContent = message;
        this.#error.classList.remove('d-none');
    }
}