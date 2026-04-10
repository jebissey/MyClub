const ACTIVE_WINDOW_MINUTES_MIN = 1;
const ACTIVE_WINDOW_MINUTES_MAX = 60 * 24;
const ACTIVE_USERS_MAX = 10;

export class WindowAdapter {
    #current;
    #low;
    #high;

    constructor(initialMinutes = 15) {
        this.#current = initialMinutes;
        this.#low = ACTIVE_WINDOW_MINUTES_MIN;
        this.#high = ACTIVE_WINDOW_MINUTES_MAX;
    }

    get minutes() {
        return this.#current;
    }

    update(userCount) {
        if (userCount > ACTIVE_USERS_MAX) {
            this.#high = this.#current;
        } else {
            this.#low = this.#current;
        }

        const next = Math.round((this.#low + this.#high) / 2);

        if (next === this.#current) {
            this.#low = Math.max(ACTIVE_WINDOW_MINUTES_MIN, this.#current - this.#current / 4);
            this.#high = Math.min(ACTIVE_WINDOW_MINUTES_MAX, this.#current + this.#current / 4);
        } else {
            this.#current = next;
        }

        return this.#current;
    }
}