import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const compile = (source) => ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const dataUrl = (source) => `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
const datesUrl = dataUrl(compile(await readFile(new URL('../../resources/js/components/ui/eure/human-date.ts', import.meta.url), 'utf8')));
const source = compile(await readFile(new URL('../../resources/js/components/family/received-table.ts', import.meta.url), 'utf8'))
    .replace(/from ['"]\.\.\/ui\/eure\/human-date['"]/g, `from '${datesUrl}'`);
const { receivedSearchText, filterReceived, sortReceived, paginateReceived } = await import(dataUrl(source));
const makeRow = (id, overrides = {}) => ({ id: String(id), number: String(id).padStart(5, '0'), subject: `Circular ${id}`, type: 'Aviso', dateTime: '2026-10-27T09:00:00-03:00', unread: false, attachmentCount: 0, url: `/comunicaciones/${id}`, sender: { name: 'María López', avatar: null }, ...overrides });
const index = (rows) => rows.map((message) => ({ message, searchText: receivedSearchText(message) }));

test('search finds a match beyond the initial page in the full loaded set', () => {
    const rows = Array.from({ length: 126 }, (_, i) => makeRow(i + 1));
    rows[110] = makeRow(111, { subject: 'Excursión al museo' });
    assert.equal(paginateReceived(rows, 1, 10).rows.some((row) => row.id === '111'), false);
    const found = filterReceived(index(rows), 'excursion museo').map((row) => row.message);
    assert.deepEqual(paginateReceived(found, 1, 10).rows.map((row) => row.id), ['111']);
    assert.equal(filterReceived(index(rows), '').length, 126);
});

test('local search covers names, formatted numbers, dates, type and state', () => {
    const rows = index([makeRow(7, { unread: true, type: 'Autorización' })]);
    for (const query of ['maria lopez', '00007', '27/10/2026', 'autorizacion', 'sin leer']) {
        assert.equal(filterReceived(rows, query).length, 1, query);
    }
    assert.equal(filterReceived(rows, 'otra cosa').length, 0);
});

test('all column sort keys sort underlying values and leave loaded data untouched', () => {
    const rows = [makeRow(2, { subject: 'Zorro', type: 'Z', unread: true, sender: { name: 'Zoe' }, dateTime: '2026-10-28T09:00:00-03:00' }), makeRow(10, { subject: 'Árbol', type: 'A', sender: { name: 'Ana' } })];
    assert.deepEqual(sortReceived(rows, 'id', true).map((row) => row.id), ['10', '2']);
    for (const key of ['sender', 'subject', 'type', 'unread', 'dateTime']) {
        assert.deepEqual(sortReceived(rows, key, false).map((row) => row.id), ['10', '2'], key);
    }
    assert.deepEqual(rows.map((row) => row.id), ['2', '10']);
});

test('local pagination covers the full result and clamps after filtering or resizing', () => {
    const rows = Array.from({ length: 126 }, (_, i) => makeRow(i + 1));
    const last = paginateReceived(rows, 13, 10);
    assert.equal(last.from, 121); assert.equal(last.to, 126); assert.equal(last.rows.length, 6);
    assert.equal(paginateReceived(rows, 13, 50).page, 3);
    const empty = paginateReceived([], 13, 10);
    assert.equal(empty.page, 1); assert.equal(empty.from, 0); assert.equal(empty.to, 0);
});
