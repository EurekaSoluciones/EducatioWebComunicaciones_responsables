import { Check, ChevronDown } from 'lucide-react';
import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { EureAvatar } from '../eureka';
import type { Notifications, Student } from './types';
import { studentGroup } from './types';

export default function FamilyStudentPicker({ selected, students, notifications, onSelect }: {
    selected: Student; students: Student[]; notifications: Notifications; onSelect: (id: string) => void;
}) {
    const id = useId();
    const trigger = useRef<HTMLButtonElement>(null);
    const panel = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const position = useCallback(() => {
        if (!trigger.current || !panel.current) return;
        const rect = trigger.current.getBoundingClientRect();
        const width = Math.min(340, window.innerWidth - 24);
        const below = window.innerHeight - rect.bottom - 20;
        const above = rect.top - 20;
        const useBelow = below >= 260 || below >= above;
        Object.assign(panel.current.style, {
            width: `${width}px`,
            left: `${Math.max(12, Math.min(rect.left, window.innerWidth - width - 12))}px`,
            top: useBelow ? `${rect.bottom + 8}px` : 'auto',
            bottom: useBelow ? 'auto' : `${window.innerHeight - rect.top + 8}px`,
            maxHeight: `${Math.max(80, Math.min(420, useBelow ? below : above))}px`,
        });
    }, []);

    useEffect(() => {
        if (!open) return;
        window.addEventListener('resize', position);
        window.addEventListener('scroll', position, true);
        return () => {
            window.removeEventListener('resize', position);
            window.removeEventListener('scroll', position, true);
        };
    }, [open, position]);

    return <div className="family-student-selector">
        <span className="family-student-picker-label" id={`${id}-label`}>Consultar por alumno</span>
        <button ref={trigger} type="button" className="family-student-picker-trigger" popoverTarget={id}
            aria-haspopup="dialog" aria-expanded={open} aria-controls={id}
            aria-label={`Cambiar alumno. Seleccionado: ${selected.name}`} onClick={position}>
            <EureAvatar name={selected.name} src={selected.avatar ?? undefined} />
            <strong>{selected.name}</strong><ChevronDown size={16} aria-hidden="true" />
        </button>
        <small>{studentGroup(selected)}</small>
        <div ref={panel} id={id} popover="auto" role="dialog" aria-labelledby={`${id}-title`}
            className="family-student-picker-panel" onToggle={(event) => {
                const expanded = event.currentTarget.matches(':popover-open');
                setOpen(expanded);
                if (expanded) {
                    position();
                    event.currentTarget.querySelector<HTMLButtonElement>('[aria-pressed="true"]')?.focus({ preventScroll: true });
                }
            }}>
            <h2 id={`${id}-title`}>Elegí un alumno</h2>
            <div className="family-student-picker-options" onKeyDown={(event) => {
                if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
                const buttons = Array.from(event.currentTarget.querySelectorAll('button'));
                const current = buttons.indexOf(document.activeElement as HTMLButtonElement);
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? buttons.length - 1
                    : (current + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length;
                event.preventDefault();
                buttons[next]?.focus();
            }}>
                {students.map((student) => {
                    const unread = notifications.byStudent.find((item) => item.studentId === student.id);
                    const pending = (unread?.received ?? 0) + (unread?.replies ?? 0);
                    return <button key={student.id} type="button" aria-pressed={student.id === selected.id}
                        className="family-student-picker-option" onClick={() => {
                            panel.current?.hidePopover();
                            trigger.current?.focus({ preventScroll: true });
                            if (student.id !== selected.id) onSelect(student.id);
                        }}>
                        <EureAvatar name={student.name} src={student.avatar ?? undefined} />
                        <span className="family-student-picker-details"><strong>{student.name}</strong><small>{studentGroup(student)}</small>
                            {pending > 0 && <span className="family-student-picker-pending">{pending} {pending === 1 ? 'pendiente' : 'pendientes'}</span>}
                        </span>
                        {student.id === selected.id && <Check size={17} aria-label="Seleccionado" />}
                    </button>;
                })}
            </div>
        </div>
    </div>;
}
