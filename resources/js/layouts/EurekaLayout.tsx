import { ArrowLeft, ArrowUpRight, ChevronRight, GraduationCap, LayoutGrid, Menu, Palette, X } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { EureAvatar } from '../components/eureka';
import DemoPageHeading from '../components/demo/DemoPageHeading';
import DemoNotifications from '../components/demo/DemoNotifications';
import DemoLayoutPicker from '../components/demo/DemoLayoutPicker';
import type { DemoUrls } from '../components/demo/DemoLayoutPicker';
import { themeStyle } from '../theme';
import type { Theme } from '../theme';

const sections = [
    ['resumen', 'Vista general', '01'], ['componentes', 'Botones y estados', '02'],
    ['formularios', 'Formularios', '03'], ['personas', 'Personas y tarjetas', '04'],
    ['tabla', 'Tablas y listados', '05'], ['tema', 'Colores del tema', '06'],
];

export default function EurekaLayout({ theme, institution, homeUrl, children, demoUrls }: {
    theme: Theme; institution: string; homeUrl: string; children: ReactNode; demoUrls: DemoUrls;
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [active, setActive] = useState('resumen');
    return <div className="eure-app" style={themeStyle(theme)}>
        <a className="skip-link" href="#contenido">Saltar al contenido</a>
        <aside className="eure-sidebar" aria-label="Navegación de la demo">
            <div className="eure-brand"><span className="brand-mark"><GraduationCap size={25} /></span>
                <div><strong>educatio<span>.</span></strong><small>Familias</small></div>
                <button type="button" className="eure-icon-button mobile-menu" aria-expanded={menuOpen}
                    aria-controls="demo-navigation" aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
                    onClick={() => setMenuOpen(!menuOpen)}>{menuOpen ? <X /> : <Menu />}</button>
            </div>
            <div className={`sidebar-content ${menuOpen ? 'is-open' : ''}`} id="demo-navigation">
                <div className="institution-label"><span className="institution-dot" /><span>{institution}</span><small>{theme.clientId.toUpperCase()}</small></div>
                <p className="nav-caption">ESPACIO DE DISEÑO</p>
                <div className="sidebar-section-title"><LayoutGrid size={17} />Biblioteca UI</div>
                <nav>{sections.map(([id, label, number]) => <a key={id} href={`#${id}`} className={active === id ? 'is-active' : ''}
                    aria-current={active === id ? 'location' : undefined} onClick={() => { setActive(id); setMenuOpen(false); }}>
                    <span className="nav-number">{number}</span>{label}{active === id && <ChevronRight size={15} />}
                </a>)}</nav>
                <div className="sidebar-note"><Palette size={22} /><strong>Un tema. Toda la interfaz.</strong><p>Tarjetas, controles y navegación con una misma identidad.</p></div>
                <div className="sidebar-footer">
                    <a className="back-to-app" href={homeUrl}><ArrowLeft size={16} />Volver al sistema<ArrowUpRight size={14} /></a>
                    <div className="sidebar-profile"><EureAvatar name="Familia de ejemplo" /><div><strong>Familia de ejemplo</strong><small>Vista de demostración</small></div></div>
                </div>
            </div>
        </aside>
        <div className="eure-main">
            <header className="eure-topbar"><span><span className="topbar-dot" />EUREKA · FAMILIAS</span>
                <div className="topbar-actions"><span className="topbar-description">Laboratorio de interfaz <span className="topbar-tag">DEMO</span></span>
                    <DemoNotifications />
                </div>
            </header>
            <main id="contenido">
                <div className="breadcrumb-row"><nav className="eure-breadcrumb" aria-label="Ruta de navegación">
                    <a href={homeUrl}>Familias</a><ChevronRight size={13} /><span>Demo</span><ChevronRight size={13} /><strong aria-current="page">Biblioteca UI</strong>
                </nav><DemoLayoutPicker current="sidebar" urls={demoUrls} /></div>
                <DemoPageHeading />
                {children}
                <footer className="eure-footer"><span>Educatio · Familias</span><span>Demo UI · Datos ficticios · Eureka</span></footer>
            </main>
        </div>
    </div>;
}
