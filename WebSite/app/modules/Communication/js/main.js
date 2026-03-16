import QuotaManager  from './QuotaManager.js';
import MemberManager from './MemberManager.js';
import EmailForm     from './EmailForm.js';

const quota   = new QuotaManager();
const members = new MemberManager();
const form    = new EmailForm(quota, members);

// ── Synchronisation quota ↔ membres ─────────────────────────────────────────
// Chaque changement de sélection déclenche un refresh quota avec le nouveau
// bcc count, pour que le serveur recalcule dailyWouldExceed / monthlyWouldExceed.
members.onChange(() => {
    const count = members.getCheckedIds().length;
    document.getElementById('recipient-count').textContent = count;
    document.getElementById('btn-send').disabled = count === 0 || !!quota.isBlocked();
    quota.refresh(count);
});

quota.onChange(() => {
    const count = members.getCheckedIds().length;
    document.getElementById('btn-send').disabled = count === 0 || !!quota.isBlocked();
});

// ── Init ──────────────────────────────────────────────────────────────────────
members.bindEvents();
form.bindEvents();
initTinyMCE('#tinymce-email');
quota.refresh();