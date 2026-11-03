import { Bell, Check, Inbox, RefreshCw, Send, X } from 'lucide-react';
import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { EureAvatar } from '../eureka';
import type { Notifications, Student } from './types';

export function useFamilyNotifications(initial: Notifications, url: string, loginUrl: string) {
    const [data, setData] = useState(initial);
    const [error, setError] = useState(false);
    const [retry, setRetry] = useState(0);
    useEffect(() => setData(initial), [initial]);
    useEffect(() => {
        let disposed = false;
        let pending: AbortController | null = null;
        const update = async () => {
            if (document.hidden || pending) return;
            const controller = new AbortController();
            pending = controller;
            const timeout = window.setTimeout(() => controller.abort(), 10000);
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
                if (disposed) return;
                if (response.status === 401 || response.status === 419) { window.location.assign(loginUrl); return; }
                if (!response.ok) throw new Error('Unable to update notifications');
                const next: Notifications = await response.json();
                if (!disposed) { setData(next); setError(false); }
            } catch { if (!disposed) setError(true); }
            finally { window.clearTimeout(timeout); pending = null; }
        };
        if (retry) void update();
        const timer = window.setInterval(() => void update(), 30000);
        document.addEventListener('visibilitychange', update);
        window.addEventListener('focus', update);
        return () => { disposed = true; window.clearInterval(timer); pending?.abort(); document.removeEventListener('visibilitychange', update); window.removeEventListener('focus', update); };
    }, [url, loginUrl, retry]);
    const refresh = useCallback(() => setRetry((value) => value + 1), []);
    return { data, error, refresh };
}

export default function FamilyNotifications({ data, students, error, refresh }: {
    data: Notifications; students: Student[]; error: boolean; refresh: () => void;
}) {
    const id = useId();
    const panel = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const count = data.received + data.replies;
    const pending = data.byStudent.filter((item) => item.received || item.replies);
    return <>
        <button type="button" className="notification-trigger" popoverTarget={id} aria-haspopup="dialog" aria-expanded={open} aria-controls={id} aria-label={`Notificaciones: ${count} pendientes`}>
            <Bell size={21} />{count > 0 && <span className="notification-count" aria-hidden="true">{count > 99 ? '99+' : count}</span>}
        </button>
        <div ref={panel} id={id} popover="auto" role="dialog" aria-labelledby={`${id}-title`} className="notification-panel family-notifications" onToggle={(event) => setOpen(event.currentTarget.matches(':popover-open'))}>
            <div className="notification-header"><div><h2 id={`${id}-title`}>Pendientes de tu familia</h2><p>{data.received} comunicaciones · {data.replies} respuestas sin leer</p></div>
                <button type="button" className="eure-icon-button" aria-label="Cerrar notificaciones" onClick={() => panel.current?.hidePopover()}><X size={18} /></button>
            </div>
            {error && <div className="family-notification-error" role="status">No pudimos actualizar los pendientes.<button type="button" onClick={refresh}><RefreshCw size={14} />Reintentar</button></div>}
            {count === 0 && <div className="notification-empty"><Check size={25} /><p>No tenés comunicaciones ni respuestas pendientes.</p></div>}
            <ul className="notification-list">{pending.map((item) => {
                const student = students.find((candidate) => candidate.id === item.studentId);
                if (!student) return null;
                return <li key={student.id}><EureAvatar name={student.name} src={student.avatar ?? undefined} /><div className="notification-content"><strong>{student.name}</strong>
                    {item.received > 0 && <a href={student.links.received}><Inbox size={15} />{item.received} comunicaciones sin leer</a>}
                    {item.replies > 0 && <a href={student.links.sent}><Send size={15} />{item.replies} respuestas sin leer</a>}
                </div></li>;
            })}</ul>
        </div>
    </>;
}
