import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import EureInputGroup from './EureInputGroup';
import type { EureInputGroupProps } from './EureInputGroup';

// Local composition of the ported EureInputGroup; no separate password field styling.
export default function EurePasswordInputGroup(props: Omit<EureInputGroupProps, 'type' | 'labelAction'>) {
    const [visible, setVisible] = useState(false);
    return <EureInputGroup {...props} type={visible ? 'text' : 'password'} labelAction={
        <button type="button" className="eureui-password-toggle" aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
            aria-controls={props.id ?? props.name} aria-pressed={visible} disabled={props.disabled} onClick={() => setVisible(!visible)}>
            {visible ? <EyeOff size={16} /> : <Eye size={16} />}<span>{visible ? 'Ocultar' : 'Mostrar'}</span>
        </button>
    } />;
}
