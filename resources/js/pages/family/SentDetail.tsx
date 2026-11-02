import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, Download, File, MessageSquareReply, Paperclip, Send } from 'lucide-react';
import { useEffect } from 'react';
import { EureAvatar } from '../../components/eureka';
import { EureCard, EureHumanDate } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import FamilySenderQuickCard from '../../components/family/FamilySenderQuickCard';
import FamilyHtmlContent from '../../components/family/FamilyHtmlContent';
import { useAnnounceReading } from '../../components/family/useCommunicationStates';
import type { SentMessage } from '../../components/family/sent-table';
import type { HomeProps } from '../../components/family/types';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';
import '../../../css/eureka-sent.css';

type DetailProps = Omit<HomeProps, 'enrollmentResult'> & {
    sent: { studentId: string; url: string; createUrl: string; message: SentMessage & {
        html: string; reply: string | null; attachments: { id: string; name: string; url: string }[];
    } };
};

export default function SentDetail(props: DetailProps) {
    const { sent, portal, theme, homeUrl } = props;
    const { message } = sent;
    const student = portal.students.find((item) => item.id === sent.studentId)!;
    useAnnounceReading(`${theme.clientId}:${portal.user.id}`, `sent:${student.id}:${message.id}`, Boolean(message.answeredAt && !message.replyUnread));
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); }
        catch { /* The selector still works without storage. */ }
    }, [theme.clientId, portal.user.id, student.id]);

    return <EurekaFrame theme={theme} className="family-portal family-received family-sent"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={notifications} close={close} currentUrl={sent.url}
            onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links.sent); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><a href={sent.url}>Enviadas y respuestas</a><ChevronRight size={14} /><strong aria-current="page">{message.number}</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Mensaje ${message.number} · ${student.name}`} />
        <div className="received-heading sent-detail-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} /></a>
            <div className="received-heading-text"><h1>{message.subject}</h1><p><strong>{student.name}</strong><span> · {studentGroup(student)}</span></p></div>
            <a href={sent.url} className="eureui-button-secondary"><ArrowLeft size={17} />Volver al listado</a>
        </div>
        {error && <div className="family-home-error" role="status">No pudimos actualizar los pendientes. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <EureCard className="sent-participants">
            <div className="sent-person"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /><div><small>De</small><strong>{portal.user.name}</strong></div></div>
            <div className="sent-person"><FamilySenderQuickCard sender={message.recipient} studentName={student.name} className="received-sender">
                <EureAvatar name={message.recipient.name} src={message.recipient.avatar ?? undefined} /><span className="received-sender-text"><small>Para</small><strong className="received-sender-name">{message.recipient.name}</strong></span>
            </FamilySenderQuickCard><span className="sent-read-state">{message.readAt ? <>Leído por la institución <EureHumanDate value={message.readAt} /></> : 'Sin leer por la institución'}</span></div>
        </EureCard>
        <EureCard title="Tu mensaje" icon={Send} className="sent-message-body">
            <div className="sent-message-date">Enviado <EureHumanDate value={message.dateTime} /></div>
            <FamilyHtmlContent html={message.html} title={`mensaje ${message.number}`} theme={theme} emptyText="Este mensaje no tiene contenido." minHeight={100} />
        </EureCard>
        <section id="respuesta"><EureCard title="Respuesta de la institución" icon={MessageSquareReply} className="sent-reply">
            {message.answeredAt ? <><div className="sent-message-date">Respondido <EureHumanDate value={message.answeredAt} /></div><p className="sent-reply-text">{message.reply || 'La respuesta no contiene texto.'}</p></>
                : <p className="sent-empty-reply">Todavía no recibiste una respuesta.</p>}
        </EureCard></section>
        <EureCard title="Adjuntos" icon={Paperclip} className="sent-attachments">
            {message.attachments.length ? <ul>{message.attachments.map((file) => <li key={file.id}><a href={file.url} download={file.name} target="_blank" rel="noopener noreferrer"><File size={22} /><span>{file.name}</span><Download size={17} /></a></li>)}</ul>
                : <p className="sent-empty-reply">Este mensaje no tiene archivos adjuntos.</p>}
        </EureCard>
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
