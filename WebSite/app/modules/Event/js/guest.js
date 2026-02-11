document.getElementById('invitationForm').addEventListener('submit', function (e) {
    const email = document.getElementById('email');
    const event = document.getElementById('event');

    let isValid = true;
    if (!email.value || !email.checkValidity()) {
        email.classList.add('is-invalid');
        isValid = false;
    } else {
        email.classList.remove('is-invalid');
    }

    if (!event.value) {
        event.classList.add('is-invalid');
        isValid = false;
    } else {
        event.classList.remove('is-invalid');
    }

    if (!isValid) {
        e.preventDefault();
    }
});
