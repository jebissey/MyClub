import { Toast }         from '../../Common/js/Toast.js';
import { ExerciseState } from './ExerciseState.js';
import { ExerciseCard }  from './ExerciseCard.js';
import { MediaPicker }   from './MediaPicker.js';

// ── Singletons ─────────────────────────────────────────────────────────────
const toast       = new Toast();
const state       = new ExerciseState(window.exercises ?? []);
const cardBuilder = new ExerciseCard();
const listEl      = document.getElementById('exerciseList');

const picker = new MediaPicker(
    // onPick: update state then re-render
    (index, field, path, soundDuration) => {
        state.setMedia(index, field, path, soundDuration);
        render();
    },
    // onError: show toast
    (message) => toast.show(message, 'danger'),
);

// ── Rendering ──────────────────────────────────────────────────────────────

/** Rebuilds the entire list from state. */
function render() {
    listEl.innerHTML = '';
    state.getAll().forEach((ex, i) => listEl.appendChild(cardBuilder.build(ex, i)));
}

// ── Event delegation — all card events bubble up to the list ───────────────

listEl.addEventListener('exercise:delete', ({ detail: { index } }) => {
    state.remove(index);
    render();
});

listEl.addEventListener('exercise:moveUp', ({ detail: { index } }) => {
    state.moveUp(index);
    render();
});

listEl.addEventListener('exercise:moveDown', ({ detail: { index } }) => {
    state.moveDown(index);
    render();
});

listEl.addEventListener('exercise:clearMedia', ({ detail: { index, field } }) => {
    state.clearMedia(index, field);
    render();
});

listEl.addEventListener('exercise:pickMedia', ({ detail: { index, field } }) => {
    picker.open(index, field);
});

/**
 * Live field changes: update state without re-rendering
 * to avoid disrupting the focused input.
 */
listEl.addEventListener('exercise:change', ({ detail: { index, group, key, value } }) => {
    state.updateProp(index, group, key, value);
});

// ── Toolbar buttons ────────────────────────────────────────────────────────

document.getElementById('btnAddExercise').addEventListener('click', () => {
    state.add({
        prep:     { title: '', text: '', image: '', sound: '', duration: 0 },
        exercise: { duration: 60 },
    });
    render();
    listEl.querySelector('.exercise-card:last-child')
        ?.scrollIntoView({ behavior: 'smooth' });
});

document.getElementById('btnSaveAll').addEventListener('click', async () => {
    const title = document.getElementById('setTitle').value.trim();

    try {
        const response = await fetch(`/api/exercise/save/${window.articleId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ exercises: state.getAll(), title }),
        });
        const json = await response.json();

        if (!json.success) throw new Error(json.message ?? window.t('msg.error'));

        toast.show(window.t('msg.saved'), 'success');

    } catch (e) {
        toast.show(e.message, 'danger');
    }
});

// ── Boot ───────────────────────────────────────────────────────────────────
render();