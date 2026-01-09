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
            const subscribed = await isSubscribed();

            if (subscribed) {
                // Désabonnement
                await unsubscribePush();
                notify("Désabonnement réussi", "success");
            } else {
                // 🔥 FIX EDGE : Appeler requestPermission() AVANT tout await
                // Edge exige que ce soit appelé directement dans le handler de clic
                const permissionPromise = Notification.requestPermission();
                
                // Maintenant on peut attendre le résultat
                const permission = await permissionPromise;
                
                if (permission !== "granted") {
                    throw new Error("Autorisation refusée");
                }

                // Abonnement push
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