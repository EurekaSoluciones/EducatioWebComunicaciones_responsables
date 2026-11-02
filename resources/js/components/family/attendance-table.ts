export type AttendanceRow = { id: string; date: string; dateLabel: string; type: string; weight: number; notes: string };
export type AttendanceSort = 'date' | 'type' | 'weight' | 'notes';
const normalize = (value: string) => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('es');
const collator = new Intl.Collator('es', { sensitivity: 'base', numeric: true });
const weightFormat = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
export const formatAttendanceWeight = (weight: number) => weightFormat.format(weight);

export function attendanceRows(rows: AttendanceRow[], search: string, key: AttendanceSort, descending: boolean): AttendanceRow[] {
    const words = normalize(search).trim().split(/\s+/).filter(Boolean);
    const filtered = rows.filter((row) => {
        const text = normalize(`${row.dateLabel} ${row.type} ${row.weight} ${formatAttendanceWeight(row.weight)} ${row.notes}`);
        return words.every((word) => text.includes(word));
    });
    return filtered.sort((a, b) => {
        const difference = key === 'weight' ? a.weight - b.weight
            : key === 'date' ? Date.parse(a.date) - Date.parse(b.date)
            : collator.compare(a[key], b[key]);
        return difference * (descending ? -1 : 1) || Number(a.id) - Number(b.id);
    });
}
