import type { InputHTMLAttributes, ReactNode } from 'react';
import EureInput from './EureInput';

// Composition and public props ported from Pranzo's EureInputGroup.
export interface EureInputGroupProps extends InputHTMLAttributes<HTMLInputElement> {
    label: ReactNode;
    labelAction?: ReactNode;
    error?: boolean;
    errorMessage?: string;
    helperText?: string;
    name: string;
    classNameInput?: string;
    classNameLabel?: string;
    classNameHeader?: string;
    classNameWrapper?: string;
}

export default function EureInputGroup({
    label, labelAction, error = false, errorMessage = '', helperText = '', name, id,
    className = '', classNameInput = '', classNameLabel = '', classNameHeader = '', classNameWrapper = '',
    'aria-describedby': describedBy, ...props
}: EureInputGroupProps) {
    const fieldId = id ?? name;
    const hasMessage = Boolean((error && errorMessage) || helperText);
    const description = [describedBy, hasMessage ? `${fieldId}-message` : undefined].filter(Boolean).join(' ') || undefined;
    return <div className={`eureui-input-group ${className} ${classNameWrapper}`}>
        <div className={`eureui-input-header ${classNameHeader}`}>
            <label htmlFor={fieldId} className={`eureui-label ${classNameLabel}`}>{label}</label>
            {labelAction}
        </div>
        <EureInput {...props} id={fieldId} name={name} error={error} className={classNameInput} aria-describedby={description} />
        {errorMessage && error ? <p id={`${fieldId}-message`} className="eureui-field-error" role="alert">{errorMessage}</p>
            : helperText ? <p id={`${fieldId}-message`} className="eureui-helper">{helperText}</p> : null}
    </div>;
}
