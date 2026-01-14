import CalendarService from './CalendarService.js';

export default class OutlookCalendar extends CalendarService {

    getUrl() {
        const base = 'https://outlook.live.com/calendar/0/deeplink/compose';

        return `${base}?subject=${encodeURIComponent(this.event.Summary)}` +
            `&startdt=${encodeURIComponent(this.startTime.toISOString())}` +
            `&enddt=${encodeURIComponent(this.endTime.toISOString())}` +
            `&body=${encodeURIComponent(this.getDetailsText())}` +
            `&location=${encodeURIComponent(this.event.Location)}`;
    }
}
