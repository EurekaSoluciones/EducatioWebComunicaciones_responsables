import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, Inbox, Paperclip } from 'lucide-react';
import { useEffect } from 'react';
import type { CSSProperties } from 'react';
import { EureAvatar } from '../../components/eureka';
import { EureAlert, EureCard, EureHumanDate } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import FamilySenderQuickCard from '../../components/family/FamilySenderQuickCard';
import type { FamilySender } from '../../components/family/FamilySenderQuickCard';
import FamilyHtmlContent from '../../components/family/FamilyHtmlContent';
import FamilyAttachmentList from '../../components/family/FamilyAttachmentList';
import type { FamilyAttachment } from '../../components/family/FamilyAttachmentList';
import ReceivedReply from '../../components/family/ReceivedReply';
import { useAnnounceReading } from '../../components/family/useCommunicationStates';
import type { ReceivedReplyData } from '../../components/family/ReceivedReply';
import type { HomeProps } from '../../components/family/types';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';
import '../../../css/eureka-received-detail.css';

type DetailProps = Omit<HomeProps, 'enrollmentResult'> & {
    received: { studentId: string; listUrl: string; success: string | null; readConfirmed: boolean; reply: ReceivedReplyData;
        message: {
            id: string; number: string; subject: string; html: string; type: string; dateTime: string | null;
            audience: string; groups: string[]; tags: string[]; sender: FamilySender; attachments: FamilyAttachment[];
            readers: { id: string; name: string; avatar: string | null; readAt: string | null }[];
        };
    };
};

// A tag always gets the same colour, so the same label reads alike across communications.
// A curated set rather than the whole wheel: the muddy yellow-green band washes out at the
// lightness these badges use, and the institutional gold would be confused with it.
const TAG_HUES = [352, 22, 140, 188, 214, 262, 300, 96];

function tagStyle(tag: string): CSSProperties {
    let hash = 0;
    for (let index = 0; index < tag.length; index += 1) hash = (hash * 31 + tag.charCodeAt(index)) % 9973;

    return { '--tag-hue': TAG_HUES[hash % TAG_HUES.length] } as CSSProperties;
}

export default function ReceivedDetail(props: DetailProps) {
    const { received, portal, theme, homeUrl } = props;
    const { message, reply } = received;
    const readCount = message.readers.filter((reader) => reader.readAt).length;
    const student = portal.students.find((item) => item.id === received.studentId)!;
    useAnnounceReading(`${theme.clientId}:${portal.user.id}`, `received:${student.id}:${message.id}`, received.readConfirmed);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); }
        catch { /* The selector still works without storage. */ }
    }, [theme.clientId, portal.user.id, student.id]);

    return <EurekaFrame theme={theme} className="family-portal family-received family-received-detail"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={notifications} close={close} currentUrl={received.listUrl}
            onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links.received); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><a href={received.listUrl}>Recibidas</a><ChevronRight size={14} /><strong aria-current="page">{message.number}</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`${message.subject} · ${student.name}`} />
        <div className="received-heading received-detail-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} /></a>
            <div className="received-heading-text"><h1>Comunicación recibida</h1><p><strong>{student.name}</strong><span> · {studentGroup(student)}</span></p></div>
            <a href={received.listUrl} className="eureui-button-secondary"><ArrowLeft size={17} />Volver al listado</a>
        </div>
        {received.success && <EureAlert type="success" message={received.success} className="received-detail-feedback" />}
        {error && <div className="family-home-error" role="status">No pudimos actualizar los pendientes. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <EureCard className="received-detail-people">
            <FamilySenderQuickCard sender={message.sender} studentName={student.name} className="received-sender">
                <EureAvatar name={message.sender.name} src={message.sender.avatar ?? undefined} />
                <span className="received-sender-text"><small>De</small><strong className="received-sender-name">{message.sender.name}</strong>{message.sender.description && <span className="received-sender-type">{message.sender.description}</span>}</span>
            </FamilySenderQuickCard>
            <div className="received-detail-audience"><small>Para</small><strong>{message.audience}</strong>{message.groups.length > 0 && <span>{message.groups.join(' · ')}</span>}</div>
            {message.readers.length > 0 && <div className="received-detail-reads">
                <small>Leída por {readCount} de {message.readers.length}</small>
                <ul className="received-detail-readers">{message.readers.map((reader) => <li key={reader.id} className={reader.readAt ? 'is-read' : ''}>
                    <EureAvatar name={reader.name} src={reader.avatar ?? undefined} />
                    <div><strong>{reader.name}</strong>{reader.readAt ? <EureHumanDate value={reader.readAt} /> : <small>Sin leer</small>}</div>
                </li>)}</ul>
            </div>}
        </EureCard>
        <EureCard icon={Inbox} className="received-detail-message" title={<>
            <span className="received-detail-subject">{message.subject}</span>
            {message.tags.map((tag) => <span key={tag} className="received-detail-tag" style={tagStyle(tag)}>{tag}</span>)}
        </>}>
            <div className="received-detail-date">
                {message.type && <span>{message.type}</span>}
                <span>Enviada <EureHumanDate value={message.dateTime} /></span>
                {message.attachments.length > 0 && <span className="received-detail-clip"><Paperclip size={12} aria-hidden="true" />{message.attachments.length} {message.attachments.length === 1 ? 'adjunto' : 'adjuntos'}</span>}
            </div>
            <FamilyHtmlContent html={message.html} title={`comunicación ${message.number}`} theme={theme} emptyText="Esta comunicación no tiene contenido." minHeight={100} />
            {/* Attachments belong with the message: you read what was sent before writing a reply. */}
            {message.attachments.length > 0 && <div className="received-detail-files">
                <h3><Paperclip size={15} aria-hidden="true" />{message.attachments.length === 1 ? 'Archivo adjunto' : `${message.attachments.length} archivos adjuntos`}</h3>
                <FamilyAttachmentList files={message.attachments} />
            </div>}
        </EureCard>
        <ReceivedReply key={`${message.id}:${student.id}`} reply={reply} csrfToken={props.csrfToken} loginUrl={props.loginUrl} />
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
