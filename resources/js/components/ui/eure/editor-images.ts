export type UploadedEditorImage = { url: string; name: string };

// Some clipboard implementations expose files only through DataTransfer.items.
export function transferredFiles(transfer: Pick<DataTransfer, 'files' | 'items'>): File[] {
    const files = Array.from(transfer.files);
    if (files.length) return files;
    return Array.from(transfer.items).flatMap((item) => {
        const file = item.kind === 'file' ? item.getAsFile() : null;
        return file ? [file] : [];
    });
}

export function prepareEditorImage(file: File): File {
    const mime = file.type.toLowerCase().replace(/^image\/jpg$/, 'image/jpeg');
    const extensions: Record<string, string> = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/gif': 'gif', 'image/webp': 'webp' };
    const extension = extensions[mime];
    const namedImage = /\.(jpe?g|png|gif|webp)$/i.test(file.name);
    if ((!extension && (mime !== '' || !namedImage)) || file.size > 12 * 1024 * 1024 || file.size === 0) {
        throw new Error('Usá imágenes JPG, PNG, GIF o WebP de hasta 12 MB.');
    }
    // Clipboard images may be named "image" with no extension. Keep server-side MIME validation.
    if (extension && (!namedImage || mime !== file.type)) return new File([file], `imagen.${extension}`, { type: mime, lastModified: file.lastModified });
    return file;
}

export function dropImageRange(root: HTMLElement, x: number, y: number): Range {
    const doc = root.ownerDocument;
    const position = doc.caretPositionFromPoint?.(x, y);
    let range = doc.caretRangeFromPoint?.(x, y) ?? null;
    if (position) { range = doc.createRange(); range.setStart(position.offsetNode, position.offset); range.collapse(true); }
    if (!range || !root.contains(range.startContainer)) {
        range = doc.createRange(); range.selectNodeContents(root); range.collapse(false);
    }
    return range;
}

export function insertEditorImages(root: HTMLElement, images: UploadedEditorImage[], range: Range | null): void {
    // Never issue a browser editing command while React still has contentEditable=false.
    if (!root.isContentEditable) throw new Error('El editor todavía no está disponible. Reintentá insertar las imágenes.');
    const doc = root.ownerDocument;
    const escape = (value: string) => value.replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]!));
    root.focus();
    const selection = doc.getSelection();
    selection?.removeAllRanges();
    if (!range || !root.contains(range.commonAncestorContainer)) { range = doc.createRange(); range.selectNodeContents(root); range.collapse(false); }
    selection?.addRange(range);
    if (!doc.execCommand('insertHTML', false, images.map((image) => `<img src="${escape(image.url)}" alt="${escape(image.name)}" style="max-width:100%;height:auto"><br>`).join(''))) {
        throw new Error('El navegador no pudo insertar las imágenes. Los archivos ya están subidos; podés reintentar.');
    }
}
