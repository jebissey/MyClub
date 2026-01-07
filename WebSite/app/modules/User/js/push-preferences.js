export default class PushPreferences {
    constructor() {
        this.elements = {};
    }

    // ================================
    // Initialisation
    // ================================
    init() {
        this.cacheElements();
        this.initUI();
        this.initListeners();
    }

    // ================================
    // DOM cache
    // ================================
    cacheElements() {
        const $ = id => document.getElementById(id);

        this.elements = {
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

    // ================================
    // UI helpers
    // ================================
    setDisplay(el, visible) {
        if (!el) return;
        el.style.display = visible ? "flex" : "none";
    }

    toggleWrapper(wrapper, checkbox, children = []) {
        if (!wrapper || !checkbox) return;

        const visible = checkbox.checked;
        this.setDisplay(wrapper, visible);

        if (!visible) {
            children.forEach(c => c && (c.checked = false));
        }
    }

    updateParent(parent, children) {
        if (!parent || !children?.length) return;

        const allChecked = [...children].every(c => c.checked);
        const someChecked = [...children].some(c => c.checked);

        parent.checked = allChecked;
        parent.indeterminate = !allChecked && someChecked;
    }

    toggleChildren(parent, children) {
        if (!parent || !children?.length) return;

        children.forEach(c => (c.checked = parent.checked));
        parent.indeterminate = false;
    }

    // ================================
    // UI init
    // ================================
    initUI() {
        const e = this.elements;
        this.setDisplay(e.alertOptions, !e.noNotification?.checked);
        this.setDisplay(e.newArticlePollWrapper, e.newArticle?.checked);
        this.setDisplay(e.updatedArticlePollWrapper, e.updatedArticle?.checked);
        this.setDisplay(e.newPollVoteOptionsWrapper, e.newPollVote?.checked);
        this.setDisplay(e.messageOnArticleIfAuthorWrapper, e.messageOnArticle?.checked);
        this.setDisplay(e.messageOnArticleIfPostWrapper, e.messageOnArticle?.checked);
        this.setDisplay(e.messageOnEventOptionsWrapper, e.messageOnEvent?.checked);

        this.updateParent(e.messageOnGroupSubscribed, e.groupsSubscribedChildren);
        this.updateParent(e.messageOnGroupJoined, e.groupsJoinedChildren);
    }

    // ================================
    // Listeners
    // ================================
    initListeners() {
        const e = this.elements;

        e.noNotification?.addEventListener("change", () =>
            this.setDisplay(e.alertOptions, !e.noNotification.checked)
        );

        e.newArticle?.addEventListener("change", () =>
            this.toggleWrapper(e.newArticlePollWrapper, e.newArticle, [e.newArticlePoll])
        );

        e.updatedArticle?.addEventListener("change", () =>
            this.toggleWrapper(e.updatedArticlePollWrapper, e.updatedArticle, [e.updatedArticlePoll])
        );

        e.newPollVote?.addEventListener("change", () =>
            this.toggleWrapper(
                e.newPollVoteOptionsWrapper,
                e.newPollVote,
                [e.newPollVoteIfVoted, e.newPollVoteIfAuthor]
            )
        );

        e.messageOnArticle?.addEventListener("change", () => {
            this.setDisplay(e.messageOnArticleIfAuthorWrapper, e.messageOnArticle.checked);
            this.setDisplay(e.messageOnArticleIfPostWrapper, e.messageOnArticle.checked);

            if (!e.messageOnArticle.checked) {
                e.messageOnArticleIfAuthor.checked = false;
                e.messageOnArticleIfPost.checked = false;
            }
        });

        e.messageOnEvent?.addEventListener("change", () =>
            this.setDisplay(e.messageOnEventOptionsWrapper, e.messageOnEvent.checked)
        );

        e.messageOnGroupSubscribed?.addEventListener("change", () =>
            this.toggleChildren(e.messageOnGroupSubscribed, e.groupsSubscribedChildren)
        );

        e.groupsSubscribedChildren.forEach(c =>
            c.addEventListener("change", () =>
                this.updateParent(e.messageOnGroupSubscribed, e.groupsSubscribedChildren)
            )
        );

        e.messageOnGroupJoined?.addEventListener("change", () =>
            this.toggleChildren(e.messageOnGroupJoined, e.groupsJoinedChildren)
        );

        e.groupsJoinedChildren.forEach(c =>
            c.addEventListener("change", () =>
                this.updateParent(e.messageOnGroupJoined, e.groupsJoinedChildren)
            )
        );
    }
}
