import { Head } from '@inertiajs/react';
import { ArrowDown, ArrowDownUp, ArrowRight, Bell, BookOpen, CalendarDays, Check, CheckCheck, ChevronLeft, ChevronRight, CircleHelp, FileText, Inbox, Mail, MessageSquare, MousePointer2, Plus, Search, Settings2, SlidersHorizontal, Users, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import { EureAlert, EureAvatar, EureBadge, EureButton, EureCard, EureDialog, EureInput, EureSwitch } from '../../components/eureka';
import type { Tone } from '../../components/eureka';
import ThemeEditor from '../../components/eureka/ThemeEditor';
import EurekaLayout from '../../layouts/EurekaLayout';
import EurekaPranzoLayout from '../../layouts/EurekaPranzoLayout';
import type { DemoLayout, DemoUrls } from '../../components/demo/DemoLayoutPicker';
import type { ColorToken, Theme } from '../../theme';

type DemoProps = { theme: Theme; institution: string; homeUrl: string; demoLayout: DemoLayout; demoUrls: DemoUrls };
type Message = { id: number; subject: string; from: string; date: string; status: 'Pendiente' | 'Leído'; attachment: string };
const messages: Message[] = [
    { id: 128, subject: 'Encuentro de familias', from: 'Equipo de orientación', date: '2026-10-28', status: 'Pendiente', attachment: 'Invitación.pdf' },
    { id: 127, subject: 'Una semana de nuevos aprendizajes', from: 'Mariana López', date: '2026-10-27', status: 'Leído', attachment: 'Actividades.pdf' },
    { id: 126, subject: 'Salida educativa al museo', from: 'Secretaría', date: '2026-10-26', status: 'Pendiente', attachment: 'Autorización.pdf' },
    { id: 125, subject: 'Calendario de noviembre', from: 'Dirección', date: '2026-10-25', status: 'Leído', attachment: 'Calendario.pdf' },
    { id: 124, subject: 'Taller de lectura', from: 'Mariana López', date: '2026-10-24', status: 'Leído', attachment: 'Lecturas.pdf' },
];

export default function Ui({ theme: originalTheme, institution, homeUrl, demoLayout, demoUrls }: DemoProps) {
    const Layout = demoLayout === 'pranzo' ? EurekaPranzoLayout : EurekaLayout;
    const [theme, setTheme] = useState(originalTheme);
    useEffect(() => setTheme(originalTheme), [originalTheme]);
    const [toast, setToast] = useState('');
    const [dialog, setDialog] = useState(false);
    const [selectedMessage, setSelectedMessage] = useState<Message | null>(null);
    const [tab, setTab] = useState<'alerts' | 'badges'>('alerts');
    const [notifications, setNotifications] = useState(true);
    const [summary, setSummary] = useState(false);
    const [name, setName] = useState('Lucía');
    const [email, setEmail] = useState('');
    const [submitted, setSubmitted] = useState(false);
    const [busy, setBusy] = useState(false);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [sort, setSort] = useState<{ key: keyof Message; ascending: boolean }>({ key: 'id', ascending: false });
    const [page, setPage] = useState(0);

    useEffect(() => {
        if (!toast) return;
        const timer = window.setTimeout(() => setToast(''), 4500);
        return () => window.clearTimeout(timer);
    }, [toast]);
    useEffect(() => {
        if (!busy) return;
        const timer = window.setTimeout(() => { setBusy(false); setToast('Ejemplo completado. No se guardaron datos.'); }, 1200);
        return () => window.clearTimeout(timer);
    }, [busy]);

    function updateColor(token: ColorToken, value: string) {
        setTheme((current) => ({ ...current, colors: { ...current.colors, [token]: value } }));
    }
    function submit(event: FormEvent) {
        event.preventDefault();
        setSubmitted(true);
        if (name.trim() && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setToast('Formulario validado. Es una demostración: no se enviaron datos.');
        }
    }
    function changeSort(key: keyof Message) {
        setSort((current) => ({ key, ascending: current.key === key ? !current.ascending : true }));
        setPage(0);
    }
    const filteredMessages = useMemo(() => messages.filter((message) =>
        `${message.subject} ${message.from}`.toLocaleLowerCase('es').includes(search.toLocaleLowerCase('es'))
        && (status === 'all' || message.status === status))
        .sort((a, b) => {
            const compared = sort.key === 'id' ? a.id - b.id : String(a[sort.key]).localeCompare(String(b[sort.key]), 'es');
            return sort.ascending ? compared : -compared;
        }), [search, status, sort]);
    const pageCount = Math.max(1, Math.ceil(filteredMessages.length / 3));
    const visibleMessages = filteredMessages.slice(page * 3, page * 3 + 3);
    const dateLabel = (date: string) => new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: 'short' }).format(new Date(`${date}T12:00:00`));

    return <Layout theme={theme} institution={institution} homeUrl={homeUrl} demoUrls={demoUrls}>
        <Head title={demoLayout === 'pranzo' ? 'Demo UI Eureka · Variante Pranzo' : 'Demo UI Eureka'} />
        <div className="demo-workbench">
            <div className="demo-gallery">
                <section className="demo-intro" id="resumen" aria-label="Resumen de componentes">
                    <div className="intro-line"><span className="demo-pill"><span />VISTA PREVIA INTERACTIVA</span><span className="sample-label">Datos de ejemplo</span></div>
                    <h2>Todo empieza por una buena base.</h2>
                    <p>La información en su lugar, las personas siempre presentes y los colores de tu institución en cada detalle.</p>
                    <div className="intro-bottom"><div className="avatar-stack"><EureAvatar name="Lucía Torres" src="/demo/avatars/lucia.svg" /><EureAvatar name="Tomás Torres" src="/demo/avatars/tomas.svg" /><EureAvatar name="Mariana López" /></div><span>Una experiencia que conecta<br /><strong>a familias y escuela.</strong></span><a href="#componentes" aria-label="Explorar componentes"><ArrowDown size={20} /></a></div>
                </section>
                <div className="demo-stats">
                    {[{ icon: MessageSquare, label: 'Comunicaciones', value: '08', detail: '2 pendientes de lectura' },
                        { icon: CalendarDays, label: 'Próximo encuentro', value: '06 nov', detail: 'Viernes · 17:00 h' },
                        { icon: Users, label: 'Alumnos', value: '02', detail: 'Siempre a un clic' }].map(({ icon: Icon, label, value, detail }) =>
                        <article className="stat-card" key={label}><div><span>{label}</span><Icon size={19} /></div><strong>{value}</strong><small>{detail}</small></article>)}
                </div>

                <div className="section-label" id="componentes"><span>01</span><h2>Acciones claras, estados visibles</h2></div>
                <EureCard title="Botones" description="Una jerarquía para cada acción." icon={<MousePointer2 size={18} />}>
                    <div className="component-caption">PRINCIPALES Y SECUNDARIOS</div>
                    <div className="demo-button-row"><EureButton onClick={() => setToast('Acción de ejemplo realizada.')}><Plus size={16} />Nueva comunicación</EureButton>
                        <EureButton variant="secondary" onClick={() => setDialog(true)}>Abrir diálogo<ArrowUpRightIcon /></EureButton>
                        <EureButton variant="quiet" onClick={() => setToast('Esta es una acción de menor énfasis.')}>Ver detalle<ArrowRight size={15} /></EureButton></div>
                    <div className="component-divider" />
                    <div className="component-caption">CARGA, DESHABILITADO Y ACCIÓN SENSIBLE</div>
                    <div className="demo-button-row"><EureButton loading={busy} onClick={() => setBusy(true)}>{busy ? 'Procesando…' : 'Probar carga'}</EureButton>
                        <EureButton disabled>Sin permiso</EureButton><EureButton variant="danger" onClick={() => setDialog(true)}>Quitar elemento</EureButton></div>
                </EureCard>
                <EureCard title="Mensajes y estados" icon={<Bell size={18} />} action={<span className="component-counter">04 estados</span>}>
                    <div className="demo-tabs" role="group" aria-label="Tipo de muestra">
                        <button type="button" aria-pressed={tab === 'alerts'} onClick={() => setTab('alerts')}>Avisos</button>
                        <button type="button" aria-pressed={tab === 'badges'} onClick={() => setTab('badges')}>Etiquetas</button>
                    </div>
                    {tab === 'alerts' ? <div className="alert-grid">
                        <EureAlert tone="success" title="Todo listo">La información se actualizó correctamente.</EureAlert>
                        <EureAlert tone="warning" title="Revisá los datos">Hay información pendiente de completar.</EureAlert>
                        <EureAlert tone="info" title="Un nuevo aviso">Tenés una comunicación de la escuela.</EureAlert>
                        <EureAlert tone="danger" title="No se pudo guardar">Revisá el campo indicado e intentá de nuevo.</EureAlert>
                    </div> : <div className="badge-showcase">{(['success', 'warning', 'info', 'danger'] as Tone[]).map((tone, index) =>
                        <div key={tone}><EureBadge tone={tone}>{['Completado', 'Pendiente', 'Informativo', 'Requiere atención'][index]}</EureBadge><small>{['Confirmación', 'Acción pendiente', 'Novedad', 'Error'][index]}</small></div>)}</div>}
                </EureCard>

                <div className="section-label" id="formularios"><span>02</span><h2>Formularios que acompañan</h2></div>
                <EureCard title="Datos de contacto" description="Probá los campos, la validación y las preferencias." icon={<Settings2 size={18} />}>
                    <form onSubmit={submit} noValidate>
                        <div className="form-grid"><EureInput label="Nombre" value={name} onChange={(event) => setName(event.target.value)}
                            error={submitted && !name.trim() ? 'Ingresá un nombre.' : undefined} required autoComplete="off" />
                            <EureInput label="Correo electrónico" placeholder="nombre@ejemplo.com" type="email" value={email} autoComplete="off" required
                                onChange={(event) => setEmail(event.target.value)} error={submitted && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? 'Ingresá un correo válido.' : undefined} hint="Usaremos este correo para las novedades." />
                            <div className="eure-field"><label htmlFor="demo-relationship">Vínculo con el alumno</label><select className="eure-input" id="demo-relationship" defaultValue="responsable"><option value="responsable">Responsable</option><option value="tutor">Tutor/a</option><option value="familiar">Familiar autorizado</option></select></div>
                            <EureInput label="Institución" value={institution} disabled hint="Ejemplo de campo no editable." />
                        </div>
                        <div className="eure-field form-observations"><label htmlFor="demo-notes">Observaciones <span className="eure-muted">· opcional</span></label><textarea id="demo-notes" className="eure-input" rows={3} placeholder="Podés agregar una aclaración…" /></div>
                        <div className="preferences"><EureSwitch label="Avisos por correo" description="Recibir novedades de la institución." checked={notifications} onChange={setNotifications} /><EureSwitch label="Resumen semanal" checked={summary} onChange={setSummary} /></div>
                        <div className="form-footer"><small><CircleHelp size={14} />Este formulario no guarda datos.</small><EureButton type="submit"><Check size={16} />Probar validación</EureButton></div>
                    </form>
                </EureCard>

                <div className="section-label" id="personas"><span>03</span><h2>Las personas, en primer plano</h2></div>
                <div className="people-grid">
                    {[{ name: 'Lucía Torres', course: '4.º A · Turno mañana', image: 'lucia', tone: 'info' as Tone, label: 'Primaria' },
                        { name: 'Tomás Torres', course: 'Sala de 5 · Turno mañana', image: 'tomas', tone: 'success' as Tone, label: 'Inicial' }].map((person) =>
                        <article className="person-card" key={person.name}><div className="person-card-top"><EureAvatar name={person.name} size="lg" src={`/demo/avatars/${person.image}.svg`} /><EureBadge tone={person.tone}>{person.label}</EureBadge></div>
                            <h3>{person.name}</h3><p>{person.course}</p><div className="person-card-bottom"><span><BookOpen size={15} />Ciclo lectivo 2026</span><button className="eure-icon-button" type="button" aria-label={`Ver ficha de ejemplo de ${person.name}`} onClick={() => setToast(`Ficha de ${person.name}: ejemplo visual, sin datos reales.`)}><ArrowRight size={18} /></button></div></article>)}
                </div>
                <EureCard title="Avatares" description="Con imagen, con iniciales y en distintos tamaños.">
                    <div className="avatar-showcase"><div><EureAvatar name="Lucía Torres" src="/demo/avatars/lucia.svg" size="sm" /><small>Compacto</small></div><div><EureAvatar name="Tomás Torres" src="/demo/avatars/tomas.svg" /><small>Estándar</small></div><div><EureAvatar name="Mariana López" size="lg" /><small>Sin foto</small></div><p>Si la foto falta o no carga, mostramos las iniciales. Las ilustraciones de esta demo representan personas ficticias.</p></div>
                </EureCard>

                <div className="section-label" id="tabla"><span>04</span><h2>Información fácil de encontrar</h2></div>
                <EureCard title="Comunicaciones" description="Buscá, filtrá y ordená los ejemplos." icon={<Mail size={18} />}>
                    <div className="table-tools"><div className="table-search"><Search size={17} /><input className="eure-input" aria-label="Buscar comunicaciones de ejemplo" placeholder="Buscar por asunto o remitente…" value={search} onChange={(event) => { setSearch(event.target.value); setPage(0); }} /></div><div className="table-filter"><SlidersHorizontal size={16} /><select className="eure-input" aria-label="Filtrar por estado" value={status} onChange={(event) => { setStatus(event.target.value); setPage(0); }}><option value="all">Todos los estados</option><option>Pendiente</option><option>Leído</option></select></div></div>
                    <div className="table-scroll"><table className="eure-table"><caption className="sr-only">Comunicaciones ficticias. Las cabeceras permiten ordenar.</caption><thead><tr>
                        {([['id', '#'], ['subject', 'Comunicación'], ['date', 'Fecha'], ['status', 'Estado']] as const).map(([key, label]) => <th key={key} scope="col" aria-sort={sort.key === key ? (sort.ascending ? 'ascending' : 'descending') : 'none'}><button type="button" onClick={() => changeSort(key)}>{label}<ArrowDownUp size={12} /></button></th>)}
                        <th scope="col"><span className="sr-only">Acciones</span></th></tr></thead><tbody>{visibleMessages.map((message) => <tr key={message.id}>
                            <td><button className="table-id" type="button" onClick={() => setSelectedMessage(message)}>{String(message.id).padStart(5, '0')}</button></td>
                            <td><strong>{message.subject}</strong><small>{message.from}</small></td><td>{dateLabel(message.date)}</td><td><EureBadge tone={message.status === 'Leído' ? 'success' : 'warning'}>{message.status}</EureBadge></td>
                            <td><button className="eure-icon-button" type="button" aria-label={`Abrir ejemplo: ${message.subject}`} onClick={() => setSelectedMessage(message)}><ChevronRight size={18} /></button></td></tr>)}</tbody></table></div>
                    {filteredMessages.length === 0 && <div className="empty-state"><Inbox size={30} /><strong>No encontramos coincidencias</strong><p>Probá con otro asunto o restablecé los filtros.</p><EureButton variant="secondary" onClick={() => { setSearch(''); setStatus('all'); setPage(0); }}>Limpiar filtros</EureButton></div>}
                    <div className="table-pagination"><span>{filteredMessages.length} comunicaciones de ejemplo</span><div><button type="button" className="eure-icon-button" aria-label="Página anterior" disabled={page === 0} onClick={() => setPage(page - 1)}><ChevronLeft size={17} /></button><span>{page + 1} / {pageCount}</span><button type="button" className="eure-icon-button" aria-label="Página siguiente" disabled={page + 1 >= pageCount} onClick={() => setPage(page + 1)}><ChevronRight size={17} /></button></div></div>
                </EureCard>
            </div>
            <ThemeEditor theme={theme} original={originalTheme} onChange={updateColor} onReset={() => setTheme(originalTheme)} />
        </div>
        <EureDialog open={dialog} onClose={() => setDialog(false)} title="Un momento para confirmar">
            <span className="dialog-illustration"><CheckCheck size={30} /></span><p>Así se presentan las confirmaciones en Eureka: una explicación breve y acciones claras.</p><EureAlert tone="info" title="Estás en una demo">Este diálogo no elimina ni modifica información.</EureAlert>
            <div className="dialog-actions"><EureButton variant="secondary" onClick={() => setDialog(false)}>Cancelar</EureButton><EureButton onClick={() => { setDialog(false); setToast('Confirmación de ejemplo completada.'); }}>Confirmar ejemplo</EureButton></div>
        </EureDialog>
        <EureDialog open={selectedMessage !== null} onClose={() => setSelectedMessage(null)} title={selectedMessage?.subject ?? 'Comunicación de ejemplo'}>
            {selectedMessage && <><div className="message-sender"><EureAvatar name={selectedMessage.from} /><div><strong>{selectedMessage.from}</strong><small>{dateLabel(selectedMessage.date)} · Datos ficticios</small></div></div><p>Queridas familias: compartimos esta comunicación de ejemplo para revisar la lectura, los espacios y la presentación del contenido.</p><p>Los mensajes reales se incorporarán en la etapa de comunicaciones.</p><div className="sample-attachment"><FileText size={22} /><span>{selectedMessage.attachment}<small>Adjunto de muestra · sin archivo para descargar</small></span></div><div className="dialog-actions"><EureButton onClick={() => setSelectedMessage(null)}>Entendido</EureButton></div></>}
        </EureDialog>
        <div className="toast-region" role="status" aria-live="polite">{toast && <div className="eure-toast"><Check size={19} /><span>{toast}</span><button type="button" className="eure-icon-button" aria-label="Cerrar aviso" onClick={() => setToast('')}><X size={16} /></button></div>}</div>
    </Layout>;
}

function ArrowUpRightIcon() { return <ArrowRight size={15} className="arrow-diagonal" aria-hidden="true" />; }
