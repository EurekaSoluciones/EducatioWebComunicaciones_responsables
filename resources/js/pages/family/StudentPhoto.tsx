import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import FamilyPhotoEditor from '../../components/family/FamilyPhotoEditor';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import type { HomeProps } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-student-photo.css';

type PhotoProps = Omit<HomeProps, 'enrollmentResult'> & { photo: { studentId: string; updateUrl: string } };

export default function StudentPhoto(props: PhotoProps) {
    const { portal, theme, photo, homeUrl } = props;
    const student = portal.students.find((item) => item.id === photo.studentId)!;
    const [busy, setBusy] = useState(false);
    const { data: notifications, error: notificationError, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);

    return <EurekaFrame theme={theme} className="family-portal family-photo"
        sidebar={(close) => <FamilyNavigation {...props} selected={student} notifications={notifications} currentUrl={student.links.profile} close={close}
            onSelect={(id) => { if (!busy) { const next = portal.students.find((item) => item.id === id); if (next && next.id !== student.id) window.location.assign(next.links.photo); } }} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><a href={student.links.profile}>Ficha</a><ChevronRight size={14} /><strong aria-current="page">Foto</strong></nav>}
        actions={<FamilyNotifications data={notifications} students={portal.students} error={notificationError} refresh={refresh} />}>
        <Head title={`Foto · ${student.name}`} />
        <div className="student-photo-heading"><div><p className="eyebrow">Foto del alumno</p><h1>{student.name}</h1></div><a href={student.links.profile}><ArrowLeft size={17} />Volver a la ficha</a></div>
        <FamilyPhotoEditor name={student.name} avatar={student.avatar} updateUrl={photo.updateUrl}
            cancelUrl={student.links.profile} csrfToken={props.csrfToken} loginUrl={props.loginUrl} onBusyChange={setBusy} />
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
