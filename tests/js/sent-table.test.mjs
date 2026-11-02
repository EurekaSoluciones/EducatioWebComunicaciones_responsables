import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const compile = (source) => ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const dataUrl = (source) => `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
const datesUrl = dataUrl(compile(await readFile(new URL('../../resources/js/components/ui/eure/human-date.ts', import.meta.url), 'utf8')));
const readModule = async (file) => import(dataUrl(compile(await readFile(new URL(`../../resources/js/components/family/${file}`, import.meta.url), 'utf8'))
    .replace(/from ['"]\.\.\/ui\/eure\/human-date['"]/g, `from '${datesUrl}'`)));
const { sentStatus, replyStatus, sentSearchText, sortSent } = await readModule('sent-table.ts');
const { filterReceived, paginateReceived } = await readModule('received-table.ts');
const row = (id, values = {}) => ({ id: String(id), number: String(id).padStart(5, '0'), subject: `Consulta ${id}`,
    dateTime: '2026-10-27T08:00:00-03:00', readAt: null, answeredAt: null, replyUnread: false,
    url: `/comunicaciones/e/show/${id}`, recipient: { name: 'María López', avatar: null }, ...values });

test('institution reading and family response reading keep separate legacy states', () => {
    assert.equal(sentStatus(row(1)), 'Sin leer');
    assert.equal(sentStatus(row(1, { readAt: '2026-10-28T08:00:00-03:00' })), 'Leído');
    const answered = row(2, { readAt: '2026-10-28T08:00:00-03:00', answeredAt: '2026-10-29T08:00:00-03:00', replyUnread: true });
    assert.equal(sentStatus(answered), 'Respondido');
    assert.equal(replyStatus(answered), 'Respuesta sin leer');
    assert.equal(replyStatus({ ...answered, replyUnread: false }), 'Respuesta leída');
    assert.equal(replyStatus(row(1)), '');
    assert.equal(sentStatus({ ...answered, readAt: null }), 'Sin leer');
});

test('search and pagination use all loaded messages including matches beyond page one', () => {
    const messages = Array.from({ length: 125 }, (_, i) => row(i + 1));
    messages[114] = row(115, { subject: 'Autorización excursión' });
    const indexed = messages.map((message) => ({ message, searchText: sentSearchText(message) }));
    const matches = filterReceived(indexed, 'autorizacion excursion').map(({ message }) => message);
    assert.deepEqual(paginateReceived(matches, 1, 10).rows.map(({ id }) => id), ['115']);
    for (const query of ['maria lopez', '27/10/2026', 'sin leer']) assert.equal(filterReceived(indexed, query).length, 125);
    assert.equal(filterReceived(indexed, '00115').length, 1);
    assert.equal(filterReceived(indexed, '').length, 125);
    assert.equal(paginateReceived(messages, 13, 10).rows.length, 5);
});

test('sorting uses numeric IDs, real dates and recipient names without changing loaded rows', () => {
    const messages = [row(9, { recipient: { name: 'Zoe', avatar: null } }), row(100, { dateTime: '2025-01-01T08:00:00-03:00' })];
    assert.deepEqual(sortSent(messages, 'id', true).map(({ id }) => id), ['100', '9']);
    assert.deepEqual(sortSent(messages, 'recipient', false).map(({ id }) => id), ['100', '9']);
    assert.deepEqual(sortSent(messages, 'dateTime', true).map(({ id }) => id), ['9', '100']);
    assert.deepEqual(messages.map(({ id }) => id), ['9', '100']);
});
