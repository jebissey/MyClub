export function notify(message, type = "info") {
    const container = document.getElementById("toastContainer");
    if (!container || !window.bootstrap) return;

    const colorMap = {
        success: "success",
        error: "danger",
        warning: "warning",
        info: "primary"
    };

    const toastEl = document.createElement("div");
    toastEl.className = `toast align-items-center text-bg-${colorMap[type] || "primary"} border-0`;
    toastEl.setAttribute("role", "alert");
    toastEl.setAttribute("aria-live", "assertive");
    toastEl.setAttribute("aria-atomic", "true");

    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>
    `;

    container.appendChild(toastEl);

    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();

    toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
}
