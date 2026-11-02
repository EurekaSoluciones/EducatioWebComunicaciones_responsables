import { ArrowRight, BookOpen } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EureCard } from '../ui/eure';
import type { HomeProps } from './types';

function BoardThumbnail({ document, title, url }: { document: string; title: string; url: string }) {
    const container = useRef<HTMLAnchorElement>(null);
    const [scale, setScale] = useState(0.25);
    useEffect(() => {
        const element = container.current;
        if (!element) return;
        const observer = new ResizeObserver(([entry]) => {
            if (entry.contentRect.width > 0) setScale(entry.contentRect.width / 800);
        });
        observer.observe(element);
        return () => observer.disconnect();
    }, []);

    return <a ref={container} href={url} className="family-board-thumbnail" aria-label={`Abrir cartelera: ${title}`}>
        <iframe title={`Miniatura: ${title}`} sandbox="" srcDoc={document} loading="lazy" tabIndex={-1} aria-hidden="true"
            className="family-board-mini-preview" style={{ height: 190 / scale, transform: `scale(${scale})` }} />
    </a>;
}

export default function FamilyBoardPreviews({ boards, theme }: {
    boards: NonNullable<HomeProps['boardPreviews']>; theme: HomeProps['theme'];
}) {
    return <section className="family-boards" aria-label="Carteleras de la institución">
        {boards.length === 0 ? <p className="eure-muted">No hay carteleras publicadas.</p> : boards.map((board) => {
            // Keep historical rich text isolated: no scripts, forms or navigation outside the preview.
            const document = `<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src http: https: data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none';"><style>html,body{overflow:hidden!important;scrollbar-width:none!important}::-webkit-scrollbar{display:none!important}body{margin:16px;font:15px/1.7 system-ui;color:${theme.colors.text};background:${theme.colors.surface};overflow-wrap:anywhere}img{max-width:100%;height:auto}table{max-width:100%}a{color:${theme.colors['primary-text']}}</style></head><body>${board.html || '<p>Sin novedades publicadas.</p>'}</body></html>`;
            return <EureCard key={board.id} title={board.title} icon={BookOpen} className="family-board-mini">
                <div className="family-board-mini-body">
                    <BoardThumbnail document={document} title={board.title} url={board.url} />
                    <a className="eure-button eure-button-secondary" href={board.url} aria-label={`Ver cartelera completa: ${board.title}`}>Ver cartelera completa<ArrowRight size={16} aria-hidden="true" /></a>
                </div>
            </EureCard>;
        })}
    </section>;
}
