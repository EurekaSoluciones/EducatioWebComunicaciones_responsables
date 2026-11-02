import { Head } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeft, ChevronRight, File, ImagePlus, Paperclip, Send, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { EureAvatar, EureDialog } from '../../components/eureka';
import { EureAlert, EureButtonPrimary, EureButtonSecondary, EureCard, EureInputGroup } from '../../components/ui/eure';
import EureRichTextEditor from '../../components/ui/eure/EureRichTextEditor';
import { cleanEditorHtml, editorHasContent } from '../../components/ui/eure/rich-text';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import FamilyHtmlContent from '../../components/family/FamilyHtmlContent';
import type { HomeProps } from '../../components/family/types';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';
import '../../../css/eureka-compose.css';

type DraftFile = { id: string; name: string; url: string; previousUrl?: string; image: boolean };
type Recipient = { id: string; name: string; description: string; role: string; avatar: string | null };
type ComposeProps = Omit<HomeProps, 'enrollmentResult'> & { compose: {
    studentId: string; tempId: string; recipients: Recipient[]; submitUrl: string; uploadUrl: string; listUrl: string; files: DraftFile[]; emojiBaseUrl: string;
} };
type Draft = { recipient: string; subject: string; html: string };
function restoreDraft(key: string, files: DraftFile[]): Draft {
    try {
        const draft = JSON.parse(sessionStorage.getItem(key) ?? '{}');
        const doc = new DOMParser().parseFromString(typeof draft.html === 'string' ? draft.html : '', 'text/html');
        // Preserve existing drafts while adopting root-relative image URLs.
        const aliases = new Map(files.filter((file) => file.image && file.previousUrl).map((file) => [file.previousUrl, file.url]));
        doc.querySelectorAll('img').forEach((image) => {
            const path = aliases.get(image.getAttribute('src') ?? '');
            if (path) image.setAttribute('src', path);
        });
        return { recipient: typeof draft.recipient === 'string' ? draft.recipient : '', subject: typeof draft.subject === 'string' ? draft.subject : '', html: doc.body.innerHTML };
    } catch { return { recipient: '', subject: '', html: '' }; }
}
export default function Compose(props: ComposeProps) { return <ComposePage key={props.compose.tempId} {...props} />; }

