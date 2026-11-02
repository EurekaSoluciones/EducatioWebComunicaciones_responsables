import { Head } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Camera, ChevronRight, ContactRound, KeyRound, Users } from 'lucide-react';
import { EureAvatar } from '../../components/eureka';
import { EureAlert, EureCard } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import useSelectedStudent from '../../components/family/useSelectedStudent';
import type { ResponsibleProfileProps } from '../../components/family/responsible-profile';
import { studentGroup } from '../../components/family/types';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-responsible-profile.css';

export default function ResponsibleProfile(props: ResponsibleProfileProps) {
    const { portal, profile, theme, homeUrl } = props;
    const { selected, select } = useSelectedStudent(portal.students, `family-student:${theme.clientId}:${portal.user.id}`);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const fields = [['Email', profile.email], ['Teléfono', profile.phone], ['Celular', profile.mobile], ['DNI', profile.dni]];

    return <EurekaFrame theme={theme} className="family-portal family-responsible-profile"
        sidebar={(close) => <FamilyNavigation {...props} selected={selected} onSelect={select} notifications={notifications} close={close} currentUrl={profile.url} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={homeUrl}>Inicio</a><ChevronRight size={14} /><strong aria-current="page">Mi perfil</strong></nav>}
        actions={<FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} />}>
        <Head title="Mi perfil" />
        <div className="responsible-profile-heading"><h1>Mi perfil</h1><a href={homeUrl}><ArrowLeft size={17} />Volver al inicio</a></div>
        {profile.success && <EureAlert type="success" message={profile.success} className="responsible-profile-feedback" />}
        <div className="responsible-profile-top">
            <EureCard title={portal.user.name} className="responsible-profile-person">
                <div className="responsible-profile-identity">
                    <EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} size="lg" />
                    <a className="eureui-button-secondary" href={profile.photoUrl}><Camera size={17} />{portal.user.avatar ? 'Cambiar foto' : 'Agregar foto'}</a>
                    {profile.login && <p>Usuario: <strong>{profile.login}</strong></p>}
                </div>
            </EureCard>
            <EureCard title="Datos personales" icon={ContactRound}>
                <dl className="responsible-profile-fields">{fields.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value || 'Sin informar'}</dd></div>)}</dl>
                <p className="responsible-profile-help">Para actualizar estos datos, comunicate con la institución.</p>
                <a className="responsible-profile-password" href={portal.user.passwordUrl}><KeyRound size={18} />Cambiar contraseña<ArrowRight size={16} /></a>
            </EureCard>
        </div>
        <EureCard title="Responsable de" icon={Users}>
            {portal.students.length ? <div className="responsible-profile-students">{portal.students.map((student) => {
                const role = student.guardians.find((guardian) => guardian.id === profile.responsibleId)?.role;
                return <a key={student.id} href={student.links.profile} className="responsible-profile-student">
                    <EureAvatar name={student.name} src={student.avatar ?? undefined} size="lg" />
                    <div><h3>{student.name}</h3>{role && <span>{role}</span>}<p>{studentGroup(student)}</p>{profile.studentPlans[student.id] && <p>{profile.studentPlans[student.id]}</p>}<strong>Ver ficha <ArrowRight size={15} /></strong></div>
                </a>;
            })}</div> : <p className="responsible-profile-help">No hay alumnos activos vinculados a tu cuenta.</p>}
        </EureCard>
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
