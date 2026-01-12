import ApiClient from "../../Common/js/ApiClient.js";
import EventTableManager from "./modules/EventTableManager.js";
import EmailManager from "./modules/EmailManager.js";
import FilterManager from "./modules/FilterManager.js";

class EventManager {
    constructor() {
        this.api = new ApiClient();
        this.eventTable = new EventTableManager(this.api);
        this.emailManager = new EmailManager(this.api);
        this.filterManager = new FilterManager();
    }
}

new EventManager();
