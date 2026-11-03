import { Head } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CalendarDays, Camera, ChevronRight, ExternalLink, File, FileImage, FileText, FolderOpen, GraduationCap, Inbox, Plus, Send, Users, Wallet } from 'lucide-react';
import { useEffect } from 'react';
import { EureAvatar } from '../../components/eureka';
import EureCard from '../../components/ui/eure/EureCard';
import EureAlert from '../../components/ui/eure/EureAlert';
import EureHumanDate from '../../components/ui/eure/EureHumanDate';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import type { HomeProps } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-student-profile.css';

type ProfileProps = Omit<HomeProps, 'enrollmentResult'> & {
    profile: {
        studentId: string; newAttachmentUrl: string; success: string | null;
        communications: { id: string; subject: string; url: string; date: string | null; dateHuman: string | null; dateTime: string | null; unread: boolean; sender: { name: string; avatar: string | null } }[];
        attachments: { id: string; name: string; extension: string; url: string }[];
    };
};

export default function StudentProfile(props: ProfileProps) {
    const { portal, profile, theme, institution, homeUrl } = props;
    const student = portal.students.find((item) => item.id === profile.studentId)!;
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const counts = notifications.byStudent.find((item) => item.studentId === student.id);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); }
        catch { /* Navigation remains available without browser storage. */ }
    }, [theme.clientId, portal.user.id, student.id]);
    const selectStudent = (id: string) => {
        const next = portal.students.find((item) => item.id === id);
        if (next && next.id !== student.id) window.location.assign(next.links.profile);
    };

    return <EurekaFrame theme={theme} className="family-portal family-profile"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} onSelect={selectStudent} notifications={notifications} close={close} currentUrl={student.links.profile} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><span>Alumnos</span><ChevronRight size={14} /><strong aria-current="page">Ficha</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`Ficha · ${student.name}`} />
        <div className="student-profile-heading"><div><p className="eyebrow">Ficha del estudiante</p><h1>{student.name}</h1></div><a href={homeUrl}><ArrowLeft size={17} />Volver al inicio</a></div>
        {profile.success && <EureAlert type="success" message={profile.success} className="student-profile-feedback" />}
        {error && <div className="family-home-error" role="status">No pudimos actualizar los pendientes. Mostramos el último dato recibido. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <div className="student-profile-top">
            <EureCard title="Datos escolares" icon={GraduationCap}>
                <div className="student-profile-identity">
                    <div className="student-profile-photo"><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /><a href={student.links.photo}><Camera size={16} />{student.avatar ? 'Cambiar foto' : 'Agregar foto'}</a></div>
                    <dl><div><dt>Curso</dt><dd>{student.course || 'Sin curso asignado'}</dd></div><div><dt>División</dt><dd>{student.division || 'Sin división asignada'}</dd></div><div><dt>Turno</dt><dd>{student.shift || 'Sin turno asignado'}</dd></div>{student.academicContact && <div><dt>Responsable académico</dt><dd>{student.academicContact}</dd></div>}</dl>
                </div>
                <nav className="student-profile-actions" aria-label="Accesos del alumno">
                    <a href={student.links.received}><Inbox size={17} />Recibidas{Boolean(counts?.received) && <span>{counts!.received} sin leer</span>}</a>
                    <a href={student.links.sent}><Send size={17} />Enviadas{Boolean(counts?.replies) && <span>{counts!.replies} sin leer</span>}</a>
                    <a href={student.links.documents}><FileText size={17} />{portal.labels.documents}</a>
                    <a href={student.links.attendance}><CalendarDays size={17} />Inasistencias</a>
                    <a href={student.links.account}><Wallet size={17} />{portal.labels.account}</a>
                    <a href={student.links.payments}><Wallet size={17} />{portal.labels.payments}</a>
                </nav>
            </EureCard>
            <EureCard title="Responsables" icon={Users}>
                {student.guardians.length ? <ul className="student-profile-guardians">{student.guardians.map((guardian) => <li key={`${guardian.id}-${guardian.role}`}><a href={guardian.url}><EureAvatar name={guardian.name} src={guardian.avatar ?? undefined} /><div><strong>{guardian.name}</strong><span>{guardian.role || 'Responsable'}</span></div><ChevronRight size={17} aria-hidden="true" /></a></li>)}</ul> : <p className="student-profile-empty">No hay responsables registrados.</p>}
            </EureCard>
        </div>
        <div className="student-profile-bottom">
            <EureCard title="Últimas comunicaciones" icon={Inbox} description="Las cinco más recientes dirigidas a vos para este alumno.">
                {profile.communications.length ? <ul className="student-profile-messages">{profile.communications.map((message) => <li key={message.id}><div className={`student-profile-message-row ${message.unread ? 'is-unread' : ''}`}>
                    <EureAvatar name={message.sender.name} src={message.sender.avatar ?? undefined} />
                    <div><a className="student-profile-message-subject" href={message.url}><strong title={message.subject}>{message.subject}</strong></a><div className="student-profile-message-meta">
                        <span className="student-profile-sender" title={message.sender.name}>{message.sender.name}</span>
                        {message.dateTime && <EureHumanDate value={message.dateTime} />}
                        {message.unread && <span className="student-profile-unread">Sin leer</span>}
                    </div></div><ChevronRight size={17} aria-hidden="true" />
                </div></li>)}</ul> : <p className="student-profile-empty">Todavía no tenés comunicaciones para este alumno.</p>}
                <a className="student-profile-more" href={student.links.received}>Ver todas las comunicaciones<ArrowRight size={16} /></a>
            </EureCard>
            <EureCard title="Legajo" icon={FolderOpen} description="Archivos adjuntos del alumno.">
                <a className="eureui-button-primary student-profile-upload" href={profile.newAttachmentUrl}><Plus size={17} />Nuevo adjunto</a>
                {profile.attachments.length ? <ul className="student-profile-files">{profile.attachments.map((attachment) => {
                    const Icon = ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(attachment.extension) ? FileImage : attachment.extension === 'pdf' ? FileText : File;
                    return <li key={attachment.id}><a href={attachment.url} target="_blank" rel="noopener noreferrer"><Icon size={24} aria-hidden="true" /><div><strong>{attachment.name}</strong><span>{attachment.extension.toUpperCase() || 'Archivo'} · Abrir en otra pestaña</span></div><ExternalLink size={16} aria-hidden="true" /></a></li>;
                })}</ul> : <p className="student-profile-empty">Todavía no hay archivos en el legajo. Podés agregarlos con «Nuevo adjunto».</p>}
            </EureCard>
        </div>
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
