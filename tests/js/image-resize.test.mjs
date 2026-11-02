import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/components/ui/eure/image-resize.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const { proportionalImageWidth, resizedImageHtml, commitImageWidth } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);

test('diagonal resizing preserves the ratio for landscape and portrait images', () => {
    assert.equal(proportionalImageWidth(400, 200, 100, 50, 900), 500);
    assert.equal(proportionalImageWidth(200, 400, 50, 100, 900), 250);
    assert.equal(proportionalImageWidth(400, 200, -100, -50, 900), 300);
    assert.ok(proportionalImageWidth(400, 200, 0, 100, 900) > 400);
});
test('resizing clamps to container width and a usable minimum, including tiny containers', () => {
    assert.equal(proportionalImageWidth(400, 200, 1000, 500, 550), 550);
    assert.equal(proportionalImageWidth(400, 200, -1000, -500, 550), 32);
    assert.equal(proportionalImageWidth(10, 5, -100, -50, 20), 20);
});

// Minimal DOM doubles inspect the editing transaction; browser undo/pointer behaviour
// still needs a real browser run. No DOM mutation is used as a substitute for native undo.
function imageNode() {
    const attributes = { src: '/storage/own.png', width: '800', height: '400' };
    const style = { width: '800px', height: '400px', maxWidth: '100%', removeProperty(name) { delete this[name]; } };
    return {
        attributes, style,
        removeAttribute(name) { delete attributes[name]; },
        cloneNode() { return imageNode(); },
        get outerHTML() { return JSON.stringify({ attributes, style }); },
    };
}
test('presets and original produce proportional HTML while leaving the original image untouched', () => {
    const image = imageNode();
    for (const width of ['25%', '50%', '100%', '250px', 'original']) {
        const html = JSON.parse(resizedImageHtml(image, width));
        assert.equal(html.attributes.src, '/storage/own.png');
        assert.equal(html.attributes.width, undefined);
        assert.equal(html.attributes.height, undefined);
        assert.equal(html.style.height, 'auto');
        assert.equal(html.style.maxWidth, '100%');
        assert.equal(html.style.width, width === 'original' ? undefined : width);
    }
    assert.equal(image.style.width, '800px');
    assert.equal(image.attributes.width, '800');
});
test('commit selects only the target image and sends a single native editing command', () => {
    const image = imageNode(), first = imageNode(), replacement = imageNode();
    const calls = [];
    let images = [first, image];
    const range = { selectNode: (node) => calls.push(['selected', node]) };
    const root = {
        isContentEditable: true, contains: (node) => images.includes(node), focus() {}, querySelectorAll: () => images,
        ownerDocument: {
            createRange: () => range, getSelection: () => ({ removeAllRanges() {}, addRange() {} }),
            execCommand: (...args) => { calls.push(args); images = [first, replacement]; return true; },
        },
    };
    assert.equal(commitImageWidth(root, image, '50%'), replacement);
    assert.deepEqual(calls[0], ['selected', image]);
    assert.equal(calls.length, 2);
    assert.equal(calls[1][0], 'insertHTML');
    assert.equal(JSON.parse(calls[1][2]).style.width, '50%');
    root.isContentEditable = false;
    assert.throws(() => commitImageWidth(root, replacement, '25%'));
});
