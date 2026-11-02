import { Bell, BellRing, Check, CheckCheck, ChevronRight, X } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { EureAvatar, EureButton } from './index';

export type EureNotification = {
    id: string;
    title: string;
    description: string;
    person: string;
    avatar?: string;
    time: string;
    read: boolean;
};

export default function EureNotifications({ notifications, onRead, onReadAll, onReset, listUrl, demo = false }: {
    notifications: EureNotification[];
    onRead: (id: string) => void;
    onReadAll: () => void;
    onReset?: () => void;
    listUrl: string;
    demo?: boolean;
}) {
    const id = useId();
    const panel = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const unread = notifications.filter((notification) => !notification.read).length;
    const label = unread === 1 ? '1 notificación pendiente' : `${unread} notificaciones pendientes`;

    return <div className="eure-notifications">
        <button type="button" className="notification-trigger" popoverTarget={id} aria-label={`Notificaciones: ${label}`}
            aria-haspopup="dialog" aria-expanded={open} aria-controls={id}>
            <Bell size={20} aria-hidden="true" />
            {unread > 0 && <span className="notification-count" aria-hidden="true">{unread > 99 ? '99+' : unread}</span>}
        </button>
        <span className="sr-only" role="status">{label}</span>
        <div ref={panel} id={id} popover="auto" className="notification-panel" role="dialog" aria-labelledby={`${id}-title`}
            onToggle={(event) => setOpen(event.currentTarget.matches(':popover-open'))}>
            <header className="notification-header"><div><h2 id={`${id}-title`}>Notificaciones</h2><p>{unread ? label : 'Estás al día'}</p></div>
                <button type="button" className="eure-icon-button" aria-label="Cerrar notificaciones" popoverTarget={id} popoverTargetAction="hide"><X size={19} /></button>
            </header>
            {demo && <p className="notification-demo-label">DEMO · Cambios solo en esta pantalla</p>}
            {unread > 0 ? <div className="notification-bulk"><EureButton variant="quiet" onClick={onReadAll}><CheckCheck size={15} />Marcar todas como leídas</EureButton></div>
                : <div className="notification-all-read"><CheckCheck size={18} /><span>No tenés notificaciones pendientes.</span></div>}
            {notifications.length > 0 ? <ul className="notification-list">{notifications.map((notification) =>
                <li key={notification.id} className={notification.read ? 'is-read' : 'is-unread'}>
                    <EureAvatar name={notification.person} src={notification.avatar} />
                    <div className="notification-content"><div className="notification-title"><h3>{notification.title}</h3>{!notification.read && <span className="notification-unread-dot" aria-label="Sin leer" />}</div>
                        <p>{notification.description}</p><div className="notification-meta"><span>{notification.person}</span><span>· {notification.time}</span></div>
                        {!notification.read ? <button type="button" className="notification-read-action" aria-label={`Marcar como leída: ${notification.title}`} onClick={() => onRead(notification.id)}><Check size={13} />Marcar como leída</button>
                            : <span className="notification-read-label"><CheckCheck size={13} />Leída</span>}
                    </div>
                </li>)}</ul>
                : <div className="notification-empty"><BellRing size={28} /><p>Cuando haya novedades, las vas a encontrar acá.</p></div>}
            <footer className="notification-footer"><a href={listUrl} onClick={() => panel.current?.hidePopover()}>{demo ? 'Ver comunicaciones de ejemplo' : 'Ver comunicaciones'}<ChevronRight size={15} /></a>
                {demo && onReset && <button type="button" onClick={onReset}>Restablecer ejemplos</button>}
            </footer>
        </div>
    </div>;
}
