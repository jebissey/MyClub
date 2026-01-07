import PushPreferences from "./push-preferences.js";
import { notify } from "../../Common/js/toast.js";
import {
    isSubscribed,
    subscribePush,
    unsubscribePush
} from "./push-subscription.js";

document.addEventListener("DOMContentLoaded", async () => {
    const pushPrefs = new PushPreferences();
    pushPrefs.init();

    // =============================
    // Push notification button
    // =============================
    const btn = document.getElementById("pushNotificationBtn");
    const btnText = document.getElementById("pushBtnText");

    if (btn) {
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
                const subscribed = await isSubscribed();

                if (subscribed) {
                    await unsubscribePush();
                    notify("Désabonnement réussi", "success");
                } else {
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
    }
});

