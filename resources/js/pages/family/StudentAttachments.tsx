import axios from 'axios';
import { FileText, FolderOpen, Save, Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import FamilyDocumentsFrame, { type DocumentsPageProps } from '../../components/family/FamilyDocumentsFrame';
import { EureAlert, EureButtonPrimary, EureButtonSecondary, EureCard } from '../../components/ui/eure';

type Props = DocumentsPageProps & { upload: { studentId: string; tempId: string; submitUrl: string; backUrl: string } };
type SelectedFile = { id: string; file: File };
const extensions = /\.(jpe?g|png|gif|pdf|docx?|rtf|xlsx?)$/i;
const size = (bytes: number) => bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;

function FilePreview({ file }: { file: File }) {
    const [url, setUrl] = useState('');
    useEffect(() => {
        if (!/^image\/(jpeg|png|gif)$/.test(file.type)) return;
        const next = URL.createObjectURL(file); setUrl(next);
        return () => URL.revokeObjectURL(next);
    }, [file]);
    return url ? <img src={url} alt="" /> : <FileText size={27} aria-hidden="true" />;
}

export default function StudentAttachments(props: Props) {
    return <AttachmentForm key={props.upload.tempId} {...props} />;
}

function AttachmentForm(props: Props) {
    const { upload } = props;
    const [files, setFiles] = useState<SelectedFile[]>([]);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);
    const [progress, setProgress] = useState(0);
    const [uncertain, setUncertain] = useState(false);
    const [expired, setExpired] = useState(false);
    const [dragging, setDragging] = useState(false);
    const input = useRef<HTMLInputElement>(null);
    const nextFileId = useRef(0);
    const working = useRef(false), leaving = useRef(false), mounted = useRef(true);
    useEffect(() => {
        mounted.current = true;
        return () => { mounted.current = false; };
    }, []);
    useEffect(() => {
        const warn = (event: BeforeUnloadEvent) => {
            if ((files.length || working.current) && !leaving.current) { event.preventDefault(); event.returnValue = ''; }
        };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [files.length]);
    const addFiles = (selected: File[]) => {
        if (working.current || uncertain) return;
        const invalid = selected.filter((file) => !extensions.test(file.name) || !file.size || file.size > 12 * 1024 * 1024);
        setError(invalid.length ? `No se agregaron: ${invalid.map((file) => file.name).join(', ')}. Usá los formatos indicados, hasta 12 MB por archivo.` : '');
        const additions = selected.filter((file) => !invalid.includes(file)).map((file) => ({ id: String(++nextFileId.current), file }));
        setFiles((current) => [...current, ...additions]);
    };
    const submit = async () => {
        if (working.current || !files.length) return;
        working.current = true; setBusy(true); setError(''); setProgress(0); setExpired(false);
        const data = new FormData(); data.append('tempId', upload.tempId); data.append('fileCount', String(files.length));
        files.forEach(({ file }) => data.append('files[]', file));
        try {
            const response = await axios.post<{ redirectUrl: string }>(upload.submitUrl, data, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, timeout: 120_000,
                onUploadProgress: (event) => { if (mounted.current && event.total) setProgress(Math.round(event.loaded / event.total * 100)); },
            });
            if (mounted.current) { leaving.current = true; window.location.assign(response.data.redirectUrl); }
        } catch (failure) {
            if (!mounted.current) return;
            const status = axios.isAxiosError(failure) ? failure.response?.status : undefined;
            const validation = axios.isAxiosError(failure) ? Object.values(failure.response?.data?.errors ?? {}).flat().join(' ') : '';
            const knownRejection = status !== undefined && [401, 403, 413, 419, 422].includes(status);
            setUncertain(!knownRejection);
            setExpired(status === 401 || status === 419);
            setError(status === 401 || status === 419 ? 'Tu sesión venció. Volvé a ingresar y abrí «Nuevo adjunto» nuevamente. Tendrás que volver a seleccionar los archivos.'
                : status === 403 ? 'No se pudo autorizar esta carga. Volvé a la ficha y abrí «Nuevo adjunto» nuevamente.'
                : status === 413 ? 'La carga supera el límite del servidor. Probá guardar menos archivos juntos.'
                : status === 422 ? validation || 'Revisá los archivos seleccionados.'
                : 'No pudimos confirmar el guardado. Reintentá con estos mismos archivos: si ya llegaron, no se duplicarán.');
        } finally {
            working.current = false;
            if (mounted.current) setBusy(false);
        }
    };
    const locked = busy || uncertain;
    return <FamilyDocumentsFrame props={props} studentId={upload.studentId} title="Nuevo adjunto" uploading>
        <EureCard title="Agregar archivos al legajo" icon={FolderOpen} description="Seleccioná los archivos y revisalos antes de guardar.">
            <form onSubmit={(event) => { event.preventDefault(); void submit(); }} className="documents-upload-form" aria-busy={busy}>
                {error && <EureAlert message={error} />}
                {expired && <a className="documents-login" href={props.loginUrl} target="_blank" rel="noopener noreferrer">Volver a ingresar en otra pestaña</a>}
                <div className={`documents-drop ${dragging ? 'is-dragging' : ''}`} onDragOver={(event) => { event.preventDefault(); if (!locked) setDragging(true); }} onDragLeave={() => setDragging(false)} onDrop={(event) => { event.preventDefault(); setDragging(false); addFiles(Array.from(event.dataTransfer.files)); }}>
                    <Upload size={28} aria-hidden="true" /><div><strong>Arrastrá los archivos acá</strong><p>JPG, PNG, GIF, PDF, Word, Excel o RTF · Hasta 12 MB por archivo.</p></div>
                    <EureButtonSecondary disabled={locked} onClick={() => input.current?.click()}>Elegir archivos</EureButtonSecondary>
                    <input ref={input} type="file" hidden multiple accept=".jpeg,.jpg,.png,.gif,.pdf,.doc,.docx,.rtf,.xls,.xlsx" disabled={locked} onChange={(event) => { addFiles(Array.from(event.target.files ?? [])); event.target.value = ''; }} />
                </div>
                {files.length > 0 && <ul className="documents-selected">{files.map(({ id, file }) => <li key={id}><FilePreview file={file} /><div><strong>{file.name}</strong><small>{size(file.size)}</small></div><EureButtonSecondary disabled={locked} onClick={() => setFiles((current) => current.filter((item) => item.id !== id))} aria-label={`Quitar ${file.name}`}><Trash2 size={16} /><span>Quitar</span></EureButtonSecondary></li>)}</ul>}
                {busy && <div className="documents-progress" role="status"><progress max={100} value={progress} aria-label="Progreso de carga" /><span>{progress < 100 ? `Subiendo archivos… ${progress}%` : 'Guardando en el legajo…'}</span></div>}
                <div className="documents-upload-actions"><span>{files.length ? `${files.length} ${files.length === 1 ? 'archivo seleccionado' : 'archivos seleccionados'}` : 'Los archivos se incorporan al presionar Guardar.'}</span><a className="eureui-button-secondary" href={upload.backUrl}>Volver a la ficha</a><EureButtonPrimary type="submit" disabled={!files.length} loading={busy}><Save size={16} />{uncertain ? 'Reintentar guardado' : 'Guardar archivos'}</EureButtonPrimary></div>
            </form>
        </EureCard>
    </FamilyDocumentsFrame>;
}
