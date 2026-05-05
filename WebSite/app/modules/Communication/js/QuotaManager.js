import ApiClient from '/app/modules/Common/js/ApiClient.js';

const api = new ApiClient();

export default class QuotaManager {
    #state = {
        dailySent: 0,
        dailyLimit: null,
        dailyRemaining: null,

        monthlySent: 0,
        monthlyLimit: null,
        monthlyRemaining: null,

        dailyWouldExceed: false,
        monthlyWouldExceed: false,

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

    get state() {
        return this.#state;
    }

    /**
     * Fetch quota state from API
     */
    async refresh(bccCount = 0) {
        const data = await api.get(`/api/communication/quota?bcc=${bccCount}`);
        if (data.success) this.#update(data);
    }

    /**
     * Apply API response after sending email
     */
    applyResponse(data) {
        this.#update(data);
    }

    /**
     * Build quota line displayed in confirmation modal
     */
    buildConfirmQuotaLine() {
        const {
            dailyLimit,
            dailyRemaining,
            monthlyLimit,
            monthlyRemaining,
            nextCount
        } = this.#state;

        const daily = dailyLimit !== null
            ? `${window.t('quotaDailyLabel')} : <strong>${dailyRemaining}</strong> ${window.t('quotaRemaining')} (${window.t('quotaThisSend')} : ${nextCount})`
            : '';

        const monthly = monthlyLimit !== null
            ? `${window.t('quotaMonthlyLabel')} : <strong>${monthlyRemaining}</strong> ${window.t('quotaRemaining')}`
            : '';

        return [daily, monthly].filter(Boolean).join('<br>');
    }

    /**
     * Update internal state and re-render UI
     */
    #update(data) {
        Object.assign(this.#state, data);
        this.#render();
        this.#onChangeCallback?.();
    }

    /**
     * Render quota bars and alert messages
     */
    #render() {
        const {
            dailySent,
            dailyLimit,
            monthlySent,
            monthlyLimit,
            dailyRemaining,
            monthlyRemaining,
            nextCount
        } = this.#state;

        const dp = dailyLimit
            ? Math.min(100, Math.round((dailySent / dailyLimit) * 100))
            : 0;

        const mp = monthlyLimit
            ? Math.min(100, Math.round((monthlySent / monthlyLimit) * 100))
            : 0;

        this.#setBar('quota-daily-bar', dp, dailyLimit, dailySent);
        this.#setBar('quota-monthly-bar', mp, monthlyLimit, monthlySent);

        const blocked = this.isBlocked();
        const alert   = document.getElementById('quota-alert');
        const alertTx = document.getElementById('quota-alert-text');

        if (blocked === 'daily') {
            alert.className = 'alert alert-danger py-2 flex-shrink-0';

            alertTx.textContent = window.t('quotaDailyReached')
                .replace('%d', nextCount)
                .replace('%d', dailyLimit);

            alert.classList.remove('d-none');

        } else if (blocked === 'monthly') {
            alert.className = 'alert alert-danger py-2 flex-shrink-0';

            alertTx.textContent = window.t('quotaMonthlyReached')
                .replace('%d', nextCount)
                .replace('%d', monthlyLimit);

            alert.classList.remove('d-none');

        } else if (
            (dailyRemaining   !== null && dailyLimit   !== null && dailyRemaining   <= Math.ceil(dailyLimit   * 0.1)) ||
            (monthlyRemaining !== null && monthlyLimit !== null && monthlyRemaining <= Math.ceil(monthlyLimit * 0.1))
        ) {
            alert.className = 'alert alert-warning py-2 flex-shrink-0';

            alertTx.textContent = window.t('quotaAlmost')
                .replace('%s', dailyRemaining ?? '∞')
                .replace('%s', monthlyRemaining ?? '∞');

            alert.classList.remove('d-none');

        } else {
            alert.classList.add('d-none');
        }
    }

    /**
     * Update progress bar UI
     */
    #setBar(id, pct, limit, sent) {
        const el = document.getElementById(id);

        el.style.width = pct + '%';

        el.className =
            'progress-bar ' +
            (pct >= 100
                ? 'bg-danger'
                : pct >= 80
                ? 'bg-warning'
                : 'bg-success');

        document.getElementById(id.replace('-bar', '-text')).textContent =
            limit !== null
                ? `${sent} / ${limit}`
                : `${sent}`;
    }
}