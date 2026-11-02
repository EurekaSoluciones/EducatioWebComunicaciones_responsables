export type ImageWidth = '25%' | '50%' | '100%' | 'original' | `${number}px`;

export function proportionalImageWidth(width: number, height: number, dx: number, dy: number, limit: number): number {
    const ratio = height / Math.max(1, width);
    // Project movement onto the image diagonal; vertical and horizontal gestures both work.
    const delta = (dx + dy * ratio) / (1 + ratio * ratio);
    return Math.round(Math.max(Math.min(32, limit), Math.min(limit, width + delta)));
}

export function resizedImageHtml(image: HTMLImageElement, width: ImageWidth): string {
    const copy = image.cloneNode(true) as HTMLImageElement;
    copy.removeAttribute('width'); copy.removeAttribute('height');
    if (width === 'original') copy.style.removeProperty('width');
    else copy.style.width = width;
    copy.style.height = 'auto'; copy.style.maxWidth = '100%';
    return copy.outerHTML;
}

export function commitImageWidth(root: HTMLElement, image: HTMLImageElement, width: ImageWidth): HTMLImageElement {
    if (!root.isContentEditable || !root.contains(image)) throw new Error('Seleccioná nuevamente la imagen para cambiar su tamaño.');
    const index = Array.from(root.querySelectorAll('img')).indexOf(image);
    const doc = root.ownerDocument;
    root.focus();
    const range = doc.createRange(); range.selectNode(image);
    const selection = doc.getSelection(); selection?.removeAllRanges(); selection?.addRange(range);
    // Replace only this image in one native editing transaction: undo restores its old size.
    if (!doc.execCommand('insertHTML', false, resizedImageHtml(image, width))) throw new Error('El navegador no pudo cambiar el tamaño de la imagen.');
    return root.querySelectorAll('img')[index];
}
