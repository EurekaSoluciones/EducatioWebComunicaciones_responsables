import type { LucideIcon } from 'lucide-react';
import type { CSSProperties, ReactNode } from 'react';

// Port of Pranzo's EureCard. Its utility styles are expressed in eureui.css.
export interface EureCardProps {
    /** A node, not just text, so a caller can follow the title with badges. */
    title?: ReactNode;
    icon?: LucideIcon;
    description?: string;
    variant?: 'default' | 'plain';
    children: ReactNode;
    className?: string;
    style?: CSSProperties;
}

export default function EureCard({ title, icon: Icon, description, variant = 'default', children, className = '', style }: EureCardProps) {
    return <section className={`eureui-card ${variant === 'plain' ? 'eureui-card-plain' : ''} ${className}`} style={style}>
        {title && <header className="eureui-card-header">
            <h2>{Icon && <Icon size={20} aria-hidden="true" />}{title}</h2>
            {description && <p>{description}</p>}
        </header>}
        {children}
    </section>;
}
