import { ArrowLeft, ChevronRight, GraduationCap, LayoutGrid, MousePointer2, Palette, SlidersHorizontal, Table2, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { EureAvatar } from '../components/eureka';
import DemoPageHeading from '../components/demo/DemoPageHeading';
import DemoNotifications from '../components/demo/DemoNotifications';
import DemoLayoutPicker from '../components/demo/DemoLayoutPicker';
import type { DemoUrls } from '../components/demo/DemoLayoutPicker';
import type { Theme } from '../theme';
import EurekaFrame from './EurekaFrame';

const sections = [
    { id: 'resumen', label: 'Vista general', icon: LayoutGrid },
    { id: 'componentes', label: 'Botones y estados', icon: MousePointer2 },
    { id: 'formularios', label: 'Formularios', icon: SlidersHorizontal },
    { id: 'personas', label: 'Personas y tarjetas', icon: Users },
    { id: 'tabla', label: 'Tablas y listados', icon: Table2 },
    { id: 'tema', label: 'Colores del tema', icon: Palette },
];

// Adapted from Pranzo's RoleSidebar / RoleLayoutFrame, without its domain or auth dependencies.
export default function EurekaPranzoLayout({ theme, institution, homeUrl, children, demoUrls }: {
    theme: Theme; institution: string; homeUrl: string; children: ReactNode; demoUrls: DemoUrls;
}) {
    const [active, setActive] = useState('resumen');

    useEffect(() => {
        const updateHash = () => {
            const id = window.location.hash.slice(1);
            setActive(sections.some((section) => section.id === id) ? id : 'resumen');
        };
        updateHash();
        window.addEventListener('hashchange', updateHash);
        return () => window.removeEventListener('hashchange', updateHash);
    }, []);

    const sidebar = (close: () => void) => <>
        <a href="#resumen" className="pranzo-brand" onClick={() => { setActive('resumen'); close(); }}>
            <GraduationCap size={34} aria-hidden="true" />
            <strong>educatio<span>.</span></strong><small>Familias</small>
        </a>
        <nav className="pranzo-navigation" aria-label="Secciones de la demo">
            <p>Biblioteca UI</p>
            {sections.map(({ id, label, icon: Icon }) => <a key={id} href={`#${id}`}
                aria-current={active === id ? 'location' : undefined} onClick={() => { setActive(id); close(); }}>
                <Icon size={20} aria-hidden="true" /><span>{label}</span>
            </a>)}
        </nav>
        <div className="pranzo-sidebar-footer">
            <div className="pranzo-scope"><small>Institución</small><strong>{institution}</strong></div>
            <div className="pranzo-user"><EureAvatar name="Familia de ejemplo" /><div><strong>Familia de ejemplo</strong><small>Vista de demostración</small></div></div>
            <a className="pranzo-back" href={homeUrl}><ArrowLeft size={16} />Volver al sistema</a>
        </div>
    </>;

    return <EurekaFrame theme={theme} sidebar={sidebar}
        header={<nav className="eure-breadcrumb" aria-label="Ruta de navegación">
                        <a href={homeUrl}>Familias</a><ChevronRight size={14} /><span>Demo</span><ChevronRight size={14} /><strong aria-current="page">Biblioteca UI</strong>
                    </nav>}
        actions={<><DemoNotifications /><span className="pranzo-header-avatar" title="Familia de ejemplo"><EureAvatar name="Familia de ejemplo" /></span></>}>
                <div className="pranzo-comparison"><DemoLayoutPicker current="pranzo" urls={demoUrls} /></div>
                <DemoPageHeading />
                {children}
                <footer className="eure-footer"><span>Educatio · Familias</span><span>Demo UI · Datos ficticios · Eureka</span></footer>
    </EurekaFrame>;
}
