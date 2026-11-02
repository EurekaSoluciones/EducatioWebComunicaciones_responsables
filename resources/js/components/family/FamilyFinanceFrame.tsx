import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';
import { EureAvatar } from '../eureka';
import FamilyNavigation from './FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from './FamilyNotifications';
import type { HomeProps } from './types';
import { studentGroup } from './types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-received.css';
import '../../../css/eureka-finance.css';

export type FinancePageProps = Omit<HomeProps, 'enrollmentResult'>;
export default function FamilyFinanceFrame({ props, studentId, section, children }: {
    props: FinancePageProps; studentId: string; section: 'account' | 'payments'; children: ReactNode;
}) {
    const { portal, theme, homeUrl, institution } = props;
    const student = portal.students.find((item) => item.id === studentId)!;
    const title = portal.labels[section];
    const { data, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    useEffect(() => {
        try { sessionStorage.setItem(`family-student:${theme.clientId}:${portal.user.id}`, student.id); } catch { /* Optional. */ }
    }, [theme.clientId, portal.user.id, student.id]);
    return <EurekaFrame theme={theme} className="family-portal family-finance"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={data} close={close} currentUrl={student.links[section]}
            onSelect={(id) => { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links[section]); }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><strong aria-current="page">{title}</strong></nav>}
        actions={<><FamilyNotifications data={data} students={portal.students} error={error} refresh={refresh} /><a className="pranzo-header-avatar" href={portal.user.profileUrl} aria-label="Ver mi perfil"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /></a></>}>
        <Head title={`${title} · ${student.name}`} />
        <div className="finance-heading"><a href={student.links.profile} aria-label={`Ver ficha de ${student.name}`}><EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" /></a><div><h1>{title}</h1><p><strong>{student.name}</strong><span>{studentGroup(student)}</span></p></div><a className="finance-back" href={student.links.profile}><ArrowLeft size={17} />Volver a la ficha</a></div>
        <nav className="finance-tabs" aria-label="Consultas de cuenta"><a href={student.links.account} aria-current={section === 'account' ? 'page' : undefined}>{portal.labels.account}</a><a href={student.links.payments} aria-current={section === 'payments' ? 'page' : undefined}>{portal.labels.payments}</a></nav>
        {children}
        <footer className="eure-footer"><span>{institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
