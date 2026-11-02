import axios from 'axios';
import { MessageSquareReply, Paperclip, Send, Trash2 } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { EureDialog } from '../eureka';
import { EureAlert, EureButtonPrimary, EureButtonSecondary, EureCard, EureHumanDate } from '../ui/eure';
import FamilyAttachmentList from './FamilyAttachmentList';
import type { FamilyAttachment } from './FamilyAttachmentList';

export type ReceivedReplyData = {
    mode: number; answered: boolean; text: string; dateTime: string | null; options: string[];
    attachments: FamilyAttachment[]; draftFiles: FamilyAttachment[]; tempId: string | null;
    submitUrl: string | null; uploadUrl: string;
};

export default function ReceivedReply({ reply, csrfToken, loginUrl }: { reply: ReceivedReplyData; csrfToken: string; loginUrl: string }) {
    const id = useId();
    const [text, setText] = useState('');
    const [files, setFiles] = useState(reply.draftFiles);
    const [error, setError] = useState('');
    const [expired, setExpired] = useState(false);
    const [confirm, setConfirm] = useState(false);
    const [busy, setBusy] = useState('');
    const working = useRef(false);
    const mounted = useRef(true);
    const pending = useRef<AbortController | null>(null);
    const picker = useRef<HTMLInputElement>(null);
    useEffect(() => { mounted.current = true; return () => { mounted.current = false; pending.current?.abort(); }; }, []);
    const requestOptions = () => {
        pending.current = new AbortController();
        return { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken }, timeout: 60_000, signal: pending.current.signal };
    };
    const fail = (failure: unknown, prefix = '') => {
        if (!mounted.current) return;
        if (axios.isAxiosError(failure)) {
            const status = failure.response?.status;
            setExpired(status === 401 || status === 419);
            const fields = failure.response?.data?.errors as Record<string, string[]> | undefined;
            setError(prefix + (status === 401 || status === 419 ? 'Tu sesión venció. Volvé a ingresar.'
                : status === 403 ? 'No tenés permiso para realizar esta acción. Recargá la página.'
                : status === 413 ? 'El archivo supera el tamaño permitido por el servidor.'
                : fields ? Object.values(fields).flat().join(' ') : 'No pudimos confirmar la operación. Revisá la conexión y recargá la página antes de reintentar.'));
        } else setError(prefix + 'No se pudo completar la operación.');
    };
    const finish = () => { working.current = false; pending.current = null; if (mounted.current) setBusy(''); };
    const upload = async (selected: File[]) => {
        if (!selected.length || working.current) return;
        working.current = true; setError(''); setExpired(false);
        try {
            for (const file of selected) {
                if (!mounted.current) return;
                if (file.size > 12 * 1024 * 1024 || !/\.(jpe?g|png|gif|pdf|docx?|rtf|xlsx?)$/i.test(file.name)) {
                    setError(`${file.name}: elegí una imagen, PDF o documento de Office/RTF de hasta 12 MB.`); break;
                }
                setBusy(`Subiendo ${file.name}…`);
                const data = new FormData(); data.append('file', file); data.append('tempId', reply.tempId ?? '');
                try {
                    const response = await axios.post<FamilyAttachment>(reply.uploadUrl, data, requestOptions());
                    if (mounted.current) setFiles((current) => [...current, response.data]);
                } catch (failure) { fail(failure, `${file.name}: `); break; }
            }
        } finally { finish(); }
    };
    const remove = async (file: FamilyAttachment) => {
        if (working.current) return;
        working.current = true; setBusy(`Quitando ${file.name}…`); setError('');
        try {
            await axios.delete(`${reply.uploadUrl}/${file.id}`, { ...requestOptions(), data: { tempId: reply.tempId } });
            if (mounted.current) setFiles((current) => current.filter((item) => item.id !== file.id));
        } catch (failure) { fail(failure); } finally { finish(); }
    };
    const send = async () => {
        if (working.current || !reply.submitUrl) return;
        working.current = true; setConfirm(false); setBusy('Enviando tu respuesta…'); setError('');
        try {
            const response = await axios.post<{ redirectUrl: string }>(reply.submitUrl, { respuesta: text, tempId: reply.tempId }, requestOptions());
            if (mounted.current) window.location.assign(response.data.redirectUrl);
        } catch (failure) { fail(failure); } finally { finish(); }
    };

    return <EureCard title={reply.answered ? 'Tu respuesta' : 'Responder'} icon={MessageSquareReply} className="received-reply-card">
        {reply.answered ? <>
            <p className="received-reply-text">{reply.text || 'Respuesta enviada.'}</p>
            {reply.dateTime && <div className="received-detail-date">Enviada <EureHumanDate value={reply.dateTime} /></div>}
            {reply.attachments.length > 0 && <FamilyAttachmentList files={reply.attachments} />}
        </> : ![2, 3].includes(reply.mode) ? <p className="eure-muted">Esta comunicación no admite respuestas.</p> : <>
            {error && <EureAlert type="error" message={error} />}
            {expired && <a href={loginUrl}>Volver a ingresar</a>}
            <form onSubmit={(event) => { event.preventDefault(); if (!working.current) setConfirm(true); }}>
                <label className="eureui-label" htmlFor={`${id}-reply`}>{reply.mode === 3 ? 'Elegí tu respuesta' : 'Tu respuesta'}</label>
                {reply.mode === 3 ? <select id={`${id}-reply`} className="received-select" value={text} onChange={(event) => setText(event.target.value)} required disabled={Boolean(busy)}>
                    <option value="">Seleccioná una opción</option>{reply.options.map((option) => <option key={option} value={option}>{option}</option>)}
                </select> : <textarea id={`${id}-reply`} rows={4} value={text} onChange={(event) => setText(event.target.value)} required disabled={Boolean(busy)} placeholder="Escribí tu respuesta…" />}
                {reply.mode === 2 && <div className="received-reply-files" onDragOver={(event) => event.preventDefault()} onDrop={(event) => { event.preventDefault(); void upload(Array.from(event.dataTransfer.files)); }}>
                    <input ref={picker} type="file" multiple accept=".jpeg,.jpg,.png,.gif,.pdf,.doc,.docx,.rtf,.xls,.xlsx" hidden disabled={Boolean(busy)} onChange={(event) => { void upload(Array.from(event.target.files ?? [])); event.target.value = ''; }} />
                    <EureButtonSecondary disabled={Boolean(busy)} onClick={() => picker.current?.click()}><Paperclip size={17} />Adjuntar archivos</EureButtonSecondary>
                    <p>Podés arrastrarlos acá. Imágenes, PDF, Word, Excel o RTF; hasta 12 MB por archivo.</p>
                    {files.length > 0 && <ul>{files.map((file) => <li key={file.id}><a href={file.url} target="_blank" rel="noopener noreferrer">{file.name}</a><EureButtonSecondary disabled={Boolean(busy)} aria-label={`Quitar ${file.name}`} onClick={() => void remove(file)}><Trash2 size={15} /></EureButtonSecondary></li>)}</ul>}
                </div>}
                <div className="received-reply-actions"><EureButtonPrimary type="submit" disabled={Boolean(busy) || !text.trim()}><Send size={17} />Responder</EureButtonPrimary><small>Una vez enviada, no podés modificarla.</small></div>
                {busy && <p role="status" className="received-reply-progress">{busy}</p>}
            </form>
            <EureDialog open={confirm} onClose={() => setConfirm(false)} title="¿Enviar respuesta?">
                <p>Una vez enviada, no podrás modificarla.</p><p className="received-reply-preview">{text}</p>
                {files.length > 0 && <p>{files.length} {files.length === 1 ? 'archivo adjunto' : 'archivos adjuntos'}.</p>}
                <div className="received-reply-actions"><EureButtonSecondary onClick={() => setConfirm(false)}>Seguir editando</EureButtonSecondary><EureButtonPrimary onClick={() => void send()}>Sí, enviar</EureButtonPrimary></div>
            </EureDialog>
        </>}
    </EureCard>;
}
