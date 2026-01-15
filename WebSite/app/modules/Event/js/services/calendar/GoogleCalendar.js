import CalendarService from './CalendarService.js';
import DateUtils from './DateUtils.js';

export default class GoogleCalendar extends CalendarService {

    getUrl() {
        const base = 'https://calendar.google.com/calendar/render';

        const dates =
            `${DateUtils.formatForGoogle(this.startTime)}/` +
            `${DateUtils.formatForGoogle(this.endTime)}`;

        return `${base}?action=TEMPLATE` +
            `&text=${encodeURIComponent(this.event.Summary)}` +
            `&dates=${encodeURIComponent(dates)}` +
            `&details=${encodeURIComponent(this.getDetailsText())}` +
            `&location=${encodeURIComponent(this.event.Location)}`;
    }
}
