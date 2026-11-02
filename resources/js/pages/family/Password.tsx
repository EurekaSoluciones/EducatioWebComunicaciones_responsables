import { Head } from '@inertiajs/react';
import { ArrowLeft, ChevronRight, KeyRound } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EureAvatar } from '../../components/eureka';
import { EureButtonPrimary, EureCard, EurePasswordInputGroup } from '../../components/ui/eure';
import FamilyNavigation from '../../components/family/FamilyNavigation';
import FamilyNotifications, { useFamilyNotifications } from '../../components/family/FamilyNotifications';
import useSelectedStudent from '../../components/family/useSelectedStudent';
import type { ResponsibleProfileProps } from '../../components/family/responsible-profile';
import EurekaFrame from '../../layouts/EurekaFrame';
import '../../../css/eureka-responsible-profile.css';
import '../../../css/eureka-password.css';

type Props = ResponsibleProfileProps & { passwordUpdateUrl: string; errors: Record<string, string> };

export default function Password(props: Props) {
    const { portal, profile, theme, errors } = props;
    const { selected, select } = useSelectedStudent(portal.students, `family-student:${theme.clientId}:${portal.user.id}`);
    const { data: notifications, error, refresh } = useFamilyNotifications(portal.notifications, props.notificationsUrl, props.loginUrl);
    const [busy, setBusy] = useState(false);
    const submitting = useRef(false);
    const form = useRef<HTMLFormElement>(null);
    useEffect(() => {
        const restore = () => { submitting.current = false; setBusy(false); form.current?.reset(); };
        window.addEventListener('pageshow', restore);
        const firstError = ['currentPassword', 'newPassword', 'confirmPassword'].find((name) => errors[name]);
        if (firstError) form.current?.querySelector<HTMLInputElement>(`[name="${firstError}"]`)?.focus();
        return () => window.removeEventListener('pageshow', restore);
    }, [errors]);

    return <EurekaFrame theme={theme} className="family-portal family-password"
        sidebar={(close) => <FamilyNavigation {...props} selected={selected} onSelect={select} notifications={notifications} close={close} currentUrl={portal.user.passwordUrl} />}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación"><a href={props.homeUrl}>Inicio</a><ChevronRight size={14} /><a href={profile.url}>Mi perfil</a><ChevronRight size={14} /><strong aria-current="page">Contraseña</strong></nav>}
        actions={<FamilyNotifications data={notifications} students={portal.students} error={error} refresh={refresh} />}>
        <Head title="Cambiar contraseña" />
        <div className="responsible-profile-heading"><h1>Cambiar contraseña</h1><a href={profile.url}><ArrowLeft size={17} />Volver a mi perfil</a></div>
        <EureCard title="Tu acceso a Familias" icon={KeyRound} className="family-password-card">
            <div className="family-password-person"><EureAvatar name={portal.user.name} src={portal.user.avatar ?? undefined} size="lg" /><div><strong>{portal.user.name}</strong>{profile.login && <span>Usuario: {profile.login}</span>}</div></div>
            <form ref={form} method="post" action={props.passwordUpdateUrl} aria-busy={busy} onSubmit={(event) => {
                if (submitting.current) { event.preventDefault(); return; }
                submitting.current = true; setBusy(true);
            }}>
                <input type="hidden" name="_token" value={props.csrfToken} />
                <EurePasswordInputGroup label="Contraseña actual" name="currentPassword" autoComplete="current-password" required readOnly={busy}
                    error={Boolean(errors.currentPassword)} errorMessage={errors.currentPassword} />
                <EurePasswordInputGroup label="Nueva contraseña" name="newPassword" autoComplete="new-password" required readOnly={busy}
                    error={Boolean(errors.newPassword)} errorMessage={errors.newPassword} />
                <EurePasswordInputGroup label="Repetí la nueva contraseña" name="confirmPassword" autoComplete="new-password" required readOnly={busy}
                    error={Boolean(errors.confirmPassword)} errorMessage={errors.confirmPassword} />
                <p className="family-password-help">Tu sesión actual seguirá abierta. En otros navegadores se te pedirá ingresar nuevamente.</p>
                <div className="family-password-actions"><EureButtonPrimary type="submit" loading={busy}>{busy ? 'Guardando…' : 'Guardar contraseña'}</EureButtonPrimary><a className="eureui-button-secondary" href={profile.url}>Cancelar</a></div>
            </form>
        </EureCard>
        <footer className="eure-footer"><span>{props.institution} · Familias</span><span>Educatio · Eureka</span></footer>
    </EurekaFrame>;
}
