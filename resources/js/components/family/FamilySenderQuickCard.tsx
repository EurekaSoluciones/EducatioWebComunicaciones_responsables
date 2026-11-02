import { useState } from 'react';
import type { ReactNode } from 'react';
import { EureAvatar, EureDialog } from '../eureka';
import '../../../css/eureka-sender-card.css';

export type FamilySender = {
    name: string;
    avatar: string | null;
    description?: string | null;
    role?: string | null;
    subjects?: string[];
};

/** Reusable trigger and read-only sender profile. Children must not contain interactive controls. */
export default function FamilySenderQuickCard({ sender, children, className = '', studentName }: {
    sender: FamilySender;
    children: ReactNode;
    className?: string;
    studentName?: string;
}) {
    const [open, setOpen] = useState(false);

    return <>
        <button type="button" className={`family-sender-trigger ${className}`}
            aria-label={`Ver ficha de ${sender.name}`} aria-haspopup="dialog" aria-expanded={open}
            onClick={(event) => { event.stopPropagation(); setOpen(true); }}>
            {children}
        </button>
        <EureDialog open={open} onClose={() => setOpen(false)} title={sender.name} className="family-sender-dialog">
            {open && <div className="family-sender-card">
                <EureAvatar name={sender.name} src={sender.avatar ?? undefined} size="lg" />
                <div className="family-sender-card-info">
                    {sender.role && <span className="family-sender-role">{sender.role}</span>}
                    {sender.description?.trim() && <p>{sender.description}</p>}
                    {!!sender.subjects?.length && <section className="family-sender-subjects">
                        <h3>{studentName ? `Materias con ${studentName}` : 'Materias'}</h3>
                        <ul>{sender.subjects.map((subject) => <li key={subject}>{subject}</li>)}</ul>
                    </section>}
                </div>
            </div>}
        </EureDialog>
    </>;
}