function ComposePage(props: ComposeProps) {
    const { compose, portal, theme, homeUrl } = props;
    const student = portal.students.find((item) => item.id === compose.studentId)!;
    const storageKey = `family-compose:${theme.clientId}:${portal.user.id}:${compose.tempId}`;
    const [draft, setDraft] = useState(() => restoreDraft(storageKey, compose.files));
    const [files, setFiles] = useState(compose.files);
    const imageUrls = useMemo(() => files.filter((file) => file.image).map((file) => file.url), [files]);
    const [busy, setBusy] = useState('');
    const [imageBusy, setImageBusy] = useState(false);
    const [error, setError] = useState('');
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [expired, setExpired] = useState(false);
    const [confirm, setConfirm] = useState(false);
    const [storageUnavailable, setStorageUnavailable] = useState(false);
    const mounted = useRef(true), working = useRef(false), leaving = useRef(false);
    const pending = useRef<AbortController | null>(null);
    const picker = useRef<HTMLInputElement>(null);
    const { data: notifications, error: notificationError, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const recipient = compose.recipients.find((item) => item.id === draft.recipient);
    const dirty = Boolean(draft.subject || draft.html || draft.recipient || files.length);
    useEffect(() => {
        mounted.current = true;
        return () => { mounted.current = false; pending.current?.abort(); };
    }, []);
    useEffect(() => {
        try { sessionStorage.setItem(storageKey, JSON.stringify(draft)); setStorageUnavailable(false); }
        catch { setStorageUnavailable(true); }
    }, [draft, storageKey]);
    useEffect(() => {
        const warn = (event: BeforeUnloadEvent) => { if ((dirty || working.current) && !leaving.current) { event.preventDefault(); event.returnValue = ''; } };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [dirty]);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); } catch { /* Optional persistence. */ }
    }, [theme.clientId, portal.user.id, student.id]);
    const requestOptions = () => {
        pending.current = new AbortController();
        return { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrfToken }, timeout: 60_000, signal: pending.current.signal };
    };
    const failureText = (failure: unknown) => {
        if (!axios.isAxiosError(failure)) return failure instanceof Error ? failure.message : 'No se pudo completar la operación.';
        const status = failure.response?.status;
        if (mounted.current) { setErrors(failure.response?.data?.errors ?? {}); setExpired(status === 401 || status === 419); }
        return status === 401 || status === 419 ? 'Tu sesión venció. El texto se conserva en esta pestaña. Volvé a ingresar en otra pestaña y reintentá.'
            : status === 403 ? 'No se pudo autorizar este borrador. Conservá el texto y recargá la página.'
            : status === 409 ? 'Este borrador ya fue enviado. Usá Enviar para recuperar el mensaje, o volvé a Enviadas.'
            : status === 413 ? 'El archivo supera el tamaño permitido por el servidor.'
            : status === 422 ? Object.values(failure.response?.data?.errors ?? {}).flat().join(' ')
            : 'No pudimos confirmar la operación. Tu texto sigue acá; podés reintentar el envío sin duplicarlo.';
    };
    const start = (message: string) => { working.current = true; setBusy(message); setError(''); setErrors({}); setExpired(false); };
    const finish = () => { working.current = false; pending.current = null; if (mounted.current) setBusy(''); };
    const uploadFile = async (file: globalThis.File, image = false): Promise<DraftFile> => {
        if (file.size > 12 * 1024 * 1024 || !/\.(jpe?g|png|gif|webp|pdf|docx?|rtf|xlsx?)$/i.test(file.name)) throw new Error(`${file.name}: elegí una imagen, PDF, Word, Excel o RTF de hasta 12 MB.`);
        const data = new FormData(); data.append('file', file); data.append('kind', image ? 'image' : 'file'); data.append('tempId', compose.tempId);
        const response = await axios.post<DraftFile>(compose.uploadUrl, data, requestOptions());
        if (mounted.current) setFiles((current) => [...current, response.data]);
        return response.data;
    };
    const uploadImages = async (file: globalThis.File) => {
        // The editor serializes pasted images and holds imageBusy throughout the batch.
        start(`Subiendo ${file.name}…`);
        try { return await uploadFile(file, true); }
        catch (failure) { const message = failureText(failure); if (mounted.current) setError(message); throw new Error(message); }
        finally { finish(); }
    };
    const uploadAttachments = async (selected: globalThis.File[]) => {
        if (working.current || imageBusy || !selected.length) return;
        start('Subiendo archivos…');
        try {
            for (const file of selected) {
                if (!mounted.current) return;
                setBusy(`Subiendo ${file.name}…`);
                await uploadFile(file);
            }
        } catch (failure) { if (mounted.current) setError(failureText(failure)); }
        finally { finish(); }
    };
    const remove = async (file: DraftFile) => {
        if (working.current || imageBusy) return;
        start(`Quitando ${file.name}…`);
        try {
            await axios.delete(`${compose.uploadUrl}/${file.id}`, { ...requestOptions(), data: { tempId: compose.tempId } });
            if (mounted.current) setFiles((current) => current.filter((item) => item.id !== file.id));
        } catch (failure) { if (mounted.current) setError(failureText(failure)); }
        finally { finish(); }
    };
    const send = async () => {
        if (working.current || imageBusy) return;
        setConfirm(false); start('Enviando tu mensaje…');
        try {
            const response = await axios.post<{ redirectUrl: string }>(compose.submitUrl, { tempId: compose.tempId, destinatario: draft.recipient, asunto: draft.subject, msg: draft.html }, requestOptions());
            if (mounted.current) {
                leaving.current = true;
                try { sessionStorage.removeItem(storageKey); } catch { /* Optional persistence. */ }
                window.location.assign(response.data.redirectUrl);
            }
        } catch (failure) { if (mounted.current) setError(failureText(failure)); }
        finally { finish(); }
    };
    const blocked = Boolean(busy) || imageBusy;
    const attachments = files.filter((file) => !file.image);

    return <EurekaFrame theme={theme} className="family-portal family-received family-compose"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={notifications} close={close} currentUrl={compose.listUrl} onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links.sent); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><a href={compose.listUrl}>Enviadas y respuestas</a><ChevronRight size={14} /><strong aria-current="page">Nuevo mensaje</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={notificationError} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Nuevo mensaje · ${student.name}`} />
        <div className="received-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a><div className="received-heading-text"><h1>Nuevo mensaje a la institución</h1><p><strong>{student.name}</strong><span> · {studentGroup(student)}</span></p></div><a href={compose.listUrl} className="eureui-button-secondary"><ArrowLeft size={17} />Volver</a></div>
        {error && <EureAlert type="error" message={error} />}
        {expired && <a href={props.loginUrl} target="_blank" rel="noopener noreferrer">Ingresar en otra pestaña</a>}
        {storageUnavailable && <EureAlert type="warning" message="El navegador no permite conservar el borrador al recargar. Mantené esta pestaña abierta hasta enviar." />}
        <form onSubmit={(event) => {
            event.preventDefault();
            if (blocked) return;
            const clean = cleanEditorHtml(draft.html, imageUrls, compose.emojiBaseUrl);
            if (!recipient || !draft.subject.trim() || !editorHasContent(clean)) { setError('Elegí el destinatario y completá el asunto y el mensaje.'); return; }
            setDraft((current) => ({ ...current, html: clean })); setError(''); setConfirm(true);
        }}>
            <EureCard className="compose-people"><div className="compose-from"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /><div><small>De</small><strong>{portal.user.name}</strong><span>Por {student.name}</span></div></div>
                <div className="compose-to"><label className="eureui-label" htmlFor="compose-recipient">Para</label><select id="compose-recipient" className="received-select" value={draft.recipient} required disabled={blocked} onChange={(event) => setDraft((current) => ({ ...current, recipient: event.target.value }))}><option value="">Elegí a quién escribirle</option>{compose.recipients.map((person) => <option key={person.id} value={person.id}>{person.name} · {person.description || person.role}</option>)}</select>
                    {recipient && <div className="compose-recipient"><EureAvatar name={recipient.name} src={recipient.avatar ?? undefined} /><div><strong>{recipient.name}</strong><small>{[recipient.role, recipient.description].filter(Boolean).join(' · ')}</small></div></div>}
                    {errors.destinatario && <p className="compose-error">{errors.destinatario.join(' ')}</p>}
                    {!compose.recipients.length && <p className="compose-error">No hay destinatarios disponibles para este alumno. Comunicate con la institución.</p>}
                </div>
            </EureCard>
            <EureCard className="compose-message">
                <EureInputGroup name="compose-subject" label="Asunto" placeholder="Escribí el asunto del mensaje" maxLength={255} value={draft.subject} required disabled={blocked} error={Boolean(errors.asunto)} errorMessage={errors.asunto?.join(' ')} onChange={(event) => setDraft((current) => ({ ...current, subject: event.target.value }))} />
                <label className="eureui-label compose-message-label" htmlFor="compose-message">Mensaje</label>
                <EureRichTextEditor id="compose-message" initialHtml={draft.html} imageUrls={imageUrls} emojiBaseUrl={compose.emojiBaseUrl} emojiStorageKey={`eure-emoji-recent:v1:${theme.clientId}:${portal.user.id}`} disabled={blocked} onChange={(html) => setDraft((current) => ({ ...current, html }))} uploadImage={uploadImages} onBusyChange={setImageBusy} />
                {errors.msg && <p className="compose-error">{errors.msg.join(' ')}</p>}
            </EureCard>
            <EureCard className="compose-attachments" title="Adjuntos" icon={Paperclip}>
                <div className="compose-drop" onDragOver={(event) => event.preventDefault()} onDrop={(event) => { event.preventDefault(); void uploadAttachments(Array.from(event.dataTransfer.files)); }}>
                    <input ref={picker} type="file" multiple hidden accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.rtf,.xls,.xlsx" disabled={blocked} onChange={(event) => { void uploadAttachments(Array.from(event.target.files ?? [])); event.target.value = ''; }} />
                    <EureButtonSecondary disabled={blocked} onClick={() => picker.current?.click()}><Paperclip size={16} />Agregar archivos</EureButtonSecondary><span>O arrastralos acá. Imágenes, PDF, Word, Excel y RTF · hasta 12 MB por archivo.</span>
                </div>
                {attachments.length > 0 && <ul className="compose-file-list">{attachments.map((file) => <li key={file.id}><a href={file.url} target="_blank" rel="noopener noreferrer">{/\.(jpe?g|png|gif|webp)$/i.test(file.name) ? <img src={file.url} alt="" /> : <File size={25} />}<span>{file.name}</span></a><EureButtonSecondary disabled={blocked} aria-label={`Quitar ${file.name}`} onClick={() => void remove(file)}><Trash2 size={16} /></EureButtonSecondary></li>)}</ul>}
                {files.some((file) => file.image) && <details className="compose-inline-files"><summary><ImagePlus size={15} />Imágenes del mensaje ({files.filter((file) => file.image).length})</summary><p>Al quitarlas acá también se retiran del texto.</p><ul className="compose-file-list">{files.filter((file) => file.image).map((file) => <li key={file.id}><a href={file.url} target="_blank" rel="noopener noreferrer"><img src={file.url} alt="" /><span>{file.name}</span></a><EureButtonSecondary disabled={blocked} aria-label={`Quitar ${file.name} del mensaje`} onClick={() => void remove(file)}><Trash2 size={16} /></EureButtonSecondary></li>)}</ul></details>}
            </EureCard>
            <div className="compose-actions"><span role="status">{busy || (imageBusy ? 'Agregando imágenes…' : 'Revisá el mensaje antes de enviarlo.')}</span><EureButtonPrimary type="submit" disabled={blocked || !compose.recipients.length} loading={Boolean(busy)}><Send size={17} />Revisar y enviar</EureButtonPrimary></div>
        </form>
        <EureDialog open={confirm} onClose={() => setConfirm(false)} title="¿Enviar este mensaje?" className="compose-confirm">
            <div className="compose-confirm-person"><EureAvatar name={recipient?.name ?? ''} src={recipient?.avatar ?? undefined} /><div><small>Para {recipient?.name}</small><strong>{draft.subject}</strong><span>Por {student.name}</span></div></div>
            <div className="compose-preview"><FamilyHtmlContent html={cleanEditorHtml(draft.html, imageUrls, compose.emojiBaseUrl)} title="vista previa del mensaje" theme={theme} minHeight={100} /></div>
            {attachments.length > 0 && <p>{attachments.length} {attachments.length === 1 ? 'archivo adjunto' : 'archivos adjuntos'}: {attachments.map((file) => file.name).join(', ')}.</p>}
            <p className="compose-confirm-note">Una vez enviado, no podrás modificarlo.</p><div className="compose-actions"><EureButtonSecondary onClick={() => setConfirm(false)}>Seguir editando</EureButtonSecondary><EureButtonPrimary disabled={blocked} onClick={() => void send()}><Send size={16} />Sí, enviar</EureButtonPrimary></div>
        </EureDialog>
    </EurekaFrame>;
}
