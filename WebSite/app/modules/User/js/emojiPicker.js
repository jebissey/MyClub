export function initEmojiPicker(options = {}) {
    const {
        pasteInputId = 'emojiPasteInput',
        feedbackId = 'emojiPasteFeedback',
        avatarFieldId = 'avatar',
        selectedSpanId = 'selectedEmoji',
        emojiLabelId = 'emojiLabel',
        emojiListId = 'emojiList',
        t = (key) => window.t?.(key) ?? key,
    } = options;

    const pasteInput = document.getElementById(pasteInputId);
    const feedback = document.getElementById(feedbackId);
    const avatarField = document.getElementById(avatarFieldId);
    const selectedSpan = document.getElementById(selectedSpanId);
    const emojiLabel = document.getElementById(emojiLabelId);
    const emojiList = document.getElementById(emojiListId);

    if (!pasteInput || !feedback || !avatarField || !selectedSpan || !emojiLabel || !emojiList) {
        console.warn(t('account.form.emoji.missing_elements'));
        return;
    }

    function extractEmoji(str) {
        const match = str.match(/\p{Emoji}+/u);
        return match ? match[0] : null;
    }

    function setSelectedEmoji(emoji) {
        avatarField.value = emoji;
        selectedSpan.textContent = emoji + ' ';
    }

    function applyEmoji(raw) {
        const emoji = extractEmoji(raw.trim());

        if (!emoji) {
            feedback.textContent = t('account.form.emoji.none_detected');
            feedback.className = 'small text-warning';
            return;
        }

        setSelectedEmoji(emoji);
        emojiLabel.textContent = t('account.form.emoji.select_label');

        emojiList.innerHTML = '';

        const li = document.createElement('li');
        const a = document.createElement('a');

        a.className = 'dropdown-item emoji-item active';
        a.href = '#';
        a.dataset.emoji = emoji;
        a.style.cssText = 'font-size: 1.5rem; padding: 8px 12px;';
        a.textContent = emoji;

        a.addEventListener('click', e => {
            e.preventDefault();
            setSelectedEmoji(emoji);
        });

        li.appendChild(a);
        emojiList.appendChild(li);

        pasteInput.value = '';

        feedback.textContent = t('account.form.emoji.selected').replace('%s', emoji);
        feedback.className = 'small text-success';
    }

    pasteInput.addEventListener('paste', function (e) {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        applyEmoji(text);
        e.preventDefault();
    });
}