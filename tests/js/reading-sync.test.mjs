import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/components/family/reading-sync.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const { announceReading, watchReadingChanges, mergeReadStates } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);

const channels = new Set();
const storageWrites = [];
globalThis.window = new EventTarget();
globalThis.document = Object.assign(new EventTarget(), { hidden: false });
globalThis.localStorage = { setItem: (key, value) => storageWrites.push({ key, value }) };
globalThis.BroadcastChannel = class {
    constructor(name) { this.name = name; channels.add(this); }
    postMessage(data) { for (const other of channels) if (other !== this && other.name === this.name) other.onmessage?.({ data }); }
    close() { channels.delete(this); }
};
const emit = (target, type, values = {}) => target.dispatchEvent(Object.assign(new Event(type), values));

test('back/forward cache, focus and visibility refresh visible lists and clean up listeners', () => {
    let calls = 0;
    const unwatch = watchReadingChanges('ifes:42', () => calls++);
    emit(window, 'pageshow', { persisted: false });
    assert.equal(calls, 0);
    emit(window, 'pageshow', { persisted: true });
    emit(window, 'focus');
    assert.equal(calls, 2);
    document.hidden = true;
    emit(window, 'focus');
    emit(document, 'visibilitychange');
    announceReading('ifes:42');
    assert.equal(calls, 2);
    document.hidden = false;
    emit(document, 'visibilitychange');
    assert.equal(calls, 3);
    unwatch();
    emit(window, 'focus');
    emit(window, 'pageshow', { persisted: true });
    emit(document, 'visibilitychange');
    announceReading('ifes:42');
    assert.equal(calls, 3);
    assert.equal(channels.size, 0);
});

test('a loaded detail in another tab invalidates only its account and deduplicates storage fallback', () => {
    let calls = 0;
    const unwatch = watchReadingChanges('ifes:42', () => calls++);
    announceReading('ifes:43');
    announceReading('another-client:42');
    assert.equal(calls, 0);
    announceReading('ifes:42');
    assert.equal(calls, 1);
    const { key, value } = storageWrites.at(-1);
    emit(window, 'storage', { key, newValue: value });
    assert.equal(calls, 1);
    assert.deepEqual(Object.keys(JSON.parse(value)).sort(), ['nonce', 'version']);
    emit(window, 'storage', { key, newValue: '{broken' });
    emit(window, 'storage', { key, newValue: JSON.stringify({ version: 2, nonce: 'invalid' }) });
    assert.equal(calls, 1);
    unwatch();
    emit(window, 'storage', { key, newValue: JSON.stringify({ version: 1, nonce: 'after-cleanup' }) });
    assert.equal(calls, 1);
});

test('storage events work without BroadcastChannel and focus still works with both APIs unavailable', () => {
    const OriginalChannel = globalThis.BroadcastChannel;
    const originalStorage = globalThis.localStorage;
    globalThis.BroadcastChannel = class { constructor() { throw new Error('disabled'); } };
    let calls = 0;
    const unwatch = watchReadingChanges('ifes:42', () => calls++);
    announceReading('ifes:42');
    const { key, value } = storageWrites.at(-1);
    emit(window, 'storage', { key, newValue: value });
    assert.equal(calls, 1);
    globalThis.localStorage = { setItem() { throw new Error('disabled'); } };
    assert.doesNotThrow(() => announceReading('ifes:42'));
    emit(window, 'focus');
    assert.equal(calls, 2);
    unwatch();
    globalThis.BroadcastChannel = OriginalChannel;
    globalThis.localStorage = originalStorage;
});

test('server states patch loaded rows without replacing content/order or adding new communications', () => {
    const rows = [{ id: '3', subject: 'Salida', unread: true }, { id: '1', subject: 'Consulta', unread: true }, { id: '2', subject: 'Retirada', unread: true }];
    const updated = mergeReadStates(rows, [{ id: '1', unread: true }, { id: '3', unread: false }, { id: '9', unread: true }]);
    assert.deepEqual(updated, [{ id: '3', subject: 'Salida', unread: false }, { id: '1', subject: 'Consulta', unread: true }]);
    assert.deepEqual(updated.filter((row) => row.unread).map((row) => row.id), ['1']);
    assert.equal(rows[0].unread, true);
    const sent = [{ id: '5', subject: 'Mensaje', readAt: null, answeredAt: null, replyUnread: false }];
    assert.deepEqual(mergeReadStates(sent, [{ id: '5', readAt: '2026-11-01T10:00:00-03:00', answeredAt: '2026-11-01T11:00:00-03:00', replyUnread: true }])[0], {
        ...sent[0], readAt: '2026-11-01T10:00:00-03:00', answeredAt: '2026-11-01T11:00:00-03:00', replyUnread: true,
    });
});
