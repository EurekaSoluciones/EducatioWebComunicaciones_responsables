import { Head } from '@inertiajs/react';
import { GraduationCap, LogIn } from 'lucide-react';
import { useEffect, useState } from 'react';
import { EureButtonPrimary, EureInputGroup, EurePasswordInputGroup } from '../../components/ui/eure';
import { themeStyle } from '../../theme';
import type { Theme } from '../../theme';

type Props = {
    theme: Theme; institution: string; logoUrl: string | null; backgroundUrl: string | null;
    authenticateUrl: string; csrfToken: string; oldLogin: string; errors: Record<string, string>;
};

export default function Login({ theme, institution, logoUrl, backgroundUrl, authenticateUrl, csrfToken, oldLogin, errors }: Props) {
    const [busy, setBusy] = useState(false);
    useEffect(() => {
        const restore = () => setBusy(false);
        window.addEventListener('pageshow', restore);
        return () => window.removeEventListener('pageshow', restore);
    }, []);
    return <div className="eure-app family-login" style={themeStyle(theme)}>
        <Head title="Ingresar" />
        <div className={`family-login-art${backgroundUrl ? ' has-background' : ''}`} aria-hidden="true" style={backgroundUrl ? { backgroundImage: `url(${JSON.stringify(backgroundUrl)})` } : undefined}>
            <div><GraduationCap size={42} /><strong>Familias, más cerca.</strong><p>Un espacio para acompañar cada día.</p></div>
        </div>
        <main className="family-login-main">
            <div className="family-login-card">
                {logoUrl ? <img className="family-login-logo" src={logoUrl} alt={institution} /> : <GraduationCap size={38} />}
                <p className="eyebrow">{institution}</p><h1>Bienvenidos a Familias</h1><p className="family-login-intro">Ingresá para consultar las novedades de tu familia.</p>
                <form method="post" action={authenticateUrl} onSubmit={() => setBusy(true)}>
                    <input type="hidden" name="_token" value={csrfToken} />
                    <EureInputGroup label="Usuario" name="login" autoComplete="username" defaultValue={oldLogin} required autoFocus
                        helperText="Tu DNI sin puntos." error={Boolean(errors.login)} errorMessage={errors.login} />
                    <EurePasswordInputGroup label="Contraseña" name="password" autoComplete="current-password" required
                        error={Boolean(errors.password)} errorMessage={errors.password} />
                    {errors.credentials && <p className="family-form-error" role="alert">{errors.credentials}</p>}
                    <EureButtonPrimary type="submit" loading={busy}><LogIn size={18} />{busy ? 'Ingresando…' : 'Ingresar'}</EureButtonPrimary>
                </form>
                <p className="family-login-help">Si necesitás ayuda para ingresar, comunicate con la institución.</p>
            </div>
            <footer>Educatio · Eureka Soluciones Informáticas</footer>
        </main>
    </div>;
}
