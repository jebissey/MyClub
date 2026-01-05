document.addEventListener('DOMContentLoaded', async () => {
    // ----------------------------
    // Sélection des éléments
    // ----------------------------
    const elements = {
        noNotification: document.getElementById('noNotification'),
        alertOptions: document.getElementById('alertOptions'),
        pushNotificationBtn: document.getElementById('pushNotificationBtn'),
        pushBtnText: document.getElementById('pushBtnText'),

        newArticle: document.getElementById('newArticle'),
        newArticlePollWrapper: document.getElementById('newArticlePollWrapper'),
        newArticlePoll: document.getElementById('newArticlePoll'),

        updatedArticle: document.getElementById('updatedArticle'),
        updatedArticlePollWrapper: document.getElementById('updatedArticlePollWrapper'),
        updatedArticlePoll: document.getElementById('updatedArticlePoll'),

        newPollVote: document.getElementById('newPollVote'),
        newPollVoteOptionsWrapper: document.getElementById('newPollVoteOptionsWrapper'),
        newPollVoteIfVoted: document.getElementById('newPollVoteIfVoted'),
        newPollVoteIfAuthor: document.getElementById('newPollVoteIfAuthor'),

        messageOnArticle: document.getElementById('messageOnArticle'),
        messageOnArticleIfAuthorWrapper: document.getElementById('messageOnArticleIfAuthorWrapper'),
        messageOnArticleIfAuthor: document.getElementById('messageOnArticleIfAuthor'),
        messageOnArticleIfPostWrapper: document.getElementById('messageOnArticleIfPostWrapper'),
        messageOnArticleIfPost: document.getElementById('messageOnArticleIfPost'),

        messageOnEvent: document.getElementById('messageOnEvent'),
        messageOnEventOptionsWrapper: document.getElementById('messageOnEventOptionsWrapper'),
        messageOnEventIfRegistered: document.getElementById('messageOnEventIfRegistered'),
        messageOnEventIfInPreferences: document.getElementById('messageOnEventIfInPreferences'),
        messageOnEventIfCreator: document.getElementById('messageOnEventIfCreator'),

        messageOnGroupSubscribed: document.getElementById('messageOnGroupSubscribed'),
        groupsSubscribedChildren: document.querySelectorAll('.group-subscribed-child'),

        messageOnGroupJoined: document.getElementById('messageOnGroupJoined'),
        groupsJoinedChildren: document.querySelectorAll('.group-joined-child')
    };

    // ----------------------------
    // Fonctions utilitaires
    // ----------------------------
    const setDisplay = (wrapper, visible) => wrapper.style.display = visible ? 'flex' : 'none';

    const toggleWrapper = (wrapper, checkbox, childrenCheckboxes = []) => {
        const visible = checkbox.checked;
        setDisplay(wrapper, visible);
        childrenCheckboxes.forEach(c => { if (!visible) c.checked = false; });
    };

    const updateParentCheckbox = (parent, children) => {
        const allChecked = Array.from(children).every(c => c.checked);
        const someChecked = Array.from(children).some(c => c.checked);
        parent.checked = allChecked;
        parent.indeterminate = !allChecked && someChecked;
    };

    const toggleChildrenCheckboxes = (parent, children) => {
        children.forEach(c => c.checked = parent.checked);
        parent.indeterminate = false;
    };

    // ----------------------------
    // Notifications toast
    // ----------------------------
    const showNotification = (message, type = 'info') => {
        const notificationDiv = document.createElement('div');
        notificationDiv.className = `notification notification-${type}`;
        notificationDiv.setAttribute('role', 'alert');
        notificationDiv.textContent = message;
        notificationDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#ff9800'};
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        document.body.appendChild(notificationDiv);
        setTimeout(() => {
            notificationDiv.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notificationDiv.remove(), 300);
        }, 3000);
    };

    // ----------------------------
    // Push notifications
    // ----------------------------
    const checkExistingSubscription = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return false;
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (!registration) return false;
            const subscription = await registration.pushManager.getSubscription();
            return !!subscription;
        } catch (e) {
            console.error('Erreur vérification abonnement:', e);
            return false;
        }
    };

    const sendSubscriptionToServer = async (subscription) => {
        const subJson = subscription.toJSON();
        const response = await fetch('/api/push-subscription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                endpoint: subJson.endpoint,
                auth: subJson.keys.auth,
                p256dh: subJson.keys.p256dh
            })
        });
        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.error || "Échec de l'enregistrement de l'abonnement");
        }
        return await response.json();
    };

    const subscribeToPushNotifications = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return false;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                showNotification('Vous devez autoriser les notifications', 'warning');
                return false;
            }

            let registration = await navigator.serviceWorker.getRegistration();
            if (!registration) registration = await navigator.serviceWorker.register('/service-worker.js');

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            await sendSubscriptionToServer(subscription); // ✅ fetch réintégré
            return true;

        } catch (error) {
            console.error('Erreur abonnement push:', error);
            showNotification('Erreur lors de l’abonnement push: ' + error.message, 'error');
            return false;
        }
    };

    const unsubscribeFromPushNotifications = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return false;
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                await subscription.unsubscribe();
                await fetch('/api/push-subscription/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ endpoint: subscription.endpoint })
                });
                console.log('Désabonnement push réussi');
                return true;
            }
            return false;
        } catch (e) {
            console.error('Erreur désabonnement push:', e);
            return false;
        }
    };

    const updatePushButtonState = async () => {
        const { pushNotificationBtn, pushBtnText } = elements;
        if (!pushNotificationBtn || !pushBtnText) return;

        try {
            const isSubscribed = await checkExistingSubscription();
            pushBtnText.textContent = isSubscribed ? 'Se désabonner' : "S'abonner";
            pushNotificationBtn.classList.toggle('btn-primary', !isSubscribed);
            pushNotificationBtn.classList.toggle('btn-danger', isSubscribed);
        } catch {
            pushBtnText.textContent = "S'abonner";
            pushNotificationBtn.classList.add('btn-primary');
            pushNotificationBtn.classList.remove('btn-danger');
        }
    };

    if (elements.pushNotificationBtn) {
        elements.pushNotificationBtn.addEventListener('click', async () => {
            const btn = elements.pushNotificationBtn;
            const text = elements.pushBtnText;
            btn.disabled = true;
            text.textContent = 'Traitement...';
            try {
                const subscribed = await checkExistingSubscription();
                const success = subscribed
                    ? await unsubscribeFromPushNotifications()
                    : await subscribeToPushNotifications();

                showNotification(
                    subscribed ? (success ? 'Désabonnement réussi' : 'Erreur lors du désabonnement')
                               : (success ? 'Abonnement réussi' : 'Erreur lors de l\'abonnement'),
                    success ? 'success' : 'error'
                );
                await updatePushButtonState();
            } catch (err) {
                console.error(err);
                showNotification('Une erreur est survenue', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    }

    // ----------------------------
    // Initialisation UI
    // ----------------------------
    // Respect de l'état initial du DOM
    setDisplay(elements.alertOptions, !elements.noNotification.checked);
    setDisplay(elements.newArticlePollWrapper, elements.newArticle.checked);
    setDisplay(elements.updatedArticlePollWrapper, elements.updatedArticle.checked);
    setDisplay(elements.newPollVoteOptionsWrapper, elements.newPollVote.checked);
    setDisplay(elements.messageOnArticleIfAuthorWrapper, elements.messageOnArticle.checked);
    setDisplay(elements.messageOnArticleIfPostWrapper, elements.messageOnArticle.checked);
    setDisplay(elements.messageOnEventOptionsWrapper, elements.messageOnEvent.checked);

    updateParentCheckbox(elements.messageOnGroupSubscribed, elements.groupsSubscribedChildren);
    updateParentCheckbox(elements.messageOnGroupJoined, elements.groupsJoinedChildren);
    updatePushButtonState();

    // ----------------------------
    // Écouteurs événements
    // ----------------------------
    elements.noNotification?.addEventListener('change', () => toggleWrapper(elements.alertOptions, elements.noNotification));
    elements.newArticle?.addEventListener('change', () => toggleWrapper(elements.newArticlePollWrapper, elements.newArticle, [elements.newArticlePoll]));
    elements.updatedArticle?.addEventListener('change', () => toggleWrapper(elements.updatedArticlePollWrapper, elements.updatedArticle, [elements.updatedArticlePoll]));
    elements.newPollVote?.addEventListener('change', () => toggleWrapper(elements.newPollVoteOptionsWrapper, elements.newPollVote, [elements.newPollVoteIfVoted, elements.newPollVoteIfAuthor]));
    elements.messageOnArticle?.addEventListener('change', () => {
        toggleWrapper(elements.messageOnArticleIfAuthorWrapper, elements.messageOnArticle, [elements.messageOnArticleIfAuthor]);
        toggleWrapper(elements.messageOnArticleIfPostWrapper, elements.messageOnArticle, [elements.messageOnArticleIfPost]);
    });
    elements.messageOnEvent?.addEventListener('change', () => toggleWrapper(elements.messageOnEventOptionsWrapper, elements.messageOnEvent, [
        elements.messageOnEventIfRegistered,
        elements.messageOnEventIfInPreferences,
        elements.messageOnEventIfCreator
    ]));

    elements.messageOnGroupSubscribed?.addEventListener('change', () => toggleChildrenCheckboxes(elements.messageOnGroupSubscribed, elements.groupsSubscribedChildren));
    elements.groupsSubscribedChildren.forEach(child => child.addEventListener('change', () => updateParentCheckbox(elements.messageOnGroupSubscribed, elements.groupsSubscribedChildren)));

    elements.messageOnGroupJoined?.addEventListener('change', () => toggleChildrenCheckboxes(elements.messageOnGroupJoined, elements.groupsJoinedChildren));
    elements.groupsJoinedChildren.forEach(child => child.addEventListener('change', () => updateParentCheckbox(elements.messageOnGroupJoined, elements.groupsJoinedChildren)));
});

// ----------------------------
// Utilitaires VAPID / Base64
// ----------------------------
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from(rawData, c => c.charCodeAt(0));
}

// ----------------------------
// Styles animations
// ----------------------------
const style = document.createElement('style');
style.textContent = `
@keyframes slideIn { from {transform:translateX(400px);opacity:0;} to {transform:translateX(0);opacity:1;} }
@keyframes slideOut { from {transform:translateX(0);opacity:1;} to {transform:translateX(400px);opacity:0;} }
`;
document.head.appendChild(style);
