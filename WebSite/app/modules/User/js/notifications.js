document.addEventListener('DOMContentLoaded', async function () {
    const noNotificationCheckbox = document.getElementById('noNotification');
    const alertOptions = document.getElementById('alertOptions');
    const pushNotificationBtn = document.getElementById('pushNotificationBtn');
    const pushBtnText = document.getElementById('pushBtnText');

    const newArticleCheckbox = document.getElementById('newArticle');
    const newArticlePollWrapper = document.getElementById('newArticlePollWrapper');
    const newArticlePollCheckbox = document.getElementById('newArticlePoll');

    const updatedArticleCheckbox = document.getElementById('updatedArticle');
    const updatedArticlePollWrapper = document.getElementById('updatedArticlePollWrapper');
    const updatedArticlePollCheckbox = document.getElementById('updatedArticlePoll');

    const newPollVoteCheckbox = document.getElementById('newPollVote');
    const newPollVoteOptionsWrapper = document.getElementById('newPollVoteOptionsWrapper');
    const newPollVoteIfVotedCheckbox = document.getElementById('newPollVoteIfVoted');
    const newPollVoteIfAuthorCheckbox = document.getElementById('newPollVoteIfAuthor');

    const messageOnArticleCheckbox = document.getElementById('messageOnArticle');
    const messageOnArticleIfAuthorWrapper = document.getElementById('messageOnArticleIfAuthorWrapper');
    const messageOnArticleIfAuthorCheckbox = document.getElementById('messageOnArticleIfAuthor');
    const messageOnArticleIfPostWrapper = document.getElementById('messageOnArticleIfPostWrapper');
    const messageOnArticleIfPostCheckbox = document.getElementById('messageOnArticleIfPost');

    const messageOnEventCheckbox = document.getElementById('messageOnEvent');
    const messageOnEventOptionsWrapper = document.getElementById('messageOnEventOptionsWrapper');
    const messageOnEventIfRegisteredCheckbox = document.getElementById('messageOnEventIfRegistered');
    const messageOnEventIfInPreferencesCheckbox = document.getElementById('messageOnEventIfInPreferences');
    const messageOnEventIfCreatorCheckbox = document.getElementById('messageOnEventIfCreator');

    const messageOnGroupSubscribedCheckbox = document.getElementById('messageOnGroupSubscribed');
    const groupsSubscribedChildren = document.querySelectorAll('.group-subscribed-child');

    const messageOnGroupJoinedCheckbox = document.getElementById('messageOnGroupJoined');
    const groupsJoinedChildren = document.querySelectorAll('.group-joined-child');


    function toggleAlertOptions() {
        if (noNotificationCheckbox.checked) {
            alertOptions.style.display = 'none';
        } else {
            alertOptions.style.display = '';
        }
    }

    function toggleNewArticlePoll() {
        if (newArticleCheckbox.checked) {
            newArticlePollWrapper.style.display = 'flex';
        } else {
            newArticlePollWrapper.style.display = 'none';
            newArticlePollCheckbox.checked = false;
        }
    }

    function toggleUpdatedArticlePoll() {
        if (updatedArticleCheckbox.checked) {
            updatedArticlePollWrapper.style.display = 'flex';
        } else {
            updatedArticlePollWrapper.style.display = 'none';
            updatedArticlePollCheckbox.checked = false;
        }
    }

    function toggleNewPollVoteOptions() {
        if (newPollVoteCheckbox.checked) {
            newPollVoteOptionsWrapper.style.display = 'flex';
        } else {
            newPollVoteOptionsWrapper.style.display = 'none';
            newPollVoteIfVotedCheckbox.checked = false;
            newPollVoteIfAuthorCheckbox.checked = false;
        }
    }

    function toggleMessageOnArticleIfAuthorOrPost() {
        if (messageOnArticleCheckbox.checked) {
            messageOnArticleIfAuthorWrapper.style.display = 'flex';
            messageOnArticleIfPostWrapper.style.display = 'flex';
        } else {
            messageOnArticleIfAuthorWrapper.style.display = 'none';
            messageOnArticleIfAuthorCheckbox.checked = false;
            messageOnArticleIfPostWrapper.style.display = 'none';
            messageOnArticleIfPostCheckbox.checked = false;
        }
    }

    function toggleMessageOnEventOptions() {
        if (messageOnEventCheckbox.checked) {
            messageOnEventOptionsWrapper.style.display = 'flex';
        } else {
            messageOnEventOptionsWrapper.style.display = 'none';
            messageOnEventIfRegisteredCheckbox.checked = false;
            messageOnEventIfInPreferencesCheckbox.checked = false;
            messageOnEventIfCreatorCheckbox.checked = false;
        }
    }

    function updateGroupSubscribedParent() {
        const allChecked = Array.from(groupsSubscribedChildren).every(child => child.checked);
        const someChecked = Array.from(groupsSubscribedChildren).some(child => child.checked);

        if (allChecked && groupsSubscribedChildren.length > 0) {
            messageOnGroupSubscribedCheckbox.checked = true;
            messageOnGroupSubscribedCheckbox.indeterminate = false;
        } else if (someChecked) {
            messageOnGroupSubscribedCheckbox.checked = false;
            messageOnGroupSubscribedCheckbox.indeterminate = true;
        } else {
            messageOnGroupSubscribedCheckbox.checked = false;
            messageOnGroupSubscribedCheckbox.indeterminate = false;
        }
    }

    function updateGroupJoinedParent() {
        const allChecked = Array.from(groupsJoinedChildren).every(child => child.checked);
        const someChecked = Array.from(groupsJoinedChildren).some(child => child.checked);

        if (allChecked && groupsJoinedChildren.length > 0) {
            messageOnGroupJoinedCheckbox.checked = true;
            messageOnGroupJoinedCheckbox.indeterminate = false;
        } else if (someChecked) {
            messageOnGroupJoinedCheckbox.checked = false;
            messageOnGroupJoinedCheckbox.indeterminate = true;
        } else {
            messageOnGroupJoinedCheckbox.checked = false;
            messageOnGroupJoinedCheckbox.indeterminate = false;
        }
    }

    function toggleGroupSubscribedChildren() {
        const isChecked = messageOnGroupSubscribedCheckbox.checked;
        groupsSubscribedChildren.forEach(child => {
            child.checked = isChecked;
        });
        messageOnGroupSubscribedCheckbox.indeterminate = false;
    }

    function toggleGroupJoinedChildren() {
        const isChecked = messageOnGroupJoinedCheckbox.checked;
        groupsJoinedChildren.forEach(child => {
            child.checked = isChecked;
        });
        messageOnGroupJoinedCheckbox.indeterminate = false;
    }

    async function updatePushButtonState() {
        if (!pushNotificationBtn || !pushBtnText) return;

        try {
            const isSubscribed = await checkExistingSubscription();

            if (isSubscribed) {
                pushBtnText.textContent = 'Se désabonner';
                pushNotificationBtn.classList.remove('btn-primary');
                pushNotificationBtn.classList.add('btn-danger');
            } else {
                pushBtnText.textContent = "S'abonner";
                pushNotificationBtn.classList.remove('btn-danger');
                pushNotificationBtn.classList.add('btn-primary');
            }
        } catch (error) {
            pushBtnText.textContent = "S'abonner";
            pushNotificationBtn.classList.remove('btn-danger');
            pushNotificationBtn.classList.add('btn-primary');
        }
    }

    if (pushNotificationBtn) {
        pushNotificationBtn.addEventListener('click', async function () {
            pushNotificationBtn.disabled = true;
            pushBtnText.textContent = 'Traitement...';

            try {
                const isSubscribed = await checkExistingSubscription();

                if (isSubscribed) {
                    // Se désabonner
                    const success = await unsubscribeFromPushNotifications();
                    if (success) {
                        showNotification('Désabonnement réussi', 'success');
                        await updatePushButtonState();
                    } else {
                        showNotification('Erreur lors du désabonnement', 'error');
                    }
                } else {
                    // S'abonner
                    const success = await subscribeToPushNotifications();
                    if (success) {
                        showNotification('Abonnement réussi', 'success');
                        await updatePushButtonState();
                    } else {
                        showNotification('Erreur lors de l\'abonnement', 'error');
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue', 'error');
            } finally {
                pushNotificationBtn.disabled = false;
            }
        });
    }

    // Initialisation au chargement
    toggleAlertOptions();
    toggleNewArticlePoll();
    toggleUpdatedArticlePoll();
    toggleNewPollVoteOptions();
    toggleMessageOnArticleIfAuthorOrPost();
    toggleMessageOnEventOptions();
    updateGroupSubscribedParent();
    updateGroupJoinedParent();
    updatePushButtonState()

    if (Notification.permission === 'granted') {
        checkExistingSubscription().catch(err => {
            console.error('Erreur vérification abonnement:', err);
        });
    }

    // Event listeners pour les checkboxes
    if (noNotificationCheckbox) {
        noNotificationCheckbox.addEventListener('change', toggleAlertOptions);
    }
    if (newArticleCheckbox) {
        newArticleCheckbox.addEventListener('change', toggleNewArticlePoll);
    }
    if (updatedArticleCheckbox) {
        updatedArticleCheckbox.addEventListener('change', toggleUpdatedArticlePoll);
    }
    if (newPollVoteCheckbox) {
        newPollVoteCheckbox.addEventListener('change', toggleNewPollVoteOptions);
    }
    if (messageOnArticleCheckbox) {
        messageOnArticleCheckbox.addEventListener('change', toggleMessageOnArticleIfAuthorOrPost);
    }
    if (messageOnEventCheckbox) {
        messageOnEventCheckbox.addEventListener('change', toggleMessageOnEventOptions);
    }

    if (messageOnGroupSubscribedCheckbox) {
        messageOnGroupSubscribedCheckbox.addEventListener('change', toggleGroupSubscribedChildren);
    }
    groupsSubscribedChildren.forEach(child => {
        child.addEventListener('change', updateGroupSubscribedParent);
    });

    if (messageOnGroupJoinedCheckbox) {
        messageOnGroupJoinedCheckbox.addEventListener('change', toggleGroupJoinedChildren);
    }
    groupsJoinedChildren.forEach(child => {
        child.addEventListener('change', updateGroupJoinedParent);
    });


});

// Vérifier si l'utilisateur est déjà abonné aux notifications push
async function checkExistingSubscription() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.getRegistration();
        if (!registration) {
            return false;
        }

        const subscription = await registration.pushManager.getSubscription();
        return !!subscription;
    } catch (error) {
        console.error('Erreur vérification abonnement:', error);
        return false;
    }
}

