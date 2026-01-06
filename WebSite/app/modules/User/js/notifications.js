import ApiClient from "../../Common/js/apiClient.js";

const api = new ApiClient("");

document.addEventListener("DOMContentLoaded", init);

/* ================================
 * Init global
 * ================================ */
async function init() {
    const elements = cacheElements();

    initUI(elements);
    initListeners(elements);

    await initPush(elements);
}

/* ================================
 * DOM cache
 * ================================ */
function cacheElements() {
    const $ = id => document.getElementById(id);

    return {
        noNotification: $("noNotification"),
        alertOptions: $("alertOptions"),

        pushBtn: $("pushNotificationBtn"),
        pushBtnText: $("pushBtnText"),

        newArticle: $("newArticle"),
        newArticlePollWrapper: $("newArticlePollWrapper"),
        newArticlePoll: $("newArticlePoll"),

        updatedArticle: $("updatedArticle"),
        updatedArticlePollWrapper: $("updatedArticlePollWrapper"),
        updatedArticlePoll: $("updatedArticlePoll"),

        newPollVote: $("newPollVote"),
        newPollVoteOptionsWrapper: $("newPollVoteOptionsWrapper"),
        newPollVoteIfVoted: $("newPollVoteIfVoted"),
        newPollVoteIfAuthor: $("newPollVoteIfAuthor"),

        messageOnArticle: $("messageOnArticle"),
        messageOnArticleIfAuthorWrapper: $("messageOnArticleIfAuthorWrapper"),
        messageOnArticleIfAuthor: $("messageOnArticleIfAuthor"),
        messageOnArticleIfPostWrapper: $("messageOnArticleIfPostWrapper"),
        messageOnArticleIfPost: $("messageOnArticleIfPost"),

        messageOnEvent: $("messageOnEvent"),
        messageOnEventOptionsWrapper: $("messageOnEventOptionsWrapper"),
        messageOnEventIfRegistered: $("messageOnEventIfRegistered"),
        messageOnEventIfInPreferences: $("messageOnEventIfInPreferences"),
        messageOnEventIfCreator: $("messageOnEventIfCreator"),

        messageOnGroupSubscribed: $("messageOnGroupSubscribed"),
        groupsSubscribedChildren: document.querySelectorAll(".group-subscribed-child"),

        messageOnGroupJoined: $("messageOnGroupJoined"),
        groupsJoinedChildren: document.querySelectorAll(".group-joined-child")
    };
}

/* ================================
 * UI helpers
 * ================================ */
function setDisplay(el, visible) {
    if (!el) return;
    el.style.display = visible ? "flex" : "none";
}

function toggleWrapper(wrapper, checkbox, children = []) {
    const visible = checkbox.checked;
    setDisplay(wrapper, visible);

    if (!visible) {
        children.forEach(c => c && (c.checked = false));
    }
}

function updateParent(parent, children) {
    const all = [...children].every(c => c.checked);
    const some = [...children].some(c => c.checked);

    parent.checked = all;
    parent.indeterminate = !all && some;
}

function toggleChildren(parent, children) {
    children.forEach(c => (c.checked = parent.checked));
    parent.indeterminate = false;
}

/* ================================
 * UI init
 * ================================ */
function initUI(e) {
    setDisplay(e.alertOptions, !e.noNotification?.checked);
    setDisplay(e.newArticlePollWrapper, e.newArticle?.checked);
    setDisplay(e.updatedArticlePollWrapper, e.updatedArticle?.checked);
    setDisplay(e.newPollVoteOptionsWrapper, e.newPollVote?.checked);
    setDisplay(e.messageOnArticleIfAuthorWrapper, e.messageOnArticle?.checked);
    setDisplay(e.messageOnArticleIfPostWrapper, e.messageOnArticle?.checked);
    setDisplay(e.messageOnEventOptionsWrapper, e.messageOnEvent?.checked);

    updateParent(e.messageOnGroupSubscribed, e.groupsSubscribedChildren);
    updateParent(e.messageOnGroupJoined, e.groupsJoinedChildren);
}

/* ================================
 * Listeners
 * ================================ */
function initListeners(e) {
    e.noNotification?.addEventListener("change", () =>
        setDisplay(e.alertOptions, !e.noNotification.checked)
    );

    e.newArticle?.addEventListener("change", () =>
        toggleWrapper(e.newArticlePollWrapper, e.newArticle, [e.newArticlePoll])
    );

    e.updatedArticle?.addEventListener("change", () =>
        toggleWrapper(e.updatedArticlePollWrapper, e.updatedArticle, [e.updatedArticlePoll])
    );

    e.newPollVote?.addEventListener("change", () =>
        toggleWrapper(e.newPollVoteOptionsWrapper, e.newPollVote, [
            e.newPollVoteIfVoted,
            e.newPollVoteIfAuthor
        ])
    );

    e.messageOnGroupSubscribed?.addEventListener("change", () =>
        toggleChildren(e.messageOnGroupSubscribed, e.groupsSubscribedChildren)
    );

    e.groupsSubscribedChildren.forEach(c =>
        c.addEventListener("change", () =>
            updateParent(e.messageOnGroupSubscribed, e.groupsSubscribedChildren)
        )
    );
}

/* ================================
 * Push Notifications
 * ================================ */
async function initPush(e) {
    if (!e.pushBtn) return;

    await updatePushButton(e);

    e.pushBtn.addEventListener("click", async () => {
        e.pushBtn.disabled = true;
        e.pushBtnText.textContent = "Traitement...";

        try {
            const subscribed = await checkSubscription();
            const success = subscribed
                ? await unsubscribe()
                : await subscribe();

            notify(
                success
                    ? subscribed ? "Désabonnement réussi" : "Abonnement réussi"
                    : "Erreur",
                success ? "success" : "error"
            );
        } catch (err) {
            console.error(err);
            notify(err.message || "Erreur push", "error");
        } finally {
            await updatePushButton(e);
            e.pushBtn.disabled = false;
        }
    });
}

function hasPushSupport() {
    return "serviceWorker" in navigator && "PushManager" in window;
}

async function checkSubscription() {
    if (!hasPushSupport()) return false;

    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) return false;

    return !!await reg.pushManager.getSubscription();
}

async function subscribe() {
    if (!hasPushSupport()) return false;

    const permission = await Notification.requestPermission();
    if (permission !== "granted") return false;

    const reg = await navigator.serviceWorker.register("/service-worker.js");
    const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
    });
    const json = sub.toJSON();
    await api.post("/api/push-subscription", {
        endpoint: json.endpoint,
        auth: json.keys.auth,
        p256dh: json.keys.p256dh
    });
    return true;
}

async function unsubscribe() {
    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.getSubscription();
    if (!sub) return false;

    await sub.unsubscribe();
    await api.post("/api/push-subscription/delete", { endpoint: sub.endpoint });
    return true;
}

async function updatePushButton(e) {
    const subscribed = await checkSubscription();
    e.pushBtnText.textContent = subscribed ? "Se désabonner" : "S'abonner";
    e.pushBtn.classList.toggle("btn-danger", subscribed);
    e.pushBtn.classList.toggle("btn-primary", !subscribed);
}

/* ================================
 * Toast
 * ================================ */
function notify(message, type = "info") {
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
            <div class="toast-body">
                ${message}
            </div>
            <button type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Close"></button>
        </div>
    `;

    container.appendChild(toastEl);

    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();

    toastEl.addEventListener("hidden.bs.toast", () => toastEl.remove());
}

/* ================================
 * Utils
 * ================================ */
function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
}
