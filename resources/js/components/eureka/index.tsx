import { Check, CircleAlert, Info, LoaderCircle, TriangleAlert, X } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode } from 'react';

export type Tone = 'success' | 'warning' | 'danger' | 'info';

export function EureCard({ title, description, icon, children, className = '', action }: {
    title: string; description?: string; icon?: ReactNode; children: ReactNode;
    className?: string; action?: ReactNode;
}) {
    return <section className={`eure-card ${className}`}>
        <header className="eure-card-heading">
            <div><h2>{icon}{title}</h2>{description && <p>{description}</p>}</div>
            {action}
        </header>
        <div className="eure-card-body">{children}</div>
    </section>;
}

export function EureButton({ children, variant = 'primary', loading = false, disabled, className = '', type = 'button', ...props }:
    ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'secondary' | 'quiet' | 'danger'; loading?: boolean }) {
    return <button {...props} type={type} disabled={disabled || loading} aria-busy={loading || undefined}
        className={`eure-button eure-button-${variant} ${className}`}>
        {loading && <LoaderCircle className="eure-spin" size={16} aria-hidden="true" />}{children}
    </button>;
}

export function EureInput({ label, hint, error, id, className = '', ...props }:
    InputHTMLAttributes<HTMLInputElement> & { label: string; hint?: string; error?: string }) {
    const generated = useId();
    const fieldId = id ?? generated;
    return <div className="eure-field">
        <label htmlFor={fieldId}>{label}</label>
        <input {...props} id={fieldId} className={`eure-input ${className}`}
            aria-invalid={Boolean(error)} aria-describedby={error || hint ? `${fieldId}-help` : undefined} />
        {(error || hint) && <small id={`${fieldId}-help`} className={error ? 'eure-error' : 'eure-muted'}>{error || hint}</small>}
    </div>;
}

export function EureBadge({ children, tone = 'info' }: { children: ReactNode; tone?: Tone }) {
    return <span className={`eure-badge eure-tone-${tone}`}><span aria-hidden="true" />{children}</span>;
}

export function EureAlert({ title, children, tone = 'info' }: { title: string; children: ReactNode; tone?: Tone }) {
    const Icon = { success: Check, warning: TriangleAlert, danger: CircleAlert, info: Info }[tone];
    return <div className={`eure-alert eure-tone-${tone}`}>
        <Icon size={19} aria-hidden="true" /><div><strong>{title}</strong><p>{children}</p></div>
    </div>;
}

export function EureAvatar({ name, src, size = 'md' }: { name: string; src?: string; size?: 'sm' | 'md' | 'lg' }) {
    const [failed, setFailed] = useState(false);
    useEffect(() => setFailed(false), [src]);
    const initials = name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('');
    return <span className={`eure-avatar eure-avatar-${size}`}>
        {src && !failed ? <img src={src} alt={name} onError={() => setFailed(true)} />
            : <span role="img" aria-label={name}>{initials}</span>}
    </span>;
}

export function EureSwitch({ label, checked, onChange, description }: {
    label: string; checked: boolean; onChange: (value: boolean) => void; description?: string;
}) {
    const id = useId();
    return <div className="eure-switch-row">
        <div><label htmlFor={id}>{label}</label>{description && <small>{description}</small>}</div>
        <button type="button" id={id} role="switch" aria-checked={checked} className="eure-switch"
            onClick={() => onChange(!checked)}><span /></button>
    </div>;
}

export function EureDialog({ open, onClose, title, children, className = '' }: {
    open: boolean; onClose: () => void; title: string; children: ReactNode; className?: string;
}) {
    const ref = useRef<HTMLDialogElement>(null);
    const titleId = useId();
    useEffect(() => {
        const dialog = ref.current;
        if (open && dialog && !dialog.open) dialog.showModal();
        if (!open && dialog?.open) dialog.close();
    }, [open]);

    return <dialog ref={ref} className={`eure-dialog ${className}`} aria-labelledby={titleId} onClose={onClose}
        onClick={(event) => { if (event.target === event.currentTarget) onClose(); }}>
        <div className="eure-dialog-header"><h2 id={titleId}>{title}</h2>
            <button type="button" className="eure-icon-button" aria-label="Cerrar diálogo" onClick={onClose}><X size={20} /></button>
        </div>
        <div className="eure-dialog-body">{children}</div>
    </dialog>;
}
