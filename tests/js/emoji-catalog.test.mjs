import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const root = new URL('../../', import.meta.url);
const compile = (source) => ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const dataUrl = (source) => `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
const catalog = JSON.parse(await readFile(new URL('resources/emoji/catalog.json', root), 'utf8'));
const labels = JSON.parse(await readFile(new URL('resources/emoji/labels.es.json', root), 'utf8'));
let source = compile(await readFile(new URL('resources/js/components/ui/eure/emoji-catalog.ts', root), 'utf8'));
source = source.replace("'../../../../emoji/catalog.json'", JSON.stringify(dataUrl(`export default ${JSON.stringify(catalog)};`)))
    .replace("'../../../../emoji/labels.es.json'", JSON.stringify(dataUrl(`export default ${JSON.stringify(labels)};`)))
    .replace("'./rich-text-escape'", JSON.stringify(dataUrl(compile(await readFile(new URL('resources/js/components/ui/eure/rich-text-escape.ts', root), 'utf8')))));
const { emojiCatalog, getEmoji, searchEmojis, recentEmojis, emojiHtml, emojiForUrl, emojiLabel } = await import(dataUrl(source));
const base = 'https://familias.example/assets/eure/emoji/v1';

test('every legacy catalog entry has an individual 56px PNG and a Spanish label', async () => {
    assert.equal(emojiCatalog.length, 845);
    assert.equal(new Set(emojiCatalog.map((item) => item.id)).size, 845);
    for (const emoji of emojiCatalog) {
        assert.ok(labels[emoji.aliases[0]], emoji.id);
        const png = await readFile(new URL(`public/assets/eure/emoji/v1/${emoji.id}.png`, root));
        assert.deepEqual([...png.subarray(0, 8)], [137, 80, 78, 71, 13, 10, 26, 10]);
        assert.equal(png.readUInt32BE(16), 56, emoji.id);
        assert.equal(png.readUInt32BE(20), 56, emoji.id);
    }
});
test('search covers Spanish accents, aliases, Unicode and the five categories', () => {
    assert.ok(searchEmojis('corazon').some((item) => item.id === '2764'));
    assert.ok(searchEmojis('LIBROS').some((item) => item.id === '1f4da'));
    assert.ok(searchEmojis('smile').some((item) => item.id === '1f604'));
    assert.ok(searchEmojis('❤️').some((item) => item.id === '2764'));
    assert.equal(searchEmojis('nombre inexistente').length, 0);
    for (let category = 0; category < 5; category++) assert.ok(searchEmojis('', category).every((item) => item.category === category));
});
test('recent entries reject corrupt storage and unknown IDs, deduplicate and cap at 24', () => {
    assert.deepEqual(recentEmojis('{broken'), []);
    assert.deepEqual(recentEmojis('{}'), []);
    assert.deepEqual(recentEmojis('["1f604","unknown","1f604",42,null]').map((emoji) => emoji.id), ['1f604']);
    assert.equal(recentEmojis(JSON.stringify(emojiCatalog.slice(0, 30).map((emoji) => emoji.id))).length, 24);
});
test('emoji images use an exact catalog allowlist with fixed inline sizing', () => {
    const emoji = getEmoji('1f604');
    assert.equal(emojiLabel(emoji), 'Sonrisa');
    const html = emojiHtml(emoji, base);
    assert.match(html, /data-eure-emoji="1f604"/);
    assert.match(html, /width:24px;height:24px/);
    assert.match(html, /alt="Sonrisa"/);
    assert.equal(emojiForUrl(`${base}/1f604.png`, base), emoji);
    for (const url of [`${base}/unknown.png`, `${base}/../1f604.png`, `${base}/1f604.png?x=1`, 'https://evil.example/assets/eure/emoji/v1/1f604.png', `${base}/1f604.svg`]) assert.equal(emojiForUrl(url, base), undefined);
});
