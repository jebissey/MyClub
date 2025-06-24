document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const forgotPasswordBtn = document.getElementById('forgotPasswordBtn');

    const signInModal = new bootstrap.Modal(document.getElementById('loginModal'));
    signInModal.show();

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showError(input) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    }

    function hideError(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }

    emailInput.addEventListener('input', function () {
        if (isValidEmail(this.value)) {
            hideError(this);
            forgotPasswordBtn.disabled = false;
            forgotPasswordBtn.style.color = '#0d6efd';
            forgotPasswordBtn.style.cursor = 'pointer';
        } else {
            showError(this);
            forgotPasswordBtn.disabled = true;
            forgotPasswordBtn.style.color = '#6c757d';
            forgotPasswordBtn.style.cursor = 'not-allowed';
        }
    });

    passwordInput.addEventListener('input', function () {
        if (this.value.length < 6) {
            showError(this);
        } else {
            hideError(this);
        }
    });

    forgotPasswordBtn.addEventListener('click', function () {
        if (!this.disabled) {
            const encodedEmail = encodeURIComponent(emailInput.value);
            window.location.href = "/user/forgotPassword/" + encodedEmail;
        }
    });

    form.addEventListener('submit', function (event) {
        let isValid = true;

        if (!isValidEmail(emailInput.value)) {
            showError(emailInput);
            isValid = false;
        }

        if (passwordInput.value.length < 6) {
            showError(passwordInput);
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    document.getElementById("togglePassword").addEventListener("click", function () {
        const passwordInput = document.getElementById("password");
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
