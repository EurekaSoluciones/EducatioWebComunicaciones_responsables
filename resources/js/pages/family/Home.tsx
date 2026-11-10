import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ArrowRight, CalendarDays, Camera, Check, ChevronDown, ChevronRight, ClipboardCheck, FileText, Inbox, Send, Users, Wallet } from 'lucide-react';
import { EureAlert, EureAvatar, EureBadge, EureCard } from '../../components/eureka';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyAccountShortcut from '../../components/family/FamilyAccountShortcut';
import FamilyBoardPreviews from '../../components/family/FamilyBoardPreviews';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import type { HomeProps } from '../../components/family/types';
import useSelectedStudent from '../../components/family/useSelectedStudent';
import EurekaFrame from '../../layouts/EurekaFrame';

export default function Home(props: HomeProps & { overdueBalancesUrl: string }) {
    const { portal, theme, institution, homeUrl, enrollmentResult } = props;
    const { selected, select } = useSelectedStudent(portal.students, `family-student:${theme.clientId}:${portal.user.id}`);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const pending = notifications.received + notifications.replies;
    const [overdueStudents, setOverdueStudents] = useState<string[]>([]);
    const hasStudents = portal.students.length > 0;
    useEffect(() => {
        setOverdueStudents([]);
        if (!hasStudents) return;
        const controller = new AbortController();
        fetch(props.overdueBalancesUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal })
            .then(async (response) => {
                if (!response.ok) return;
                const data: { students?: { studentId: string; hasOverdue: boolean }[] } = await response.json();
                if (!controller.signal.aborted && Array.isArray(data.students)) {
                    setOverdueStudents(data.students.filter((item) => item.hasOverdue === true).map((item) => item.studentId));
                }
            })
            // An unavailable balance must not be presented as debt or as an account in good standing.
            .catch(() => {});
        return () => controller.abort();
    }, [props.overdueBalancesUrl, portal.user.id, theme.clientId, hasStudents]);

    return <EurekaFrame theme={theme} className="family-portal family-home-current"
        sidebar={(close) => <FamilyNavigation {...props} selected={selected} onSelect={select} notifications={notifications} close={close} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><span>Familias</span><ChevronRight size={14} /><strong aria-current="page">Inicio</strong></nav>}
        actions={<><FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title="Inicio familiar" />
        <section className="family-welcome" aria-labelledby="family-title">
            <div className="family-welcome-person"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} size="lg" /><div><p className="eyebrow">{institution}</p><h1 id="family-title">Hola, {portal.user.firstName}.</h1><p>Este es el espacio de tu familia.</p></div></div>
            <a className="eure-button eure-button-secondary" href={portal.user.editUrl}>{portal.user.avatar ? 'Editar mi perfil' : 'Completar mi perfil'}<ArrowRight size={16} /></a>
        </section>
        {error && <div className="family-home-error" role="status">Los pendientes no se pudieron actualizar. Mostramos el último dato recibido. <button type="button" onClick={refresh}>Reintentar</button></div>}
        <div className="family-home-grid">
            <section className="family-students" id="alumnos" aria-labelledby="students-title">
                <div className="family-section-heading"><div><h2 id="students-title">Estudiantes a tu cargo</h2><p>{pending ? 'Consultá las novedades y lo que queda por leer.' : 'Sus datos y accesos, siempre a mano.'}</p></div><span>{portal.students.length}</span></div>
                {portal.students.length === 0 && <EureAlert title="Todavía no hay alumnos vinculados">Si esperabas ver un alumno, comunicate con la institución para revisar la vinculación.</EureAlert>}
                {portal.students.map((student) => {
                    const counts = notifications.byStudent.find((item) => item.studentId === student.id);
                    const hasResult = enrollmentResult?.studentId === student.id;
                    const successful = hasResult && enrollmentResult.type !== 'error';
                    const shortcuts = <>
                        <a href={student.links.profile}><Users size={17} aria-hidden="true" />Ficha</a>
                        <a href={student.links.documents}><FileText size={17} aria-hidden="true" />{portal.labels.documents}</a>
                        <a href={student.links.attendance}><CalendarDays size={17} aria-hidden="true" />Inasistencias</a>
                        <FamilyAccountShortcut href={student.links.account} label={portal.labels.account} hasOverdue={overdueStudents.includes(student.id)} />
                        <a href={student.links.payments}><Wallet size={17} aria-hidden="true" />{portal.labels.payments}</a>
                    </>;
                    return <EureCard key={student.id} title={student.name} className={`family-student-card ${selected?.id === student.id ? 'is-selected' : ''}`}
                        action={<button type="button" className="family-select-action" onClick={() => select(student.id)} aria-pressed={selected?.id === student.id}>{selected?.id === student.id ? <><Check size={14} />En el menú</> : 'Ver en el menú'}</button>}>
                        <div className="family-student-top">
                        <div className="family-student-details"><a href={student.links.profile}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a>
                            <div className="family-student-meta">
                                {student.course && <strong>{student.course}</strong>}
                                {student.division && <strong>{student.division}</strong>}
                                {student.shift && <strong>{student.shift}</strong>}
                                {!student.course && !student.division && !student.shift && <strong>Sin curso asignado</strong>}
                                {student.academicContact && <p>{student.academicContact}</p>}
                                <a className="family-photo-link" href={student.links.photo}><Camera size={14} />{student.avatar ? 'Cambiar foto' : 'Agregar foto'}</a>
                            </div>
                        </div>
                        <div className="family-student-pending"><a href={student.links.received}><Inbox size={17} /><span>Comunicaciones recibidas</span><EureBadge tone={counts?.received ? 'warning' : 'success'}>{counts?.received ? `${counts.received} sin leer` : 'Al día'}</EureBadge></a>
                            <a href={student.links.sent}><Send size={17} /><span>Enviadas y respuestas</span><EureBadge tone={counts?.replies ? 'warning' : 'info'}>{counts?.replies ? `${counts.replies} respuestas sin leer` : 'Ver enviadas'}</EureBadge></a>
                        </div>
                        </div>
                        <nav className="family-student-shortcuts family-shortcuts-inline" aria-label={`Accesos de ${student.name}`}>{shortcuts}</nav>
                        <details className="family-shortcuts-dropdown">
                            <summary aria-label={`Más opciones de ${student.name}`}>Más opciones<ChevronDown size={17} aria-hidden="true" /></summary>
                            <nav className="family-student-shortcuts" aria-label={`Accesos de ${student.name}`}>{shortcuts}</nav>
                        </details>
                        {student.guardians.length > 0 && <div className="family-guardians"><p>Responsables</p>{student.guardians.map((guardian) => <a key={`${guardian.id}-${guardian.role}`} href={guardian.url}><EureAvatar name={guardian.name} src={guardian.avatar ?? undefined} size="sm" /><span>{guardian.name}<small>{guardian.role}</small></span></a>)}</div>}
                        {(student.enrollment.periodoActivo || hasResult) && <div className="family-enrollment">
                            {hasResult && <EureAlert title={successful ? 'Reinscripción' : 'Revisá la reinscripción'} tone={successful ? 'success' : 'danger'}>{enrollmentResult.message}</EureAlert>}
                            {!successful && student.enrollment.habilitaBoton && <a className="eure-button eure-button-primary" href={student.links.enrollment}><ClipboardCheck size={17} />Completar reinscripción</a>}
                            {!successful && student.enrollment.periodoActivo && !student.enrollment.habilitaBoton && !student.enrollment.mensaje && <button className="eure-button eure-button-secondary" disabled>Completar reinscripción</button>}
                            {!successful && student.enrollment.mensaje && <p>{student.enrollment.mensaje}</p>}
                        </div>}
                    </EureCard>;
                })}
            </section>
            <FamilyBoardPreviews boards={props.boardPreviews ?? []} theme={theme} />
        </div>
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
