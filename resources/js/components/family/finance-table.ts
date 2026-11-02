export type AccountState = 'paid' | 'overdue' | 'today' | 'month' | 'upcoming';
export type AccountRow = { id: string; reference: string; description: string; installment: string; date: string; dateLabel: string; amount: number; balance: number; state: AccountState };
export type PaymentRow = { id: string; reference: string; method: string; date: string | null; dateLabel: string | null; amount: number; pending: boolean; url: string };
export const accountStates: Record<AccountState, string> = { paid: 'Pagado', overdue: 'Vencido', today: 'Vence hoy', month: 'Vence este mes', upcoming: 'A vencer' };
const money = new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
export const formatFinanceMoney = (value: number) => `$ ${money.format(value)}`;
const fold = (value: string) => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('es');
const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });

export function searchFinance<T>(rows: T[], search: string, text: (row: T) => string): T[] {
    const words = fold(search).trim().split(/\s+/).filter(Boolean);
    return rows.filter((row) => words.every((word) => fold(text(row)).includes(word)));
}
export function sortFinance<T extends { id: string }>(rows: T[], value: (row: T) => string | number, descending: boolean): T[] {
    return [...rows].sort((a, b) => {
        const left = value(a), right = value(b);
        const comparison = typeof left === 'number' && typeof right === 'number' ? left - right : collator.compare(String(left), String(right));
        return comparison * (descending ? -1 : 1) || Number(a.id) - Number(b.id);
    });
}
export const accountSearchText = (row: AccountRow) => `${row.reference} ${row.description} ${row.installment} ${row.dateLabel} ${row.amount} ${formatFinanceMoney(row.amount)} ${row.balance} ${formatFinanceMoney(row.balance)} ${accountStates[row.state]}`;
export const paymentSearchText = (row: PaymentRow) => `${row.reference} ${row.method} ${row.dateLabel ?? ''} ${row.amount} ${formatFinanceMoney(row.amount)} ${row.pending ? 'Pendiente' : 'Confirmado'}`;

// Preserve decimal digits as text; never guess whether a grouping separator was a decimal point.
export function normalizePaymentAmount(raw: string): string | null {
    const value = raw.trim();
    if (!/^\d+(?:[.,]\d{1,2})?$/.test(value)) return null;
    const numeric = Number(value.replace(',', '.'));
    return Number.isFinite(numeric) && numeric > 0 ? value.replace('.', ',') : null;
}
