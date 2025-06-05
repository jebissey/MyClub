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
    var detailsText = event.Description + '\n\nPlus d\'infos : ' + window.location.href;
    var details = 'details=' + encodeURIComponent(detailsText);
    var location = 'location=' + encodeURIComponent(event.Location);

    return base + '?' + 'action=TEMPLATE' + '&' + text + '&' + dates + '&' + details + '&' + location;
}

function createOutlookCalendarUrl(event) {
    var base = 'https://outlook.live.com/calendar/0/deeplink/compose';
    var subject = 'subject=' + encodeURIComponent(event.Summary);
    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);
    var startdt = 'startdt=' + encodeURIComponent(startTime.toISOString());
    var enddt = 'enddt=' + encodeURIComponent(endTime.toISOString());
    var location = 'location=' + encodeURIComponent(event.Location);
    var bodyText = event.Description + '\n\nPlus d\'infos : ' + window.location.href;
    var body = 'body=' + encodeURIComponent(bodyText);

    return base + '?' + subject + '&' + startdt + '&' + enddt + '&' + body + '&' + location;
}

function downloadICalFile(event) {
    var startTime = new Date(event.StartTime);
    var endTime = new Date(startTime.getTime() + event.Duration * 1000);

    function formatDateICS(date) {
        return date.toISOString().replace(/-|:|\.\d+/g, '').replace(/(\.\d+)?Z$/, 'Z');
    }

    var icsContent =
        'BEGIN:VCALENDAR\n' +
        'VERSION:2.0\n' +
        'PRODID:-//YourAppName//EN\n' +
        'BEGIN:VEVENT\n' +
        'UID:' + Date.now() + '@yourapp.com\n' +
        'DTSTAMP:' + formatDateICS(new Date()) + '\n' +
        'DTSTART:' + formatDateICS(startTime) + '\n' +
        'DTEND:' + formatDateICS(endTime) + '\n' +
        'SUMMARY:' + event.Summary + '\n' +
        'DESCRIPTION:' + event.Description.replace(/\n/g, '\\n') + '\\n\\nPlus d\'infos : ' + window.location.href + '\n' +
        'LOCATION:' + event.Location + '\n' +
        'END:VEVENT\n' +
        'END:VCALENDAR';

    var blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    var url = URL.createObjectURL(blob);

    var a = document.createElement('a');
    a.href = url;
    a.download = event.Summary.replace(/\s+/g, '_') + '.ics';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}