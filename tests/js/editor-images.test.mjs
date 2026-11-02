import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/components/ui/eure/editor-images.ts', import.meta.url), 'utf8');
const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022 } }).outputText;
const { transferredFiles, prepareEditorImage, dropImageRange, insertEditorImages } = await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`);

test('Explorer files and clipboard items are read once, including fallback-only clipboard files', () => {
    const file = new File(['png'], 'foto.png', { type: 'image/png' });
    const item = { kind: 'file', getAsFile: () => file };
    assert.deepEqual(transferredFiles({ files: [file], items: [item] }), [file]);
    assert.deepEqual(transferredFiles({ files: [], items: [{ kind: 'string' }, item, { kind: 'file', getAsFile: () => null }] }), [file]);
});

test('image names from Explorer and unnamed clipboard images pass the upload contract', async () => {
    const explorer = new File(['jpeg'], 'Foto.JPG'); // Windows can omit MIME.
    assert.equal(prepareEditorImage(explorer), explorer);
    const clipboard = new File(['png'], 'image', { type: 'image/png', lastModified: 50 });
    const prepared = prepareEditorImage(clipboard);
    assert.equal(prepared.name, 'imagen.png');
    assert.equal(prepared.type, 'image/png');
    assert.equal(await prepared.text(), 'png');
    assert.equal(prepared.lastModified, 50);
    assert.throws(() => prepareEditorImage(new File(['svg'], 'foto.svg', { type: 'image/svg+xml' })));
    assert.throws(() => prepareEditorImage(new File(['pdf'], 'foto.png', { type: 'application/pdf' })));
    assert.throws(() => prepareEditorImage(new File([], 'foto.png')));
    assert.throws(() => prepareEditorImage({ name: 'foto.png', type: 'image/png', size: 12 * 1024 * 1024 + 1 }));
});

function editingSurface() {
    const text = {}, outside = {};
    const calls = [];
    const range = { commonAncestorContainer: text, startContainer: text };
    const selection = { removeAllRanges: () => calls.push('clear'), addRange: (value) => calls.push(['range', value]) };
    const doc = {
        getSelection: () => selection,
        execCommand: (...args) => { calls.push(args); return true; },
        createRange: () => ({ selectNodeContents: (value) => calls.push(['contents', value]), collapse: (value) => calls.push(['collapse', value]) }),
    };
    const root = { ownerDocument: doc, isContentEditable: false, contains: (node) => node === text, focus: () => calls.push('focus') };
    return { root, doc, range, text, outside, calls };
}

test('uploaded images cannot be inserted until the editing surface is enabled; batch uses one undo command', () => {
    const { root, calls, range } = editingSurface();
    const images = [{ url: '/storage/one.png', name: 'Foto "uno"' }, { url: '/storage/two.png', name: 'Dos' }];
    assert.throws(() => insertEditorImages(root, images, range), /todavía no está disponible/);
    assert.deepEqual(calls, []); // Regresses the old silent command against a disabled editor.
    root.isContentEditable = true;
    insertEditorImages(root, images, range);
    assert.equal(calls[0], 'focus');
    assert.deepEqual(calls[2], ['range', range]);
    assert.equal(calls[3][0], 'insertHTML');
    assert.match(calls[3][2], /Foto &quot;uno&quot;/);
    assert.ok(calls[3][2].indexOf('one.png') < calls[3][2].indexOf('two.png'));
    assert.equal(calls.filter((call) => call[0] === 'insertHTML').length, 1);
});

test('failed browser insertion is reported instead of pretending the uploaded image is in the message', () => {
    const { root, doc, range } = editingSurface();
    root.isContentEditable = true;
    doc.execCommand = () => false;
    assert.throws(() => insertEditorImages(root, [{ url: '/storage/one.png', name: 'Foto' }], range), /archivos ya están subidos/);
});

test('drop uses pointer coordinates and never an old selection outside the editor', () => {
    const { root, doc, range, calls, outside } = editingSurface();
    doc.caretRangeFromPoint = (x, y) => { assert.equal(x, 40); assert.equal(y, 70); return range; };
    assert.equal(dropImageRange(root, 40, 70), range);
    doc.caretRangeFromPoint = () => ({ startContainer: outside });
    dropImageRange(root, 5, 6);
    assert.deepEqual(calls, [['contents', root], ['collapse', false]]);
});
