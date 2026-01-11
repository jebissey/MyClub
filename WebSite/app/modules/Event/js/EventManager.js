import ApiClient from "../../Common/js/ApiClient.js";
import EventFormManager from "./modules/EventFormManager.js";
import EventTableManager from "./modules/EventTableManager.js";
import EmailManager from "./modules/EmailManager.js";
import FilterManager from "./modules/FilterManager.js";

class EventManager {
    constructor() {
        this.api = new ApiClient();
        this.eventForm = null;
        this.eventTable = null;
        this.emailManager = null;
        this.filterManager = null;
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.eventForm = new EventFormManager(this.api);
            this.eventTable = new EventTableManager(this.api);
            this.emailManager = new EmailManager(this.api);
            this.filterManager = new FilterManager();

            this.eventForm.init();
            this.eventTable.init();
            this.emailManager.init();
            this.filterManager.init();
        });
    }
}

const app = new EventManager();
app.init();
