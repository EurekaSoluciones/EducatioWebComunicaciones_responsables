import { emojiForUrl, emojiLabel, emojiStyle } from './emoji-catalog';
export { escapeEditorText } from './rich-text-escape';
const tags = new Set('p div br strong b em i u s strike span font h1 h2 h3 h4 h5 h6 blockquote pre code ul ol li a img table thead tbody tr td th hr sub sup'.split(' '));
const discard = new Set('script style iframe object embed svg math template form input button textarea select'.split(' '));
const styles = new Set('color background-color font-family font-size font-weight font-style text-decoration text-align margin-left width height'.split(' '));
export const safeEditorLink = (value: string) => /^(https?:\/\/|mailto:|tel:)[^\s\x00-\x20]+$/i.test(value.trim());

export function hasExternalEditorImages(html: string, imageUrls: string[], emojiBaseUrl: string): boolean {
    return Array.from(new DOMParser().parseFromString(html, 'text/html').querySelectorAll('img')).some((image) => {
        const src = image.getAttribute('src') ?? '';
        return !imageUrls.includes(src) && !emojiForUrl(src, emojiBaseUrl);
    });
}

// Inert parsing before any pasted/source/local-draft HTML enters the editable DOM.
// The backend independently enforces the same policy before legacy clients see it.
export function cleanEditorHtml(html: string, imageUrls: string[], emojiBaseUrl = '/assets/eure/emoji/v1'): string {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const walk = (parent: Element) => {
        for (const node of Array.from(parent.childNodes)) {
            if (node.nodeType === Node.TEXT_NODE) continue;
            if (!(node instanceof Element) || discard.has(node.tagName.toLowerCase())) { node.remove(); continue; }
            const tag = node.tagName.toLowerCase();
            walk(node);
            if (!tags.has(tag)) { node.replaceWith(...Array.from(node.childNodes)); continue; }
            const attrs = Object.fromEntries(Array.from(node.attributes, (attr) => [attr.name.toLowerCase(), attr.value]));
            for (const attr of Array.from(node.attributes)) node.removeAttribute(attr.name);
            if (tag === 'a' && safeEditorLink(attrs.href ?? '')) { node.setAttribute('href', attrs.href); node.setAttribute('target', '_blank'); node.setAttribute('rel', 'noopener noreferrer'); }
            if (tag === 'img') {
                const emoji = emojiForUrl(attrs.src ?? '', emojiBaseUrl);
                if (emoji) {
                    node.setAttribute('src', attrs.src); node.setAttribute('alt', emojiLabel(emoji));
                    node.setAttribute('data-eure-emoji', emoji.id); node.setAttribute('width', '24'); node.setAttribute('height', '24'); node.setAttribute('style', emojiStyle);
                    continue;
                }
                if (!imageUrls.includes(attrs.src)) { node.remove(); continue; }
                node.setAttribute('src', attrs.src); node.setAttribute('alt', (attrs.alt ?? '').slice(0, 200));
            }
            if (tag === 'font') for (const attr of ['color', 'face', 'size']) if (attrs[attr] && /^[\w\s,#.\-]+$/.test(attrs[attr])) node.setAttribute(attr, attrs[attr].slice(0, 80));
            if (tag === 'table') { node.setAttribute('border', '1'); node.setAttribute('cellpadding', '7'); }
            for (const attr of ['width', 'height', 'colspan', 'rowspan']) if (/^\d{1,4}%?$/.test(attrs[attr] ?? '')) node.setAttribute(attr, attrs[attr]);
            const declarations = (attrs.style ?? '').split(';').flatMap((decl) => {
                const index = decl.indexOf(':');
                const name = decl.slice(0, index).trim().toLowerCase(), value = decl.slice(index + 1).trim();
                return index > 0 && styles.has(name) && /^[a-zA-Z0-9\s#.,()%\-]+$/.test(value) && !/url|expression|var\s*\(/i.test(value) ? [`${name}:${value}`] : [];
            });
            if (tag === 'img') declarations.push('max-width:100%', 'height:auto');
            if (declarations.length) node.setAttribute('style', declarations.join(';'));
        }
    };
    walk(doc.body);
    return doc.body.innerHTML;
}

export function editorHasContent(html: string): boolean {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return Boolean(doc.body.textContent?.replace(/[\s\u00a0\u200b-\u200d\ufeff]/g, '') || doc.querySelector('img[src]'));
}
