import ApiClient from './ApiClient.js';
import DistributionChart from './DistributionChart.js';
import TrendChart from './TrendChart.js';

const ERR_GENERIC = 'Une erreur est survenue.';
const ERR_NO_DATA = 'Aucune donnée disponible pour cette page.';

export default class CreationTimeModal {
    #api = new ApiClient();
    #distribution = new DistributionChart();
    #trend = new TrendChart();

    #modal = document.getElementById('creationTimeModal');
    #uriLabel = document.getElementById('creationTimeModalUri');
    #footer = document.getElementById('creationTimeModalFooter');

    bind() {
        document.addEventListener('click', e => {
            const trigger = e.target.closest('.creation-time-trigger');
            if (!trigger) return;
            this.#open(trigger.dataset);
        });

        this.#modal.addEventListener('hidden.bs.modal', () => this.#destroy());

        document.getElementById('creationTimeTabs')
            .addEventListener('shown.bs.tab', e => {
                const isDistribution = e.target.id === 'tab-distribution-btn';
                this.#footer.classList.toggle('d-none', !isDistribution);
            });
    }

    // ── Private ────────────────────────────────────────────────────────

    #open({ uri, from, to }) {
        this.#uriLabel.textContent = uri;
        this.#footer.classList.remove('d-none');

        bootstrap.Tab.getOrCreateInstance(
            document.getElementById('tab-distribution-btn')
        ).show();

        bootstrap.Modal.getOrCreateInstance(this.#modal).show();

        this.#loadDistribution(uri, from, to);
        this.#loadTrend(uri, from, to);
    }

    #destroy() {
        this.#distribution.destroy();
        this.#trend.destroy();
    }

    async #loadDistribution(uri, from, to) {
        this.#distribution.destroy();
        this.#distribution.setLoading(true);

        const params = new URLSearchParams({ uri, from, to });
        const response = await this.#api.get(`/api/visitor-insights/creation-time-distribution?${params}`);

        if (response?.success === false) {
            this.#distribution.showError(response.error ?? ERR_GENERIC);
            return;
        }

        const data = response?.data ?? response;
        if (!Array.isArray(data) || data.length === 0) {
            this.#distribution.showError(ERR_NO_DATA);
            return;
        }

        this.#distribution.render(data);
    }

    async #loadTrend(uri, from, to) {
        this.#trend.destroy();
        this.#trend.setLoading(true);

        const params = new URLSearchParams({ uri, from, to });
        const response = await this.#api.get(`/api/visitor-insights/creation-time-trend?${params}`);

        if (response?.success === false) {
            this.#trend.showError(response.error ?? ERR_GENERIC);
            return;
        }

        const data = response?.data ?? response;
        if (!Array.isArray(data) || data.length === 0) {
            this.#trend.showError(ERR_NO_DATA);
            return;
        }

        this.#trend.render(data);
    }
}