import type { ButtonHTMLAttributes } from 'react';

// Port of Pranzo's EureButtonSecondary + Button outline variant; native-button subset.
export default function EureButtonSecondary({ children, type = 'button', className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement>) {
    return <button type={type} {...props} className={`eureui-button-secondary ${className}`}>{children}</button>;
}
