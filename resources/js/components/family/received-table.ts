import { exactDateLabel, humanDateTimestamp } from '../ui/eure/human-date';
import type { FamilySender } from './FamilySenderQuickCard';

export type ReceivedMessage = {
    id: string; number: string; subject: string; preview: string; type: string; dateTime: string | null;
    unread: boolean; attachmentCount: number; url: string; sender: FamilySender;
};
export type ReceivedSort = 'id' | 'sender' | 'subject' | 'type' | 'unread' | 'dateTime';
const normalize = (value: string) => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('es');
const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });

export function receivedSearchText(message: ReceivedMessage): string {
    const timestamp = humanDateTimestamp(message.dateTime);
    return normalize([message.id, message.number, message.subject, message.preview, message.type, message.sender.name,
        message.unread ? 'sin leer' : 'leída', message.dateTime ?? '', timestamp === null ? '' : exactDateLabel(timestamp),
        message.attachmentCount ? `${message.attachmentCount} adjuntos` : '',
    ].join(' '));
}

export function filterReceived<T extends { searchText: string }>(rows: T[], search: string): T[] {
    const terms = normalize(search).trim().split(/\s+/).filter(Boolean);
    return terms.length ? rows.filter((row) => terms.every((term) => row.searchText.includes(term))) : rows;
}

export function sortReceived(rows: ReceivedMessage[], key: ReceivedSort, descending: boolean): ReceivedMessage[] {
    const value = (row: ReceivedMessage): string | number => {
        if (key === 'id') return Number(row.id);
        if (key === 'dateTime') return humanDateTimestamp(row.dateTime) ?? 0;
        if (key === 'unread') return Number(row.unread);
        if (key === 'sender') return row.sender.name;
        return row[key];
    };
    return [...rows].sort((a, b) => {
        const left = value(a), right = value(b);
        const comparison = typeof left === 'number' && typeof right === 'number' ? left - right : collator.compare(String(left), String(right));
        return (comparison || Number(a.id) - Number(b.id)) * (descending ? -1 : 1);
    });
}

export function paginateReceived<T>(rows: T[], page: number, size: number) {
    const lastPage = Math.max(1, Math.ceil(rows.length / size));
    const currentPage = Math.max(1, Math.min(page, lastPage));
    const start = (currentPage - 1) * size;
    return { rows: rows.slice(start, start + size), page: currentPage, lastPage,
        from: rows.length ? start + 1 : 0, to: Math.min(start + size, rows.length), total: rows.length };
}
