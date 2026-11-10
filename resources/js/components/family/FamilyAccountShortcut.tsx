import { TriangleAlert, Wallet } from 'lucide-react';
import { useId, useState } from 'react';

export default function FamilyAccountShortcut({ href, label, hasOverdue }: { href: string; label: string; hasOverdue: boolean }) {
    const tooltipId = useId();
    const [showTooltip, setShowTooltip] = useState(false);
    return <a href={href} className="family-account-shortcut"
        aria-describedby={hasOverdue && showTooltip ? tooltipId : undefined}
        onMouseEnter={() => setShowTooltip(true)} onMouseLeave={() => setShowTooltip(false)}
        onFocus={() => setShowTooltip(true)} onBlur={() => setShowTooltip(false)}
        onKeyDown={(event) => { if (event.key === 'Escape') setShowTooltip(false); }}>
        <Wallet size={17} aria-hidden="true" />
        <span>{label}</span>
        {hasOverdue && <span className="family-account-warning"><TriangleAlert size={15} aria-hidden="true" /></span>}
        {hasOverdue && showTooltip && <span role="tooltip" id={tooltipId} className="family-account-tooltip">
            Hay saldo vencido en esta cuenta. Entrá para consultar el detalle.
        </span>}
    </a>;
}
