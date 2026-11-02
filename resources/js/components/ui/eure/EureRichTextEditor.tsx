import { Bold, Italic, Underline, List, ListOrdered, Link, Unlink, ImagePlus, Smile, Undo2, Redo2, Eraser, AlignLeft, AlignCenter, AlignRight, Table, Code, Maximize2, Minimize2 } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import type { LucideIcon } from 'lucide-react';
import { EureDialog } from '../../eureka';
import EureButtonPrimary from './EureButtonPrimary';
import EureButtonSecondary from './EureButtonSecondary';
import { cleanEditorHtml, escapeEditorText, safeEditorLink, hasExternalEditorImages } from './rich-text';
import { dropImageRange, insertEditorImages, prepareEditorImage, transferredFiles } from './editor-images';
import type { UploadedEditorImage } from './editor-images';
import EureImageResizeControls from './EureImageResizeControls';
import EureEmojiPicker from './EureEmojiPicker';
import { emojiHtml } from './emoji-catalog';
import '../../../../css/eure-rich-text.css';

type Props = {
    id: string; initialHtml?: string; imageUrls: string[]; disabled?: boolean;
    emojiBaseUrl?: string; emojiStorageKey?: string;
    onChange: (html: string) => void;
    uploadImage: (file: File) => Promise<{ url: string; name: string }>;
    onBusyChange: (busy: boolean) => void;
};

