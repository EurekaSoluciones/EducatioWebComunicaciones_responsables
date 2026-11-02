import { Head } from '@inertiajs/react';
import { CheckCheck, ChevronRight, MessageCircle, Send } from 'lucide-react';
import { useEffect } from 'react';
import { EureAvatar } from '../../components/eureka';
import { EureAlert, EureCard } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import SentTable from '../../components/family/SentTable';
import useCommunicationStates from '../../components/family/useCommunicationStates';
import type { SentMessage } from '../../components/family/sent-table';
import type { HomeProps } from '../../components/family/types';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';
import '../../../css/eureka-sent.css';

type SentProps = Omit<HomeProps, 'enrollmentResult'> & {
    sent: { studentId: string; url: string; statusUrl: string; createUrl: string; success: string | null; messages: SentMessage[] };
};

export default function Sent(props: SentProps) {
    const { sent, portal, theme, homeUrl } = props;
    const student = portal.students.find((item) => item.id === sent.studentId)!;
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const states = useCommunicationStates(sent.messages, sent.statusUrl, `${theme.clientId}:${portal.user.id}`, portal.user.id, refresh);
    const unread = notifications.byStudent.find((item) => item.studentId === student.id)?.replies ?? 0;
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); }
        catch { /* The selector still works without storage. */ }
    }, [theme.clientId, portal.user.id, student.id]);

    return <EurekaFrame theme={theme} className="family-portal family-received family-sent"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={notifications} close={close} currentUrl={sent.url}
            onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links.sent); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><strong aria-current="page">Enviadas y respuestas</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Enviadas y respuestas · ${student.name}`} />
        <div className="received-heading sent-list-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a>
            <div className="received-heading-text"><div className="sent-heading-title"><h1>Enviadas y respuestas</h1>
                <span className={`received-summary ${unread ? 'has-unread' : ''}`}>{unread ? <MessageCircle size={17} /> : <CheckCheck size={17} />}{unread ? `${unread} ${unread === 1 ? 'respuesta sin leer' : 'respuestas sin leer'}` : 'Respuestas al día'}</span>
            </div><p><strong>{student.name}</strong><span> · {studentGroup(student)}</span></p></div>
            <a className="eureui-button-primary sent-create-link" href={sent.createUrl}><Send size={17} />Nuevo mensaje a la institución</a>
        </div>
        {sent.success && <EureAlert type="success" message={sent.success} />}
        {error && <div className="family-home-error" role="status">No pudimos actualizar los pendientes. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <EureCard className="received-results">
            {states.error && <div className="family-home-error" role="status">No pudimos actualizar los estados de lectura. <button type="button" onClick={states.refresh}>Reintentar</button></div>}
            <SentTable key={student.id} messages={states.messages} studentName={student.name} />
        </EureCard>
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
