import { Download, Palette, RotateCcw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { colorGroups, contrastRatio } from '../../theme';
import type { ColorToken, Theme } from '../../theme';
import { EureButton } from './index';

function ColorField({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
    const [draft, setDraft] = useState(value);
    useEffect(() => setDraft(value), [value]);
    const valid = /^#[0-9a-f]{6}$/i.test(draft);
    return <div className="theme-color-field">
        <label className="theme-color-picker">
            <input type="color" value={value} onChange={(event) => onChange(event.target.value)} aria-label={`Elegir ${label.toLowerCase()}`} />
            <span>{label}</span>
        </label>
        <input className="theme-hex" aria-label={`${label}, código hexadecimal`} aria-invalid={!valid}
            value={draft} spellCheck={false} maxLength={7} onChange={(event) => {
                setDraft(event.target.value);
                if (/^#[0-9a-f]{6}$/i.test(event.target.value)) onChange(event.target.value.toLowerCase());
            }} onBlur={() => { if (!valid) setDraft(value); }} />
    </div>;
}

export default function ThemeEditor({ theme, original, onChange, onReset }: {
    theme: Theme; original: Theme; onChange: (token: ColorToken, color: string) => void; onReset: () => void;
}) {
    const changed = JSON.stringify(theme.colors) !== JSON.stringify(original.colors);
    const ratios = [
        { label: 'Texto sobre tarjeta', value: contrastRatio(theme.colors.text, theme.colors.surface) },
        { label: 'Botón principal', value: contrastRatio(theme.colors['on-primary'], theme.colors.primary) },
        { label: 'Franja superior', value: contrastRatio(theme.colors['on-header'], theme.colors.header) },
        { label: 'Pie del menú', value: contrastRatio(theme.colors['sidebar-footer-text'], theme.colors['sidebar-footer']) },
        { label: 'Texto secundario del pie', value: contrastRatio(theme.colors['sidebar-footer-muted'], theme.colors['sidebar-footer']) },
    ];

    function download() {
        const blob = new Blob([JSON.stringify({ name: theme.name, colors: theme.colors }, null, 4) + '\n'], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `${theme.clientId}.json`;
        anchor.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    return <aside className="theme-editor" id="tema" aria-label="Editor del tema">
        <div className="theme-editor-title"><span className="eure-icon-tile"><Palette size={20} /></span>
            <div><h2>Tu identidad, en vivo</h2><p>{theme.name} · {theme.clientId}</p></div>
        </div>
        <p className="theme-intro">Probá los colores y mirá cómo cambian todos los componentes.</p>
        <div className={`theme-status ${changed ? 'is-changed' : ''}`} role="status">
            <span />{changed ? 'Vista previa · cambios sin guardar' : 'Colores del archivo de tema'}
        </div>
        {colorGroups.map((group, index) => <details className="theme-group" key={group.title} open={index === 0 || undefined}>
            <summary>{group.title}<span>{group.tokens.length}</span></summary>
            <div>{group.tokens.map(([token, label]) => <ColorField key={token} label={label}
                value={theme.colors[token]} onChange={(color) => onChange(token, color)} />)}</div>
        </details>)}
        <div className="theme-contrast">
            <h3>Legibilidad del tema</h3>
            <p>Referencia: 4,5:1 para texto normal.</p>
            {ratios.map(({ label, value }) => <div key={label}><span>{label}</span>
                <strong className={value >= 4.5 ? 'contrast-good' : 'contrast-low'}>{value.toFixed(1)}:1</strong></div>)}
        </div>
        <div className="theme-editor-actions">
            <EureButton onClick={download}><Download size={16} />Descargar tema completo</EureButton>
            <EureButton variant="secondary" disabled={!changed} onClick={onReset}><RotateCcw size={15} />Restablecer archivo</EureButton>
        </div>
        <p className="theme-file-note">Para conservar los cambios, reemplazá <code>resources/themes/{theme.clientId}.json</code> por el archivo descargado. No se guarda nada en el servidor desde esta demo.</p>
    </aside>;
}
