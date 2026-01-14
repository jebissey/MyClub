import CalendarService from './CalendarService.js';
import DateUtils from '../utils/DateUtils.js';

export default class ICalCalendar extends CalendarService {

    download() {
        const content = `
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//YourApp//EN
BEGIN:VEVENT
UID:${Date.now()}@yourapp.com
DTSTAMP:${DateUtils.formatForICS(new Date())}
DTSTART:${DateUtils.formatForICS(this.startTime)}
DTEND:${DateUtils.formatForICS(this.endTime)}
SUMMARY:${this.event.Summary}
DESCRIPTION:${this.getDetailsText().replace(/\n/g, '\\n')}
LOCATION:${this.event.Location}
END:VEVENT
END:VCALENDAR`.trim();

        const blob = new Blob([content], { type: 'text/calendar;charset=utf-8' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `${this.event.Summary.replace(/\s+/g, '_')}.ics`;
        a.click();

        URL.revokeObjectURL(url);
    }
}
