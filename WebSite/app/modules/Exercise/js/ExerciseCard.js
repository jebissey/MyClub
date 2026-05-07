/**
 * Builds the DOM card for a single exercise entry.
 * Communicates outward exclusively through bubbling CustomEvents —
 * no direct state mutations here.
 *
 * Events dispatched (all bubble):
 *   exercise:delete      { index }
 *   exercise:moveUp      { index }
 *   exercise:moveDown    { index }
 *   exercise:change      { index, group, key, value }
 *   exercise:pickMedia   { index, field }
 *   exercise:clearMedia  { index, field }
 */
export class ExerciseCard {
    #template;

    constructor() {
        this.#template = document.getElementById('tplExercise');
        if (!this.#template) {
            throw new Error('ExerciseCard: #tplExercise template not found in DOM');
        }
    }

    /**
     * Clones the template, populates it, and attaches all event listeners.
     * @param  {object} exercise
     * @param  {number} index
     * @returns {DocumentFragment}
     */
    build(exercise, index) {
        const fragment = this.#template.content.cloneNode(true);
        const card     = fragment.querySelector('.exercise-card');

        card.dataset.index = index;
        card.querySelector('.idx').textContent = index + 1;

        this.#fillFields(card, exercise);
        this.#fillPreviews(card, exercise);
        this.#wireEvents(card, index);

        return fragment;
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /** Populates all form fields from the exercise data object. */
    #fillFields(card, exercise) {
        card.querySelector('.ex-prep-title').value    = exercise.prep?.title    ?? '';
        card.querySelector('.ex-prep-text').value     = exercise.prep?.text     ?? '';
        card.querySelector('.ex-prep-duration').value = exercise.prep?.duration ?? 0;
        card.querySelector('.ex-prep-image').value    = exercise.prep?.image    ?? '';
        card.querySelector('.ex-prep-sound').value    = exercise.prep?.sound    ?? '';
        card.querySelector('.ex-ex-duration').value   = exercise.exercise?.duration ?? 60;
    }

    /** Renders the image thumbnail and sound duration hint when present. */
    #fillPreviews(card, exercise) {
        const image = exercise.prep?.image ?? '';
        if (image) {
            card.querySelector('.ex-image-preview').innerHTML =
                `<img src="/${image}"
                      style="max-height:80px;border-radius:4px"
                      alt="preview">`;
        }

        const soundDuration = exercise.prep?.soundDuration ?? 0;
        if (soundDuration) {
            card.querySelector('.ex-sound-duration').textContent =
                `Duration: ${soundDuration}s`;
        }
    }

    /**
     * Attaches all click / input listeners and dispatches typed CustomEvents.
     * @param {HTMLElement} card
     * @param {number}      index
     */
    #wireEvents(card, index) {
        /** Shorthand to fire a bubbling CustomEvent from this card. */
        const emit = (name, detail) =>
            card.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));

        // ── Structural actions ─────────────────────────────────────────────
        card.querySelector('.btn-del-exercise')
            .addEventListener('click', () => emit('exercise:delete', { index }));

        card.querySelector('.btn-move-up')
            .addEventListener('click', () => emit('exercise:moveUp', { index }));

        card.querySelector('.btn-move-down')
            .addEventListener('click', () => emit('exercise:moveDown', { index }));

        // ── Media pickers ──────────────────────────────────────────────────
        card.querySelector('.btn-pick-image')
            .addEventListener('click', () => emit('exercise:pickMedia', { index, field: 'image' }));

        card.querySelector('.btn-clear-image')
            .addEventListener('click', () => emit('exercise:clearMedia', { index, field: 'image' }));

        card.querySelector('.btn-pick-sound')
            .addEventListener('click', () => emit('exercise:pickMedia', { index, field: 'sound' }));

        card.querySelector('.btn-clear-sound')
            .addEventListener('click', () => emit('exercise:clearMedia', { index, field: 'sound' }));

        // ── Live field sync ────────────────────────────────────────────────
        const liveSync = (selector, group, key, cast = String) => {
            card.querySelector(selector).addEventListener('input', e =>
                emit('exercise:change', { index, group, key, value: cast(e.target.value) }));
        };

        liveSync('.ex-prep-title',    'prep',     'title');
        liveSync('.ex-prep-text',     'prep',     'text');
        liveSync('.ex-prep-duration', 'prep',     'duration', Number);
        liveSync('.ex-ex-duration',   'exercise', 'duration', Number);
    }
}