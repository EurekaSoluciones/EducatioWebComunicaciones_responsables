import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/components/family/finance-table.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const { formatFinanceMoney, normalizePaymentAmount, searchFinance, sortFinance, accountSearchText, paymentSearchText } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);

test('payment amounts remain decimal text without guessing thousands or silently multiplying values', () => {
    for (const [input, expected] of [['1234,50', '1234,50'], ['1234.50', '1234,50'], [' 25 ', '25'], ['0,01', '0,01'], ['1.234,50', null], ['1,234.50', null], ['1.234', null], ['0', null], ['-5', null], ['$ 20', null], ['1e3', null], ['', null]]) {
        assert.equal(normalizePaymentAmount(input), expected, input);
    }
    assert.equal(formatFinanceMoney(1234.5), '$ 1.234,50');
    assert.equal(formatFinanceMoney(-5), '$ -5,00');
});

test('search covers every loaded account row, statuses and displayed money without changing balances', () => {
    const rows = Array.from({ length: 125 }, (_, index) => ({ id: String(index), reference: String(index), description: 'Cuota escolar', installment: '10', date: '2026-10-20T00:00:00-03:00', dateLabel: '20/10/2026', amount: 2000, balance: 2000, state: 'upcoming' }));
    rows[111] = { ...rows[111], description: 'Inscripción', amount: 1234.5, balance: 0, state: 'paid' };
    assert.deepEqual(searchFinance(rows, 'inscripcion pagado 1.234,50', accountSearchText).map((row) => row.id), ['111']);
    assert.equal(searchFinance(rows, '', accountSearchText).length, 125);
    assert.equal(rows[111].balance, 0);
});

test('numeric sorting uses underlying money and dates and preserves stable duplicates', () => {
    const rows = [{ id: '0', amount: 10 }, { id: '1', amount: 2 }, { id: '2', amount: -1 }, { id: '3', amount: 2 }];
    assert.deepEqual(sortFinance(rows, (row) => row.amount, false).map((row) => row.id), ['2', '1', '3', '0']);
    assert.deepEqual(rows.map((row) => row.id), ['0', '1', '2', '3']);
    assert.deepEqual(sortFinance([{ id: '0', date: '2026-09-30T00:00:00-03:00' }, { id: '1', date: '2026-10-01T00:00:00-03:00' }], (row) => Date.parse(row.date), true).map((row) => row.id), ['1', '0']);
});

test('payments search includes receipt, method, exact date, amount and pending state', () => {
    const row = { id: '0', reference: 'A-00023', method: 'Débito', dateLabel: '30/10/2026', amount: 1250.75, pending: true };
    for (const query of ['00023', 'debito', '30/10/2026', '1.250,75', 'pendiente']) assert.equal(searchFinance([row], query, paymentSearchText).length, 1);
    assert.equal(searchFinance([row], 'confirmado', paymentSearchText).length, 0);
});
