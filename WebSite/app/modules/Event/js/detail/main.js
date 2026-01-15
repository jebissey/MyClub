import SupplyController from '../services/supply/SupplyController.js';
import CalendarController from '../services/calendar/CalendarController.js';

document.addEventListener('DOMContentLoaded', () => {
    new SupplyController();
    new CalendarController().init();
});