export default function EureRichTextEditor({ id, initialHtml = '', imageUrls, disabled = false, emojiBaseUrl = '/assets/eure/emoji/v1', emojiStorageKey = 'eure-emoji-recent:v1', onChange, uploadImage, onBusyChange }: Props) {
    const editor = useRef<HTMLDivElement>(null);
    const picker = useRef<HTMLInputElement>(null);
    const selection = useRef<Range | null>(null);
    const initialized = useRef(false);
    const mounted = useRef(true);
    const uploading = useRef(false);
    const uploadRange = useRef<Range | null>(null);
    const [pendingImages, setPendingImages] = useState<UploadedEditorImage[]>([]);
    const [insertFailed, setInsertFailed] = useState(false);
    const knownImages = useRef(imageUrls);
    const [error, setError] = useState('');
    const [emojiOpen, setEmojiOpen] = useState(false);
    const [dialog, setDialog] = useState<'link' | 'table' | null>(null);
    const [link, setLink] = useState('https://');
    const [linkText, setLinkText] = useState('');
    const [rows, setRows] = useState(3), [columns, setColumns] = useState(3);
    const [source, setSource] = useState<string | null>(null);
    const [expanded, setExpanded] = useState(false);
    const blocked = disabled || source !== null;
    useEffect(() => {
        mounted.current = true;
        return () => { mounted.current = false; };
    }, []);
    useEffect(() => {
        if (!initialized.current && editor.current) { editor.current.innerHTML = cleanEditorHtml(initialHtml, imageUrls, emojiBaseUrl); initialized.current = true; }
    }, [initialHtml, imageUrls, emojiBaseUrl]);
    const saveRange = () => {
        const current = window.getSelection();
        if (current?.rangeCount && editor.current?.contains(current.anchorNode) && editor.current.contains(current.focusNode)) selection.current = current.getRangeAt(0).cloneRange();
    };
    const restore = () => {
        const root = editor.current;
        if (!root) return;
        root.focus();
        const current = window.getSelection();
        current?.removeAllRanges();
        if (selection.current && root.contains(selection.current.commonAncestorContainer)) current?.addRange(selection.current);
        else { const range = document.createRange(); range.selectNodeContents(root); range.collapse(false); current?.addRange(range); }
    };
    const publish = () => { saveRange(); onChange(editor.current?.innerHTML ?? ''); };
    useEffect(() => {
        const removed = knownImages.current.filter((url) => !imageUrls.includes(url));
        knownImages.current = imageUrls;
        if (!removed.length) return;
        editor.current?.querySelectorAll('img').forEach((image) => { if (removed.includes(image.getAttribute('src') ?? '')) image.remove(); });
        if (source !== null) {
            const html = cleanEditorHtml(source, imageUrls, emojiBaseUrl); setSource(html); onChange(html);
        } else onChange(editor.current?.innerHTML ?? '');
    }, [imageUrls]);
    const command = (name: string, value?: string) => {
        if (blocked) return;
        restore();
        // Browser editing commands retain native undo; no HTML from clipboard is inserted unfiltered.
        document.execCommand(name, false, value);
        publish();
    };
    // React commits contentEditable=true before this effect runs. The async uploader
    // only queues URLs; it must not insert while the parent's busy state disables us.
    useLayoutEffect(() => {
        if (disabled || !pendingImages.length || insertFailed || !editor.current) return;
        try {
            insertEditorImages(editor.current, pendingImages, uploadRange.current);
            setPendingImages([]); uploading.current = false;
            publish();
        } catch (failure) {
            setInsertFailed(true);
            setError(failure instanceof Error ? failure.message : 'No se pudieron insertar las imágenes.');
        }
    }, [disabled, pendingImages, insertFailed]);
    const images = async (files: File[], insertionRange?: Range) => {
        if (blocked || uploading.current || !files.length) return;
        if (!insertionRange) saveRange();
        uploadRange.current = insertionRange ?? selection.current?.cloneRange() ?? null;
        uploading.current = true; onBusyChange(true); setError(''); setInsertFailed(false);
        const uploaded: UploadedEditorImage[] = [];
        try {
            for (const file of files) {
                const result = await uploadImage(prepareEditorImage(file));
                if (!mounted.current) return;
                uploaded.push(result);
            }
        } catch (failure) { if (mounted.current) setError(failure instanceof Error ? failure.message : 'No se pudo agregar la imagen.'); }
        finally {
            if (mounted.current) { setPendingImages(uploaded); onBusyChange(false); }
            if (!uploaded.length) uploading.current = false;
        }
    };
    const toolbarButton = (label: string, Icon: LucideIcon, action: () => void, isDisabled = blocked) => <button key={label} type="button" title={label} aria-label={label} disabled={isDisabled} onMouseDown={(event) => event.preventDefault()} onClick={action}><Icon size={17} /></button>;

    return <div className={`eure-rich-editor ${expanded ? 'is-expanded' : ''}`}>
        <div className="eure-rich-toolbar" role="toolbar" aria-label="Formato del mensaje">
            {toolbarButton('Deshacer', Undo2, () => command('undo'))}{toolbarButton('Rehacer', Redo2, () => command('redo'))}
            <select aria-label="Estilo de párrafo" disabled={blocked} defaultValue="p" onChange={(event) => command('formatBlock', event.target.value)}><option value="p">Texto normal</option><option value="h2">Título</option><option value="h3">Subtítulo</option><option value="blockquote">Cita</option><option value="pre">Código</option></select>
            {toolbarButton('Negrita', Bold, () => command('bold'))}{toolbarButton('Cursiva', Italic, () => command('italic'))}{toolbarButton('Subrayado', Underline, () => command('underline'))}
            {toolbarButton('Lista', List, () => command('insertUnorderedList'))}{toolbarButton('Lista numerada', ListOrdered, () => command('insertOrderedList'))}
            {toolbarButton('Alinear a la izquierda', AlignLeft, () => command('justifyLeft'))}{toolbarButton('Centrar', AlignCenter, () => command('justifyCenter'))}{toolbarButton('Alinear a la derecha', AlignRight, () => command('justifyRight'))}
            {toolbarButton('Agregar enlace o video', Link, () => { saveRange(); setLinkText(selection.current?.toString() ?? ''); setLink('https://'); setDialog('link'); })}
            {toolbarButton('Quitar enlace', Unlink, () => command('unlink'))}
            {toolbarButton('Insertar imagen', ImagePlus, () => { saveRange(); picker.current?.click(); })}
            <button type="button" title="Emojis" aria-label="Emojis" aria-expanded={emojiOpen} disabled={blocked} onMouseDown={(event) => event.preventDefault()} onClick={() => { saveRange(); setEmojiOpen(!emojiOpen); }}><Smile size={17} /></button>
            {toolbarButton('Insertar tabla', Table, () => { saveRange(); setDialog('table'); })}
            {toolbarButton('Quitar formato', Eraser, () => command('removeFormat'))}
            {toolbarButton(source === null ? 'Editar HTML' : 'Volver al editor visual', Code, () => {
                if (source === null) setSource(editor.current?.innerHTML ?? '');
                else { const html = cleanEditorHtml(source, imageUrls, emojiBaseUrl); if (editor.current) editor.current.innerHTML = html; onChange(html); selection.current = null; setSource(null); }
            }, disabled)}
            {toolbarButton(expanded ? 'Reducir editor' : 'Ampliar editor', expanded ? Minimize2 : Maximize2, () => setExpanded(!expanded), disabled)}
        </div>
        <div className="eure-rich-toolbar eure-rich-secondary">
            <select aria-label="Tipografía" disabled={blocked} defaultValue="Arial" onChange={(event) => command('fontName', event.target.value)}>{['Arial', 'Verdana', 'Georgia', 'Times New Roman', 'Courier New'].map((font) => <option key={font}>{font}</option>)}</select>
            <select aria-label="Tamaño del texto" disabled={blocked} defaultValue="3" onChange={(event) => command('fontSize', event.target.value)}>{['Pequeño', 'Mediano pequeño', 'Normal', 'Grande', 'Muy grande', 'Título', 'Título grande'].map((label, index) => <option key={label} value={index + 1}>{label}</option>)}</select>
            <label>Texto <input aria-label="Color del texto" type="color" defaultValue="#222222" disabled={blocked} onChange={(event) => command('foreColor', event.target.value)} /></label>
            <label>Resaltar <input aria-label="Color de resaltado" type="color" defaultValue="#ffff00" disabled={blocked} onChange={(event) => command('hiliteColor', event.target.value)} /></label>
        </div>
        {emojiOpen && <EureEmojiPicker baseUrl={emojiBaseUrl} storageKey={emojiStorageKey} disabled={blocked} onClose={() => { setEmojiOpen(false); restore(); }} onSelect={(emoji) => { command('insertHTML', emojiHtml(emoji, emojiBaseUrl)); setEmojiOpen(false); }} />}
        <input ref={picker} type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden onChange={(event) => { void images(Array.from(event.target.files ?? [])); event.target.value = ''; }} />
        {error && <p className="eure-rich-error" role="alert">{error}</p>}
        {insertFailed && <EureButtonSecondary disabled={disabled} onClick={() => { setError(''); setInsertFailed(false); }}>Reintentar insertar imágenes</EureButtonSecondary>}
        <div className="eure-rich-canvas">
        <div id={id} ref={editor} className="eure-rich-content" role="textbox" aria-label="Mensaje" aria-multiline="true" aria-disabled={disabled} contentEditable={!disabled} suppressContentEditableWarning hidden={source !== null}
            data-placeholder="Escribí tu mensaje…" onInput={publish} onKeyUp={saveRange} onMouseUp={saveRange} onBlur={saveRange}
            onClick={(event) => { if ((event.target as HTMLElement).closest('a')) event.preventDefault(); }}
            onPaste={(event) => {
                event.preventDefault(); if (blocked) return;
                const files = transferredFiles(event.clipboardData);
                if (files.length) { void images(files); return; }
                const html = event.clipboardData.getData('text/html');
                if (html) {
                    if (hasExternalEditorImages(html, imageUrls, emojiBaseUrl)) setError('Las imágenes externas no se importan con el texto. Copiá la imagen y pegala, o usá Insertar imagen.');
                    command('insertHTML', cleanEditorHtml(html, imageUrls, emojiBaseUrl));
                } else command('insertText', event.clipboardData.getData('text/plain'));
            }}
            onDragOver={(event) => { event.preventDefault(); event.dataTransfer.dropEffect = blocked ? 'none' : 'copy'; }} onDrop={(event) => {
                event.preventDefault(); if (blocked || !editor.current) return;
                const range = dropImageRange(editor.current, event.clientX, event.clientY);
                const files = transferredFiles(event.dataTransfer);
                if (files.length) { void images(files, range); return; }
                selection.current = range;
                const html = event.dataTransfer.getData('text/html');
                if (hasExternalEditorImages(html, imageUrls, emojiBaseUrl)) setError('El navegador entregó un enlace a la imagen. Copiá la imagen y pegala, o arrastrá el archivo descargado.');
                if (html) command('insertHTML', cleanEditorHtml(html, imageUrls, emojiBaseUrl));
                else command('insertText', event.dataTransfer.getData('text/plain'));
            }} />
        <EureImageResizeControls editor={editor} disabled={blocked} onChange={publish} onError={setError} />
        </div>
        {source !== null && <textarea className="eure-rich-source" aria-label="Código HTML del mensaje" value={source} disabled={disabled} onChange={(event) => { setSource(event.target.value); onChange(cleanEditorHtml(event.target.value, imageUrls, emojiBaseUrl)); }} />}
        <div className="eure-rich-hint">Podés pegar imágenes con Ctrl+V o arrastrarlas al mensaje. {disabled && 'Esperá a que termine la operación.'}</div>
        <EureDialog open={dialog !== null} onClose={() => setDialog(null)} title={dialog === 'table' ? 'Insertar tabla' : 'Agregar enlace'}>
            {dialog === 'link' ? <div className="eure-rich-dialog-fields" onKeyDown={(event) => { if (event.key === 'Enter') event.preventDefault(); }}><label>Dirección<input className="eureui-input" type="text" value={link} onChange={(event) => setLink(event.target.value)} placeholder="https://" /></label><label>Texto del enlace<input className="eureui-input" value={linkText} onChange={(event) => setLinkText(event.target.value)} /></label><small>Los videos se comparten como un enlace que se abre en otra pestaña.</small></div>
                : <div className="eure-rich-dialog-fields" onKeyDown={(event) => { if (event.key === 'Enter') event.preventDefault(); }}><label>Filas<input className="eureui-input" type="number" min={1} max={20} value={rows} disabled={dialog !== 'table'} onChange={(event) => setRows(Number(event.target.value))} /></label><label>Columnas<input className="eureui-input" type="number" min={1} max={10} value={columns} disabled={dialog !== 'table'} onChange={(event) => setColumns(Number(event.target.value))} /></label></div>}
            <div className="eure-rich-dialog-actions"><EureButtonSecondary onClick={() => setDialog(null)}>Cancelar</EureButtonSecondary><EureButtonPrimary disabled={dialog === 'link' ? !safeEditorLink(link) : !Number.isInteger(rows) || !Number.isInteger(columns) || rows < 1 || rows > 20 || columns < 1 || columns > 10} onClick={(event) => {
                // A modal makes the editor inert. Close it before restoring the editing selection.
                event.currentTarget.closest('dialog')?.close();
                if (dialog === 'link') command('insertHTML', `<a href="${escapeEditorText(link.trim())}" target="_blank" rel="noopener noreferrer">${escapeEditorText(linkText || link.trim())}</a>`);
                else command('insertHTML', `<table style="width:100%"><tbody>${Array.from({ length: rows }, () => `<tr>${'<td><br></td>'.repeat(columns)}</tr>`).join('')}</tbody></table><p><br></p>`);
                setDialog(null);
            }}>Insertar</EureButtonPrimary></div>
        </EureDialog>
    </div>;
}
