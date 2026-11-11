import PushPreferences from "./push-preferences.js";
import { notify } from "../../Common/js/toast.js";
import {
    isSubscribed,
    subscribePush,
    unsubscribePush,
    getCurrentPushEndpoint
} from "./push-subscription.js";

document.addEventListener("DOMContentLoaded", async () => {
    const pushPrefs = new PushPreferences();
    pushPrefs.init();

    // =============================
    // Push notification button
    // =============================
    const btn = document.getElementById("pushNotificationBtn");
    const btnText = document.getElementById("pushBtnText");

    if (!btn) return;

    // Refresh the button label and style
    async function refreshButton() {
        const subscribed = await isSubscribed();
        btnText.textContent = subscribed ? I18N.unsubscribe : I18N.subscribe;
        btn.classList.toggle("btn-danger", subscribed);
        btn.classList.toggle("btn-primary", !subscribed);
    }

    await refreshButton();

    btn.addEventListener("click", async () => {
        btn.disabled = true;

        try {
            if (!("Notification" in window)) {
                throw new Error(I18N.unsupported);
            }

            const subscribed = await isSubscribed();

            if (subscribed) {
                await unsubscribePush();
                notify(I18N.unsubscribed, "success");
            } else {
                const permission = await Notification.requestPermission();

                if (permission !== "granted") {
                    throw new Error(I18N.permissionDenied);
                }

                await subscribePush(VAPID_PUBLIC_KEY);
                notify(I18N.subscribed, "success");
            }
        } catch (e) {
            console.error(e);
            notify(e.message || I18N.error, "error");
        } finally {
            await refreshButton();
            btn.disabled = false;
        }
    });
});