async function subscribeToPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return false;
    }

    try {
        // Permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            showNotification(
                'Vous devez autoriser les notifications dans votre navigateur',
                'warning'
            );
            return false;
        }

        // Récupération immédiate (non bloquante)
        let registration = await navigator.serviceWorker.getRegistration();

        // Si pas encore de SW → on enregistre
        if (!registration) {
            registration = await navigator.serviceWorker.register('/service-worker.js');
        }

        // Abonnement
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });

        await sendSubscriptionToServer(subscription);
        return true;

    } catch (error) {
        console.error('Erreur abonnement push:', error);
        return false;
    }
}


// Envoyer l'abonnement au serveur
async function sendSubscriptionToServer(subscription) {
    const subscriptionJson = subscription.toJSON();

    const response = await fetch('/api/push-subscription', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            endpoint: subscriptionJson.endpoint,
            auth: subscriptionJson.keys.auth,
            p256dh: subscriptionJson.keys.p256dh
        })
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || 'Échec de l\'enregistrement de l\'abonnement');
    }

    return await response.json();
}

// Se désabonner des notifications push
async function unsubscribeFromPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await subscription.unsubscribe();

            // Informer le serveur de la désinscription
            const subscriptionJson = subscription.toJSON();
            await fetch('/api/push-subscription/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: subscriptionJson.endpoint
                })
            });

            console.log('Désabonnement des notifications push réussi');
            return true;
        }
        return false;
    } catch (error) {
        console.error('Erreur lors du désabonnement:', error);
        return false;
    }
}

// Convertir la clé VAPID de base64 en Uint8Array
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Fonction utilitaire pour afficher des notifications à l'utilisateur
function showNotification(message, type = 'info') {
    // Vous pouvez utiliser votre propre système de notification (toast, alert, etc.)
    // Voici un exemple simple avec une alerte personnalisée

    const notificationDiv = document.createElement('div');
    notificationDiv.className = `notification notification-${type}`;
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
        setTimeout(() => {
            document.body.removeChild(notificationDiv);
        }, 300);
    }, 3000);
}

// Ajouter les animations CSS nécessaires
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);