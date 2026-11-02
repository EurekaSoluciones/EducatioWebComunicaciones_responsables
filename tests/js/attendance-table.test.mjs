import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/components/family/attendance-table.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const { attendanceRows, formatAttendanceWeight } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);
const row = (id, overrides = {}) => ({ id: String(id), date: '2026-10-27T00:00:00-03:00', dateLabel: '27/10/2026', type: 'Falta', weight: 1, notes: '', ...overrides });

test('search includes all loaded rows, accents, calendar dates, notes and fractional weights', () => {
    const rows = Array.from({ length: 126 }, (_, index) => row(index));
    rows[111] = row(111, { type: 'Tardanza', weight: 0.25, notes: 'Certificación médica' });
    for (const search of ['certificacion medica', 'tardanza 0,25', '27/10/2026 0.25']) {
        assert.deepEqual(attendanceRows(rows, search, 'date', true).map((item) => item.id), ['111']);
    }
    assert.equal(attendanceRows(rows, '', 'date', true).length, 126);
    assert.equal(attendanceRows(rows, 'inexistente', 'date', true).length, 0);
});

test('dates and weights sort by underlying values, with stable ties and no input mutation', () => {
    const rows = [row(0, { date: '2026-09-30T00:00:00-03:00', dateLabel: '30/09/2026', weight: 10 }), row(1, { date: '2026-10-01T00:00:00-03:00', dateLabel: '01/10/2026', weight: 2 }), row(2, { weight: 0 }), row(3, { weight: 0 })];
    assert.deepEqual(attendanceRows(rows, '', 'date', true).map((item) => item.id), ['2', '3', '1', '0']);
    assert.deepEqual(attendanceRows(rows, '', 'weight', false).map((item) => item.id), ['2', '3', '1', '0']);
    assert.deepEqual(rows.map((item) => item.id), ['0', '1', '2', '3']);
    assert.equal(formatAttendanceWeight(0.25), '0,25');
    assert.equal(formatAttendanceWeight(0), '0');
});

test('types and observations remain sortable and zero-weight rows remain visible', () => {
    const rows = [row(0, { type: 'Tardanza', notes: 'Z', weight: 0.25 }), row(1, { type: 'Justificada', notes: 'Á', weight: 0 })];
    for (const column of ['type', 'notes']) assert.deepEqual(attendanceRows(rows, '', column, false).map((item) => item.id), ['1', '0']);
    assert.equal(attendanceRows(rows, 'justificada', 'date', true)[0].weight, 0);
});
