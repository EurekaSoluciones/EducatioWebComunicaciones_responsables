import { useEffect, useMemo, useRef, useState } from 'react';
import type { Theme } from '../../theme';

export default function FamilyHtmlContent({ html, title, theme, emptyText = 'No hay contenido.', minHeight = 180 }: {
    html: string; title: string; theme: Theme; emptyText?: string; minHeight?: number;
}) {
    const emptyHtml = `<p>${emptyText.replace(/[&<>"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[character]!))}</p>`;
    const observer = useRef<ResizeObserver | null>(null);
    const [height, setHeight] = useState(360);
    const document = useMemo(() => `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src http: https: data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none';"><style>html{overflow-wrap:anywhere}body{display:flow-root;box-sizing:border-box;margin:0;padding:16px;font:15px/1.7 system-ui;color:${theme.colors.text};background:${theme.colors.surface}}img{max-width:100%;height:auto}table{max-width:100%}a{color:${theme.colors['primary-text']};text-underline-offset:3px}a:focus-visible{outline:2px solid ${theme.colors.ring};outline-offset:3px}pre{white-space:pre-wrap}p:first-child{margin-top:0}</style></head><body>${html || emptyHtml}</body></html>`, [html, theme.colors, emptyHtml]);

    useEffect(() => () => observer.current?.disconnect(), [document]);

    return <iframe title={`Contenido de ${title}`} className="family-board-content" srcDoc={document}
        // No scripts or forms. Same-origin permits the parent to measure content; the document cannot run code.
        sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox" style={{ height, width: '100%', display: 'block', border: 0 }}
        onLoad={(event) => {
            observer.current?.disconnect();
            let content: Document | null;
            try { content = event.currentTarget.contentDocument; } catch { return; }
            if (!content?.body) return;
            // Links open independently of the portal, including links with a legacy explicit target.
            content.querySelectorAll('a').forEach((link) => {
                if (!['http:', 'https:', 'mailto:', 'tel:'].includes(link.protocol)) {
                    link.removeAttribute('href');
                    return;
                }
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
            });
            const body = content.body;
            const resize = () => setHeight(Math.max(minHeight, Math.ceil(body.getBoundingClientRect().height) + 2));
            observer.current = new ResizeObserver(resize);
            observer.current.observe(body);
            resize();
        }} />;
}
