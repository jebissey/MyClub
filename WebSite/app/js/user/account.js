document.addEventListener("DOMContentLoaded", function () {
    const emojiLinks = document.querySelectorAll("#emojiList a");
    const userAvatar = document.getElementById("userAvatar");
    const avatarInput = document.getElementById("avatar");
    const emaiValue = document.getElementById("email");
    const useGravatarCheckbox = document.getElementById("useGravatar");
    const emojiDropdown = document.querySelector(".dropdown");

    function toggleEmojiSelector() {
        if (useGravatarCheckbox.checked) {
            emojiDropdown.style.display = "none";
        } else {
            emojiDropdown.style.display = "block";
        }
    }

    toggleEmojiSelector();
    useGravatarCheckbox.addEventListener("change", toggleEmojiSelector);

    emojiLinks.forEach(link => {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            const emojiImg = this.querySelector("img").src;
            userAvatar.src = emojiImg;
            avatarInput.value = emojiImg;
        });
    });
});