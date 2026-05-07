/**
 * Holds and mutates the exercises array.
 * Single source of truth — no DOM knowledge.
 */
export class ExerciseState {
    /** @type {Array<object>} */
    #exercises = [];

    /** @param {Array<object>} initial */
    constructor(initial = []) {
        this.#exercises = structuredClone(initial);
    }

    /** Returns a deep copy of the current exercises array. */
    getAll() {
        return structuredClone(this.#exercises);
    }

    get length() {
        return this.#exercises.length;
    }

    /**
     * Appends a new exercise entry.
     * @param {object} exercise
     */
    add(exercise) {
        this.#exercises.push(structuredClone(exercise));
    }

    /**
     * Removes the exercise at the given index.
     * @param {number} index
     */
    remove(index) {
        this.#exercises.splice(index, 1);
    }

    /**
     * Swaps exercise at index with the one above it.
     * @param {number} index
     */
    moveUp(index) {
        if (index <= 0) return;
        [this.#exercises[index - 1], this.#exercises[index]] =
            [this.#exercises[index], this.#exercises[index - 1]];
    }

    /**
     * Swaps exercise at index with the one below it.
     * @param {number} index
     */
    moveDown(index) {
        if (index >= this.#exercises.length - 1) return;
        [this.#exercises[index], this.#exercises[index + 1]] =
            [this.#exercises[index + 1], this.#exercises[index]];
    }

    /**
     * Updates a single scalar property inside a group (prep|exercise).
     * Does not trigger a re-render — the caller decides.
     * @param {number} index
     * @param {'prep'|'exercise'} group
     * @param {string} key
     * @param {*} value
     */
    updateProp(index, group, key, value) {
        if (!this.#exercises[index][group]) {
            this.#exercises[index][group] = {};
        }
        this.#exercises[index][group][key] = value;
    }

    /**
     * Assigns a media file path to an exercise.
     * For sounds, also stores duration and pre-fills prep.duration when empty.
     * @param {number}      index
     * @param {'image'|'sound'} field
     * @param {string}      path
     * @param {number|null} soundDuration  Duration in seconds (sound only)
     */
    setMedia(index, field, path, soundDuration = null) {
        if (!this.#exercises[index].prep) {
            this.#exercises[index].prep = {};
        }
        this.#exercises[index].prep[field] = path;

        if (field === 'sound' && soundDuration !== null) {
            this.#exercises[index].prep.soundDuration = soundDuration;
            // Auto-fill prep duration only when it has not been set yet
            if (!this.#exercises[index].prep.duration) {
                this.#exercises[index].prep.duration = soundDuration;
            }
        }
    }

    /**
     * Clears a media file assignment from an exercise.
     * @param {number}          index
     * @param {'image'|'sound'} field
     */
    clearMedia(index, field) {
        if (!this.#exercises[index].prep) return;
        this.#exercises[index].prep[field] = '';
        if (field === 'sound') {
            this.#exercises[index].prep.soundDuration = 0;
        }
    }
}