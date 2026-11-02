import catalog from '../../../../emoji/catalog.json';
import spanishLabels from '../../../../emoji/labels.es.json';
import { escapeEditorText } from './rich-text-escape';

export type CatalogEmoji = typeof catalog[number];
export const emojiCatalog: CatalogEmoji[] = catalog;
export const emojiCategories = ['Caras y personas', 'Naturaleza', 'Objetos y actividades', 'Viajes y lugares', 'Símbolos'];
export const emojiStyle = 'display:inline-block;width:24px;height:24px;max-width:none;vertical-align:-4px';
const byId = new Map(emojiCatalog.map((emoji) => [emoji.id, emoji]));
const labels: Record<string, string> = spanishLabels;
const keywords: Record<string, string> = {
    '1f604': 'feliz alegría contento risa', '1f603': 'feliz alegre contento', '1f600': 'feliz alegría', '1f60a': 'feliz alegría gracias',
    '1f602': 'jajaja reír carcajada divertido', '1f622': 'triste tristeza lágrima', '1f62d': 'triste tristeza lágrimas',
    '1f64f': 'por favor oración rezar agradecimiento', '1f44d': 'bien aprobado acuerdo sí felicitaciones',
    '1f44f': 'felicitaciones bravo bien', '1f389': 'fiesta celebración felicitaciones cumpleaños',
    '1f382': 'cumple cumpleanos cumpleaños fiesta', '2764': 'amor cariño afecto', '1f4da': 'leer lectura estudio educación escuela',
    '1f393': 'egreso egresados educación estudio', '1f3eb': 'colegio educación',
};
const fold = (text: string) => text.normalize('NFD').replace(/[\u0300-\u036f\ufe0f]/g, '').toLocaleLowerCase();
export const emojiLabel = (emoji: CatalogEmoji) => labels[emoji.aliases[0]] ?? emoji.aliases[0].replaceAll('_', ' ');
export const getEmoji = (id: string) => byId.get(id);
export const emojiUrl = (emoji: CatalogEmoji, baseUrl: string) => `${baseUrl.replace(/\/$/, '')}/${emoji.id}.png`;
export function emojiForUrl(src: string, baseUrl: string): CatalogEmoji | undefined {
    const prefix = `${baseUrl.replace(/\/$/, '')}/`;
    if (!src.startsWith(prefix) || !src.endsWith('.png')) return;
    return byId.get(src.slice(prefix.length, -4));
}
export function emojiHtml(emoji: CatalogEmoji, baseUrl: string): string {
    return `<img src="${escapeEditorText(emojiUrl(emoji, baseUrl))}" alt="${escapeEditorText(emojiLabel(emoji))}" data-eure-emoji="${emoji.id}" width="24" height="24" style="${emojiStyle}">`;
}
export function searchEmojis(query: string, category: number | null = null): CatalogEmoji[] {
    const words = fold(query.trim()).split(/\s+/).filter(Boolean);
    return emojiCatalog.filter((emoji) => (category === null || emoji.category === category) && words.every((word) => fold(`${emojiLabel(emoji)} ${emoji.aliases.join(' ')} ${emoji.unicode} ${emojiCategories[emoji.category]} ${keywords[emoji.id] ?? ''}`).includes(word)));
}
export function recentEmojis(raw: string | null): CatalogEmoji[] {
    try { const ids: unknown = JSON.parse(raw ?? '[]'); return Array.isArray(ids) ? [...new Set(ids)].flatMap((id) => typeof id === 'string' && byId.has(id) ? [byId.get(id)!] : []).slice(0, 24) : []; }
    catch { return []; }
}
