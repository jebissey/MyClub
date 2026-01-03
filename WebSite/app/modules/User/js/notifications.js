document.addEventListener('DOMContentLoaded', function () {
    const noNotificationCheckbox = document.getElementById('noNotification');
    const alertOptions = document.getElementById('alertOptions');

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

    toggleAlertOptions();
    toggleNewArticlePoll();
    toggleUpdatedArticlePoll();
    toggleNewPollVoteOptions();
    toggleMessageOnArticleIfAuthorOrPost();
    toggleMessageOnEventOptions();
    updateGroupSubscribedParent();
    updateGroupJoinedParent();

    noNotificationCheckbox.addEventListener('change', toggleAlertOptions);
    newArticleCheckbox.addEventListener('change', toggleNewArticlePoll);
    updatedArticleCheckbox.addEventListener('change', toggleUpdatedArticlePoll);
    newPollVoteCheckbox.addEventListener('change', toggleNewPollVoteOptions);
    messageOnArticleCheckbox.addEventListener('change', toggleMessageOnArticleIfAuthorOrPost);
    messageOnEventCheckbox.addEventListener('change', toggleMessageOnEventOptions);

    messageOnGroupSubscribedCheckbox.addEventListener('change', toggleGroupSubscribedChildren);
    groupsSubscribedChildren.forEach(child => {
        child.addEventListener('change', updateGroupSubscribedParent);
    });

    messageOnGroupJoinedCheckbox.addEventListener('change', toggleGroupJoinedChildren);
    groupsJoinedChildren.forEach(child => {
        child.addEventListener('change', updateGroupJoinedParent);
    });
});