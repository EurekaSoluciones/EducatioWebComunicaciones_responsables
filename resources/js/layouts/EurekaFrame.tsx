import { Menu, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { themeStyle } from '../theme';
import type { Theme } from '../theme';

export default function EurekaFrame({ theme, sidebar, header, actions, children, className = '' }: {
    theme: Theme; sidebar: (close: () => void) => ReactNode; header: ReactNode;
    actions: ReactNode; children: ReactNode; className?: string;
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const drawer = useRef<HTMLDialogElement>(null);
    const close = () => setMenuOpen(false);
    useEffect(() => {
        const dialog = drawer.current;
        if (menuOpen && dialog && !dialog.open) dialog.showModal();
        if (!menuOpen && dialog?.open) dialog.close();
    }, [menuOpen]);
    useEffect(() => {
        const desktop = window.matchMedia('(min-width: 1024px)');
        const closeOnDesktop = () => { if (desktop.matches) setMenuOpen(false); };
        desktop.addEventListener('change', closeOnDesktop);
        return () => desktop.removeEventListener('change', closeOnDesktop);
    }, []);

    return <div className={`eure-app eure-pranzo ${className}`} style={themeStyle(theme)}>
        <a className="skip-link" href="#contenido">Saltar al contenido</a>
        <aside className="pranzo-sidebar" aria-label="Navegación lateral">{sidebar(close)}</aside>
        <dialog ref={drawer} className="pranzo-drawer" id="eureka-mobile-menu" aria-label="Navegación lateral"
            onCancel={close} onClose={close} onClick={(event) => { if (event.target === event.currentTarget) close(); }}>
            <div className="pranzo-drawer-content">
                <button className="pranzo-drawer-close" type="button" aria-label="Cerrar menú" onClick={close}><X size={20} /></button>
                {sidebar(close)}
            </div>
        </dialog>
        <div className="pranzo-frame"><div className="pranzo-panel">
            <header className="pranzo-header">
                <div className="pranzo-header-start">
                    <button type="button" className="pranzo-menu-toggle" aria-label="Abrir menú" aria-expanded={menuOpen}
                        aria-controls="eureka-mobile-menu" onClick={() => setMenuOpen(true)}><Menu size={21} /></button>
                    {header}
                </div>
                <div className="pranzo-header-actions">{actions}</div>
            </header>
            <main className="pranzo-main" id="contenido">{children}</main>
        </div></div>
    </div>;
}
