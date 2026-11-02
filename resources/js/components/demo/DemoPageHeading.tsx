import { Palette } from 'lucide-react';
import { EureButton } from '../eureka';

export default function DemoPageHeading() {
    return <div className="page-heading"><div><p className="eyebrow">DISEÑADO PARA ESTAR CERCA</p><h1>La misma familia.<br /><span>Una nueva experiencia.</span></h1>
        <p>Explorá los componentes y encontrá los colores de tu institución.</p></div>
        <EureButton variant="secondary" onClick={() => document.getElementById('tema')?.scrollIntoView({ behavior: 'smooth' })}><Palette size={17} />Personalizar colores</EureButton>
    </div>;
}
