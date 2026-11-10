import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import * as icons from 'lucide-react';
import ts from 'typescript';

// Render the real TSX and EureUI components using the installed React, without a browser or bank.
const require = createRequire(import.meta.url);
const modules = new Map();
function load(sourcePath) {
    const file = [sourcePath, `${sourcePath}.tsx`, `${sourcePath}.ts`, `${sourcePath}/index.ts`]
        .find((candidate) => existsSync(candidate) && /\.(tsx|ts)$/.test(candidate));
    assert.ok(file, `Module not found: ${sourcePath}`);
    if (modules.has(file)) return modules.get(file).exports;
    const module = { exports: {} };
    modules.set(file, module);
    const compiled = ts.transpileModule(readFileSync(file, 'utf8'), { compilerOptions: {
        module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, jsx: ts.JsxEmit.ReactJSX,
        esModuleInterop: true,
    } }).outputText;
    new Function('require', 'module', 'exports', compiled)(
        (id) => id === 'lucide-react' ? icons : id.startsWith('.') ? load(resolve(dirname(file), id)) : require(id), module, module.exports,
    );
    return module.exports;
}
const { default: FamilyOnlinePayment } = load(fileURLToPath(new URL('../../resources/js/components/family/FamilyOnlinePayment.tsx', import.meta.url)));

function render(methods, error = null) {
    return renderToStaticMarkup(createElement(FamilyOnlinePayment, {
        payment: { url: '/cc/pagar', suggestedAmount: '1234,50', error,
            methods: methods.map((id) => ({ id, name: id, logo: null })) },
        studentId: '11', csrfToken: 'fake-csrf',
    }));
}

function hiddenValues(html) {
    return Object.fromEntries([...html.matchAll(/<input\b[^>]*type="hidden"[^>]*>/g)].map(([input]) => [
        input.match(/name="([^"]*)"/)[1], input.match(/value="([^"]*)"/)[1],
    ]));
}

test('ISI form starts with a valid method, student, decimal amount and CSRF in its native POST', () => {
    const html = render(['supervielle']);
    assert.deepEqual(hiddenValues(html), { _token: 'fake-csrf', alumno: '11', importe: '1234,50', metodo: 'supervielle' });
    const form = html.match(/<form\b[^>]*>/)[0];
    assert.ok(form.includes('method="POST"'));
    assert.ok(form.includes('action="/cc/pagar"'));
    assert.match(html, /<button[^>]*value="supervielle"[^>]*type="submit"/);
    assert.doesNotMatch(html, /<button[^>]*\sdisabled(?:=|\s|>)/);
});

test('all enabled provider combinations use the shared dispatcher with a valid default for Enter', () => {
    for (const methods of [['pagostic'], ['mercadopago'], ['pagostic', 'mercadopago'], ['supervielle', 'pagostic'], ['supervielle', 'mercadopago'], ['supervielle', 'pagostic', 'mercadopago']]) {
        const html = render(methods);
        assert.ok(html.includes('action="/cc/pagar"'));
        assert.equal(hiddenValues(html).metodo, methods[0]);
        for (const id of methods) assert.ok(html.includes(`value="${id}"`));
    }
});

test('server validation errors are visible instead of silently returning to the account', () => {
    const message = 'No recibimos un medio de pago válido.';
    const html = render(['supervielle'], message);
    assert.ok(html.includes(message));
    assert.match(html, /role="alert"/);
    assert.equal(hiddenValues(html).importe, '1234,50');
});
