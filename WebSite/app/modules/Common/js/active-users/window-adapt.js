import { ACTIVE_USERS_MAX } from './constants.js';

const ACTIVE_WINDOW_MINUTES_MIN = 1;
const ACTIVE_WINDOW_MINUTES_MAX = 60 * 8;

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

    /**
     * @param {Array<{minutesAgo: number}>} users
     */
    update(users) {
        const count = users.length;

        if (count > ACTIVE_USERS_MAX) {
            const sorted = [...users].sort((a, b) => a.minutesAgo - b.minutesAgo);
            const pivot = sorted[ACTIVE_USERS_MAX - 1].minutesAgo;

            this.#current = Math.min(
                ACTIVE_WINDOW_MINUTES_MAX,
                Math.max(ACTIVE_WINDOW_MINUTES_MIN, Math.ceil(pivot))
            );
            this.#low = Math.max(ACTIVE_WINDOW_MINUTES_MIN, this.#current - 1);
            this.#high = Math.min(ACTIVE_WINDOW_MINUTES_MAX, this.#current + 2);

        } else {
            this.#low = this.#current;
            const next = Math.round((this.#low + this.#high) / 2);

            if (next === this.#current) {
                this.#low = Math.max(ACTIVE_WINDOW_MINUTES_MIN, this.#current - this.#current / 4);
                this.#high = Math.min(ACTIVE_WINDOW_MINUTES_MAX, this.#current + this.#current / 4);
            } else {
                this.#current = next;
            }
        }

        return this.#current;
    }
}