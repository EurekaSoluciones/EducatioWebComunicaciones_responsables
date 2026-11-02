import { Loader2Icon } from 'lucide-react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

// Port of Pranzo's EureButtonPrimary; colors resolve through the client theme.
export interface EureButtonPrimaryProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: ReactNode;
    loading?: boolean;
    loadingPosition?: 'left' | 'right';
}

export default function EureButtonPrimary({
    children, className = '', loading = false, loadingPosition = 'left', disabled, type = 'button', ...props
}: EureButtonPrimaryProps) {
    return <button {...props} type={type} disabled={loading || disabled} aria-busy={loading || undefined}
        className={`eureui-button-primary ${className}`}>
        {loading && loadingPosition === 'left' && <Loader2Icon className="eure-spin" size={16} aria-hidden="true" />}
        {children}
        {loading && loadingPosition === 'right' && <Loader2Icon className="eure-spin" size={16} aria-hidden="true" />}
    </button>;
}
