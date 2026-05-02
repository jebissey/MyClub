import ApiClient from '../ApiClient.js';
import { getCurrentPushEndpoint } from '../../../User/js/push-subscription.js';

const api = new ApiClient('');

export function initMessageForm() {
    const form                = document.getElementById('new-message-form');
    const messageText         = document.getElementById('message-text');
    const articleId           = document.getElementById('article-id').value;
    const eventId             = document.getElementById('event-id').value;
    const groupId             = document.getElementById('group-id').value;
    const imageInput          = document.getElementById('message-image');
    const imagePreviewWrapper = document.getElementById('image-preview-wrapper');
    const imagePreview        = document.getElementById('image-preview');
    const removeImageBtn      = document.getElementById('remove-image-btn');

    // ── Prévisualisation ──────────────────────────────────────────────────────
    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            imagePreviewWrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });

    removeImageBtn.addEventListener('click', () => {
        imageInput.value = '';
        imagePreview.src = '';
        imagePreviewWrapper.classList.add('d-none');
    });

    // ── Lecture base64 ────────────────────────────────────────────────────────
    function readAsBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload  = () => resolve(reader.result); // "data:image/jpeg;base64,..."
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    // ── Envoi ─────────────────────────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const text  = messageText.value.trim();
        const file  = imageInput.files[0] ?? null;

        if (!text && !file) return;

        const senderEndpoint = await getCurrentPushEndpoint();
        const imageBase64    = file ? await readAsBase64(file) : null;

        const data = await api.post('/api/message/add', {
            articleId,
            eventId,
            groupId,
            text,
            senderEndpoint: senderEndpoint ?? '',
            imageBase64,           // "data:image/jpeg;base64,..." ou null
            imageName: file?.name ?? null,
        });

        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || "Impossible d'envoyer le message"));
        }
    });
}