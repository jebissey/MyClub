import ApiClient from "../../Common/js/apiClient.js";

const api = new ApiClient("");

export function hasPushSupport() {
    return "serviceWorker" in navigator && "PushManager" in window;
}

export async function isSubscribed() {
    if (!hasPushSupport()) return false;

    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.getSubscription();

    return !!sub;
}

export async function subscribePush(vapidKey) {
    if (!hasPushSupport()) throw new Error("Push non supporté");

    // 🔥 Edge-safe : DIRECT click → permission
    const permission = await Notification.requestPermission();
    if (permission !== "granted") {
        throw new Error("Autorisation refusée");
    }

    const reg = await navigator.serviceWorker.register("/service-worker.js");

    const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey)
    });

    const json = sub.toJSON();
    await api.post("/api/push-subscription", {
        endpoint: json.endpoint,
        auth: json.keys.auth,
        p256dh: json.keys.p256dh
    });
}

export async function unsubscribePush() {
    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.getSubscription();
    if (!sub) return;

    await sub.unsubscribe();
    await api.post("/api/push-subscription/delete", { endpoint: sub.endpoint });
}

export async function getCurrentPushEndpoint() {
    if (!hasPushSupport()) return null;

    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.getSubscription();
    return sub?.endpoint ?? null;
}


function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
}
