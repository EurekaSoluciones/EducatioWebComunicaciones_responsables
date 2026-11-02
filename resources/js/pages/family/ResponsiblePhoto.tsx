import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import FamilyPhotoEditor from '../../components/family/FamilyPhotoEditor';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import useSelectedStudent from '../../components/family/useSelectedStudent';
import type { ResponsibleProfileProps } from '../../components/family/responsible-profile';
import EurekaFrame from '../../layouts/EurekaFrame';

export default function ResponsiblePhoto(props: ResponsibleProfileProps) {
    const { portal, profile, theme, homeUrl } = props;
    const { selected, select } = useSelectedStudent(portal.students, `family-student:${theme.clientId}:${portal.user.id}`);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);

    return <EurekaFrame theme={theme} className="family-portal family-photo"
        sidebar={(close) => <FamilyNavigation {...props} selected={selected} onSelect={select} notifications={notifications} close={close} currentUrl={profile.photoUrl} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><a href={profile.url}>Mi perfil</a><ChevronRight size={14} /><strong aria-current="page">Foto</strong></nav>}
        actions={<FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} />}>
        <Head title="Mi foto de perfil" />
        <div className="student-photo-heading"><div><p className="eyebrow">Mi foto de perfil</p><h1>{portal.user.name}</h1></div><a href={profile.url}><ArrowLeft size={17} />Volver a mi perfil</a></div>
        <FamilyPhotoEditor name={portal.user.name} avatar={portal.user.avatar} updateUrl={profile.updatePhotoUrl} method="POST"
            cancelUrl={profile.url} csrfToken={props.csrfToken} loginUrl={props.loginUrl} />
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
