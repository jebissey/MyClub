document.addEventListener('DOMContentLoaded', function () {
        document.getElementById("togglePassword").addEventListener("click", function () {
        const passwordInput = document.getElementById("sendEmailPassword");
        const icon = this.querySelector("i");

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    });
});