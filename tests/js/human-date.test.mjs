import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

// Use the existing TypeScript compiler; no test runtime or dependencies need installing.
const base = '../../resources/js/components/ui/eure/';
const source = await readFile(new URL(`${base}human-date.ts`, import.meta.url), 'utf8');
const compile = (source) => ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022, jsx: ts.JsxEmit.ReactJSX } }).outputText;
const dataUrl = (source) => `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
const helpersUrl = dataUrl(compile(source));
const { humanDateTimestamp, startsWithExactDate, relativeDateLabel, exactDateLabel } = await import(helpersUrl);
const componentSource = compile(await readFile(new URL(`${base}EureHumanDate.tsx`, import.meta.url), 'utf8'))
    .replace(/from ['"]react['"]/g, `from '${import.meta.resolve('react')}'`)
    .replace(/from ['"]react\/jsx-runtime['"]/g, `from '${import.meta.resolve('react/jsx-runtime')}'`)
    .replace(/from ['"].\/human-date['"]/g, `from '${helpersUrl}'`);
const { default: EureHumanDate } = await import(dataUrl(componentSource));
const now = Date.parse('2026-10-29T12:00:00Z');
const day = 86_400_000;

test('only timezone-qualified valid timestamps can become interactive dates', () => {
    assert.equal(humanDateTimestamp(null), null);
    assert.equal(humanDateTimestamp('not a date'), null);
    assert.equal(humanDateTimestamp('2026-10-29 09:00:00'), null);
    assert.equal(humanDateTimestamp('2026-10-29T09:00:00-03:00'), now);
    assert.equal(humanDateTimestamp('2026-10-29T12:00:00Z'), now);
});

test('the exact-date threshold includes the boundary and can be customized', () => {
    assert.equal(startsWithExactDate(now - 30 * day + 1, now), false);
    assert.equal(startsWithExactDate(now - 30 * day, now), true);
    assert.equal(startsWithExactDate(now - 7 * day, now, 7), true);
    assert.equal(startsWithExactDate(now - 7 * day + 1, now, 7), false);
    assert.equal(startsWithExactDate(now + 31 * day, now), false);
    assert.equal(startsWithExactDate(now, now, 0), true);
    assert.equal(startsWithExactDate(now - day, now, -1), false);
});

test('relative labels handle recent, past and future timestamps in Spanish', () => {
    assert.equal(relativeDateLabel(now, now), 'ahora');
    assert.equal(relativeDateLabel(now - 3000, now), 'hace unos segundos');
    assert.equal(relativeDateLabel(now - 2 * day, now), 'hace 2 días');
    assert.equal(relativeDateLabel(now + 3600_000, now), 'dentro de 1 hora');
});

test('exact labels preserve the configured timezone and midnight', () => {
    assert.equal(exactDateLabel(now), '29/10/2026 09:00');
    assert.equal(exactDateLabel(Date.parse('2026-10-29T02:00:00Z')), '28/10/2026 23:00');
    assert.equal(exactDateLabel(Date.parse('2026-10-29T03:00:00Z')), '29/10/2026 00:00');
    assert.equal(exactDateLabel(now, 'UTC'), '29/10/2026 12:00');
});

test('the component renders a keyboard button, starts in the correct mode and handles missing dates', () => {
    const realNow = Date.now;
    Date.now = () => now;
    try {
        const recent = renderToStaticMarkup(createElement(EureHumanDate, { value: '2026-10-27T12:00:00Z' }));
        assert.match(recent, /<button type="button"/);
        assert.match(recent, /aria-pressed="false"/);
        assert.match(recent, /Mostrar fecha exacta/);
        assert.match(recent, /hace 2 días/);
        assert.doesNotMatch(recent, /href=/);
        const old = renderToStaticMarkup(createElement(EureHumanDate, { value: '2026-09-29T12:00:00Z' }));
        assert.match(old, /aria-pressed="true"/);
        assert.match(old, /29\/09\/2026 09:00/);
        assert.match(old, /Mostrar fecha relativa/);
        const missing = renderToStaticMarkup(createElement(EureHumanDate, { value: null }));
        assert.match(missing, /^<span[^>]*>—<\/span>$/);
        assert.doesNotMatch(missing, /button|time datetime/);
    } finally { Date.now = realNow; }
});
