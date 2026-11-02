import { CircleAlertIcon } from 'lucide-react';
import type { InputHTMLAttributes, Ref } from 'react';

// Port of Pranzo/resources/js/components/ui/eure/EureInput.tsx.
// Utility styles are mapped to eureui.css and the client's theme tokens.
// The masked-input branch is not included: the login does not use masks.
export interface EureInputProps extends InputHTMLAttributes<HTMLInputElement> {
    error?: boolean;
    inputRef?: Ref<HTMLInputElement>;
}

export default function EureInput({ className = '', error = false, inputRef, ...props }: EureInputProps) {
    return <div className="eureui-input-wrapper">
        <input {...props} ref={inputRef} aria-invalid={error || undefined}
            className={`eureui-input ${error ? 'eureui-input-invalid' : ''} ${className}`} />
        {error && <CircleAlertIcon className="eureui-input-error" size={16} aria-hidden="true" />}
    </div>;
}
