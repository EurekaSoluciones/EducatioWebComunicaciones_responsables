import { Download, File } from 'lucide-react';

export type FamilyAttachment = { id: string; name: string; url: string };

export default function FamilyAttachmentList({ files, emptyText = 'No hay archivos adjuntos.' }: { files: FamilyAttachment[]; emptyText?: string }) {
    return files.length ? <ul className="family-attachment-list">{files.map((file) => <li key={file.id}>
        <a href={file.url} download={file.name} target="_blank" rel="noopener noreferrer"><File size={22} aria-hidden="true" /><span>{file.name}</span><Download size={17} aria-hidden="true" /></a>
    </li>)}</ul> : <p className="eure-muted">{emptyText}</p>;
}
