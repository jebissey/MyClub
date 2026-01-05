import ApiClient from "../../Common/js/apiClient.js";

const api = new ApiClient("");

document.addEventListener("DOMContentLoaded", async () => {

    const $ = (id) => document.getElementById(id);
    const elements = {
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

    // ----------------------------
    // Helpers UI
    // ----------------------------
    const setDisplay = (el, visible) => el && (el.style.display = visible ? "flex" : "none");

    const toggleWrapper = (wrapper, checkbox, children = []) => {
        const visible = checkbox.checked;
        setDisplay(wrapper, visible);
        if (!visible) children.forEach(c => c.checked = false);
    };

    const updateParent = (parent, children) => {
        const all = [...children].every(c => c.checked);
        const some = [...children].some(c => c.checked);
        parent.checked = all;
        parent.indeterminate = !all && some;
    };

    const toggleChildren = (parent, children) => {
        children.forEach(c => c.checked = parent.checked);
        parent.indeterminate = false;
    };

    // ----------------------------
    // Toast notifications
    // ----------------------------
    const notify = (message, type = "info") => {
        const div = document.createElement("div");
        div.textContent = message;
        div.setAttribute("role", "alert");
        div.style.cssText = `
            position:fixed;top:20px;right:20px;
            padding:12px 18px;
            background:${type === "success" ? "#4CAF50" : type === "error" ? "#f44336" : "#ff9800"};
            color:#fff;border-radius:4px;
            box-shadow:0 2px 5px rgba(0,0,0,.2);
            animation:slideIn .3s ease-out;
            z-index:10000;
        `;
        document.body.appendChild(div);
        setTimeout(() => {
            div.style.animation = "slideOut .3s ease-out";
            setTimeout(() => div.remove(), 300);
        }, 3000);
    };

    // ----------------------------
    // Push notifications
    // ----------------------------
    const hasPushSupport = () =>
        "serviceWorker" in navigator && "PushManager" in window;

    const checkSubscription = async () => {
        if (!hasPushSupport()) return false;
        const reg = await navigator.serviceWorker.getRegistration();
        if (!reg) return false;
        return !!await reg.pushManager.getSubscription();
    };

    const sendSubscriptionToServer = async (subscription) => {
        const json = subscription.toJSON();
        return api.post("/api/push-subscription", {
            endpoint: json.endpoint,
            auth: json.keys.auth,
            p256dh: json.keys.p256dh
        });
    };

    const subscribe = async () => {
        if (!hasPushSupport()) return false;

        const permission = await Notification.requestPermission();
        if (permission !== "granted") {
            notify("Notifications refusées", "warning");
            return false;
        }

        let reg = await navigator.serviceWorker.getRegistration();
        if (!reg) reg = await navigator.serviceWorker.register("/service-worker.js");

        const subscription = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });

        const response = await sendSubscriptionToServer(subscription);
        if (response.success === false) throw new Error(response.error);

        return true;
    };

    const unsubscribe = async () => {
        if (!hasPushSupport()) return false;

        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (!sub) return false;

        await sub.unsubscribe();
        await api.post("/api/push-subscription/delete", { endpoint: sub.endpoint });

        return true;
    };

    const updatePushButton = async () => {
        if (!elements.pushBtn) return;
        const subscribed = await checkSubscription();
        elements.pushBtnText.textContent = subscribed ? "Se désabonner" : "S'abonner";
        elements.pushBtn.classList.toggle("btn-danger", subscribed);
        elements.pushBtn.classList.toggle("btn-primary", !subscribed);
    };

    // ----------------------------
    // Push button event
    // ----------------------------
    elements.pushBtn?.addEventListener("click", async () => {
        elements.pushBtn.disabled = true;
        elements.pushBtnText.textContent = "Traitement...";

        try {
            const subscribed = await checkSubscription();
            const success = subscribed ? await unsubscribe() : await subscribe();
            notify(
                success
                    ? subscribed ? "Désabonnement réussi" : "Abonnement réussi"
                    : "Erreur",
                success ? "success" : "error"
            );
            await updatePushButton();
        } catch (e) {
            console.error(e);
            notify(e.message, "error");
        } finally {
            elements.pushBtn.disabled = false;
        }
    });

    // ----------------------------
    // Initialisation UI
    // ----------------------------
    setDisplay(elements.alertOptions, !elements.noNotification.checked);
    setDisplay(elements.newArticlePollWrapper, elements.newArticle.checked);
    setDisplay(elements.updatedArticlePollWrapper, elements.updatedArticle.checked);
    setDisplay(elements.newPollVoteOptionsWrapper, elements.newPollVote.checked);
    setDisplay(elements.messageOnArticleIfAuthorWrapper, elements.messageOnArticle.checked);
    setDisplay(elements.messageOnArticleIfPostWrapper, elements.messageOnArticle.checked);
    setDisplay(elements.messageOnEventOptionsWrapper, elements.messageOnEvent.checked);

    updateParent(elements.messageOnGroupSubscribed, elements.groupsSubscribedChildren);
    updateParent(elements.messageOnGroupJoined, elements.groupsJoinedChildren);

    await updatePushButton();

    // ----------------------------
    // Listeners UI
    // ----------------------------
    elements.noNotification?.addEventListener("change", () =>
        setDisplay(elements.alertOptions, !elements.noNotification.checked)
    );

    elements.newArticle?.addEventListener("change", () =>
        toggleWrapper(elements.newArticlePollWrapper, elements.newArticle, [elements.newArticlePoll])
    );

    elements.updatedArticle?.addEventListener("change", () =>
        toggleWrapper(elements.updatedArticlePollWrapper, elements.updatedArticle, [elements.updatedArticlePoll])
    );

    elements.newPollVote?.addEventListener("change", () =>
        toggleWrapper(elements.newPollVoteOptionsWrapper, elements.newPollVote, [
            elements.newPollVoteIfVoted,
            elements.newPollVoteIfAuthor
        ])
    );

    elements.messageOnGroupSubscribed?.addEventListener("change", () =>
        toggleChildren(elements.messageOnGroupSubscribed, elements.groupsSubscribedChildren)
    );

    elements.groupsSubscribedChildren.forEach(c =>
        c.addEventListener("change", () =>
            updateParent(elements.messageOnGroupSubscribed, elements.groupsSubscribedChildren)
        )
    );
});

// ----------------------------
// Utils
// ----------------------------
function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
}
