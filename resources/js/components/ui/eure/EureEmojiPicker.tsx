import { Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { emojiCategories, emojiLabel, emojiUrl, recentEmojis, searchEmojis } from './emoji-catalog';
import type { CatalogEmoji } from './emoji-catalog';

type Props = { baseUrl: string; storageKey: string; disabled: boolean; onSelect: (emoji: CatalogEmoji) => void; onClose: () => void };

export default function EureEmojiPicker({ baseUrl, storageKey, disabled, onSelect, onClose }: Props) {
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState<number | null>(0);
    const [recent, setRecent] = useState<CatalogEmoji[]>(() => {
        try { return recentEmojis(localStorage.getItem(storageKey)); } catch { return []; }
    });
    const [active, setActive] = useState(0);
    const input = useRef<HTMLInputElement>(null);
    const grid = useRef<HTMLDivElement>(null);
    const matches = useMemo(() => category === -1 ? recent : searchEmojis(query, query.trim() ? null : category), [query, category, recent]);
    useEffect(() => { input.current?.focus(); }, []);
    useEffect(() => { setActive(0); if (grid.current) grid.current.scrollTop = 0; }, [query, category]);
    const choose = (emoji: CatalogEmoji) => {
        if (disabled) return;
        const next = [emoji, ...recent.filter((item) => item.id !== emoji.id)].slice(0, 24);
        setRecent(next);
        try { localStorage.setItem(storageKey, JSON.stringify(next.map((item) => item.id))); } catch { /* The catalog still works without persistence. */ }
        onSelect(emoji);
    };
    const focusEmoji = (index: number) => {
        if (!matches.length) return;
        const next = Math.max(0, Math.min(matches.length - 1, index));
        setActive(next); grid.current?.querySelectorAll<HTMLButtonElement>('button')[next]?.focus();
    };
    return <section className="eure-emoji-picker" aria-label="Catálogo de emojis" onKeyDown={(event) => {
        if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); onClose(); }
    }}>
        <div className="eure-emoji-search"><Search size={16} aria-hidden="true" /><input ref={input} type="search" aria-label="Buscar emoji" placeholder="Buscar: sonrisa, corazón, libros…" value={query} disabled={disabled} onChange={(event) => { setQuery(event.target.value); setCategory(null); }} onKeyDown={(event) => {
            if (event.key === 'ArrowDown') { event.preventDefault(); focusEmoji(0); }
            if (event.key === 'Enter') { event.preventDefault(); if (matches[0]) choose(matches[0]); }
        }} /><button type="button" aria-label="Cerrar emojis" onClick={onClose}><X size={17} /></button></div>
        <div className="eure-emoji-categories" role="group" aria-label="Categorías de emojis">
            <button type="button" aria-pressed={category === -1} onClick={() => { setQuery(''); setCategory(-1); }}>Recientes</button>
            {emojiCategories.map((name, index) => <button key={name} type="button" aria-pressed={category === index && !query} onClick={() => { setQuery(''); setCategory(index); }}>{name}</button>)}
        </div>
        <div ref={grid} className="eure-emoji-grid" role="group" aria-label="Emojis disponibles" onKeyDown={(event) => {
            const columns = grid.current ? getComputedStyle(grid.current).gridTemplateColumns.split(' ').length : 8;
            const offset = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -columns, ArrowDown: columns }[event.key];
            if (offset !== undefined) { event.preventDefault(); focusEmoji(active + offset); }
            if (event.key === 'Home') { event.preventDefault(); focusEmoji(0); }
            if (event.key === 'End') { event.preventDefault(); focusEmoji(matches.length - 1); }
        }}>
            {matches.map((emoji, index) => <button key={emoji.id} type="button" title={emojiLabel(emoji)} aria-label={`Insertar ${emojiLabel(emoji)}`} tabIndex={index === Math.min(active, matches.length - 1) ? 0 : -1} disabled={disabled} onFocus={() => setActive(index)} onClick={() => choose(emoji)}><img src={emojiUrl(emoji, baseUrl)} width={28} height={28} alt="" loading="lazy" draggable={false} /></button>)}
        </div>
        <p className="eure-emoji-status" role="status">{matches.length ? `${matches.length} emojis · Usá las flechas para recorrerlos.` : category === -1 ? 'Los emojis que uses van a aparecer acá.' : 'No encontramos emojis con esa búsqueda.'}</p>
    </section>;
}
