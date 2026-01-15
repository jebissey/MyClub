import GoogleCalendar from './GoogleCalendar.js';
import OutlookCalendar from './OutlookCalendar.js';
import ICalCalendar from './ICalCalendar.js';

export default class CalendarController {

    init() {
        document
            .querySelectorAll('[data-event]')
            .forEach(container => this._bind(container));
    }

    _bind(container) {
        const event = JSON.parse(container.dataset.event);

        container
            .querySelector('.js-google-calendar')
            ?.addEventListener('click', () => {
                const url = new GoogleCalendar(event).getUrl();
                window.open(url, '_blank');
            });

        container
            .querySelector('.js-outlook-calendar')
            ?.addEventListener('click', () => {
                const url = new OutlookCalendar(event).getUrl();
                window.open(url, '_blank');
            });

        container
            .querySelector('.js-ical-calendar')
            ?.addEventListener('click', () => {
                new ICalCalendar(event).download();
            });
    }
}
