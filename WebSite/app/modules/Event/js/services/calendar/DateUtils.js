export default class DateUtils {

    static addSeconds(date, seconds) {
        return new Date(date.getTime() + seconds * 1000);
    }

    static formatForGoogle(date) {
        return date.toISOString().replace(/-|:|\.\d+/g, '');
    }

    static formatForICS(date) {
        return date
            .toISOString()
            .replace(/-|:|\.\d+/g, '')
            .replace(/(\.\d+)?Z$/, 'Z');
    }
}
