function createGoogleCalendarUrl(event) {

    function formatDateForGoogle(startTime, endTime) {
        function formatDate(date) {
            return date.toISOString().replace(/-|:|\.\d+/g, '');
        }
        return formatDate(startTime) + '/' + formatDate(endTime);
    }

    var base = 'https://calendar.google.com/calendar/render';
    var text = 'text=' + encodeURIComponent(event.Summary);
    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);
    var dates = 'dates=' + encodeURIComponent(formatDateForGoogle(startTime, endTime));
    var details = 'details=' + encodeURIComponent(event.Description) + '&link=' + encodeURIComponent(window.location.href);
    var location = 'location=' + encodeURIComponent(event.Location);

    return base + '?' + 'action=TEMPLATE' + '&' + text + '&' + dates + '&' + details + '&' + location;
}

function createOutlookCalendarUrl(event) {
    var base = 'https://outlook.office.com/calendar/action/compose';
    var subject = 'subject=' + encodeURIComponent(event.Summary);
    var startdt = 'startdt=' + encodeURIComponent(new Date(event.StartTime).toISOString());
    var enddt = 'enddt=' + encodeURIComponent(new Date(new Date(event.StartTime).getTime() + event.Duration * 1000).toISOString());
    var body = 'body=' + encodeURIComponent(event.Description);
    var location = 'location=' + encodeURIComponent(event.Location);

    return base + '?' + subject + '&' + startdt + '&' + enddt + '&' + body + '&' + location;
}

function generateICalFile(event) {
    function formatICalDate(date) {
        return date.toISOString().replace(/-|:|\.\d+/g, '').slice(0, -1) + 'Z';
    }

    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);
    var now = formatICalDate(new Date());
    var uid = 'event-' + event.Id + '-' + now + '@bnw.com';

    return [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//YourApp//Calendar//FR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' + uid,
        'DTSTAMP:' + now,
        'DTSTART:' + formatICalDate(startTime),
        'DTEND:' + formatICalDate(endTime),
        'SUMMARY:' + event.Summary,
        'DESCRIPTION:' + event.Description.replace(/\n/g, '\\n'),
        'LOCATION:' + event.Location,
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\r\n');
}