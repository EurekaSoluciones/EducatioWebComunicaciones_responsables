import { PanelLeft } from 'lucide-react';

export type DemoLayout = 'sidebar' | 'pranzo';
export type DemoUrls = Record<DemoLayout, string>;

export default function DemoLayoutPicker({ current, urls }: { current: DemoLayout; urls: DemoUrls }) {
    return <nav className="demo-layout-picker" aria-label="Comparar opciones de diseño">
        <a href={urls.sidebar} aria-current={current === 'sidebar' ? 'page' : undefined}><PanelLeft size={14} /><span>1 · Lateral</span></a>
        <a href={urls.pranzo} aria-current={current === 'pranzo' ? 'page' : undefined}><PanelLeft size={14} /><span>2 · Pranzo</span></a>
    </nav>;
}
