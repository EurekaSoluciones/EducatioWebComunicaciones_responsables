import { useEffect, useRef, useState } from 'react';
import type { RefObject, PointerEvent as ReactPointerEvent } from 'react';
import { commitImageWidth, proportionalImageWidth } from './image-resize';
import type { ImageWidth } from './image-resize';

type Props = { editor: RefObject<HTMLDivElement | null>; disabled: boolean; onChange: () => void; onError: (message: string) => void };
type Box = { left: number; top: number; width: number; height: number; viewport: number; offset: number };
type Drag = { image: HTMLImageElement; style: string | null; x: number; y: number; width: number; height: number; limit: number; next: number; pointerId: number };

export default function EureImageResizeControls({ editor, disabled, onChange, onError }: Props) {
    const [selected, setSelected] = useState<HTMLImageElement | null>(null);
    const [box, setBox] = useState<Box | null>(null);
    const drag = useRef<Drag | null>(null);
    const handle = useRef<HTMLButtonElement>(null);
    const latest = useRef({ onChange, onError }); latest.current = { onChange, onError };
    const restorePreview = (action: Drag) => {
        if (action.style === null) action.image.removeAttribute('style');
        else action.image.setAttribute('style', action.style);
    };
    const cancel = () => { if (drag.current) { restorePreview(drag.current); drag.current = null; } };
    const measure = () => {
        const root = editor.current;
        if (!root || !selected || !root.contains(selected)) { setBox(null); return; }
        const bounds = root.getBoundingClientRect(), image = selected.getBoundingClientRect();
        setBox({ left: image.left - bounds.left, top: image.top - bounds.top, width: image.width, height: image.height, viewport: root.clientHeight, offset: root.offsetTop });
    };
    useEffect(() => {
        const root = editor.current;
        if (!root || disabled) { cancel(); setSelected(null); setBox(null); return; }
        const choose = (event: MouseEvent) => {
            const target = event.target;
            setSelected(target instanceof HTMLImageElement && !target.hasAttribute('data-eure-emoji') && root.contains(target) ? target : null);
        };
        const outside = (event: PointerEvent) => {
            if (!root.parentElement?.contains(event.target as Node)) { cancel(); setSelected(null); }
        };
        const escape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') { cancel(); setSelected(null); }
        };
        const duringDrag = (event: Event) => { if (drag.current) event.preventDefault(); };
        root.addEventListener('click', choose);
        root.addEventListener('beforeinput', duringDrag);
        root.ownerDocument.addEventListener('pointerdown', outside);
        root.ownerDocument.addEventListener('keydown', escape);
        return () => {
            cancel(); root.removeEventListener('click', choose); root.removeEventListener('beforeinput', duringDrag);
            root.ownerDocument.removeEventListener('pointerdown', outside);
            root.ownerDocument.removeEventListener('keydown', escape);
        };
    }, [editor, disabled]);
    useEffect(() => {
        const root = editor.current;
        if (!root || !selected || disabled) { setBox(null); return; }
        let frame = 0;
        const update = () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                if (!root.contains(selected)) { setSelected(null); setBox(null); return; }
                measure();
            });
        };
        const resize = new ResizeObserver(update); resize.observe(root); resize.observe(selected);
        const mutations = new MutationObserver(update); mutations.observe(root, { childList: true, subtree: true, attributes: true });
        root.addEventListener('scroll', update); selected.addEventListener('load', update); window.addEventListener('resize', update);
        measure();
        return () => { cancelAnimationFrame(frame); resize.disconnect(); mutations.disconnect(); root.removeEventListener('scroll', update); selected.removeEventListener('load', update); window.removeEventListener('resize', update); };
    }, [editor, selected, disabled]);
    const commit = (image: HTMLImageElement, width: ImageWidth) => {
        const root = editor.current;
        if (!root || disabled) return;
        try { setSelected(commitImageWidth(root, image, width)); latest.current.onChange(); }
        catch (failure) { latest.current.onError(failure instanceof Error ? failure.message : 'No se pudo cambiar el tamaño.'); }
    };
    const limit = (image: HTMLImageElement) => {
        const parent = image.parentElement!;
        const styles = getComputedStyle(parent);
        return Math.max(1, (parent.clientWidth || editor.current!.clientWidth) - parseFloat(styles.paddingLeft || '0') - parseFloat(styles.paddingRight || '0'));
    };
    const begin = (event: ReactPointerEvent<HTMLButtonElement>) => {
        if (disabled || !selected || event.button !== 0) return;
        event.preventDefault(); event.stopPropagation();
        const bounds = selected.getBoundingClientRect();
        drag.current = { image: selected, style: selected.getAttribute('style'), x: event.clientX, y: event.clientY, width: bounds.width, height: bounds.height, limit: limit(selected), next: bounds.width, pointerId: event.pointerId };
        event.currentTarget.setPointerCapture(event.pointerId);
    };
    const move = (event: ReactPointerEvent<HTMLButtonElement>) => {
        const action = drag.current;
        if (!action || action.pointerId !== event.pointerId) return;
        event.preventDefault();
        action.next = proportionalImageWidth(action.width, action.height, event.clientX - action.x, event.clientY - action.y, action.limit);
        action.image.style.width = `${action.next}px`; action.image.style.height = 'auto';
        measure();
    };
    const end = (event: ReactPointerEvent<HTMLButtonElement>) => {
        const action = drag.current;
        if (!action || action.pointerId !== event.pointerId) return;
        drag.current = null;
        restorePreview(action); // The undo snapshot must contain the size from before dragging.
        if (event.currentTarget.hasPointerCapture(event.pointerId)) event.currentTarget.releasePointerCapture(event.pointerId);
        if (Math.abs(action.next - action.width) >= 1) commit(action.image, `${action.next}px`);
        else measure();
    };

    if (disabled || !selected || !box) return null;
    return <>
        <div className="eure-image-resize-layer" style={{ top: box.offset, height: box.viewport }}>
            <div className="eure-image-selection" style={{ left: box.left, top: box.top, width: box.width, height: box.height }}>
                <button ref={handle} type="button" className="eure-image-resize-handle" aria-label="Cambiar tamaño de la imagen. Arrastrá o usá las flechas." title="Arrastrá para cambiar el tamaño" onPointerDown={begin} onPointerMove={move} onPointerUp={end} onPointerCancel={() => { cancel(); measure(); }} onLostPointerCapture={() => { if (drag.current) { cancel(); measure(); } }}
                    onKeyDown={(event) => {
                        if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                            event.preventDefault();
                            const change = (event.shiftKey ? 50 : 10) * (['ArrowLeft', 'ArrowUp'].includes(event.key) ? -1 : 1);
                            const next = Math.round(Math.max(Math.min(32, limit(selected)), Math.min(limit(selected), box.width + change)));
                            commit(selected, `${next}px`); requestAnimationFrame(() => handle.current?.focus());
                        }
                    }} />
            </div>
        </div>
        <div className="eure-rich-toolbar eure-image-toolbar" role="group" aria-label="Tamaño de la imagen seleccionada">
            <span>Imagen</span>{(['25%', '50%', '100%', 'original'] as const).map((size) => <button type="button" key={size} onMouseDown={(event) => event.preventDefault()} onClick={() => commit(selected, size)}>{size === 'original' ? 'Original' : size}</button>)}
            <output aria-live="polite">{Math.round(box.width)} × {Math.round(box.height)} px</output>
            <small>Arrastrá la esquina para ajustar. Se mantienen las proporciones.</small>
        </div>
    </>;
}
