import type { CSSProperties } from 'react';

export const colorGroups = [
    { title: 'Identidad', tokens: [
        ['primary', 'Principal'], ['primary-hover', 'Principal al pasar'], ['on-primary', 'Texto del principal'],
        ['primary-text', 'Enlaces y texto de identidad'],
        ['accent', 'Acento'], ['on-accent', 'Texto del acento'],
    ] },
    { title: 'Superficies y texto', tokens: [
        ['page', 'Fondo de página'], ['surface', 'Tarjetas'], ['surface-muted', 'Fondo secundario'],
        ['text', 'Texto'], ['muted', 'Texto secundario'], ['border', 'Bordes'], ['ring', 'Foco de teclado'],
    ] },
    { title: 'Encabezado y menú', tokens: [
        ['header', 'Franja superior'], ['on-header', 'Texto de la franja'], ['sidebar', 'Menú'],
        ['toolbar', 'Fondo de la barra del inicio'],
        ['on-sidebar', 'Texto del menú'], ['sidebar-muted', 'Texto secundario del menú'],
        ['sidebar-active', 'Opción activa'], ['sidebar-active-text', 'Texto de opción activa'],
    ] },
    { title: 'Pie del menú', tokens: [
        ['sidebar-footer', 'Fondo del bloque'], ['sidebar-footer-text', 'Texto e iconos'],
        ['sidebar-footer-muted', 'Texto secundario'], ['sidebar-footer-border', 'Separadores'],
    ] },
    { title: 'Estados y efectos', tokens: [
        ['success', 'Éxito'], ['success-soft', 'Fondo de éxito'], ['warning', 'Advertencia'], ['warning-soft', 'Fondo de advertencia'],
        ['danger', 'Error'], ['danger-soft', 'Fondo de error'], ['info', 'Información'], ['info-soft', 'Fondo de información'],
        ['overlay', 'Fondo detrás del diálogo'], ['shadow', 'Sombras'],
    ] },
] as const;

export type ColorToken = typeof colorGroups[number]['tokens'][number][0];
export type Theme = { clientId: string; name: string; colors: Record<ColorToken, string> };
export function themeStyle(theme: Theme): CSSProperties {
    return Object.fromEntries(Object.entries(theme.colors).map(([name, value]) => [`--eure-${name}`, value])) as CSSProperties;
}

export function contrastRatio(first: string, second: string): number {
    const luminance = (hex: string) => {
        const rgb = [1, 3, 5].map((offset) => parseInt(hex.slice(offset, offset + 2), 16) / 255)
            .map((value) => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
        return rgb[0] * 0.2126 + rgb[1] * 0.7152 + rgb[2] * 0.0722;
    };
    const a = luminance(first), b = luminance(second);
    return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}
