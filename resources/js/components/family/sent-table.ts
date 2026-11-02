import type { FamilySender } from './FamilySenderQuickCard';
import { exactDateLabel, humanDateTimestamp } from '../ui/eure/human-date';

export type SentMessage = {
    id: string; number: string; subject: string; dateTime: string | null;
    readAt: string | null; answeredAt: string | null; replyUnread: boolean;
    url: string; recipient: FamilySender;
};
export type SentSort = 'id' | 'recipient' | 'subject' | 'dateTime' | 'status';
export function sentStatus(message: SentMessage): string {
    return !message.readAt ? 'Sin leer' : message.answeredAt ? 'Respondido' : 'Leído';
}
export function replyStatus(message: SentMessage): string {
    return message.answeredAt ? message.replyUnread ? 'Respuesta sin leer' : 'Respuesta leída' : '';
}
export function sentSearchText(message: SentMessage): string {
    const timestamp = humanDateTimestamp(message.dateTime);
    return [message.id, message.number, message.subject, message.recipient.name, message.recipient.role,
        sentStatus(message), replyStatus(message), message.dateTime ?? '', timestamp === null ? '' : exactDateLabel(timestamp)]
        .join(' ').normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('es');
}
const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });
export function sortSent(rows: SentMessage[], key: SentSort, descending: boolean): SentMessage[] {
    const value = (message: SentMessage): string | number => {
        if (key === 'id') return Number(message.id);
        if (key === 'dateTime') return humanDateTimestamp(message.dateTime) ?? 0;
        if (key === 'recipient') return message.recipient.name;
        if (key === 'status') return `${sentStatus(message)} ${replyStatus(message)}`;
        return message.subject;
    };
    return [...rows].sort((a, b) => {
        const left = value(a), right = value(b);
        const comparison = typeof left === 'number' && typeof right === 'number' ? left - right : collator.compare(String(left), String(right));
        return (comparison || Number(a.id) - Number(b.id)) * (descending ? -1 : 1);
    });
}
