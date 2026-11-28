import { initEmojiPicker } from './emojiPicker.js';

document.addEventListener("DOMContentLoaded", function () {
    const useGravatarCheckbox = document.getElementById("useGravatar");
    const emojiSelectorWrapper = document.getElementById("emojiSelectorWrapper");

    // Gravatar toggle: hide the emoji selector when Gravatar is used
    if (useGravatarCheckbox && emojiSelectorWrapper) {
        function toggleEmojiSelector() {
            emojiSelectorWrapper.style.display = useGravatarCheckbox.checked ? "none" : "block";
        }

        toggleEmojiSelector();
        useGravatarCheckbox.addEventListener("change", toggleEmojiSelector);
    }

    // Clicks on the emojis of the predefined list
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

    // Initialize the paste picker
    initEmojiPicker();
});