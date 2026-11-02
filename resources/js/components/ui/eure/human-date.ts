export const HUMAN_DATE_EXACT_AFTER_DAYS = 30;
export const HUMAN_DATE_TIME_ZONE = 'America/Argentina/Buenos_Aires';
const DAY = 86_400_000;

/** Dates crossing the API must include an explicit timezone, never ambiguous local SQL strings. */
export function humanDateTimestamp(value: string | null | undefined): number | null {
    if (!value || !/^\d{4}-\d{2}-\d{2}T.*(?:Z|[+-]\d{2}:\d{2})$/i.test(value)) return null;
    const timestamp = Date.parse(value);
    return Number.isFinite(timestamp) ? timestamp : null;
}

export function startsWithExactDate(timestamp: number, now: number, exactAfterDays = HUMAN_DATE_EXACT_AFTER_DAYS): boolean {
    const days = Number.isFinite(exactAfterDays) && exactAfterDays >= 0 ? exactAfterDays : HUMAN_DATE_EXACT_AFTER_DAYS;
    return now - timestamp >= days * DAY;
}

export function relativeDateLabel(timestamp: number, now: number): string {
    const seconds = (timestamp - now) / 1000;
    if (Math.abs(seconds) < 1) return 'ahora';
    if (Math.abs(seconds) < 10) return seconds > 0 ? 'en unos segundos' : 'hace unos segundos';
    const units = [
        ['year', 365 * 86400], ['month', 30 * 86400], ['day', 86400],
        ['hour', 3600], ['minute', 60], ['second', 1],
    ] as const;
    const [unit, size] = units.find(([, size]) => Math.abs(seconds) >= size) ?? units[units.length - 1];
    return new Intl.RelativeTimeFormat('es', { numeric: 'always' }).format(Math.trunc(seconds / size), unit);
}

export function exactDateLabel(timestamp: number, timeZone = HUMAN_DATE_TIME_ZONE): string {
    const parts = new Intl.DateTimeFormat('es-AR', {
        timeZone, day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    }).formatToParts(timestamp);
    const get = (name: Intl.DateTimeFormatPartTypes) => parts.find((part) => part.type === name)?.value ?? '';
    return `${get('day')}/${get('month')}/${get('year')} ${get('hour')}:${get('minute')}`;
}
