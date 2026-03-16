import ApiClient from '/app/modules/Common/js/ApiClient.js';

const api = new ApiClient();

export default class QuotaManager {
    #state = {
        dailySent: 0,   dailyLimit: null,   dailyRemaining: null,
        monthlySent: 0, monthlyLimit: null, monthlyRemaining: null,
        dailyWouldExceed: false, monthlyWouldExceed: false,
        nextCount: 1,
    };

    #onChangeCallback = null;

    onChange(cb) {
        this.#onChangeCallback = cb;
        return this;
    }

    isBlocked() {
        if (this.#state.dailyWouldExceed)   return 'daily';
        if (this.#state.monthlyWouldExceed) return 'monthly';
        return false;
    }

    get state() { return this.#state; }

    async refresh(bccCount = 0) {
        const data = await api.get(`/api/communication/quota?bcc=${bccCount}`);
        if (data.success) this.#update(data);
    }

    applyResponse(data) {
        this.#update(data);
    }

    buildConfirmQuotaLine() {
        const { dailyLimit, dailyRemaining, monthlyLimit, monthlyRemaining, nextCount } = this.#state;
        const daily   = dailyLimit   !== null
            ? `journalier : <strong>${dailyRemaining}</strong> restant(s) (cet envoi : ${nextCount} crédit(s))`
            : '';
        const monthly = monthlyLimit !== null
            ? `mensuel : <strong>${monthlyRemaining}</strong> restant(s)`
            : '';
        return [daily, monthly].filter(Boolean).join('<br>');
    }

    #update(data) {
        Object.assign(this.#state, data);
        this.#render();
        this.#onChangeCallback?.();
    }

    #render() {
        const { dailySent, dailyLimit, monthlySent, monthlyLimit,
                dailyRemaining, monthlyRemaining, nextCount } = this.#state;

        const dp = dailyLimit   ? Math.min(100, Math.round(dailySent   / dailyLimit   * 100)) : 0;
        const mp = monthlyLimit ? Math.min(100, Math.round(monthlySent / monthlyLimit * 100)) : 0;

        this.#setBar('quota-daily-bar',   dp, dailyLimit,   dailySent);
        this.#setBar('quota-monthly-bar', mp, monthlyLimit, monthlySent);

        const blocked = this.isBlocked();
        const alert   = document.getElementById('quota-alert');
        const alertTx = document.getElementById('quota-alert-text');

        if (blocked === 'daily') {
            alert.className = 'alert alert-danger py-2 flex-shrink-0';
            alertTx.textContent =
                `Plafond journalier atteint — cet envoi (${nextCount} crédit(s)) dépasserait la limite de ${dailyLimit}.`;
            alert.classList.remove('d-none');
        } else if (blocked === 'monthly') {
            alert.className = 'alert alert-danger py-2 flex-shrink-0';
            alertTx.textContent =
                `Plafond mensuel atteint — cet envoi (${nextCount} crédit(s)) dépasserait la limite de ${monthlyLimit}.`;
            alert.classList.remove('d-none');
        } else if (
            (dailyRemaining   !== null && dailyLimit   !== null && dailyRemaining   <= Math.ceil(dailyLimit   * .1)) ||
            (monthlyRemaining !== null && monthlyLimit !== null && monthlyRemaining <= Math.ceil(monthlyLimit * .1))
        ) {
            alert.className = 'alert alert-warning py-2 flex-shrink-0';
            alertTx.textContent =
                `Quota presque épuisé — journalier : ${dailyRemaining ?? '∞'} restant(s), mensuel : ${monthlyRemaining ?? '∞'} restant(s).`;
            alert.classList.remove('d-none');
        } else {
            alert.classList.add('d-none');
        }
    }

    #setBar(id, pct, limit, sent) {
        const el = document.getElementById(id);
        el.style.width = pct + '%';
        el.className   = 'progress-bar ' + (pct >= 100 ? 'bg-danger' : pct >= 80 ? 'bg-warning' : 'bg-success');
        document.getElementById(id.replace('-bar', '-text')).textContent =
            limit !== null ? `${sent} / ${limit}` : `${sent}`;
    }
}