import { CircleCheckIcon, CircleXIcon, InfoIcon, TriangleAlertIcon, XIcon } from 'lucide-react';

// Port of Pranzo's EureAlert, with the same message/type/onClose contract.
export interface EureAlertProps {
    type?: 'success' | 'error' | 'warning' | 'info';
    message: string;
    className?: string;
    onClose?: () => void;
}

const icons = { success: CircleCheckIcon, error: CircleXIcon, warning: TriangleAlertIcon, info: InfoIcon };

export default function EureAlert({ type = 'error', message, className = '', onClose }: EureAlertProps) {
    const Icon = icons[type];
    return <div role="alert" className={`eureui-alert eureui-alert-${type} ${className}`}>
        <Icon size={20} aria-hidden="true" /><p>{message}</p>
        {onClose && <button type="button" onClick={onClose} aria-label="Cerrar"><XIcon size={16} /></button>}
    </div>;
}
