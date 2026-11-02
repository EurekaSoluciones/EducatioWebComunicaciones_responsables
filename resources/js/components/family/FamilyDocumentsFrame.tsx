import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';
import { EureAvatar } from '../eureka';
import FamilyNavigation from './FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from './FamilyNotifications';
import type { HomeProps } from './types';
import { studentGroup } from './types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-documents.css';

export type DocumentsPageProps = Omit<HomeProps, 'enrollmentResult'>;

export default function FamilyDocumentsFrame({ props, studentId, title, uploading = false, children }: {
    props: DocumentsPageProps; studentId: string; title: string; uploading?: boolean; children: ReactNode;
}) {
    const { portal, theme, homeUrl, institution } = props;
    const student = portal.students.find((item) => item.id === studentId)!;
    const { data, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); } catch { /* Optional. */ }
    }, [theme.clientId, portal.user.id, student.id]);
    return <EurekaFrame theme={theme} className="family-portal family-documents"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={data} close={close}
            currentUrl={uploading ? student.links.profile : student.links.documents}
            onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(uploading ? next.links.profile : next.links.documents); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} />{uploading && <><a href={student.links.profile}>Ficha del alumno</a><ChevronRight size={14} /></>}<strong aria-current="page">{title}</strong></nav>}
        actions={<><FamilyNotifications data={data} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`${title} · ${student.name}`} />
        <div className="documents-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a><div><h1>{title}</h1><p><strong>{student.name}</strong><span>{studentGroup(student)}</span></p></div><a className="documents-back" href={student.links.profile}><ArrowLeft size={17} />Volver a la ficha</a></div>
        {children}
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
