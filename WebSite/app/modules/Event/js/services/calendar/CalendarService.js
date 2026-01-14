export default class CalendarService {

    constructor(event) {
        this.event = event;
        this.startTime = new Date(event.StartTime);
        this.endTime = new Date(
            this.startTime.getTime() + event.Duration * 1000
        );
    }

    getDetailsText() {
        return `${this.event.Description}\n\nPlus d'infos : ${window.location.href}`;
    }
}
