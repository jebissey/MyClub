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

    // Rafraîchir le texte et le style du bouton
    async function refreshButton() {
        const subscribed = await isSubscribed();
        btnText.textContent = subscribed ? "Se désabonner" : "S'abonner";
        btn.classList.toggle("btn-danger", subscribed);
        btn.classList.toggle("btn-primary", !subscribed);
    }

    await refreshButton();

    btn.addEventListener("click", async () => {
        btn.disabled = true;

        try {
            if (!("Notification" in window)) {
                throw new Error("Notifications non supportées");
            }

            const subscribed = await isSubscribed();

            if (subscribed) {
                await unsubscribePush();
                notify("Désabonnement réussi", "success");
            } else {
                const permission = await Notification.requestPermission();

                if (permission !== "granted") {
                    throw new Error("Autorisation refusée");
                }

                await subscribePush(VAPID_PUBLIC_KEY);
                notify("Abonnement réussi", "success");
            }
        } catch (e) {
            console.error(e);
            notify(e.message || "Erreur push", "error");
        } finally {
            await refreshButton();
            btn.disabled = false;
        }
    });
});