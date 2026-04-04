import { initEmojiPicker } from './emojiPicker.js';

document.addEventListener("DOMContentLoaded", function () {
    const useGravatarCheckbox = document.getElementById("useGravatar");
    const emojiSelectorWrapper = document.getElementById("emojiSelectorWrapper");

    // Gravatar toggle
    if (useGravatarCheckbox && emojiSelectorWrapper) {
        function toggleEmojiSelector() {
            emojiSelectorWrapper.style.display = useGravatarCheckbox.checked ? "none" : "block";
        }

        toggleEmojiSelector();
        useGravatarCheckbox.addEventListener("change", toggleEmojiSelector);
    }

    // Clics sur les emojis de la liste prédéfinie
    const avatarInput = document.getElementById("avatar");
    const userAvatar = document.getElementById("userAvatar");

    document.querySelectorAll("#emojiList a").forEach(link => {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            const emoji = this.getAttribute("data-emoji");
            if (userAvatar) userAvatar.textContent = emoji;
            if (avatarInput) avatarInput.value = emoji;
            const selectedEmoji = document.getElementById("selectedEmoji");
            if (selectedEmoji) selectedEmoji.textContent = emoji + " ";
        });
    });

    // Initialisation du picker collage
    initEmojiPicker();
});