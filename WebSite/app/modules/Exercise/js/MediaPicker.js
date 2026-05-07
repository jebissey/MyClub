/**
 * Manages the image and sound media-picker modals.
 * Uploads files via POST /api/media/upload and
 * resolves audio duration from the browser before notifying the caller.
 */
export class MediaPicker {
    /** @type {{ index: number, field: 'image'|'sound' }|null} */
    #currentTarget = null;

    /** @type {bootstrap.Modal} */
    #modalImage;

    /** @type {bootstrap.Modal} */
    #modalSound;

    /** @type {function(index: number, field: string, path: string, duration: number|null): void} */
    #onPick;

    /** @type {function(message: string): void} */
    #onError;

    /**
     * @param {function} onPick   Called with (index, field, path, soundDuration|null) on success
     * @param {function} onError  Called with an error message string on failure
     */
    constructor(onPick, onError) {
        this.#onPick  = onPick;
        this.#onError = onError;

        // getOrCreateInstance is the Bootstrap 5 recommended pattern:
        // avoids creating a conflicting second instance when Bootstrap
        // has already auto-initialised the element via data attributes.
        this.#modalImage = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalPickImage')
        );
        this.#modalSound = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalPickSound')
        );

        this.#wireUploadHandlers();
    }

    /**
     * Opens the appropriate modal and registers which exercise/field is targeted.
     * @param {number}              index
     * @param {'image'|'sound'}     field
     */
    open(index, field) {
        this.#currentTarget = { index, field };

        if (field === 'image') {
            // Reset so re-selecting the same file still fires the change event
            document.getElementById('uploadImage').value = '';
            this.#modalImage.show();
        } else {
            document.getElementById('uploadSound').value = '';
            this.#modalSound.show();
        }
    }

    // ── Private ────────────────────────────────────────────────────────────

    /** Binds change listeners on both hidden file inputs. */
    #wireUploadHandlers() {
        document.getElementById('uploadImage')
            .addEventListener('change', e => this.#handleUpload(e.target, 'image'));

        document.getElementById('uploadSound')
            .addEventListener('change', e => this.#handleUpload(e.target, 'sound'));
    }

    /**
     * Uploads the chosen file, then closes the modal and notifies the parent.
     *
     * Critical ordering: modal.hide() is called BEFORE onPick() / render().
     * Calling render() first clears and rebuilds the exercise list DOM, which
     * can interfere with Bootstrap's internal modal state and prevent hide()
     * from working correctly.
     *
     * @param {HTMLInputElement}    inputEl
     * @param {'image'|'sound'}     type
     */
    async #handleUpload(inputEl, type) {
        // Guard: open() must have been called before a file can be selected
        if (!this.#currentTarget) return;

        const file = inputEl.files[0];
        if (!file) return;

        // Capture index synchronously before any await — avoids stale-closure
        // issues if the user interacts with another card while uploading.
        const { index } = this.#currentTarget;
        const modal = type === 'image' ? this.#modalImage : this.#modalSound;

        const fd = new FormData();
        fd.append('file', file);

        try {
            const response = await fetch('/api/media/upload', {
                method: 'POST',
                body: fd,
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const json = await response.json();

            if (!json.success) {
                throw new Error(json.message ?? 'Upload failed');
            }

            const path = json.data?.path;
            if (!path) {
                throw new Error(
                    `Server response is missing the path field. ` +
                    `Received: ${JSON.stringify(json.data)}`
                );
            }

            // ── Resolve audio duration before touching the DOM ─────────────
            let soundDuration = null;
            if (type === 'sound') {
                soundDuration = await this.#resolveAudioDuration(path);
            }

            // ── Close modal FIRST, then update state and re-render ──────────
            // Reversing this order causes render() to rebuild the exercise list
            // DOM before Bootstrap can complete the hide transition, which
            // silently prevents the modal from closing.
            modal.hide();
            this.#onPick(index, type, path, soundDuration);

        } catch (e) {
            this.#onError(e.message);
        }
    }

    /**
     * Resolves the duration (in seconds) of a remote audio file.
     * Resolves to 0 on error so the caller always receives a number.
     * @param  {string}           path  Server-relative path (no leading slash)
     * @returns {Promise<number>}
     */
    #resolveAudioDuration(path) {
        return new Promise(resolve => {
            const audio = new Audio('/' + path);
            audio.addEventListener('loadedmetadata', () =>
                resolve(Math.round(audio.duration)));
            audio.addEventListener('error', () => resolve(0));
        });
    }
}