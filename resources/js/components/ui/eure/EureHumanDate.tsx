import { useEffect, useState } from 'react';
import { exactDateLabel, HUMAN_DATE_EXACT_AFTER_DAYS, HUMAN_DATE_TIME_ZONE, humanDateTimestamp, relativeDateLabel, startsWithExactDate } from './human-date';

export interface EureHumanDateProps {
    /** ISO 8601 including Z or an explicit UTC offset. */
    value: string | null | undefined;
    exactAfterDays?: number;
    timeZone?: string;
    className?: string;
    fallback?: string;
}

// Local EureUI extension for the project's interactive human-date convention.
export default function EureHumanDate({ value, exactAfterDays = HUMAN_DATE_EXACT_AFTER_DAYS, timeZone = HUMAN_DATE_TIME_ZONE, className = '', fallback = '—' }: EureHumanDateProps) {
    const timestamp = humanDateTimestamp(value);
    if (timestamp === null) return <span className={className}>{fallback}</span>;
    return <HumanDateValue key={`${timestamp}:${exactAfterDays}:${timeZone}`} timestamp={timestamp} exactAfterDays={exactAfterDays} timeZone={timeZone} className={className} />;
}

function HumanDateValue({ timestamp, exactAfterDays, timeZone, className }: {
    timestamp: number; exactAfterDays: number; timeZone: string; className: string;
}) {
    const [now, setNow] = useState(Date.now);
    const [chosenExact, setChosenExact] = useState<boolean | null>(null);
    useEffect(() => {
        const update = () => { if (!document.hidden) setNow(Date.now()); };
        const timer = window.setInterval(update, 60_000);
        window.addEventListener('focus', update);
        document.addEventListener('visibilitychange', update);
        return () => {
            window.clearInterval(timer);
            window.removeEventListener('focus', update);
            document.removeEventListener('visibilitychange', update);
        };
    }, []);
    const showExact = chosenExact ?? startsWithExactDate(timestamp, now, exactAfterDays);
    const exact = exactDateLabel(timestamp, timeZone);
    const relative = relativeDateLabel(timestamp, now);
    const label = showExact ? exact : relative;
    const action = showExact ? 'Mostrar fecha relativa' : 'Mostrar fecha exacta';
    return <button type="button" className={`eureui-human-date ${className}`} aria-label={`${label}. ${action}`} aria-pressed={showExact}
        title={showExact ? `${relative} · ${action}` : `${exact} · ${action}`}
        onClick={(event) => { event.stopPropagation(); setChosenExact(!showExact); setNow(Date.now()); }}>
        <time dateTime={new Date(timestamp).toISOString()}>{label}</time>
    </button>;
}
