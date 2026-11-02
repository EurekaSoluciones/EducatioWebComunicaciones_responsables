import { BookOpen, CalendarDays, CreditCard, FileText, Home, Inbox, LogOut, Send, UserRound, Wallet } from 'lucide-react';
import { EureAvatar } from '../eureka';
import type { HomeProps, Notifications, Student } from './types';
import FamilyStudentPicker from './FamilyStudentPicker';

export default function FamilyNavigation({ portal, institution, institutionLogoUrl, homeUrl, logoutUrl, csrfToken, selected, onSelect, notifications, close, currentUrl = homeUrl }: Pick<HomeProps, 'portal' | 'institution' | 'institutionLogoUrl' | 'homeUrl' | 'logoutUrl' | 'csrfToken'> & {
    currentUrl?: string;
    selected?: Student; onSelect: (id: string) => void; notifications: Notifications; close: () => void;
}) {
    const counts = notifications.byStudent.find((item) => item.studentId === selected?.id);
    const isCurrent = (href: string) => href.split(/[?#]/)[0] === currentUrl.split(/[?#]/)[0];
    const links = selected ? [
        { label: 'Comunicaciones recibidas', icon: Inbox, href: selected.links.received, count: counts?.received },
        { label: 'Enviadas y respuestas', icon: Send, href: selected.links.sent, count: counts?.replies },
        { label: 'Ficha del alumno', icon: UserRound, href: selected.links.profile },
        { label: portal.labels.documents, icon: FileText, href: selected.links.documents },
        { label: 'Inasistencias', icon: CalendarDays, href: selected.links.attendance },
        { label: portal.labels.account, icon: Wallet, href: selected.links.account },
        { label: portal.labels.payments, icon: CreditCard, href: selected.links.payments },
    ] : [];

    return <>
        <a href={homeUrl} className="pranzo-brand" onClick={close}>
            {institutionLogoUrl && <img className="family-institution-logo" src={institutionLogoUrl} alt="" />}
            <strong>{institution}</strong><small>Módulo Familias</small>
        </a>
        <div className="family-navigation-scroll">
            <nav className="family-general-nav" aria-label="Tu familia"><a href={homeUrl} aria-current={isCurrent(homeUrl) ? 'page' : undefined}><Home size={19} />Inicio familiar</a></nav>
            {selected ? <section className="family-student-context" aria-label={`Alumno seleccionado: ${selected.name}`}>
                <FamilyStudentPicker selected={selected} students={portal.students} notifications={notifications} onSelect={onSelect} />
                <nav className="family-student-nav" aria-label={`Accesos de ${selected.name}`}>{links.map(({ label, icon: Icon, href, count }) =>
                    <a key={href} href={href} onClick={close} aria-current={isCurrent(href) ? 'page' : undefined}><Icon size={19} aria-hidden="true" /><span>{label}</span>{Boolean(count) && <span className="family-nav-count" aria-label={`${count} sin leer`}>{count}</span>}</a>)}
                </nav>
            </section> : <p className="family-menu-empty">No hay alumnos vinculados a tu cuenta.</p>}
            <nav className="family-board-nav" aria-label="Carteleras de la institución"><p>Institución</p>
                {portal.boards.length ? portal.boards.map((board) => <a href={board.url} key={board.id} onClick={close} aria-current={isCurrent(board.url) ? 'page' : undefined}><BookOpen size={18} /><span>{board.name}</span></a>) : <small>No hay carteleras publicadas.</small>}
            </nav>
        </div>
        <div className="pranzo-sidebar-footer family-sidebar-footer">
            <div className="pranzo-scope"><small>Institución</small><strong>{institution}</strong></div>
            <a href={portal.user.profileUrl} className="pranzo-user" onClick={close}><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} /><div><strong>{portal.user.name}</strong><small>Mi perfil</small></div></a>
            <a className="family-password-link" href={portal.user.passwordUrl}>Cambiar contraseña</a>
            <form method="post" action={logoutUrl}><input type="hidden" name="_token" value={csrfToken} /><button type="submit" className="pranzo-back"><LogOut size={16} />Cerrar sesión</button></form>
        </div>
    </>;
}
